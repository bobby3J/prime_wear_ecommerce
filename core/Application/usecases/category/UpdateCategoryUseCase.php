<?php
namespace Application\Usecases\Category;

use Application\DTO\UpdateCategoryDTO;
use Domain\Category\CategoryRepository;
use Domain\Shared\Slugger;

class UpdateCategoryUseCase
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function execute(UpdateCategoryDTO $dto)
    {
        $category = $this->categoryRepository->findById($dto->id);

        if (!$category) {
            throw new \RuntimeException('Category not found.');
        }

        $slug = $category->getSlug();

        if ($dto->name !== $category->getName()) {
            $slug = Slugger::fromString($dto->name);
            $existing = $this->categoryRepository->findBySlug($slug);
            if ($existing && $existing->getId() !== $category->getId()) {
                $slug .= '-' . time();
            }
        }

        $category->updateDetails(
            name: $dto->name,
            slug: $slug,
            parent_id: null,
            status: $dto->status
        );

        $this->categoryRepository->save($category);

        return $category;
    }
}
