<?php
namespace Domain\Payment;

interface PaymentRepository
{
    public function create(Payment $payment): Payment;

    public function listForAdmin(array $filters, int $page, int $perPage): array;

    /**
     * Attaches gateway/provider metadata to a payment after initiation.
     */
    public function attachGatewayMetadata(
        int $paymentId,
        ?string $provider,
        ?string $providerTxnId,
        ?string $idempotencyKey,
        ?string $rawStatus
    ): void;

    /**
     * Stores immutable provider webhook/initiation events for auditing.
     * Returns false when provider_event_id already exists (idempotent duplicate).
     */
    public function recordGatewayEvent(
        int $paymentId,
        string $providerEventId,
        string $eventType,
        array $payload,
        bool $signatureValid
    ): bool;

    public function findByTransactionRef(string $transactionRef): ?array;

    public function updateStatusFromGateway(
        int $paymentId,
        string $status,
        ?string $rawStatus,
        ?string $providerTxnId
    ): void;
}
