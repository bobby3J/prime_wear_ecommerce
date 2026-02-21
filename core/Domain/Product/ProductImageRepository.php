<?php
namespace Domain\Product;

interface ProductImageRepository
{
    public function save(ProductImage $image, int $productId): void;

    public function findByProductId(int $productId): array;

    public function findPathsByCategoryId(int $categoryId): array;

    public function deleteByProductId(int $productId): void;
}
