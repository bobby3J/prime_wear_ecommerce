<?php
namespace Application\DTO;

class ProductViewDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $categoryId,
        public string $categoryName,
        public float $price,
        public int $stock,
        public ?string $imagePath,
        public ?string $description
    ) {}
}

