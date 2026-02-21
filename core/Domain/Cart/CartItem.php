<?php
namespace Domain\Cart;

class CartItem
{
    public function __construct(
        private int $id,
        private int $productId,
        private string $name,
        private string $slug,
        private float $price,
        private int $stock,
        private string $status,
        private int $quantity,
        private ?string $imageUrl
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function lineTotal(): float
    {
        return $this->price * $this->quantity;
    }
}

