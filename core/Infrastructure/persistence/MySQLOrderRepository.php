<?php
namespace Infrastructure\Persistence;

use Core\Infrastructure\Persistence\Database;
use Domain\Order\Order;
use Domain\Order\OrderDeliveryDetails;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use PDO;

class MySQLOrderRepository implements OrderRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function create(Order $order, array $items): Order
    {
        if (empty($items)) {
            throw new \RuntimeException('Cannot create order without order items.');
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO orders (customer_id, order_number, status, total_amount)
             VALUES (?, ?, ?, ?)"
        );
        $insert->execute([
            $order->getCustomerId(),
            $order->getOrderNumber(),
            $order->getStatus(),
            round($order->getTotalAmount(), 2),
        ]);

        $orderId = (int) $this->pdo->lastInsertId();

        $itemInsert = $this->pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            if (!$item instanceof OrderItem) {
                throw new \InvalidArgumentException('Order items must be instances of Domain\\Order\\OrderItem.');
            }
            $itemInsert->execute([
                $orderId,
                $item->getProductId(),
                $item->getQuantity(),
                round($item->getPriceAtPurchase(), 2),
            ]);
        }

        return $order->withId($orderId);
    }

    public function saveDeliveryDetails(int $orderId, OrderDeliveryDetails $details): void
    {
        $insert = $this->pdo->prepare(
            "INSERT INTO order_delivery_details (order_id, full_name, phone, street_address)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                phone = VALUES(phone),
                street_address = VALUES(street_address)"
        );
        $insert->execute([
            $orderId,
            $details->getFullName(),
            $details->getPhone(),
            $details->getStreetAddress(),
        ]);
    }

    public function markAsPaid(int $orderId): void
    {
        $update = $this->pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $update->execute([$orderId]);
    }

    public function listForAdmin(array $filters, int $page, int $perPage): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(c.name LIKE ? OR c.email LIKE ? OR o.order_number LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT
                    o.id,
                    o.order_number,
                    o.status,
                    o.total_amount,
                    o.created_at,
                    c.id AS customer_id,
                    c.name AS customer_name,
                    c.email AS customer_email,
                    COUNT(oi.id) AS line_items_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
                FROM orders o
                INNER JOIN customers c ON c.id = o.customer_id
                LEFT JOIN order_items oi ON oi.order_id = o.id
                {$whereSql}
                GROUP BY
                    o.id, o.order_number, o.status, o.total_amount, o.created_at,
                    c.id, c.name, c.email
                ORDER BY o.created_at DESC
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

    public function findForAdmin(int $orderId): ?array
    {
        $orderStmt = $this->pdo->prepare(
            "SELECT
                o.id,
                o.order_number,
                o.status,
                o.total_amount,
                o.created_at,
                o.updated_at,
                c.id AS customer_id,
                c.name AS customer_name,
                c.email AS customer_email,
                odd.full_name,
                odd.phone,
                odd.street_address
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             LEFT JOIN order_delivery_details odd ON odd.order_id = o.id
             WHERE o.id = ?
             LIMIT 1"
        );
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $itemsStmt = $this->pdo->prepare(
            "SELECT
                oi.id,
                oi.product_id,
                p.name AS product_name,
                oi.quantity,
                oi.price_at_purchase,
                (oi.quantity * oi.price_at_purchase) AS line_total
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $paymentsStmt = $this->pdo->prepare(
            "SELECT id, method, status, transaction_ref, amount, created_at
             FROM payments
             WHERE order_id = ?
             ORDER BY id DESC"
        );
        $paymentsStmt->execute([$orderId]);
        $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'order' => $order,
            'items' => $items,
            'payments' => $payments,
        ];
    }
}
