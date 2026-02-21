<?php
namespace Infrastructure\Http;

use Core\Infrastructure\Persistence\Database;
use PDO;

class DashboardController
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
        $kpis = [
            'products' => $this->countRows('products'),
            'categories' => $this->countRows('categories'),
            'customers' => $this->countRows('customers'),
            'carts' => $this->countRows('carts'),
            'orders' => $this->countRows('orders'),
            'payments' => $this->countRows('payments'),
        ];

        $trends = [
            'active_carts_24h' => $this->countActiveCarts24h(),
            'new_customers_7d' => $this->countRecentCustomers(7),
            'orders_7d' => $this->countRecentRows('orders', 7),
        ];

        $alerts = [
            'low_stock_products' => $this->countLowStockProducts(5),
            'abandoned_carts' => $this->countAbandonedCarts(24),
        ];

        $orders7d = $this->countRowsByDay('orders', 7);
        $payments7d = $this->countRowsByDay('payments', 7);
        $customers7d = $this->countRowsByDay('customers', 7);

        $chartData = [
            'orders_vs_payments_7d' => [
                'labels' => $orders7d['labels'],
                'orders' => $orders7d['values'],
                'payments' => $payments7d['values'],
            ],
            'customers_7d' => [
                'labels' => $customers7d['labels'],
                'values' => $customers7d['values'],
            ],
            'order_status' => $this->countStatusBreakdown('orders'),
            'payment_status' => $this->countStatusBreakdown('payments'),
            'cart_health' => [
                'labels' => ['Active (24h)', 'Abandoned (>24h)', 'Converted'],
                'values' => [
                    (int) $trends['active_carts_24h'],
                    (int) $alerts['abandoned_carts'],
                    $this->countConvertedCarts(),
                ],
            ],
        ];

        return [
            'view' => 'dashboard.php',
            'data' => [
                'kpis' => $kpis,
                'trends' => $trends,
                'alerts' => $alerts,
                'chartData' => $chartData,
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

    private function countRows(string $table): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$table}");
        return (int) $stmt->fetchColumn();
    }

    private function countRecentRows(string $table, int $days): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);
        return (int) $stmt->fetchColumn();
    }

    private function countRecentCustomers(int $days): int
    {
        return $this->countRecentRows('customers', $days);
    }

    private function countActiveCarts24h(): int
    {
        if (!$this->tableExists('carts')) {
            return 0;
        }

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM carts WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        return (int) $stmt->fetchColumn();
    }

    private function countLowStockProducts(int $threshold): int
    {
        if (!$this->tableExists('products')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= ?"
        );
        $stmt->execute([$threshold]);
        return (int) $stmt->fetchColumn();
    }

    private function countAbandonedCarts(int $hours): int
    {
        if (!$this->tableExists('carts') || !$this->tableExists('cart_items')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM carts c
             WHERE c.updated_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
               AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)"
        );
        $stmt->execute([$hours]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Returns a compact day-by-day series for dashboard charts.
     * Output is stable even when no rows exist for some days.
     */
    private function countRowsByDay(string $table, int $days): array
    {
        $labels = [];
        $isoDays = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} day");
            $isoDays[] = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
        }

        $valuesByDay = array_fill_keys($isoDays, 0);

        if (
            !$this->tableExists($table)
            || !$this->columnExists($table, 'created_at')
        ) {
            return [
                'labels' => $labels,
                'values' => array_values($valuesByDay),
            ];
        }

        $sinceDays = max(0, $days - 1);
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day_key, COUNT(*) AS total
             FROM {$table}
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)"
        );
        $stmt->execute([$sinceDays]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $key = (string) ($row['day_key'] ?? '');
            if (array_key_exists($key, $valuesByDay)) {
                $valuesByDay[$key] = (int) $row['total'];
            }
        }

        return [
            'labels' => $labels,
            'values' => array_values($valuesByDay),
        ];
    }

    private function countStatusBreakdown(string $table): array
    {
        if (
            !$this->tableExists($table)
            || !$this->columnExists($table, 'status')
        ) {
            return ['labels' => [], 'values' => []];
        }

        $stmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS total
             FROM {$table}
             GROUP BY status
             ORDER BY status ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = ucfirst((string) $row['status']);
            $values[] = (int) $row['total'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function countConvertedCarts(): int
    {
        if (
            !$this->tableExists('carts')
            || !$this->tableExists('orders')
            || !$this->columnExists('carts', 'customer_id')
            || !$this->columnExists('carts', 'created_at')
            || !$this->columnExists('orders', 'customer_id')
            || !$this->columnExists('orders', 'created_at')
        ) {
            return 0;
        }

        $stmt = $this->pdo->query(
            "SELECT COUNT(*)
             FROM carts c
             WHERE EXISTS (
                SELECT 1
                FROM orders o
                WHERE o.customer_id = c.customer_id
                  AND o.created_at >= c.created_at
             )"
        );

        return (int) $stmt->fetchColumn();
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
}
