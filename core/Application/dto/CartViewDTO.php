<?php
namespace Application\DTO;

use Domain\Cart\Cart;

class CartViewDTO
{
    /**
     * @param CartItemViewDTO[] $items
     */
    public function __construct(
        public int $cartId,
        public int $customerId,
        public array $items,
        public int $totalItems,
        public int $totalQuantity,
        public float $subTotal
    ) {}

    public static function fromEntity(Cart $cart): self
    {
        $items = array_map(
            fn($item) => CartItemViewDTO::fromEntity($item),
            $cart->getItems()
        );

        return new self(
            cartId: $cart->getId(),
            customerId: $cart->getCustomerId(),
            items: $items,
            totalItems: $cart->totalItems(),
            totalQuantity: $cart->totalQuantity(),
            subTotal: $cart->subTotal()
        );
    }

    public function toArray(): array
    {
        return [
            'cart_id' => $this->cartId,
            'customer_id' => $this->customerId,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'total_items' => $this->totalItems,
            'total_quantity' => $this->totalQuantity,
            'sub_total' => $this->subTotal,
        ];
    }
}

