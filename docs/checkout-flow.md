# Checkout, Orders, Payments Flow (DDD-Oriented)

## Related Docs
- Frontend API request helper pattern: `docs/frontend-request-helper.md`
- Legacy simulated payment flow (historical reference): `docs/payment-simulated-flow.md`
- Real payment integration blueprint (secure MTN MoMo/Telecel/Bank): `docs/payment-real-integration-blueprint.md`
- Phase 1 operations and webhook examples: `docs/payment-phase1-operational-guide.md`

## Overview
This project now uses a cleaner split for checkout:

1. `Infrastructure/Http` controllers only orchestrate input/output.
2. `Application/usecases` coordinate workflow and validation.
3. `Infrastructure/persistence` repositories own SQL.
4. Domain models represent order/payment concepts.

## Main Write Flow

### Step 1: Confirm Checkout Details
Endpoint: `POST /ecommerce/shared/api/checkout/confirm.php`

Controller: `core/Infrastructure/http/CheckoutController.php` -> `ConfirmCheckoutUseCase`

Behavior:
1. Validates `name`, `phone`, `street_address`.
2. Ensures customer exists and is active.
3. Loads cart snapshot and validates item readiness (active + stock).
4. Stores confirmation payload in session (`SessionCheckoutConfirmationStore`) with:
   - delivery details
   - cart signature hash
   - expiry (TTL)

### Step 2: Payment Initiation + Order Creation
Endpoint: `POST /ecommerce/shared/api/checkout/pay.php`

Controller: `core/Infrastructure/http/CheckoutController.php` -> `PayCheckoutUseCase`

Behavior (single transaction):
1. Reads confirmation payload from session.
2. Reloads cart and re-validates against signature.
3. For mobile money methods, validates `payer_phone` for customer prompt flow.
4. Creates `orders` row.
5. Creates `order_items` rows (line item snapshots).
6. Creates `order_delivery_details` row.
7. Creates `payments` row in `pending` status for provider-backed methods.
8. Persists provider metadata (`provider`, `provider_txn_id`, `idempotency_key`, `raw_status`) when available.
9. Clears cart items.
10. Clears checkout confirmation session state.

## Payment Rules (Phase 1)
Supported checkout methods:
1. `mtn_momo`
2. `telecel_cash`
3. `bank`
4. `cash_on_delivery`

Rules:
1. `mtn_momo`, `telecel_cash`, and `bank` initiate payment and stay `pending` until webhook confirmation.
2. Stock is reduced only after webhook confirms `successful`.
3. Order is marked `paid` only after webhook confirms `successful`.
4. `cash_on_delivery` stays `pending` and returns a customer notice to pay at delivery.

## Webhook Finalization
Endpoint: `POST /ecommerce/shared/api/payments/webhook.php?provider={provider}`

Supported providers:
1. `mtn_momo`
2. `telecel_cash`
3. `bank`

Behavior:
1. Verifies signature and timestamp.
2. Records immutable webhook event in `payment_events`.
3. Applies idempotent status update on `payments`.
4. On `successful`:
   - reduces stock from persisted `order_items` snapshot
   - marks order as `paid`

## Admin Read Flow

### Orders
Route: `/admin/orders/view`, `/admin/orders/show?id=...`
Controller: `core/Infrastructure/http/OrderController.php`
Repository: `core/Infrastructure/persistence/MySQLOrderRepository.php`

List page includes:
1. customer info
2. order number/status/total
3. line item count and quantity summary

Detail page includes:
1. order summary
2. delivery details snapshot
3. order line items
4. payment records for that order

### Payments
Route: `/admin/payments/view`
Controller: `core/Infrastructure/http/PaymentController.php`
Repository: `core/Infrastructure/persistence/MySQLPaymentRepository.php`

List page includes:
1. payment method/status/amount/reference
2. linked order number
3. customer name/email

## Key Files Added/Refactored

### Domain
1. `core/Domain/Order/Order.php`
2. `core/Domain/Order/OrderItem.php`
3. `core/Domain/Order/OrderDeliveryDetails.php`
4. `core/Domain/Order/OrderRepositoryInterface.php`
5. `core/Domain/Payment/Payment.php`
6. `core/Domain/Payment/PaymentMethod.php`
7. `core/Domain/Payment/PaymentRepository.php`
8. `core/Domain/Shared/TransactionManager.php`

### Application
1. `core/Application/usecases/checkout/CheckoutConfirmationStore.php`
2. `core/Application/usecases/checkout/ConfirmCheckoutUseCase.php`
3. `core/Application/usecases/checkout/PayCheckoutUseCase.php`
4. `core/Application/dto/OrderDTO.php`
5. `core/Application/dto/PaymentDTO.php`

### Infrastructure
1. `core/Infrastructure/Auth/SessionCheckoutConfirmationStore.php`
2. `core/Infrastructure/http/CheckoutController.php`
3. `core/Infrastructure/http/OrderController.php`
4. `core/Infrastructure/http/PaymentController.php`
5. `core/Infrastructure/persistence/MySQLOrderRepository.php`
6. `core/Infrastructure/persistence/MySQLPaymentRepository.php`
7. `core/Infrastructure/persistence/PdoTransactionManager.php`
8. `core/Infrastructure/persistence/MySQLCartRepository.php` (added `clearCustomerCart`)

### UI/API
1. `ecommerce/shared/api/checkout/confirm.php`
2. `ecommerce/shared/api/checkout/pay.php`
3. `ecommerce/pages/cart.php`
4. `ecommerce/assets/js/cart-page.js`
5. `admin/views/orders/orders.php`
6. `admin/views/orders/show.php`
7. `admin/views/payments/payments.php`

## Upgrading to Real Payment Gateways Later
Gateway integration scaffold is now in place:
1. Provider initiation client: `core/Infrastructure/Payments/ProviderGatewayClient.php`
2. Webhook use case: `core/Application/usecases/Payment/HandleGatewayWebhookUseCase.php`
3. Webhook controller: `core/Infrastructure/http/GatewayWebhookController.php`
4. Webhook API endpoint: `ecommerce/shared/api/payments/webhook.php`
 
