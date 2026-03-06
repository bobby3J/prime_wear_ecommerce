<?php
namespace Infrastructure\Http;

use Infrastructure\Persistence\MySQLPaymentRepository;

class PaymentController
{
    private MySQLPaymentRepository $paymentRepository;

    public function __construct()
    {
        $this->paymentRepository = new MySQLPaymentRepository();
    }

    public function view(): array
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'method' => trim((string) ($_GET['method'] ?? '')),
            'provider' => trim((string) ($_GET['provider'] ?? '')),
        ];

        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = $this->paymentRepository->listForAdmin($filters, $page, $perPage);

        return [
            'view' => 'payments/payments.php',
            'data' => [
                'payments' => $result['rows'] ?? [],
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
}
