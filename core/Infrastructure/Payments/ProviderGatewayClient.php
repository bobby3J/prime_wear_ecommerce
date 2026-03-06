<?php
namespace Infrastructure\Payments;

/**
 * Gateway client scaffold for provider-backed payment initiation and webhook verification.
 *
 * This class intentionally supports two modes:
 * 1) mock mode (default): no external network call, deterministic pending response.
 * 2) live mode: posts to configured provider endpoint with bearer token.
 *
 * Environment variables:
 * - PAYMENT_LIVE_ENABLED=1 to enable live HTTP calls.
 * - PAYMENT_CURRENCY=GHS (optional; defaults to GHS).
 *
 * Provider-specific:
 * - MTN_MOMO_INIT_URL, MTN_MOMO_API_TOKEN, MTN_MOMO_WEBHOOK_SECRET
 * - TELECEL_CASH_INIT_URL, TELECEL_CASH_API_TOKEN, TELECEL_CASH_WEBHOOK_SECRET
 * - BANK_INIT_URL, BANK_API_TOKEN, BANK_WEBHOOK_SECRET
 */
class ProviderGatewayClient
{
    public function collectionDestinations(): array
    {
        return [
            'mtn_momo' => $this->resolveCollectionDestination('mtn_momo'),
            'telecel_cash' => $this->resolveCollectionDestination('telecel_cash'),
            'bank' => $this->resolveCollectionDestination('bank'),
        ];
    }

    public function isLiveEnabled(): bool
    {
        return $this->envBool('PAYMENT_LIVE_ENABLED', false);
    }

    /**
     * Initiates a payment with provider. In mock mode this returns a safe pending payload.
     *
     * @param array{
     *   provider:string,
     *   amount:float,
     *   transaction_ref:string,
     *   customer_phone:string,
     *   order_number:string
     * } $payload
     */
    public function initiate(array $payload): array
    {
        $provider = $this->normalizeProvider((string) ($payload['provider'] ?? ''));
        if ($provider === '') {
            throw new \RuntimeException('Provider is required for gateway initiation.');
        }
        $collectionDestination = $this->resolveCollectionDestination($provider);

        if (!$this->isLiveEnabled()) {
            return [
                'provider' => $provider,
                'provider_txn_id' => strtoupper($provider) . '-MOCK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
                'raw_status' => 'PENDING_MOCK',
                'checkout_url' => null,
                'requires_redirect' => false,
                'collection_destination' => $collectionDestination,
                'mode' => 'mock',
            ];
        }

        $initUrl = $this->requiredEnv($this->providerEnvPrefix($provider) . '_INIT_URL');
        $apiToken = $this->requiredEnv($this->providerEnvPrefix($provider) . '_API_TOKEN');
        $currency = getenv('PAYMENT_CURRENCY') ?: 'GHS';

        $requestBody = [
            'amount' => round((float) ($payload['amount'] ?? 0), 2),
            'currency' => $currency,
            'transaction_ref' => (string) ($payload['transaction_ref'] ?? ''),
            'customer_phone' => (string) ($payload['customer_phone'] ?? ''),
            'payer_phone' => (string) ($payload['payer_phone'] ?? ''),
            'order_number' => (string) ($payload['order_number'] ?? ''),
            'collection_destination' => $collectionDestination,
        ];

        $response = $this->postJson($initUrl, $apiToken, $requestBody);
        $responseArray = $response['json'];

        $providerTxnId = $this->pickFirstString($responseArray, [
            'provider_txn_id',
            'transaction_id',
            'id',
            'reference',
        ]);
        if ($providerTxnId === '') {
            throw new \RuntimeException('Provider response missing transaction identifier.');
        }

        $rawStatus = $this->pickFirstString($responseArray, ['raw_status', 'status', 'state']);
        if ($rawStatus === '') {
            $rawStatus = 'PENDING';
        }

        $checkoutUrl = $this->pickFirstString($responseArray, ['checkout_url', 'payment_url', 'redirect_url']);

        return [
            'provider' => $provider,
            'provider_txn_id' => $providerTxnId,
            'raw_status' => $rawStatus,
            'checkout_url' => $checkoutUrl !== '' ? $checkoutUrl : null,
            'requires_redirect' => $checkoutUrl !== '',
            'collection_destination' => $collectionDestination,
            'mode' => 'live',
        ];
    }

