<?php
require_once __DIR__ . '/bootstrap.php';

use Core\Infrastructure\Persistence\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed.', 405);
}

// Storefront categories endpoint:
// - Returns active storefront categories grouped into men/ladies/unisex.
try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query(
        "SELECT
            c.id,
            c.name,
            c.slug,
            c.parent_id,
            parent.slug AS parent_slug,
            COUNT(p.id) AS product_count
         FROM categories c
         LEFT JOIN categories parent ON parent.id = c.parent_id
         LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
         WHERE c.status = 'active'
         GROUP BY c.id, c.name, c.slug, c.parent_id, parent.slug
         ORDER BY c.name ASC"
    );

    $normalizeGroup = static function (string $value): ?string {
        $slug = strtolower(trim($value));
        return match ($slug) {
            'men' => 'men',
            'ladies' => 'ladies',
            'unisex', 'couples-unisex' => 'unisex',
            default => null,
        };
    };

    $items = [];

    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        $slug = strtolower((string) $row['slug']);
        if (in_array($slug, ['men', 'ladies', 'unisex', 'couples-unisex'], true)) {
            continue;
        }

        $group = $normalizeGroup((string) ($row['parent_slug'] ?? '')) ?? 'unisex';

        $items[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'group' => $group,
            'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'product_count' => (int) $row['product_count'],
        ];
    }

    api_success([
        'count' => count($items),
        'items' => $items,
    ]);
} catch (\Throwable $e) {
    api_error($e->getMessage(), 500);
}
