<?php
namespace Application\Usecases\Payment;

use Domain\Shared\TransactionManager;
use Infrastructure\Payments\ProviderGatewayClient;
use Infrastructure\Persistence\MySQLOrderRepository;
use Infrastructure\Persistence\MySQLPaymentRepository;

class HandleGatewayWebhookUseCase
{
    public function __construct(
        private ProviderGatewayClient $gatewayClient,
        private MySQLPaymentRepository $paymentRepository,
        private MySQLOrderRepository $orderRepository,
        private TransactionManager $transactionManager
    ) {}

    public function execute(string $provider, array $headers, string $rawBody): array
    {
        $event = $this->gatewayClient->verifyWebhook($provider, $headers, $rawBody);
        $payment = $this->paymentRepository->findByTransactionRef((string) $event['transaction_ref']);
        if (!$payment) {
            throw new \RuntimeException('No payment found for transaction_ref in webhook payload.');
        }

        return $this->transactionManager->transactional(function () use ($event, $payment): array {
            $paymentId = (int) ($payment['id'] ?? 0);
            $orderId = (int) ($payment['order_id'] ?? 0);

            $inserted = $this->paymentRepository->recordGatewayEvent(
                paymentId: $paymentId,
                providerEventId: (string) ($event['event_id'] ?? ''),
                eventType: (string) ($event['event_type'] ?? 'payment.update'),
                payload: (array) ($event['payload'] ?? []),
                signatureValid: true
            );

            if (!$inserted) {
                return [
                    'processed' => false,
                    'reason' => 'duplicate_event',
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                ];
            }

            $status = (string) ($event['status'] ?? 'pending');
            $this->paymentRepository->updateStatusFromGateway(
                paymentId: $paymentId,
                status: $status,
                rawStatus: isset($event['raw_status']) ? (string) $event['raw_status'] : null,
                providerTxnId: isset($event['provider_txn_id']) ? (string) $event['provider_txn_id'] : null
            );

            if ($status === 'successful' && !$this->orderRepository->isOrderPaid($orderId)) {
                $items = $this->orderRepository->fetchOrderItems($orderId);
                $this->orderRepository->reduceStockForOrderItems($items);
                $this->orderRepository->markAsPaid($orderId);
            }

            return [
                'processed' => true,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'status' => $status,
            ];
        });
    }
}

