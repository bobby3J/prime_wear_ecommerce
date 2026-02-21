<?php
namespace Domain\Product;

interface ProductRepository {
    public function save(Product $product): void;

    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function slugExists(string $slug): bool;

    /**
     * @return Product[]
     */
    public function findAll(): array;

    public function delete(int $id): void;
}