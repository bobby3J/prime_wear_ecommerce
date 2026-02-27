<?php
namespace Application\Usecases\Category;

use Application\DTO\CreateCategoryDTO;
use Domain\Category\Category;
use Domain\Category\CategoryRepository;
use Domain\Shared\Slugger;

class CreateCategoryUseCase
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        $slug = Slugger::fromString($dto->name);

        if ($this->categoryRepository->slugExists($slug)) {
            $slug .= '-' . time();
        }

        $category = Category::create(
            name: $dto->name,
            slug: $slug,
            parent_id: $dto->parentId,
            status: $dto->status
        );

        $this->categoryRepository->save($category);

        return $category;
    }
}
