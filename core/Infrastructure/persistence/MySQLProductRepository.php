<?php
namespace Infrastructure\Persistence;

use Domain\Product\Product;
use Domain\Product\ProductRepository;
use PDO;

class MySQLProductRepository implements ProductRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Product $product): void
    {
        if ($product->getId() === null) {

            $stmt = $this->pdo->prepare(
                "INSERT INTO products (
                    category_id,
                    name,
                    slug,
                    description,
                    price,
                    stock,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?,?,?,?,?,?,?,?,?)"
            );

            $stmt->execute([
                $product->getCategoryId(),
                $product->getName(),
                $product->getSlug(),
                $product->getDescription(),
                $product->getPrice(),
                $product->getStock(),
                $product->getStatus(),
                $product->getCreatedAt()->format('Y-m-d H:i:s'),
                $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            // Set generated ID back on entity
            $product->setId((int) $this->pdo->lastInsertId());
            return;
        }

        // UPDATE
        $stmt = $this->pdo->prepare(
            "UPDATE products SET
                category_id = ?,
                name = ?,
                slug = ?,
                description = ?,
                price = ?,
                stock = ?,
                status = ?,
                updated_at = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $product->getCategoryId(),
            $product->getName(),
            $product->getSlug(),
            $product->getDescription(),
            $product->getPrice(),
            $product->getStock(),
            $product->getStatus(),
            $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            $product->getId(),
        ]);
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Product::fromPersistence(
            (int) $row['id'],
            (int) $row['category_id'],
            $row['name'],
            $row['slug'],
            $row['description'],
            (float) $row['price'],
            (int) $row['stock'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM products ORDER BY created_at DESC");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn($row) => Product::fromPersistence(
            (int) $row['id'],
            (int) $row['category_id'],
            $row['name'],
            $row['slug'],
            $row['description'],
            (float) $row['price'],
            (int) $row['stock'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        ), $rows);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM products WHERE id = ?"
        );

        $stmt->execute([$id]);
    }

    public function findBySlug(string $slug): ?Product
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM products WHERE slug = ? LIMIT 1"
        );

        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Product::fromPersistence(
            (int) $row['id'],
            (int) $row['category_id'],
            $row['name'],
            $row['slug'],
            $row['description'],
            (float) $row['price'],
            (int) $row['stock'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM products WHERE slug = ?"
        );

        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
