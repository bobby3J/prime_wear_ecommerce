<?php
namespace Domain\Cart;

interface CartRepository
{
    public function getOrCreateCartId(int $customerId): int;

    public function addOrIncrementItem(int $customerId, int $productId, int $quantity): void;

    public function updateItemQuantity(int $customerId, int $itemId, int $quantity): void;

    public function removeItem(int $customerId, int $itemId): void;

    public function fetchCart(int $customerId): Cart;

    public function countItems(int $customerId): int;

    public function clearCustomerCart(int $customerId): void;
}
