<?php
namespace Domain\Payment;

class PaymentMethod
{
    public const MTN_MOMO = 'mtn_momo';
    public const TELECEL_CASH = 'telecel_cash';
    public const BANK = 'bank';
    public const CASH_ON_DELIVERY = 'cash_on_delivery';

    public static function fromInput(string $method): string
    {
        $normalized = strtolower(trim($method));
        return match ($normalized) {
            'momo', 'mtn_momo', 'mtn momo' => self::MTN_MOMO,
            'telecel_cash', 'telecel cash', 'vodafone', 'vodafone_cash' => self::TELECEL_CASH,
            'bank' => self::BANK,
            'cash_on_delivery', 'cod', 'cash on delivery' => self::CASH_ON_DELIVERY,
            default => '',
        };
    }

    public static function isValid(string $method): bool
    {
        return in_array($method, [self::MTN_MOMO, self::TELECEL_CASH, self::BANK, self::CASH_ON_DELIVERY], true);
    }
}
