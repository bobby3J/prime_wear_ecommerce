<?php
namespace Domain\Cart;

class Cart
{
    /** @var CartItem[] */
    private array $items;

    /**
     * @param CartItem[] $items
     */
    public function __construct(
        private int $id,
        private int $customerId,
        array $items = []
    ) {
        $this->items = $items;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function totalItems(): int
    {
        return count($this->items);
    }

    public function totalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }

    public function subTotal(): float
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += $item->lineTotal();
        }
        return round($sum, 2);
    }
}

