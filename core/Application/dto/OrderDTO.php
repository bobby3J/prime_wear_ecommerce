<?php
namespace Application\DTO;

use Domain\Order\Order;

class OrderDTO
{
    public function __construct(
        public ?int $id,
        public int $customerId,
        public string $orderNumber,
        public string $status,
        public float $totalAmount
    ) {}

    public static function fromEntity(Order $order): self
    {
        return new self(
            id: $order->getId(),
            customerId: $order->getCustomerId(),
            orderNumber: $order->getOrderNumber(),
            status: $order->getStatus(),
            totalAmount: $order->getTotalAmount()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
        ];
    }
}
