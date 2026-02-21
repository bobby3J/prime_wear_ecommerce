<?php
namespace Application\Usecases\Checkout;

use Application\DTO\OrderDTO;
use Application\DTO\PaymentDTO;
use Domain\Cart\Cart;
use Domain\Cart\CartRepository;
use Domain\Order\Order;
use Domain\Order\OrderDeliveryDetails;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use Domain\Payment\Payment;
use Domain\Payment\PaymentMethod;
use Domain\Payment\PaymentRepository;
use Domain\Shared\TransactionManager;

class PayCheckoutUseCase
{
    public function __construct(
        private CartRepository $cartRepository,
        private OrderRepositoryInterface $orderRepository,
        private PaymentRepository $paymentRepository,
        private CheckoutConfirmationStore $confirmationStore,
        private TransactionManager $transactionManager
    ) {}

    public function execute(int $customerId, string $methodInput, string $transactionRef, string $simulateResult): array
    {
        $method = PaymentMethod::fromInput($methodInput);
        if (!PaymentMethod::isValid($method)) {
            throw new \RuntimeException('Payment method must be momo, bank, or cash_on_delivery.');
        }

        $paymentStatus = $this->resolveSimulatedStatus($simulateResult, $method);
        $transactionRef = $this->resolveTransactionRef($transactionRef, $method);

        $confirmation = $this->confirmationStore->get($customerId);
        if ($confirmation === null) {
            throw new \RuntimeException('Please confirm delivery details before making payment.');
        }

        return $this->transactionManager->transactional(function () use (
            $customerId,
            $method,
            $paymentStatus,
            $transactionRef,
            $confirmation
        ): array {
            $cart = $this->cartRepository->fetchCart($customerId);
            if ($cart->totalItems() === 0) {
                throw new \RuntimeException('Your cart is empty. Add products before payment.');
            }

            $this->assertCartItemsAreCheckoutReady($cart);

            $currentSignature = $this->buildCartSignature($cart);
            if (!hash_equals((string) ($confirmation['cart_signature'] ?? ''), $currentSignature)) {
                throw new \RuntimeException('Cart changed after confirmation. Please reconfirm checkout details.');
            }

            $order = Order::place(
                customerId: $customerId,
                orderNumber: $this->orderRepository->generateOrderNumber(),
                totalAmount: $cart->subTotal()
            );

            $items = array_map(
                static fn($cartItem) => new OrderItem(
                    productId: $cartItem->getProductId(),
                    quantity: $cartItem->getQuantity(),
                    priceAtPurchase: $cartItem->getPrice()
                ),
                $cart->getItems()
            );

            $savedOrder = $this->orderRepository->create($order, $items);
            $orderId = (int) $savedOrder->getId();

            $this->orderRepository->saveDeliveryDetails(
                $orderId,
                new OrderDeliveryDetails(
                    fullName: (string) ($confirmation['name'] ?? ''),
                    phone: (string) ($confirmation['phone'] ?? ''),
                    streetAddress: (string) ($confirmation['street_address'] ?? '')
                )
            );

            $payment = Payment::create(
                orderId: $orderId,
                method: $method,
                status: $paymentStatus,
                amount: $savedOrder->getTotalAmount(),
                transactionRef: $transactionRef
            );
            $savedPayment = $this->paymentRepository->create($payment);

            if ($paymentStatus === 'successful') {
                $this->orderRepository->markAsPaid($orderId);
                $savedOrder = $savedOrder->withStatus('paid');
            }

            $this->cartRepository->clearCustomerCart($customerId);
            $this->confirmationStore->clear($customerId);

            return [
                'order' => OrderDTO::fromEntity($savedOrder)->toArray(),
                'payment' => PaymentDTO::fromEntity($savedPayment)->toArray(),
                'simulation' => [
                    'applied_status' => $paymentStatus,
                    'note' => 'Payment is simulated for workflow testing.',
                ],
            ];
        });
    }

    private function resolveSimulatedStatus(string $simulateResult, string $method): string
    {
        if ($method === PaymentMethod::CASH_ON_DELIVERY) {
            // COD is intentionally left pending until manual fulfillment.
            return 'pending';
        }

        $normalized = strtolower(trim($simulateResult));
        if ($normalized === '') {
            return 'successful';
        }

        return match ($normalized) {
            'successful', 'failed', 'pending' => $normalized,
            default => throw new \RuntimeException(
                'simulate_result must be successful, failed, or pending.'
            ),
        };
    }

    private function resolveTransactionRef(string $transactionRef, string $method): string
    {
        $transactionRef = trim($transactionRef);
        if ($transactionRef === '') {
            $prefix = match ($method) {
                PaymentMethod::MOMO => 'MOMO',
                PaymentMethod::BANK => 'BANK',
                PaymentMethod::CASH_ON_DELIVERY => 'COD',
                default => 'PAY',
            };
            $transactionRef = $prefix . '-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        }

        if (mb_strlen($transactionRef) < 4 || mb_strlen($transactionRef) > 100) {
            throw new \RuntimeException('Transaction reference must be between 4 and 100 characters.');
        }

        return $transactionRef;
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
}
