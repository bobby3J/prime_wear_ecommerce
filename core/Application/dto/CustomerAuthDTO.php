<?php
namespace Application\DTO;

use Domain\Customer\Customer;

class CustomerAuthDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role = 'customer'
    ) {}

    public static function fromEntity(Customer $customer): self
    {
        return new self(
            id: $customer->getId(),
            name: $customer->getName(),
            email: $customer->getEmail(),
            role: 'customer'
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}

