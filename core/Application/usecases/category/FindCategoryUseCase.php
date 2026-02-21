<?php
namespace Application\Usecases\Category;

use Domain\Category\CategoryRepository;
use Domain\Category\Category;

class FindCategoryUseCase
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function execute(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }
}
