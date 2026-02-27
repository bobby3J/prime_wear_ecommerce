<?php
require_once __DIR__ . '/bootstrap.php';

use Core\Infrastructure\Persistence\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

// Products read endpoint for storefront listing/search.
// Supports:
// - q: product name/description search
// - category_id: exact category ID filter
// - category: category name/slug filter (fallback)
// Returns normalized shape used by product-fetch.js / ProductCard.js.
try {
    $pdo = Database::getConnection();

    $search = trim((string) ($_GET['q'] ?? ''));
    $categoryFilter = trim((string) ($_GET['category'] ?? ''));
    $categoryIdFilter = (int) ($_GET['category_id'] ?? 0);

    $sql = "SELECT
                p.id,
                p.name AS title,
                c.name AS category,
                p.price,
                p.description,
                p.stock,
                p.status,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.id ASC
                    LIMIT 1
                ) AS image_path
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'active'";

    $params = [];
    if ($categoryIdFilter > 0) {
        $sql .= " AND c.id = ?";
        $params[] = $categoryIdFilter;
    } elseif ($categoryFilter !== '') {
        $sql .= " AND (c.name LIKE ? OR c.slug LIKE ?)";
        $params[] = '%' . $categoryFilter . '%';
        $params[] = '%' . $categoryFilter . '%';
    }
    if ($search !== '') {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = array_map(function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'category' => $row['category'],
            'price' => (float) $row['price'],
            'description' => $row['description'] ?: '',
            'stock' => (int) $row['stock'],
            'status' => $row['status'],
            'image_url' => !empty($row['image_path']) ? '/storage/' . ltrim($row['image_path'], '/\\') : null,
        ];
    }, $rows);

    api_success([
        'count' => count($products),
        'items' => $products
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 500);
}
