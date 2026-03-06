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
use Infrastructure\Payments\ProviderGatewayClient;

class PayCheckoutUseCase
{
    public function __construct(
        private CartRepository $cartRepository,
        private OrderRepositoryInterface $orderRepository,
        private PaymentRepository $paymentRepository,
        private CheckoutConfirmationStore $confirmationStore,
        private TransactionManager $transactionManager,
        private ProviderGatewayClient $gatewayClient
    ) {}

    public function execute(
        int $customerId,
        string $methodInput,
        string $transactionRef,
        string $legacySimulateResult,
        string $payerPhone
    ): array
    {
        // Kept only for backward-compatible API payload shape; phase-1 no longer uses simulate_result.
        unset($legacySimulateResult);

        $method = PaymentMethod::fromInput($methodInput);
        if (!PaymentMethod::isValid($method)) {
            throw new \RuntimeException('Payment method must be mtn_momo, telecel_cash, bank, or cash_on_delivery.');
        }

        $transactionRef = $this->resolveTransactionRef($transactionRef, $method);
        $confirmation = $this->confirmationStore->get($customerId);
        if ($confirmation === null) {
            throw new \RuntimeException('Please confirm delivery details before making payment.');
        }

        if ($method === PaymentMethod::CASH_ON_DELIVERY) {
            return $this->executeCashOnDeliveryFlow($customerId, $method, $transactionRef, $confirmation);
        }

        $payerPhone = $this->resolvePayerPhone($payerPhone, (string) ($confirmation['phone'] ?? ''), $method);

        return $this->executeProviderGatewayFlow(
            customerId: $customerId,
            method: $method,
            transactionRef: $transactionRef,
            confirmation: $confirmation,
            payerPhone: $payerPhone
        );
    }

