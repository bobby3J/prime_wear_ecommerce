<?php
namespace Infrastructure\Query;

use PDO;

/**
 * Read-only query service used by the storefront categories API endpoint.
 *
 * Connection flow:
 * - ecommerce/shared/api/categories.php creates this service with a PDO instance.
 * - The endpoint calls fetchActiveCategories().
 * - This service runs SQL and returns normalized array rows for JSON output.
 */
class StorefrontCategoryQueryService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Returns active storefront categories in API-ready shape.
     *
     * Output item shape:
     * - id: int
     * - name: string
     * - slug: string
     * - group: men|ladies|unisex
     * - parent_id: int|null
     * - product_count: int
     */
    public function fetchActiveCategories(): array
    {
        // Pull active categories, join parent to infer storefront grouping,
        // and count active products per category for quick UI badges/metadata.
        $stmt = $this->pdo->query(
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

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            // Root collection categories are navigation groups, not display cards.
            $slug = strtolower((string) $row['slug']);
            if (in_array($slug, ['men', 'ladies', 'unisex', 'couples-unisex'], true)) {
                continue;
            }

            // If parent slug cannot be mapped, default to "unisex" so frontend
            // still receives a predictable group value.
            $group = $this->normalizeGroup((string) ($row['parent_slug'] ?? '')) ?? 'unisex';

            $items[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'group' => $group,
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'product_count' => (int) $row['product_count'],
            ];
        }

        return $items;
    }

    // Maps category parent slug values from DB into frontend group names.
    private function normalizeGroup(string $value): ?string
    {
        $slug = strtolower(trim($value));

        return match ($slug) {
            'men' => 'men',
            'ladies' => 'ladies',
            'unisex', 'couples-unisex' => 'unisex',
            default => null,
        };
    }
}
