<?php
namespace Application\DTO;

use Domain\Cart\CartItem;

class CartItemViewDTO
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $name,
        public string $slug,
        public float $price,
        public int $stock,
        public string $status,
        public int $quantity,
        public float $lineTotal,
        public ?string $imageUrl
    ) {}

    public static function fromEntity(CartItem $item): self
    {
        return new self(
            id: $item->getId(),
            productId: $item->getProductId(),
            name: $item->getName(),
            slug: $item->getSlug(),
            price: $item->getPrice(),
            stock: $item->getStock(),
            status: $item->getStatus(),
            quantity: $item->getQuantity(),
            lineTotal: round($item->lineTotal(), 2),
            imageUrl: $item->getImageUrl()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
            'image_url' => $this->imageUrl,
        ];
    }
}

