<?php
namespace Application\DTO;

class CreateProductDTO {
    
    public function __construct(
        public int $category_id,
        public string $name,
        public ?string $description,
        public float $price,
        public int $stock
    )
    {}

} 