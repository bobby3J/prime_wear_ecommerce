<?php
namespace Domain\Payment;

interface PaymentRepository
{
    public function create(Payment $payment): Payment;

    public function listForAdmin(array $filters, int $page, int $perPage): array;
}
