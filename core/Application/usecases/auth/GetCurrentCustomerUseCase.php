<?php
namespace Application\Usecases\Auth;

use Domain\Customer\Customer;
use Domain\Customer\CustomerRepository;

class GetCurrentCustomerUseCase
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function execute(int $customerId): ?Customer
    {
        return $this->customerRepository->findById($customerId);
    }
}
