# Payment Phase 1 Operational Guide

## Scope
This guide documents the implemented phase-1 behavior:
1. `mtn_momo`, `telecel_cash`, `bank`: payment initiation + webhook finalization
2. `cash_on_delivery`: pending order with customer notice

## Customer-Facing Flow

### 1) Confirm checkout
`POST /ecommerce/shared/api/checkout/confirm.php`

Request:
```json
{
  "name": "Jane Doe",
  "phone": "+233200000000",
  "street_address": "1 Example Street"
}
```

### 2) Initiate payment
`POST /ecommerce/shared/api/checkout/pay.php`

Request:
```json
{
  "method": "mtn_momo",
  "transaction_ref": "MTNMOMO-20260228-ABCD",
  "payer_phone": "0241234567"
}
```

Successful response envelope:
```json
{
  "success": true,
  "data": {
    "order": { "order_number": "ORD-..." },
    "payment": { "status": "pending", "method": "mtn_momo" },
    "gateway": {
      "provider": "mtn_momo",
      "provider_txn_id": "MTN_MOMO-MOCK-...",
      "checkout_url": null,
      "requires_redirect": false,
      "mode": "mock",
      "note": "Payment initiated. Final status will be set by gateway webhook."
    },
    "collection_destination": {
      "type": "mobile_money",
      "network": "mtn",
      "number": "024XXXXXXX",
      "name": "Prime Wear Ltd"
    }
  }
}
```

COD response includes:
```json
{
  "customer_notice": "Cash on delivery selected. Payment will be collected at delivery."
}
```

## Webhook Flow

### Endpoint
`POST /ecommerce/shared/api/payments/webhook.php?provider={provider}`

Providers:
1. `mtn_momo`
2. `telecel_cash`
3. `bank`

### Required headers
1. `X-Signature`
2. `X-Timestamp` (unix epoch seconds)

### Signature rule
Expected signature is:
`hex(hmac_sha256("<timestamp>.<raw_body>", WEBHOOK_SECRET))`

Where `WEBHOOK_SECRET` is provider-specific env var:
1. `MTN_MOMO_WEBHOOK_SECRET`
2. `TELECEL_CASH_WEBHOOK_SECRET`
3. `BANK_WEBHOOK_SECRET`

### Example payload
```json
{
  "event_id": "evt_001",
  "event_type": "payment.success",
  "transaction_ref": "MTNMOMO-20260228-ABCD",
  "provider_txn_id": "txn_9x1",
  "status": "SUCCESSFUL"
}
```

## Webhook Processing Guarantees
1. Signature + timestamp window verification happen first.
2. Event is written to `payment_events` once (idempotent by unique `provider_event_id`).
3. Payment status update is forward-safe:
   - `successful` is terminal
   - later failed/pending events do not downgrade successful payments
4. On first successful transition only:
   - stock is reduced from persisted `order_items`
   - order status is marked `paid`

## Environment Configuration

Global:
1. `PAYMENT_LIVE_ENABLED=0` (default mock)
2. `PAYMENT_CURRENCY=GHS`
3. `PAYMENT_WEBHOOK_TOLERANCE_SECONDS=300`

MTN MoMo:
1. `MTN_MOMO_INIT_URL`
2. `MTN_MOMO_API_TOKEN`
3. `MTN_MOMO_WEBHOOK_SECRET`

Telecel Cash:
1. `TELECEL_CASH_INIT_URL`
2. `TELECEL_CASH_API_TOKEN`
3. `TELECEL_CASH_WEBHOOK_SECRET`

Bank:
1. `BANK_INIT_URL`
2. `BANK_API_TOKEN`
3. `BANK_WEBHOOK_SECRET`

Business destination details:
1. `BUSINESS_MTN_MOMO_NUMBER`, `BUSINESS_MTN_MOMO_NAME`
2. `BUSINESS_TELECEL_CASH_NUMBER`, `BUSINESS_TELECEL_CASH_NAME`
3. `BUSINESS_BANK_NAME`, `BUSINESS_BANK_ACCOUNT_NUMBER`, `BUSINESS_BANK_ACCOUNT_NAME`

## .env Setup (Recommended)
1. Copy `.env.example` to `.env`.
2. Fill all payment credentials and business destination values in `.env`.
3. Keep `.env` out of git (already ignored by `.gitignore`).
4. Restart Apache/PHP service after changes.

Loader behavior:
1. `.env` is loaded from `config/app.php`.
2. Existing server env vars are not overridden (safer for production).

## Review Checklist
1. `payments.provider` stores `mtn_momo|telecel_cash|bank` for provider methods.
2. `payments.provider_txn_id` is filled after initiation or webhook.
3. `payments.idempotency_key` is generated and unique.
4. `payment_events` captures every accepted webhook event.
5. COD always returns pending + customer notice.
6. Stock reduction happens only on successful webhook.
