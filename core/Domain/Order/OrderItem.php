<?php
namespace Domain\Order;

class OrderItem
{
    public function __construct(
        private int $productId,
        private int $quantity,
        private float $priceAtPurchase
    ) {}

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPriceAtPurchase(): float
    {
        return $this->priceAtPurchase;
    }

    public function lineTotal(): float
    {
        return round($this->priceAtPurchase * $this->quantity, 2);
    }
}
