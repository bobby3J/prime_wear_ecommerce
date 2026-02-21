<?php
namespace Application\Usecases\Category;

use Domain\Category\CategoryRepository;

class ListCategoriesUseCase
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function execute(): array
    {
        return $this->categoryRepository->findAll();
    }
}