    private function executeProviderGatewayFlow(
        int $customerId,
        string $method,
        string $transactionRef,
        array $confirmation,
        string $payerPhone
    ): array
    {
        // Pre-flight cart and signature validation before provider initiation.
        $cart = $this->cartRepository->fetchCart($customerId);
        if ($cart->totalItems() === 0) {
            throw new \RuntimeException('Your cart is empty. Add products before payment.');
        }
        $this->assertCartItemsAreCheckoutReady($cart);

        $currentSignature = $this->buildCartSignature($cart);
        if (!hash_equals((string) ($confirmation['cart_signature'] ?? ''), $currentSignature)) {
            throw new \RuntimeException('Cart changed after confirmation. Please reconfirm checkout details.');
        }

        $orderNumber = $this->orderRepository->generateOrderNumber();
        $gatewayInit = $this->gatewayClient->initiate([
            'provider' => $method,
            'amount' => $cart->subTotal(),
            'transaction_ref' => $transactionRef,
            'customer_phone' => (string) ($confirmation['phone'] ?? ''),
            'payer_phone' => $payerPhone,
            'order_number' => $orderNumber,
        ]);

        $idempotencyKey = $this->buildIdempotencyKey($customerId, $transactionRef, $method);

        return $this->transactionManager->transactional(function () use (
            $customerId,
            $method,
            $transactionRef,
            $confirmation,
            $orderNumber,
            $gatewayInit,
            $idempotencyKey,
            $payerPhone
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
                orderNumber: $orderNumber,
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
                status: 'pending',
                amount: $savedOrder->getTotalAmount(),
                transactionRef: $transactionRef
            );
            $savedPayment = $this->paymentRepository->create($payment);

            // Optional interface extension in infrastructure repository.
            if (method_exists($this->paymentRepository, 'attachGatewayMetadata')) {
                $this->paymentRepository->attachGatewayMetadata(
                    paymentId: (int) $savedPayment->getId(),
                    provider: isset($gatewayInit['provider']) ? (string) $gatewayInit['provider'] : $method,
                    providerTxnId: isset($gatewayInit['provider_txn_id']) ? (string) $gatewayInit['provider_txn_id'] : null,
                    idempotencyKey: $idempotencyKey,
                    rawStatus: isset($gatewayInit['raw_status']) ? (string) $gatewayInit['raw_status'] : null
                );
            }

            if (method_exists($this->paymentRepository, 'recordGatewayEvent')) {
                $this->paymentRepository->recordGatewayEvent(
                    paymentId: (int) $savedPayment->getId(),
                    providerEventId: 'init-' . (int) $savedPayment->getId() . '-' . date('YmdHis'),
                    eventType: 'payment.initiated',
                    payload: [
                        'provider' => (string) ($gatewayInit['provider'] ?? $method),
                        'provider_txn_id' => (string) ($gatewayInit['provider_txn_id'] ?? ''),
                        'raw_status' => (string) ($gatewayInit['raw_status'] ?? ''),
                        'payer_phone' => $payerPhone,
                        'collection_destination' => $gatewayInit['collection_destination'] ?? null,
                    ],
                    signatureValid: true
                );
            }

            $this->cartRepository->clearCustomerCart($customerId);
            $this->confirmationStore->clear($customerId);

            return [
                'order' => OrderDTO::fromEntity($savedOrder)->toArray(),
                'payment' => PaymentDTO::fromEntity($savedPayment)->toArray(),
                'gateway' => [
                    'provider' => (string) ($gatewayInit['provider'] ?? $method),
                    'provider_txn_id' => (string) ($gatewayInit['provider_txn_id'] ?? ''),
                    'checkout_url' => $gatewayInit['checkout_url'] ?? null,
                    'requires_redirect' => (bool) ($gatewayInit['requires_redirect'] ?? false),
                    'mode' => (string) ($gatewayInit['mode'] ?? 'mock'),
                    'note' => 'Payment initiated. Final status will be set by gateway webhook.',
                ],
                'collection_destination' => $gatewayInit['collection_destination'] ?? null,
            ];
        });
    }

    private function executeCashOnDeliveryFlow(int $customerId, string $method, string $transactionRef, array $confirmation): array
    {
        return $this->transactionManager->transactional(function () use (
            $customerId,
            $method,
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
                status: 'pending',
                amount: $savedOrder->getTotalAmount(),
                transactionRef: $transactionRef
            );
            $savedPayment = $this->paymentRepository->create($payment);

            $this->cartRepository->clearCustomerCart($customerId);
            $this->confirmationStore->clear($customerId);

            return [
                'order' => OrderDTO::fromEntity($savedOrder)->toArray(),
                'payment' => PaymentDTO::fromEntity($savedPayment)->toArray(),
                'customer_notice' => 'Cash on delivery selected. Payment will be collected at delivery.',
            ];
        });
    }

    private function resolveTransactionRef(string $transactionRef, string $method): string
    {
        $transactionRef = trim($transactionRef);
        if ($transactionRef === '') {
            $prefix = match ($method) {
                PaymentMethod::MTN_MOMO => 'MTNMOMO',
                PaymentMethod::TELECEL_CASH => 'TELECEL',
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

    private function buildIdempotencyKey(int $customerId, string $transactionRef, string $method): string
    {
        $seed = $customerId . '|' . $method . '|' . $transactionRef;
        return 'idem-' . substr(hash('sha256', $seed), 0, 64);
    }

    private function resolvePayerPhone(string $payerPhoneInput, string $fallbackPhone, string $method): string
    {
        $payerPhone = trim($payerPhoneInput);
        if ($payerPhone === '') {
            $payerPhone = trim($fallbackPhone);
        }

        if (in_array($method, [PaymentMethod::MTN_MOMO, PaymentMethod::TELECEL_CASH], true)) {
            if (!preg_match('/^\+?[0-9][0-9\s\-]{6,19}$/', $payerPhone)) {
                throw new \RuntimeException('Enter a valid payer mobile money number.');
            }
        }

        return $payerPhone;
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
