<?php
namespace Application\Usecases\Product;

use Domain\Product\ProductRepository;

class FindProductUseCase {
    public function __construct(private ProductRepository $repo) {}
    
    public function execute(int $productId)
    {
        return $this->repo->findById($productId);
    }
}