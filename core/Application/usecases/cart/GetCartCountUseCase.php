<?php
namespace Application\Usecases\Cart;

use Domain\Cart\CartRepository;

class GetCartCountUseCase
{
    public function __construct(private CartRepository $cartRepository) {}

    public function execute(int $customerId): int
    {
        return $this->cartRepository->countItems($customerId);
    }
}

