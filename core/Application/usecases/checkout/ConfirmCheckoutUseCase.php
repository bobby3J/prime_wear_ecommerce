<?php
namespace Application\Usecases\Checkout;

use Domain\Cart\Cart;
use Domain\Cart\CartRepository;
use Domain\Customer\CustomerRepository;

class ConfirmCheckoutUseCase
{
    /**
     * Keeping TTL in the use case makes behavior explicit and test-friendly.
     */
    private const CONFIRMATION_TTL_SECONDS = 900;

    public function __construct(
        private CustomerRepository $customerRepository,
        private CartRepository $cartRepository,
        private CheckoutConfirmationStore $confirmationStore
    ) {}

    public function execute(int $customerId, string $name, string $phone, string $streetAddress): array
    {
        $name = $this->validateName($name);
        $phone = $this->validatePhone($phone);
        $streetAddress = $this->validateStreetAddress($streetAddress);

        $customer = $this->customerRepository->findById($customerId);
        if (!$customer) {
            throw new \RuntimeException('Customer account was not found.');
        }
        if ($customer->getStatus() !== 'active') {
            throw new \RuntimeException('Only active customers can checkout.');
        }

        $cart = $this->cartRepository->fetchCart($customerId);
        if ($cart->totalItems() === 0) {
            throw new \RuntimeException('Your cart is empty. Add products before checkout.');
        }

        $this->assertCartItemsAreCheckoutReady($cart);
        $signature = $this->buildCartSignature($cart);

        $this->confirmationStore->save($customerId, [
            'name' => $name,
            'phone' => $phone,
            'street_address' => $streetAddress,
            'cart_signature' => $signature,
        ], self::CONFIRMATION_TTL_SECONDS);

        return [
            'confirmed' => true,
            'expires_in_seconds' => self::CONFIRMATION_TTL_SECONDS,
            'delivery' => [
                'name' => $name,
                'phone' => $phone,
                'street_address' => $streetAddress,
            ],
            'cart_summary' => [
                'total_items' => $cart->totalItems(),
                'total_quantity' => $cart->totalQuantity(),
                'sub_total' => $cart->subTotal(),
            ],
            'allowed_payment_methods' => ['momo', 'bank', 'cash_on_delivery'],
        ];
    }

    private function buildCartSignature(Cart $cart): string
    {
        $payload = [
            'cart_id' => $cart->getId(),
            'total_items' => $cart->totalItems(),
            'total_quantity' => $cart->totalQuantity(),
            'sub_total' => $cart->subTotal(),
            'items' => array_map(
                static fn($item) => [
                    'product_id' => $item->getProductId(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                ],
                $cart->getItems()
            ),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function assertCartItemsAreCheckoutReady(Cart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getStatus() !== 'active') {
                throw new \RuntimeException('Checkout blocked: one or more products are inactive.');
            }
            if ($item->getStock() < $item->getQuantity()) {
                throw new \RuntimeException('Checkout blocked: one or more products exceed available stock.');
            }
        }
    }

    private function validateName(string $value): string
    {
        $value = trim($value);
        if (mb_strlen($value) < 2 || mb_strlen($value) > 120) {
            throw new \RuntimeException('Full name must be between 2 and 120 characters.');
        }
        return $value;
    }

    private function validatePhone(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^\+?[0-9][0-9\s\-]{6,19}$/', $value)) {
            throw new \RuntimeException('Enter a valid phone number.');
        }
        return $value;
    }

    private function validateStreetAddress(string $value): string
    {
        $value = trim($value);
        if (mb_strlen($value) < 5 || mb_strlen($value) > 255) {
            throw new \RuntimeException('Street address must be between 5 and 255 characters.');
        }
        return $value;
    }
}
