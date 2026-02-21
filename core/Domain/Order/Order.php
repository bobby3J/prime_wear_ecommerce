<?php
namespace Domain\Order;

class Order
{
    public function __construct(
        private int $customerId,
        private string $orderNumber,
        private string $status,
        private float $totalAmount,
        private ?int $id = null
    ) {}

    public static function place(int $customerId, string $orderNumber, float $totalAmount): self
    {
        return new self(
            customerId: $customerId,
            orderNumber: $orderNumber,
            status: 'pending',
            totalAmount: round($totalAmount, 2),
            id: null
        );
    }

    public function withId(int $id): self
    {
        return new self(
            customerId: $this->customerId,
            orderNumber: $this->orderNumber,
            status: $this->status,
            totalAmount: $this->totalAmount,
            id: $id
        );
    }

    public function withStatus(string $status): self
    {
        return new self(
            customerId: $this->customerId,
            orderNumber: $this->orderNumber,
            status: $status,
            totalAmount: $this->totalAmount,
            id: $this->id
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }
}
