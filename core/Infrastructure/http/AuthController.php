<?php
namespace Infrastructure\Http;

use Application\DTO\CustomerAuthDTO;
use Application\Usecases\Auth\GetCurrentCustomerUseCase;
use Application\Usecases\Auth\LoginCustomerUseCase;
use Application\Usecases\Auth\RegisterCustomerUseCase;
use Core\Infrastructure\Persistence\Database;
use Infrastructure\Auth\SessionAuth;
use Infrastructure\Persistence\MySQLCustomerRepository;

class AuthController
{
    // Controller layer for customer auth API.
    // Handles orchestration only: validation/business logic stays in use cases.
    private RegisterCustomerUseCase $registerUseCase;
    private LoginCustomerUseCase $loginUseCase;
    private GetCurrentCustomerUseCase $currentCustomerUseCase;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $customerRepository = new MySQLCustomerRepository($pdo);

        $this->registerUseCase = new RegisterCustomerUseCase($customerRepository);
        $this->loginUseCase = new LoginCustomerUseCase($customerRepository);
        $this->currentCustomerUseCase = new GetCurrentCustomerUseCase($customerRepository);
    }

    public function register(array $input): array
    {
        $customer = $this->registerUseCase->execute(
            (string) ($input['name'] ?? ''),
            (string) ($input['email'] ?? ''),
            (string) ($input['password'] ?? '')
        );

        SessionAuth::loginCustomer($customer);
        return CustomerAuthDTO::fromEntity($customer)->toArray();
    }

    public function login(array $input): array
    {
        $customer = $this->loginUseCase->execute(
            (string) ($input['email'] ?? ''),
            (string) ($input['password'] ?? '')
        );

        SessionAuth::loginCustomer($customer);
        return CustomerAuthDTO::fromEntity($customer)->toArray();
    }

    public function logout(): void
    {
        SessionAuth::logoutCustomer();
    }

    public function me(): ?array
    {
        $customerId = SessionAuth::customerId();
        if (!$customerId) {
            return null;
        }

        $customer = $this->currentCustomerUseCase->execute($customerId);
        if (!$customer || $customer->getStatus() !== 'active') {
            SessionAuth::logoutCustomer();
            return null;
        }
        return CustomerAuthDTO::fromEntity($customer)->toArray();
    }
}
