<?php
namespace Infrastructure\Persistence;

use Core\Infrastructure\Persistence\Database;
use Domain\Payment\Payment;
use Domain\Payment\PaymentRepository;
use PDO;

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

        $insert = $this->pdo->prepare(
            "INSERT INTO payments (order_id, method, status, transaction_ref, amount)
             VALUES (?, ?, ?, ?, ?)"
        );
        $insert->execute([
            $payment->getOrderId(),
            $method,
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
     * Maps internal testing methods to current DB enum values.
     * - momo -> mobile money
     * - bank -> card (until bank enum is added)
     * - cash_on_delivery -> cash on delivery
     */
    private function mapMethodToDatabaseValue(string $method): string
    {
        return match ($method) {
            'momo' => 'mobile money',
            'bank' => 'card',
            'cash_on_delivery' => 'cash on delivery',
            default => $method,
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
