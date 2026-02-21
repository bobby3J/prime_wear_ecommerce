<?php
namespace Application\Usecases\Product;

use Domain\Product\ProductRepository;
use Infrastructure\Persistence\MySQLProductImageRepository;
use Infrastructure\Files\ProductImageDeleter;

class DeleteProductUsecase {
    public function __construct(
        private ProductRepository $productRepo,
        private MySQLProductImageRepository $imageRepo,
        private ProductImageDeleter $fileDeleter
    ) {}
    
    public function execute(int $productId): void 
    {
        // fetch image paths
        $images = $this->imageRepo->findByProductId($productId);

        // delete physical image files
        foreach ($images as $image) {
            $path = $image['image_path'] ?? $image['path'] ?? null;
            if (is_string($path) && $path !== '') {
                $this->fileDeleter->delete($path);
            }
        }

        // delete image db records
        $this->imageRepo->deleteByProductId($productId);

        // delete product row
        $this->productRepo->delete($productId);
    }
}
