<?php
namespace Application\DTO;

use Domain\Payment\Payment;

class PaymentDTO
{
    public function __construct(
        public ?int $id,
        public int $orderId,
        public string $method,
        public string $status,
        public float $amount,
        public string $transactionRef
    ) {}

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            id: $payment->getId(),
            orderId: $payment->getOrderId(),
            method: $payment->getMethod(),
            status: $payment->getStatus(),
            amount: $payment->getAmount(),
            transactionRef: $payment->getTransactionRef()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'method' => $this->method,
            'status' => $this->status,
            'amount' => $this->amount,
            'transaction_ref' => $this->transactionRef,
        ];
    }
}
