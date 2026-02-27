# Checkout, Orders, Payments Flow (DDD-Oriented)

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

### Step 2: Simulated Payment + Order Creation
Endpoint: `POST /ecommerce/shared/api/checkout/pay.php`

Controller: `core/Infrastructure/http/CheckoutController.php` -> `PayCheckoutUseCase`

Behavior (single transaction):
1. Reads confirmation payload from session.
2. Reloads cart and re-validates against signature.
3. Creates `orders` row.
4. Creates `order_items` rows (line item snapshots).
5. Creates `order_delivery_details` row.
6. Creates `payments` row.
7. Marks order `paid` only when simulated status is `successful`.
8. Clears cart items.
9. Clears checkout confirmation session state.

## Simulation Rules
Supported checkout methods:
1. `momo`
2. `bank`
3. `cash_on_delivery`

Supported simulation statuses via `simulate_result`:
1. `successful`
2. `pending`
3. `failed`

Rules:
1. For `cash_on_delivery`, payment is forced to `pending`.
2. For `momo`/`bank`, default is `successful` if `simulate_result` is omitted.
3. `bank` currently maps to `payments.method = 'card'` because current DB enum has no `bank` value yet.

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
To integrate real MoMo/Bank providers:
1. Keep `PayCheckoutUseCase` orchestration unchanged.
2. Replace simulation input logic with a gateway adapter port (application interface).
3. Infrastructure adapters call provider APIs and return normalized statuses.
4. Persist gateway response metadata (provider transaction id, response code, webhook state).
 