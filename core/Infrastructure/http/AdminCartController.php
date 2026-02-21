<?php
namespace Infrastructure\Http;

use Core\Infrastructure\Persistence\Database;
use PDO;

class AdminCartController
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

        $rows = $this->fetchCartMonitoringRows($filters);

        if ($filters['status'] !== '') {
            $rows = array_values(array_filter($rows, fn($row) => $row['status'] === $filters['status']));
        }

        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($rows, $offset, $perPage);

        $summary = [
            'active' => 0,
            'abandoned' => 0,
            'converted' => 0,
        ];
        foreach ($this->fetchCartMonitoringRows(['q' => '', 'status' => '']) as $row) {
            if (isset($summary[$row['status']])) {
                $summary[$row['status']]++;
            }
        }

        return [
            'view' => 'carts/view_carts.php',
            'data' => [
                'carts' => $rows,
                'summary' => $summary,
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
        $cartId = (int) ($_GET['id'] ?? 0);
        if ($cartId <= 0) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.customer_id, c.created_at, c.updated_at, cu.name AS customer_name, cu.email AS customer_email
             FROM carts c
             INNER JOIN customers cu ON cu.id = c.customer_id
             WHERE c.id = ?
             LIMIT 1"
        );
        $stmt->execute([$cartId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $itemsStmt = $this->pdo->prepare(
            "SELECT
                ci.id,
                ci.product_id,
                p.name,
                p.price,
                p.stock,
                ci.quantity,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.id ASC
                    LIMIT 1
                ) AS image_path
             FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id DESC"
        );
        $itemsStmt->execute([$cartId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $status = $this->resolveCartStatus(
            (int) $cart['customer_id'],
            $cart['created_at'],
            $cart['updated_at'],
            count($items)
        );

        $subTotal = 0.0;
        foreach ($items as &$item) {
            $lineTotal = (float) $item['price'] * (int) $item['quantity'];
            $subTotal += $lineTotal;
            $item['line_total'] = round($lineTotal, 2);
            $item['image_url'] = !empty($item['image_path']) ? '/storage/' . ltrim($item['image_path'], '/\\') : null;
        }
        unset($item);

        return [
            'view' => 'carts/show_cart.php',
            'data' => [
                'cart' => $cart,
                'status' => $status,
                'items' => $items,
                'sub_total' => round($subTotal, 2),
            ],
        ];
    }

    private function fetchCartMonitoringRows(array $filters): array
    {
        $where = [];
        $params = [];

        if ($filters['q'] !== '') {
            $where[] = "(cu.name LIKE ? OR cu.email LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT
                    c.id,
                    c.customer_id,
                    cu.name AS customer_name,
                    cu.email AS customer_email,
                    c.created_at,
                    c.updated_at,
                    COUNT(ci.id) AS total_items,
                    COALESCE(SUM(ci.quantity), 0) AS total_quantity,
                    COALESCE(SUM(ci.quantity * p.price), 0) AS sub_total
                FROM carts c
                INNER JOIN customers cu ON cu.id = c.customer_id
                LEFT JOIN cart_items ci ON ci.cart_id = c.id
                LEFT JOIN products p ON p.id = ci.product_id
                {$whereSql}
                GROUP BY c.id, c.customer_id, cu.name, cu.email, c.created_at, c.updated_at
                ORDER BY c.updated_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['status'] = $this->resolveCartStatus(
                (int) $row['customer_id'],
                $row['created_at'],
                $row['updated_at'],
                (int) $row['total_items']
            );
            $row['sub_total'] = round((float) $row['sub_total'], 2);
        }
        unset($row);

        return $rows;
    }

    private function resolveCartStatus(int $customerId, string $createdAt, string $updatedAt, int $totalItems): string
    {
        if ($this->hasConvertedOrder($customerId, $createdAt)) {
            return 'converted';
        }

        if ($totalItems === 0) {
            return 'active';
        }

        $updatedTs = strtotime($updatedAt);
        if ($updatedTs !== false && (time() - $updatedTs) > (24 * 3600)) {
            return 'abandoned';
        }

        return 'active';
    }

    private function hasConvertedOrder(int $customerId, string $cartCreatedAt): bool
    {
        if (
            !$this->tableExists('orders')
            || !$this->columnExists('orders', 'customer_id')
            || !$this->columnExists('orders', 'created_at')
        ) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM orders
             WHERE customer_id = ?
               AND created_at >= ?
             LIMIT 1"
        );
        $stmt->execute([$customerId, $cartCreatedAt]);
        return (bool) $stmt->fetchColumn();
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
}
