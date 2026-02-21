<?php
namespace Application\DTO;

class UpdateProductDTO
{
    public function __construct(
        public int $id,
        public int $category_id,
        public string $name,
        public ?string $description,
        public float $price,
        public int $stock
    ) {}
}
