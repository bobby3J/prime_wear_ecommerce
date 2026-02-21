<?php
namespace Application\Usecases\Category;

use Domain\Category\CategoryRepository;
use Domain\Product\ProductImageRepository;
use Infrastructure\Files\ProductImageDeleter;

class DeleteCategoryUseCase
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductImageRepository $imageRepository,
        private ProductImageDeleter $fileDeleter
    ) {}

    public function execute(int $id): void
    {
        $paths = $this->imageRepository->findPathsByCategoryId($id);

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                $this->fileDeleter->delete($path);
            }
        }

        $this->categoryRepository->delete($id);
    }
}
