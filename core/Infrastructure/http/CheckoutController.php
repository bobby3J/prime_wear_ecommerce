<?php
namespace Infrastructure\Http;

use Application\Usecases\Checkout\ConfirmCheckoutUseCase;
use Application\Usecases\Checkout\PayCheckoutUseCase;
use Core\Infrastructure\Persistence\Database;
use Infrastructure\Auth\SessionCheckoutConfirmationStore;
use Infrastructure\Persistence\MySQLCartRepository;
use Infrastructure\Persistence\MySQLCustomerRepository;
use Infrastructure\Persistence\MySQLOrderRepository;
use Infrastructure\Persistence\MySQLPaymentRepository;
use Infrastructure\Persistence\PdoTransactionManager;

class CheckoutController
{
    /**
     * Controller is intentionally thin:
     * - request parsing/shape
     * - orchestration of application use cases
     * - no SQL and no cross-aggregate mutation logic
     */
    private ConfirmCheckoutUseCase $confirmCheckoutUseCase;
    private PayCheckoutUseCase $payCheckoutUseCase;

    public function __construct()
    {
        $pdo = Database::getConnection();

        $customerRepository = new MySQLCustomerRepository($pdo);
        $cartRepository = new MySQLCartRepository($pdo);
        $orderRepository = new MySQLOrderRepository($pdo);
        $paymentRepository = new MySQLPaymentRepository($pdo);
        $confirmationStore = new SessionCheckoutConfirmationStore();
        $transactionManager = new PdoTransactionManager();

        $this->confirmCheckoutUseCase = new ConfirmCheckoutUseCase(
            customerRepository: $customerRepository,
            cartRepository: $cartRepository,
            confirmationStore: $confirmationStore
        );

        $this->payCheckoutUseCase = new PayCheckoutUseCase(
            cartRepository: $cartRepository,
            orderRepository: $orderRepository,
            paymentRepository: $paymentRepository,
            confirmationStore: $confirmationStore,
            transactionManager: $transactionManager
        );
    }

    public function confirm(int $customerId, array $input): array
    {
        return $this->confirmCheckoutUseCase->execute(
            customerId: $customerId,
            name: (string) ($input['name'] ?? ''),
            phone: (string) ($input['phone'] ?? ''),
            streetAddress: (string) ($input['street_address'] ?? '')
        );
    }

    public function pay(int $customerId, array $input): array
    {
        return $this->payCheckoutUseCase->execute(
            customerId: $customerId,
            methodInput: (string) ($input['method'] ?? ''),
            transactionRef: (string) ($input['transaction_ref'] ?? ''),
            simulateResult: (string) ($input['simulate_result'] ?? '')
        );
    }
}
