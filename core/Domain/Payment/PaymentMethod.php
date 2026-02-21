<?php
namespace Domain\Payment;

class PaymentMethod
{
    public const MOMO = 'momo';
    public const BANK = 'bank';
    public const CASH_ON_DELIVERY = 'cash_on_delivery';

    public static function fromInput(string $method): string
    {
        $normalized = strtolower(trim($method));
        return match ($normalized) {
            'momo' => self::MOMO,
            'bank' => self::BANK,
            'cash_on_delivery', 'cod', 'cash on delivery' => self::CASH_ON_DELIVERY,
            default => '',
        };
    }

    public static function isValid(string $method): bool
    {
        return in_array($method, [self::MOMO, self::BANK, self::CASH_ON_DELIVERY], true);
    }
}
