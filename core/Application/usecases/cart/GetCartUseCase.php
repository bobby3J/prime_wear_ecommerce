<?php
namespace Application\Usecases\Cart;

use Domain\Cart\Cart;
use Domain\Cart\CartRepository;

class GetCartUseCase
{
    public function __construct(private CartRepository $cartRepository) {}

    public function execute(int $customerId): Cart
    {
        return $this->cartRepository->fetchCart($customerId);
    }
}
