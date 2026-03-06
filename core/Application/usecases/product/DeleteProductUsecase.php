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
        // Capture image paths first, but do not delete files yet.
        // If product row deletion fails (FK references), files should remain intact.
        $images = $this->imageRepo->findByProductId($productId);

        try {
            // Delete product row first.
            // `product_images` rows are removed by ON DELETE CASCADE.
            $this->productRepo->delete($productId);
        } catch (\PDOException $e) {
            // MySQL integrity violation = referenced by cart/order rows.
            if (($e->getCode() ?? '') === '23000') {
                throw new \RuntimeException(
                    'Cannot delete this product because it is used in carts or orders. ' .
                    'Remove cart references first or keep it as inactive.'
                );
            }
            throw $e;
        }

        // Delete physical files only after DB deletion succeeded.
        foreach ($images as $image) {
            $path = $image['image_path'] ?? $image['path'] ?? null;
            if (is_string($path) && $path !== '') {
                $this->fileDeleter->delete($path);
            }
        }
    }
}
