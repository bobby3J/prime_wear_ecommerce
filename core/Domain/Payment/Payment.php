<?php
namespace Domain\Payment;

class Payment
{
    public function __construct(
        private int $orderId,
        private string $method,
        private string $status,
        private float $amount,
        private string $transactionRef,
        private ?int $id = null
    ) {}

    public static function create(
        int $orderId,
        string $method,
        string $status,
        float $amount,
        string $transactionRef
    ): self {
        return new self(
            orderId: $orderId,
            method: $method,
            status: $status,
            amount: round($amount, 2),
            transactionRef: $transactionRef
        );
    }

    public function withId(int $id): self
    {
        return new self(
            orderId: $this->orderId,
            method: $this->method,
            status: $this->status,
            amount: $this->amount,
            transactionRef: $this->transactionRef,
            id: $id
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getTransactionRef(): string
    {
        return $this->transactionRef;
    }
}
