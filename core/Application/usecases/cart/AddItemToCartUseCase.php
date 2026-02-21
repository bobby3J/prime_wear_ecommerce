<?php
namespace Application\Usecases\Cart;

use Domain\Cart\CartRepository;

class AddItemToCartUseCase
{
    public function __construct(private CartRepository $cartRepository) {}

    public function execute(int $customerId, int $productId, int $quantity): void
    {
        $this->cartRepository->addOrIncrementItem($customerId, $productId, $quantity);
    }
}

