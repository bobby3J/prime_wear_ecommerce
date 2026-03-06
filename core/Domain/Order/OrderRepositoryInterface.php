<?php
namespace Domain\Order;

interface OrderRepositoryInterface
{
    public function generateOrderNumber(): string;

    /**
     * Persists the order aggregate root and its line items.
     * Returns the persisted order with database id.
     *
     * @param OrderItem[] $items
     */
    public function create(Order $order, array $items): Order;

    public function saveDeliveryDetails(int $orderId, OrderDeliveryDetails $details): void;

    /**
     * Atomically decrements product stock for each order item.
     * Implementations should fail when any item has insufficient stock.
     *
     * @param OrderItem[] $items
     */
    public function reduceStockForOrderItems(array $items): void;

    public function markAsPaid(int $orderId): void;

    public function listForAdmin(array $filters, int $page, int $perPage): array;

    public function findForAdmin(int $orderId): ?array;
}
