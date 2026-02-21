<?php
namespace Domain\Category;

interface CategoryRepository
{
    public function save(Category $category): void;

    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    public function slugExists(string $slug): bool;

    /**
     * @return Category[]
     */
    public function findAll(): array;

    public function delete(int $id): void;
}
