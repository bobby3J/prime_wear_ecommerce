<?php
namespace Domain\Order;

class OrderDeliveryDetails
{
    public function __construct(
        private string $fullName,
        private string $phone,
        private string $streetAddress
    ) {}

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }
}
