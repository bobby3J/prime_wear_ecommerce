<?php
namespace Infrastructure\Query;

use PDO;

/**
 * Read-only query service used by the storefront products API endpoint.
 *
 * Connection flow:
 * - ecommerce/shared/api/products.php builds filter input from $_GET.
 * - The endpoint calls fetchActiveProducts($filters).
 * - This service runs SQL and returns normalized API rows.
 */
class StorefrontProductQueryService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Returns active products for storefront listing/search.
     *
     * Supported filters:
     * - q: product search text (name + description)
     * - category_id: exact category match (highest priority)
     * - category: fallback partial match on category name/slug
     *
     * Output item shape:
     * - id, title, category, price, description, stock, status, image_url
     */
    public function fetchActiveProducts(array $filters = []): array
    {
        // Normalize external input once so query-building stays predictable.
        $search = trim((string) ($filters['q'] ?? ''));
        $categoryFilter = trim((string) ($filters['category'] ?? ''));
        $categoryIdFilter = (int) ($filters['category_id'] ?? 0);

        // Base query always returns active products plus one representative image.
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

        // Exact category_id takes precedence when both category_id and category exist.
        if ($categoryIdFilter > 0) {
            $sql .= " AND c.id = ?";
            $params[] = $categoryIdFilter;
        } elseif ($categoryFilter !== '') {
            $sql .= " AND (c.name LIKE ? OR c.slug LIKE ?)";
            $likeValue = '%' . $categoryFilter . '%';
            $params[] = $likeValue;
            $params[] = $likeValue;
        }

        // Apply product text search after category filters.
        if ($search !== '') {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $likeValue = '%' . $search . '%';
            $params[] = $likeValue;
            $params[] = $likeValue;
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Map DB column names into the exact contract expected by storefront JS.
        return array_map(
            static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'category' => (string) $row['category'],
                    'price' => (float) $row['price'],
                    'description' => (string) ($row['description'] ?? ''),
                    'stock' => (int) $row['stock'],
                    'status' => (string) $row['status'],
                    'image_url' => !empty($row['image_path'])
                        ? '/storage/' . ltrim((string) $row['image_path'], '/\\')
                        : null,
                ];
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
