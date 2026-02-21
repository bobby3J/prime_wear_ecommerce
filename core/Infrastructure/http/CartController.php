<?php
namespace Infrastructure\Http;

use Application\DTO\CartViewDTO;
use Application\Usecases\Cart\AddItemToCartUseCase;
use Application\Usecases\Cart\GetCartCountUseCase;
use Application\Usecases\Cart\GetCartUseCase;
use Application\Usecases\Cart\RemoveCartItemUseCase;
use Application\Usecases\Cart\UpdateCartItemQuantityUseCase;
use Core\Infrastructure\Persistence\Database;
use Infrastructure\Persistence\MySQLCartRepository;

class CartController
{
    // Controller layer for cart endpoints.
    // Receives API input, delegates to use cases, returns normalized responses.
    private GetCartUseCase $getCartUseCase;
    private GetCartCountUseCase $getCartCountUseCase;
    private AddItemToCartUseCase $addItemUseCase;
    private UpdateCartItemQuantityUseCase $updateItemUseCase;
    private RemoveCartItemUseCase $removeItemUseCase;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $repository = new MySQLCartRepository($pdo);

        $this->getCartUseCase = new GetCartUseCase($repository);
        $this->getCartCountUseCase = new GetCartCountUseCase($repository);
        $this->addItemUseCase = new AddItemToCartUseCase($repository);
        $this->updateItemUseCase = new UpdateCartItemQuantityUseCase($repository);
        $this->removeItemUseCase = new RemoveCartItemUseCase($repository);
    }

    public function get(int $customerId): array
    {
        $cart = $this->getCartUseCase->execute($customerId);
        return CartViewDTO::fromEntity($cart)->toArray();
    }

    public function count(int $customerId): int
    {
        return $this->getCartCountUseCase->execute($customerId);
    }

    public function add(int $customerId, array $input): void
    {
        $productId = (int) ($input['product_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);

        if ($productId <= 0) {
            throw new \InvalidArgumentException('Valid product_id is required.');
        }

        $this->addItemUseCase->execute($customerId, $productId, $quantity);
    }

    public function update(int $customerId, array $input): void
    {
        $itemId = (int) ($input['item_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 0);

        if ($itemId <= 0) {
            throw new \InvalidArgumentException('Valid item_id is required.');
        }

        $this->updateItemUseCase->execute($customerId, $itemId, $quantity);
    }

    public function remove(int $customerId, array $input): void
    {
        $itemId = (int) ($input['item_id'] ?? 0);
        if ($itemId <= 0) {
            throw new \InvalidArgumentException('Valid item_id is required.');
        }

        $this->removeItemUseCase->execute($customerId, $itemId);
    }
}
