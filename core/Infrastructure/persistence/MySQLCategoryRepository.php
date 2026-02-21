<?php
namespace Infrastructure\Persistence;

use Domain\Category\Category;
use Domain\Category\CategoryRepository;
use PDO;

class MySQLCategoryRepository implements CategoryRepository
{
    public function __construct(private PDO $pdo) {}

    public function save(Category $category): void
    {
        if ($category->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO categories (
                    name,
                    slug,
                    parent_id,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?,?,?,?,?,?)"
            );

            $stmt->execute([
                $category->getName(),
                $category->getSlug(),
                $category->getParentId(),
                $category->getStatus(),
                $category->getCreatedAt()->format('Y-m-d H:i:s'),
                $category->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            $category->setId((int) $this->pdo->lastInsertId());
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE categories SET
                name = ?,
                slug = ?,
                parent_id = ?,
                status = ?,
                updated_at = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $category->getName(),
            $category->getSlug(),
            $category->getParentId(),
            $category->getStatus(),
            $category->getUpdatedAt()->format('Y-m-d H:i:s'),
            $category->getId(),
        ]);
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Category::fromPersistence(
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Category::fromPersistence(
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        ), $rows);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function findBySlug(string $slug): ?Category
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories WHERE slug = ? LIMIT 1"
        );

        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Category::fromPersistence(
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM categories WHERE slug = ?"
        );

        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
