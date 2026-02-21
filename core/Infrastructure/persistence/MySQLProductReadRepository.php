<?php
namespace Infrastructure\Persistence;

use PDO;
use Domain\Product\ProductReadRepository;

class MySQLProductReadRepository implements ProductReadRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.stock,
                p.category_id,
                c.name AS category_name,
                pi.image_path
             FROM products p
             JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_images pi ON pi.product_id = p.id"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
