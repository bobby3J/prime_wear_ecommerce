<?php
namespace Infrastructure\Persistence;

use Core\Infrastructure\Persistence\Database;
use Domain\Payment\Payment;
use Domain\Payment\PaymentRepository;
use PDO;
use PDOException;

class MySQLPaymentRepository implements PaymentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function create(Payment $payment): Payment
    {
        $method = $this->mapMethodToDatabaseValue($payment->getMethod());
        $status = $this->mapStatusToDatabaseValue($payment->getStatus());
        $provider = $this->mapProviderToDatabaseValue($payment->getMethod());

        $insert = $this->pdo->prepare(
            "INSERT INTO payments (order_id, method, provider, status, transaction_ref, amount)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([
            $payment->getOrderId(),
            $method,
            $provider,
            $status,
            $payment->getTransactionRef(),
            round($payment->getAmount(), 2),
        ]);

        return $payment->withId((int) $this->pdo->lastInsertId());
    }

    public function listForAdmin(array $filters, int $page, int $perPage): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $method = trim((string) ($filters['method'] ?? ''));
        $provider = trim((string) ($filters['provider'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(c.name LIKE ? OR c.email LIKE ? OR o.order_number LIKE ? OR p.transaction_ref LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($status !== '') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($method !== '') {
            $where[] = 'p.method = ?';
            $params[] = $method;
        }
        if ($provider !== '') {
            $where[] = 'p.provider = ?';
            $params[] = $provider;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM payments p
             INNER JOIN orders o ON o.id = p.order_id
             INNER JOIN customers c ON c.id = o.customer_id
             {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT
                    p.id,
                    p.order_id,
                    p.method,
                    p.provider,
                    p.status,
                    p.transaction_ref,
                    p.amount,
                    p.created_at,
                    o.order_number,
                    c.name AS customer_name,
                    c.email AS customer_email
                FROM payments p
                INNER JOIN orders o ON o.id = p.order_id
                INNER JOIN customers c ON c.id = o.customer_id
                {$whereSql}
                ORDER BY p.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * Phase-1 gateway metadata is persisted separately so Payment entity remains stable.
     */
    public function attachGatewayMetadata(
        int $paymentId,
        ?string $provider,
        ?string $providerTxnId,
        ?string $idempotencyKey,
        ?string $rawStatus
    ): void {
        $update = $this->pdo->prepare(
            "UPDATE payments
             SET provider = COALESCE(?, provider),
                 provider_txn_id = COALESCE(?, provider_txn_id),
                 idempotency_key = COALESCE(?, idempotency_key),
                 raw_status = COALESCE(?, raw_status)
             WHERE id = ?"
        );
        $update->execute([
            $provider,
            $providerTxnId,
            $idempotencyKey,
            $rawStatus,
            $paymentId,
        ]);
    }

    public function findByTransactionRef(string $transactionRef): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, order_id, method, provider, status, transaction_ref, amount
             FROM payments
             WHERE transaction_ref = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$transactionRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Stores immutable provider events. Returns false when duplicate event id exists.
     */
    public function recordGatewayEvent(
        int $paymentId,
        string $providerEventId,
        string $eventType,
        array $payload,
        bool $signatureValid
    ): bool {
        try {
            $insert = $this->pdo->prepare(
                "INSERT INTO payment_events
                 (payment_id, provider_event_id, event_type, payload_json, signature_valid)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insert->execute([
                $paymentId,
                $providerEventId,
                $eventType,
                json_encode($payload, JSON_UNESCAPED_SLASHES),
                $signatureValid ? 1 : 0,
            ]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Applies provider status transitions safely:
     * - successful is terminal and cannot be downgraded by later events.
     */
    public function updateStatusFromGateway(
        int $paymentId,
        string $status,
        ?string $rawStatus,
        ?string $providerTxnId
    ): void {
        $status = $this->mapStatusToDatabaseValue($status);
        if ($status === 'successful') {
            $stmt = $this->pdo->prepare(
                "UPDATE payments
                 SET status = 'successful',
                     raw_status = COALESCE(?, raw_status),
                     provider_txn_id = COALESCE(?, provider_txn_id),
                     confirmed_at = COALESCE(confirmed_at, NOW()),
                     failed_at = NULL
                 WHERE id = ?"
            );
            $stmt->execute([$rawStatus, $providerTxnId, $paymentId]);
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE payments
             SET status = ?,
                 raw_status = COALESCE(?, raw_status),
                 provider_txn_id = COALESCE(?, provider_txn_id),
                 failed_at = CASE WHEN ? = 'failed' THEN COALESCE(failed_at, NOW()) ELSE failed_at END
             WHERE id = ?
               AND status <> 'successful'"
        );
        $stmt->execute([$status, $rawStatus, $providerTxnId, $status, $paymentId]);
    }

    /**
     * Maps internal methods to DB enum values.
     * - mtn_momo|telecel_cash -> mobile money
     * - bank -> bank
     * - cash_on_delivery -> cash on delivery
     */
    private function mapMethodToDatabaseValue(string $method): string
    {
        return match ($method) {
            'mtn_momo', 'telecel_cash' => 'mobile money',
            'bank' => 'bank',
            'cash_on_delivery' => 'cash on delivery',
            default => $method,
        };
    }

    /**
     * Provider column stores normalized machine-safe source channel.
     * COD stays null because current enum does not include cash_on_delivery.
     */
    private function mapProviderToDatabaseValue(string $method): ?string
    {
        return match ($method) {
            'mtn_momo' => 'mtn_momo',
            'telecel_cash' => 'telecel_cash',
            'bank' => 'bank',
            default => null,
        };
    }

    private function mapStatusToDatabaseValue(string $status): string
    {
        return match ($status) {
            'successful', 'failed', 'pending' => $status,
            default => 'pending',
        };
    }
}
