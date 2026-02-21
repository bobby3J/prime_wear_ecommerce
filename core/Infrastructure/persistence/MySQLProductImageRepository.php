<?php
namespace Infrastructure\Persistence;

use Domain\Product\ProductImage;
use Domain\Product\ProductImageRepository;
use PDO;

class MySQLProductImageRepository implements ProductImageRepository
{
    public function __construct(private PDO $pdo) {}
    
    public function save(ProductImage $image, int $productId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO product_images (product_id, image_path, is_primary)
            VALUES (:product_id, :image_path, :is_primary)"
        );

        $stmt->execute([
            'product_id' => $productId,
            'image_path' => $image->path(),
            'is_primary' => (int)$image->isPrimary() ? 1 : 0,
        ]);
    }

    public function findByProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPathsByCategoryId(int $categoryId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pi.image_path
             FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE p.category_id = ?"
        );
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function deleteByProductId(int $productId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
    } 
}
