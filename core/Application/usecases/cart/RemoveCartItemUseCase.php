<?php
namespace Application\Usecases\Cart;

use Domain\Cart\CartRepository;

class RemoveCartItemUseCase
{
    public function __construct(private CartRepository $cartRepository) {}

    public function execute(int $customerId, int $itemId): void
    {
        $this->cartRepository->removeItem($customerId, $itemId);
    }
}

