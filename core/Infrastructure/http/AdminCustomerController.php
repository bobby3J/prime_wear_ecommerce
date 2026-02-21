<?php
namespace Infrastructure\Http;

use Core\Infrastructure\Persistence\Database;
use PDO;

class AdminCustomerController
{
    private PDO $pdo;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function view(): array
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $where = [];
        $params = [];

        if ($filters['q'] !== '') {
            $where[] = "(c.name LIKE ? OR c.email LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if ($filters['status'] !== '') {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM customers c {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $ordersByCustomerAvailable = $this->hasOrdersCustomerLink();
        $paymentsByCustomerAvailable = $ordersByCustomerAvailable
            && $this->tableExists('payments')
            && $this->columnExists('payments', 'order_id')
            && $this->columnExists('orders', 'id');

        $ordersCountExpr = $ordersByCustomerAvailable
            ? "(SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id)"
            : "0";
        $paymentsCountExpr = $paymentsByCustomerAvailable
            ? "(SELECT COUNT(*)
                FROM payments p
                INNER JOIN orders o2 ON o2.id = p.order_id
               WHERE o2.customer_id = c.id)"
            : "0";

        $sql = "SELECT
                    c.id,
                    c.name,
                    c.email,
                    c.status,
                    c.created_at,
                    (SELECT COUNT(*) FROM carts ct WHERE ct.customer_id = c.id) AS carts_count,
                    {$ordersCountExpr} AS orders_count,
                    {$paymentsCountExpr} AS payments_count
                FROM customers c
                {$whereSql}
                ORDER BY c.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'view' => 'customers/view_customers.php',
            'data' => [
                'customers' => $customers,
                'filters' => $filters,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ],
        ];
    }

    public function show(): array
    {
        $customerId = (int) ($_GET['id'] ?? 0);
        if ($customerId <= 0) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, status, created_at, updated_at
             FROM customers
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $ordersCount = 0;
        $paymentsCount = 0;

        $ordersByCustomerAvailable = $this->hasOrdersCustomerLink();
        $paymentsByCustomerAvailable = $ordersByCustomerAvailable
            && $this->tableExists('payments')
            && $this->columnExists('payments', 'order_id')
            && $this->columnExists('orders', 'id');

        if ($ordersByCustomerAvailable) {
            $ordersStmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
            $ordersStmt->execute([$customerId]);
            $ordersCount = (int) $ordersStmt->fetchColumn();
        }

        if ($paymentsByCustomerAvailable) {
            $paymentsStmt = $this->pdo->prepare(
                "SELECT COUNT(*)
                 FROM payments p
                 INNER JOIN orders o ON o.id = p.order_id
                 WHERE o.customer_id = ?"
            );
            $paymentsStmt->execute([$customerId]);
            $paymentsCount = (int) $paymentsStmt->fetchColumn();
        }

        $cartsSql = "SELECT
                        c.id,
                        c.created_at,
                        c.updated_at,
                        COUNT(ci.id) AS total_items,
                        COALESCE(SUM(ci.quantity), 0) AS total_quantity
                     FROM carts c
                     LEFT JOIN cart_items ci ON ci.cart_id = c.id
                     WHERE c.customer_id = ?
                     GROUP BY c.id, c.created_at, c.updated_at
                     ORDER BY c.updated_at DESC";
        $cartsStmt = $this->pdo->prepare($cartsSql);
        $cartsStmt->execute([$customerId]);
        $carts = $cartsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'view' => 'customers/show_customer.php',
            'data' => [
                'customer' => $customer,
                'summary' => [
                    'carts_count' => count($carts),
                    'orders_count' => $ordersCount,
                    'payments_count' => $paymentsCount,
                ],
                'carts' => $carts,
            ],
        ];
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = (bool) $stmt->fetchColumn();

        $this->tableExistsCache[$table] = $exists;
        return $exists;
    }

    private function columnExists(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        if (!$this->tableExists($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }

        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        $exists = (bool) $stmt->fetchColumn();

        $this->columnExistsCache[$cacheKey] = $exists;
        return $exists;
    }

    private function hasOrdersCustomerLink(): bool
    {
        return $this->tableExists('orders')
            && $this->columnExists('orders', 'customer_id');
    }
}
