# Real Payment Integration Blueprint (MTN MoMo + Telecel Cash + Bank)

## Goal
Define a secure production architecture to replace simulation mode while keeping the current checkout workflow stable.

## Design Principles
1. Keep `PayCheckoutUseCase` as orchestration center.
2. Move provider specif ics behind gateway adapters.
3. Trust gateway callbacks/webhooks for final status.
4. Make all payment writes idempotent and auditable.
5. Never expose provider secrets to frontend code.

## Target Providers
1. MTN MoMo (`mtn_momo`)
2. Telecel Cash (`telecel_cash`)
3. Bank (`bank`)

## Recommended Architecture

### Application layer contract
Introduce an interface (example):

```php
interface PaymentGateway
{
    public function initiatePayment(PaymentRequest $request): PaymentInitiationResult;
    public function verifyCallback(array $headers, string $rawBody): VerifiedCallback;
    public function queryStatus(string $providerTxnId): PaymentStatusResult;
}
```

Use a gateway factory/router keyed by method (`mtn_momo`, `telecel_cash`, `bank`).

### Flow split
1. `Initiate` phase:
   - Create local payment attempt in `pending`.
   - Call provider API server-to-server.
   - Return redirect/checkout token to frontend if needed.
2. `Callback/Webhook` phase:
   - Provider calls backend webhook.
   - Backend verifies signature and idempotency.
   - Backend updates payment status and order status.
3. `Reconciliation` phase:
   - Scheduled job queries pending/stale provider transactions.
   - Resolves missed callbacks safely.

## Security Requirements (Mandatory)

### Secrets and keys
1. Store API keys/secrets in environment or secret manager, not in git.
2. Rotate secrets regularly and support dual-key rollover.
3. Separate sandbox vs production credentials by environment.

### Request authenticity
1. Verify webhook signatures/HMAC using raw request body.
2. Enforce timestamp tolerance (anti-replay).
3. Enforce nonce or event-id dedupe where provider supports it.
4. Optionally IP allowlist provider callback ranges (defense-in-depth only).

### Data protection
1. Use HTTPS/TLS everywhere.
2. Never store full PAN/card data in your DB.
3. Mask PII and tokens in logs.
4. Encrypt sensitive metadata at rest where possible.

### Access control
1. Frontend only sends method and customer intent.
2. Backend computes final payable amount from cart/order server-side.
3. Do not trust frontend amount, status, or transaction identifiers without verification.

## Idempotency Strategy
Use idempotency at multiple layers:
1. Client request id (`Idempotency-Key`) for initiate endpoint.
2. Unique DB constraint on provider transaction id.
3. Process each webhook event id once.
4. State transitions only move forward (example: `pending -> successful/failed`; no downgrade from `successful`).

Suggested DB constraints:
1. `payments.provider_txn_id` unique (nullable until assigned).
2. `payment_events.provider_event_id` unique.
3. `payments.idempotency_key` unique per customer/order attempt.

## Data Model Additions (Recommended)
Add or extend:
1. `payments`
   - `provider` (`mtn_momo`, `bank`, `telecel_cash`)
   - `provider_txn_id`
   - `idempotency_key`
   - `raw_status`
   - `confirmed_at`
   - `failed_at`
2. `payment_events` (immutable audit trail)
   - `payment_id`
   - `provider_event_id`
   - `event_type`
   - `payload_json`
   - `signature_valid` (bool)
   - `received_at`

## Status Model
Normalize provider statuses to internal values:
1. `pending`
2. `successful`
3. `failed`
4. optional `cancelled`/`expired` (if needed)

Internal rule:
- only `successful` should trigger:
  - stock reduction
  - order marked `paid`

## Operational Safety

### Retry policy
1. Retry outbound provider calls with exponential backoff and jitter.
2. Do not retry non-retriable errors (validation/auth failures).
3. Keep retry logs with correlation id.

### Reconciliation job
1. Scan pending payments older than threshold (for example 5-15 minutes).
2. Query provider status endpoint.
3. Apply same idempotent update logic as webhook path.

### Monitoring
Track:
1. payment initiation success rate
2. callback verification failures
3. pending-to-success latency
4. reconciliation correction count

## API Surface (Suggested)
1. `POST /ecommerce/shared/api/checkout/pay.php`
2. `POST /ecommerce/shared/api/payments/webhook.php?provider={provider}`
3. `GET /checkout/pay/status?order_number=...` (future polling fallback)

## Migration Plan from Simulation
1. Keep legacy simulation docs as historical reference.
2. Use provider initiation path for `mtn_momo`, `telecel_cash`, and `bank`.
3. Finalize payment status via webhook only.
4. Keep reconciliation active before full launch.

## Current Scaffold (Implemented)
1. Provider initiation client:
   - `core/Infrastructure/Payments/ProviderGatewayClient.php`
2. Webhook finalization:
   - `core/Application/usecases/Payment/HandleGatewayWebhookUseCase.php`
   - `core/Infrastructure/http/GatewayWebhookController.php`
   - `ecommerce/shared/api/payments/webhook.php`
3. Checkout orchestration:
   - `core/Application/usecases/checkout/PayCheckoutUseCase.php`
   - Provider methods create pending payment + gateway metadata
   - COD creates pending payment with customer notice

## Environment Variables (Scaffold)
Global:
1. `PAYMENT_LIVE_ENABLED` (`1` for live calls, default mock mode)
2. `PAYMENT_CURRENCY` (default `GHS`)
3. `PAYMENT_WEBHOOK_TOLERANCE_SECONDS` (default `300`)

Provider-specific:
1. `MTN_MOMO_INIT_URL`, `MTN_MOMO_API_TOKEN`, `MTN_MOMO_WEBHOOK_SECRET`
2. `TELECEL_CASH_INIT_URL`, `TELECEL_CASH_API_TOKEN`, `TELECEL_CASH_WEBHOOK_SECRET`
3. `BANK_INIT_URL`, `BANK_API_TOKEN`, `BANK_WEBHOOK_SECRET`

Business destination metadata (display/audit):
1. `BUSINESS_MTN_MOMO_NUMBER`, `BUSINESS_MTN_MOMO_NAME`
2. `BUSINESS_TELECEL_CASH_NUMBER`, `BUSINESS_TELECEL_CASH_NAME`
3. `BUSINESS_BANK_NAME`, `BUSINESS_BANK_ACCOUNT_NUMBER`, `BUSINESS_BANK_ACCOUNT_NAME`

## Production Readiness Checklist
1. Provider sandbox tests passed for success, failed, timeout, duplicate callback.
2. Webhook signature verification tested with tampered payload.
3. Idempotency tested with duplicate client submit and duplicate callback.
4. Stock is reduced exactly once per successful payment.
5. Orders are never marked paid on unverified callback.
6. Alerting dashboards and incident runbook are in place.

## Notes for This Codebase
1. Existing `PayCheckoutUseCase` already has strong transaction boundaries; keep that.
2. Provider metadata now persists in `payments` (`provider`, `provider_txn_id`, `idempotency_key`, `raw_status`).
3. Use machine-safe provider values in DB; map labels at display layer.