    /**
     * Verifies webhook integrity and returns normalized event payload.
     */
    public function verifyWebhook(string $providerInput, array $headers, string $rawBody): array
    {
        $provider = $this->normalizeProvider($providerInput);
        if ($provider === '') {
            throw new \RuntimeException('Unknown provider.');
        }

        $headers = $this->normalizeHeaders($headers);
        $signature = (string) ($headers['x-signature'] ?? '');
        $timestamp = (string) ($headers['x-timestamp'] ?? '');
        if ($signature === '' || $timestamp === '') {
            throw new \RuntimeException('Missing webhook signature headers.');
        }

        $secret = $this->requiredEnv($this->providerEnvPrefix($provider) . '_WEBHOOK_SECRET');
        $tolerance = (int) (getenv('PAYMENT_WEBHOOK_TOLERANCE_SECONDS') ?: 300);
        $ts = (int) $timestamp;
        if ($ts <= 0 || abs(time() - $ts) > $tolerance) {
            throw new \RuntimeException('Webhook timestamp outside tolerance window.');
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \RuntimeException('Webhook signature validation failed.');
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Webhook body must be valid JSON.');
        }

        $eventId = (string) ($payload['event_id'] ?? $payload['id'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? 'payment.update');
        $transactionRef = (string) ($payload['transaction_ref'] ?? $payload['merchant_ref'] ?? '');
        $providerTxnId = (string) ($payload['provider_txn_id'] ?? $payload['transaction_id'] ?? '');
        $rawStatus = (string) ($payload['raw_status'] ?? $payload['status'] ?? '');
        $status = $this->normalizeStatus($rawStatus !== '' ? $rawStatus : (string) ($payload['status'] ?? ''));

        if ($eventId === '' || $transactionRef === '') {
            throw new \RuntimeException('Webhook payload missing event_id or transaction_ref.');
        }

        return [
            'provider' => $provider,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'transaction_ref' => $transactionRef,
            'provider_txn_id' => $providerTxnId !== '' ? $providerTxnId : null,
            'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            'status' => $status,
            'payload' => $payload,
        ];
    }

    private function normalizeProvider(string $provider): string
    {
        $normalized = strtolower(trim($provider));
        return match ($normalized) {
            'mtn_momo', 'momo' => 'mtn_momo',
            'telecel_cash', 'vodafone', 'vodafone_cash' => 'telecel_cash',
            'bank' => 'bank',
            default => '',
        };
    }

    private function providerEnvPrefix(string $provider): string
    {
        return match ($provider) {
            'mtn_momo' => 'MTN_MOMO',
            'telecel_cash' => 'TELECEL_CASH',
            'bank' => 'BANK',
            default => throw new \RuntimeException('Unsupported provider for env prefix resolution.'),
        };
    }

    /**
     * Business settlement destination for transparency and payload enrichment.
     * Gateway providers usually bind settlement to merchant credentials, but we
     * still expose configured destination details to the frontend/admin flow.
     */
    private function resolveCollectionDestination(string $provider): array
    {
        return match ($provider) {
            'mtn_momo' => [
                'type' => 'mobile_money',
                'network' => 'mtn',
                'number' => (string) (getenv('BUSINESS_MTN_MOMO_NUMBER') ?: ''),
                'name' => (string) (getenv('BUSINESS_MTN_MOMO_NAME') ?: ''),
            ],
            'telecel_cash' => [
                'type' => 'mobile_money',
                'network' => 'telecel',
                'number' => (string) (getenv('BUSINESS_TELECEL_CASH_NUMBER') ?: ''),
                'name' => (string) (getenv('BUSINESS_TELECEL_CASH_NAME') ?: ''),
            ],
            'bank' => [
                'type' => 'bank',
                'bank_name' => (string) (getenv('BUSINESS_BANK_NAME') ?: ''),
                'account_number' => (string) (getenv('BUSINESS_BANK_ACCOUNT_NUMBER') ?: ''),
                'account_name' => (string) (getenv('BUSINESS_BANK_ACCOUNT_NAME') ?: ''),
            ],
            default => [],
        };
    }

    private function normalizeStatus(string $status): string
    {
        $value = strtolower(trim($status));
        if ($value === '') {
            return 'pending';
        }

        if (str_contains($value, 'success') || in_array($value, ['paid', 'completed'], true)) {
            return 'successful';
        }
        if (str_contains($value, 'fail') || in_array($value, ['declined', 'cancelled', 'canceled', 'error'], true)) {
            return 'failed';
        }
        return 'pending';
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function requiredEnv(string $key): string
    {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') {
            throw new \RuntimeException("Missing required environment variable: {$key}");
        }
        return trim((string) $value);
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $name = strtolower(trim((string) $key));
            $name = str_replace('_', '-', $name);
            if (str_starts_with($name, 'http-')) {
                $name = substr($name, 5);
            }
            $normalized[$name] = is_array($value) ? implode(',', $value) : (string) $value;
        }
        return $normalized;
    }

    private function postJson(string $url, string $apiToken, array $payload): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Unable to initialize gateway HTTP client.');
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $apiToken,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            throw new \RuntimeException('Gateway HTTP request failed: ' . $curlError);
        }

        $json = json_decode((string) $responseBody, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Gateway returned non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($json['message'] ?? $json['error'] ?? 'Gateway request was rejected.');
            throw new \RuntimeException('Gateway error: ' . $message);
        }

        return [
            'status' => $status,
            'json' => $json,
        ];
    }

    private function pickFirstString(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }
        return '';
    }
}
