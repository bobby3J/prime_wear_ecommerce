<?php
namespace Application\Usecases\Cart;

use Domain\Cart\CartRepository;

class UpdateCartItemQuantityUseCase
{
    public function __construct(private CartRepository $cartRepository) {}

    public function execute(int $customerId, int $itemId, int $quantity): void
    {
        $this->cartRepository->updateItemQuantity($customerId, $itemId, $quantity);
    }
}

