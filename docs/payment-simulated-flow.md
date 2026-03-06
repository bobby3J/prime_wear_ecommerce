# Simulated Payment Flow (Legacy v1 Reference)

## Purpose
This document describes the previous simulation-first payment flow. Keep it for historical reference and regression comparisons.

## Scope
- Checkout confirmation and payment submission flow
- Session checkpoint and cart consistency checks
- Order/payment creation behavior
- Stock update behavior

## Endpoints
1. `POST /ecommerce/shared/api/checkout/confirm.php`
2. `GET /ecommerce/shared/api/checkout/status.php`
3. `POST /ecommerce/shared/api/checkout/pay.php`

All endpoints require an authenticated customer session (`SessionAuth::requireCustomer()`).

## Response Envelope
All checkout APIs return the same shape from `ecommerce/shared/api/bootstrap.php`:

```json
{ "success": true, "data": { } }
```

or

```json
{ "success": false, "message": "..." }
```

## Step-by-Step Flow

### 1) Confirm checkout details
Frontend sends:

```json
{
  "name": "Jane Doe",
  "phone": "+233200000000",
  "street_address": "1 Example Street, Accra"
}
```

Backend (`ConfirmCheckoutUseCase`):
1. Validates name, phone, and street address.
2. Verifies customer exists and is active.
3. Loads cart and blocks if empty.
4. Validates each cart item is active and has enough stock.
5. Builds a cart signature hash (cart id, totals, and line snapshot).
6. Stores confirmation payload in session with 15-minute TTL (`900s`).

Returns:
- `confirmed`
- `expires_in_seconds`
- `delivery`
- `cart_summary`
- `allowed_payment_methods` (`momo`, `bank`, `cash_on_delivery`)

### 2) Check confirmation state
Frontend can call `GET /checkout/status.php`.

Backend returns:
- whether checkout is confirmed
- saved delivery details (if still valid)
- current cart summary
- allowed payment methods

If TTL expires, confirmation is cleared automatically and `confirmed` becomes false.

### 3) Submit payment (simulated)
Frontend sends:

```json
{
  "method": "momo",
  "simulate_result": "successful",
  "transaction_ref": "MOMO-ABC123"
}
```

`simulate_result` options:
- `successful`
- `pending`
- `failed`

Behavior (`PayCheckoutUseCase`, inside one DB transaction):
1. Validates payment method (`momo`, `bank`, `cash_on_delivery`).
2. Resolves simulation status:
   - `cash_on_delivery` always forced to `pending`
   - `momo`/`bank` default to `successful` if omitted
3. Validates confirmation checkpoint exists.
4. Reloads cart and re-validates:
   - cart not empty
   - item status/stock valid
   - cart signature matches confirmed snapshot
5. Creates `orders` row.
6. Creates `order_items` snapshot rows.
7. Creates `order_delivery_details` row.
8. Creates `payments` row.
9. If payment status is `successful`:
   - reduces stock for ordered items
   - marks order status as `paid`
10. Clears cart items.
11. Clears checkout confirmation session.

## Important Rules

### Stock reduction timing
Stock is reduced only when payment status is `successful`.

### COD behavior
`cash_on_delivery` is intentionally `pending` and does not auto-mark order paid.

### Method-to-DB mapping
Current payment method DB mapping in repository:
- `momo` -> `mobile money`
- `bank` -> `card` (temporary mapping because DB enum does not yet include `bank`)
- `cash_on_delivery` -> `cash on delivery`

## Failure Scenarios
Common blocking errors:
1. Confirmation missing or expired: user must reconfirm delivery details.
2. Cart changed after confirmation: user must reconfirm.
3. Item inactive or stock insufficient.
4. Empty cart.
5. Invalid payment method or invalid `simulate_result`.

Transaction rollback ensures partial records are not committed.

## Sequence (Current)
```text
Customer -> Checkout page: enter delivery details
Checkout page -> confirm.php: POST delivery details
confirm.php -> ConfirmCheckoutUseCase: validate + session checkpoint
Customer -> Payment page: choose method + simulate result
Payment page -> pay.php: POST payment payload
pay.php -> PayCheckoutUseCase: validate + transactional order/payment write
PayCheckoutUseCase -> DB: create order/order_items/delivery/payment
PayCheckoutUseCase -> DB: reduce stock + mark paid (only if successful)
PayCheckoutUseCase -> Session: clear cart + clear confirmation
pay.php -> Frontend: success envelope with order/payment data
```

## Quick Test Matrix
1. `method=momo`, no `simulate_result` -> expect `successful`, order `paid`, stock reduced.
2. `method=bank`, `simulate_result=failed` -> expect payment `failed`, order not paid, stock not reduced.
3. `method=cash_on_delivery` with any simulate value -> expect payment `pending`.
4. Confirm checkout, then modify cart, then pay -> expect cart signature mismatch error.
