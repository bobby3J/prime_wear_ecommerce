<?php
namespace Application\Usecases\Product;

use Application\DTO\UpdateProductDTO;
use Domain\Product\ProductRepository;
use Domain\Shared\Slugger;

class UpdateProductUseCase
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function execute(UpdateProductDTO $dto)
    {
        $product = $this->productRepository->findById($dto->id);

        if (!$product) {
            throw new \RuntimeException('Product not found.');
        }

        $slug = $product->getSlug();

        if ($dto->name !== $product->getName()) {
            $slug = Slugger::fromString($dto->name);
            $existing = $this->productRepository->findBySlug($slug);
            if ($existing && $existing->getId() !== $product->getId()) {
                $slug .= '-' . time();
            }
        }

        $product->updateDetails(
            category_id: $dto->category_id,
            name: $dto->name,
            slug: $slug,
            description: $dto->description,
            price: $dto->price,
            stock: $dto->stock
        );

        $this->productRepository->save($product);

        return $product;
    }
}
