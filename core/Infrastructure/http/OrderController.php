<?php
namespace Infrastructure\Http;

use Infrastructure\Persistence\MySQLOrderRepository;

class OrderController
{
    private MySQLOrderRepository $orderRepository;

    public function __construct()
    {
        $this->orderRepository = new MySQLOrderRepository();
    }

    public function view(): array
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = $this->orderRepository->listForAdmin($filters, $page, $perPage);

        return [
            'view' => 'orders/orders.php',
            'data' => [
                'orders' => $result['rows'] ?? [],
                'filters' => $filters,
                'pagination' => $result['pagination'] ?? [
                    'page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 1,
                ],
            ],
        ];
    }

    public function show(): array
    {
        $orderId = (int) ($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        $detail = $this->orderRepository->findForAdmin($orderId);
        if (!$detail) {
            return [
                'view' => 'errors/404.php',
                'data' => [],
            ];
        }

        return [
            'view' => 'orders/show.php',
            'data' => [
                'order' => $detail['order'] ?? [],
                'items' => $detail['items'] ?? [],
                'payments' => $detail['payments'] ?? [],
            ],
        ];
    }
}
