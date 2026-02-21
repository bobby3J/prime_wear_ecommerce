<?php
namespace Application\Usecases\Auth;

use Domain\Customer\Customer;
use Domain\Customer\CustomerRepository;

class RegisterCustomerUseCase
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function execute(string $name, string $email, string $password): Customer
    {
        $name = trim($name);
        $email = trim(strtolower($email));

        if ($name === '') {
            throw new \InvalidArgumentException('Name is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email is required.');
        }
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters.');
        }

        if ($this->customerRepository->findByEmail($email)) {
            throw new \RuntimeException('Email is already in use.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->customerRepository->create($name, $email, $hash);
    }
}
