<?php
namespace Application\Usecases\Checkout;

interface CheckoutConfirmationStore
{
    public function save(int $customerId, array $payload, int $ttlSeconds): void;

    public function get(int $customerId): ?array;

    public function clear(int $customerId): void;
}
