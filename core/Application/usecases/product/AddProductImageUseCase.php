<?php
namespace Application\Usecases\Product;

use Domain\Product\ProductRepository;
use Domain\Product\ProductImage;
use Domain\Product\ProductImageRepository;
use Infrastructure\files\ProductImageUploader;

class AddProductImageUseCase {
    public function __construct(
        private ProductImageRepository $imageRepo
    ) {}
    
    public function execute(int $productId, string $path, bool $isPrimary = false): void
    {
        $image = ProductImage::create($path, $isPrimary);
        
        $this->imageRepo->save($image, $productId);
    }

}