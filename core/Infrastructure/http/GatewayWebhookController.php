<?php
namespace Infrastructure\Http;

use Application\Usecases\Payment\HandleGatewayWebhookUseCase;
use Core\Infrastructure\Persistence\Database;
use Infrastructure\Payments\ProviderGatewayClient;
use Infrastructure\Persistence\MySQLOrderRepository;
use Infrastructure\Persistence\MySQLPaymentRepository;
use Infrastructure\Persistence\PdoTransactionManager;

class GatewayWebhookController
{
    private HandleGatewayWebhookUseCase $useCase;

    public function __construct()
    {
        $pdo = Database::getConnection();

        $this->useCase = new HandleGatewayWebhookUseCase(
            gatewayClient: new ProviderGatewayClient(),
            paymentRepository: new MySQLPaymentRepository($pdo),
            orderRepository: new MySQLOrderRepository($pdo),
            transactionManager: new PdoTransactionManager()
        );
    }

    public function handle(string $provider, array $headers, string $rawBody): array
    {
        return $this->useCase->execute($provider, $headers, $rawBody);
    }
}

