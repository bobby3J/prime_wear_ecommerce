<?php
namespace Infrastructure\Auth;

use Application\Usecases\Checkout\CheckoutConfirmationStore;

class SessionCheckoutConfirmationStore implements CheckoutConfirmationStore
{
    private const SESSION_KEY = 'checkout_confirmation';

    public function save(int $customerId, array $payload, int $ttlSeconds): void
    {
        SessionAuth::start();
        $_SESSION[self::SESSION_KEY] = [
            'customer_id' => $customerId,
            'payload' => $payload,
            'expires_at' => time() + $ttlSeconds,
        ];
    }

    public function get(int $customerId): ?array
    {
        SessionAuth::start();
        $record = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($record)) {
            return null;
        }

        if ((int) ($record['customer_id'] ?? 0) !== $customerId) {
            return null;
        }

        $expiresAt = (int) ($record['expires_at'] ?? 0);
        if ($expiresAt <= 0 || time() > $expiresAt) {
            unset($_SESSION[self::SESSION_KEY]);
            return null;
        }

        $payload = $record['payload'] ?? null;
        return is_array($payload) ? $payload : null;
    }

    public function clear(int $customerId): void
    {
        SessionAuth::start();
        $record = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($record)) {
            return;
        }
        if ((int) ($record['customer_id'] ?? 0) !== $customerId) {
            return;
        }
        unset($_SESSION[self::SESSION_KEY]);
    }
}
