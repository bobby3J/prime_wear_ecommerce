<?php
namespace Application\Usecases\Product;

use Application\DTO\CreateProductDTO;
use Domain\Product\Product;
use Domain\Product\ProductRepository;
use Domain\Shared\Slugger;

class CreateProductUseCase {
    public function __construct(
        private ProductRepository $productRepository
    ) {}
    
    public function execute(CreateProductDTO $dto): Product
    {
       $slug = Slugger::fromString($dto->name);

       if ($this->productRepository->slugExists($slug)) {
           $slug .= '-'. time();
       }

        // Create domain entity
        $product = Product::create(
            category_id: $dto->category_id,
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            price: $dto->price,
            stock: $dto->stock
        );

        // Persist 
        $this->productRepository->save($product);

        return $product;

    }
} 
