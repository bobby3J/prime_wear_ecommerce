<?php
namespace Application\Usecases\Product;

use Application\DTO\ProductViewDTO;
use Domain\Product\ProductReadRepository;

class ListProductsViewUseCase
{
    public function __construct(
        private ProductReadRepository $readRepo
    ) {}


    public function execute(): array
    {
        $rows = $this->readRepo->fetchAll(); // returns associative array with category_name

        return array_map(fn($row) => new ProductViewDTO(
            id: (int)$row['id'],
            name: $row['name'],
            categoryId: (int)$row['category_id'],
            categoryName: $row['category_name'],
            price: (float)$row['price'],
            stock: (int)$row['stock'],
            imagePath: $row['image_path'] ?? null,
            description: $row['description'] ?? null
        ), $rows);
    }
}
