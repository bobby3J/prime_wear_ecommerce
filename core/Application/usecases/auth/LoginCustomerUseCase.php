<?php
namespace Application\Usecases\Auth;

use Domain\Customer\Customer;
use Domain\Customer\CustomerRepository;

class LoginCustomerUseCase
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function execute(string $email, string $password): Customer
    {
        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email is required.');
        }
        if ($password === '') {
            throw new \InvalidArgumentException('Password is required.');
        }

        $customer = $this->customerRepository->findByEmail($email);
        if (!$customer) {
            throw new \RuntimeException('Invalid credentials.');
        }
        if ($customer->getStatus() !== 'active') {
            throw new \RuntimeException('This account is inactive.');
        }
        if (!password_verify($password, $customer->getPasswordHash())) {
            throw new \RuntimeException('Invalid credentials.');
        }

        return $customer;
    }
}
