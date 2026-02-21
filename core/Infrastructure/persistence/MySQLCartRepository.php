<?php
namespace Infrastructure\Persistence;

use Domain\Cart\Cart;
use Domain\Cart\CartItem;
use Domain\Cart\CartRepository;
use PDO;

class MySQLCartRepository implements CartRepository
{
    // Cart persistence adapter.
    // Core flow:
    // 1) Resolve cart id for authenticated customer.
    // 2) Enforce product cart rules (active product, stock checks).
    // 3) Perform cart_items mutations.
    // 4) Return full cart snapshot for frontend rendering.
    public function __construct(private PDO $pdo) {}

    public function getOrCreateCartId(int $customerId): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM carts WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $cartId = $stmt->fetchColumn();

        if ($cartId !== false) {
            return (int) $cartId;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO carts (customer_id, created_at, updated_at) VALUES (?, NOW(), NOW())"
        );
        $stmt->execute([$customerId]);

        return (int) $this->pdo->lastInsertId();
    }

    public function addOrIncrementItem(int $customerId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $cartId = $this->getOrCreateCartId($customerId);
        $product = $this->fetchProductForCart($productId);

        $stmt = $this->pdo->prepare(
            "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1"
        );
        $stmt->execute([$cartId, $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = (int) $existing['quantity'] + $quantity;
            if ($newQty > (int) $product['stock']) {
                throw new \RuntimeException('Requested quantity exceeds current stock.');
            }

            $update = $this->pdo->prepare(
                "UPDATE cart_items SET quantity = ? WHERE id = ?"
            );
            $update->execute([$newQty, (int) $existing['id']]);
        } else {
            if ($quantity > (int) $product['stock']) {
                throw new \RuntimeException('Requested quantity exceeds current stock.');
            }

            $insert = $this->pdo->prepare(
                "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)"
            );
            $insert->execute([$cartId, $productId, $quantity]);
        }

        $this->touchCart($cartId);
    }

    public function updateItemQuantity(int $customerId, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $cartId = $this->getOrCreateCartId($customerId);

        $stmt = $this->pdo->prepare(
            "SELECT ci.id, ci.product_id
             FROM cart_items ci
             WHERE ci.id = ? AND ci.cart_id = ?
             LIMIT 1"
        );
        $stmt->execute([$itemId, $cartId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new \RuntimeException('Cart item not found.');
        }

        $product = $this->fetchProductForCart((int) $item['product_id']);
        if ($quantity > (int) $product['stock']) {
            throw new \RuntimeException('Requested quantity exceeds current stock.');
        }

        $update = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $update->execute([$quantity, $itemId]);

        $this->touchCart($cartId);
    }

    public function removeItem(int $customerId, int $itemId): void
    {
        $cartId = $this->getOrCreateCartId($customerId);

        $stmt = $this->pdo->prepare(
            "DELETE FROM cart_items WHERE id = ? AND cart_id = ?"
        );
        $stmt->execute([$itemId, $cartId]);

        $this->touchCart($cartId);
    }

    public function fetchCart(int $customerId): Cart
    {
        $cartId = $this->getOrCreateCartId($customerId);

        $stmt = $this->pdo->prepare(
            "SELECT
                ci.id AS cart_item_id,
                ci.product_id,
                p.name,
                p.slug,
                p.price,
                p.stock,
                p.status,
                ci.quantity,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.id ASC
                    LIMIT 1
                ) AS image_path
             FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id DESC"
        );
        $stmt->execute([$cartId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $unitPrice = (float) $row['price'];
            $qty = (int) $row['quantity'];

            $items[] = new CartItem(
                id: (int) $row['cart_item_id'],
                productId: (int) $row['product_id'],
                name: $row['name'],
                slug: $row['slug'],
                price: $unitPrice,
                stock: (int) $row['stock'],
                status: $row['status'],
                quantity: $qty,
                imageUrl: !empty($row['image_path']) ? '/storage/' . ltrim($row['image_path'], '/\\') : null
            );
        }

        return new Cart(
            id: $cartId,
            customerId: $customerId,
            items: $items
        );
    }

    public function countItems(int $customerId): int
    {
        $cartId = $this->getOrCreateCartId($customerId);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?"
        );
        $stmt->execute([$cartId]);

        return (int) $stmt->fetchColumn();
    }

    public function clearCustomerCart(int $customerId): void
    {
        $cartId = $this->getOrCreateCartId($customerId);

        $delete = $this->pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $delete->execute([$cartId]);

        $this->touchCart($cartId);
    }

    private function fetchProductForCart(int $productId): array
    {
        // Product-level guardrails for cart operations.
        $stmt = $this->pdo->prepare(
            "SELECT id, name, price, stock, status
             FROM products
             WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new \RuntimeException('Product does not exist.');
        }

        if ($product['status'] !== 'active') {
            throw new \RuntimeException('Only active products can be added to cart.');
        }

        if ((int) $product['stock'] <= 0) {
            throw new \RuntimeException('Product is out of stock.');
        }

        return $product;
    }

    private function touchCart(int $cartId): void
    {
        $stmt = $this->pdo->prepare("UPDATE carts SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$cartId]);
    }
}
