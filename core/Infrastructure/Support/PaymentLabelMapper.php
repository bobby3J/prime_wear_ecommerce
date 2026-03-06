<?php
namespace Infrastructure\Support;

class PaymentLabelMapper
{
    public static function paymentMethod(string $value): string
    {
        $normalized = self::normalize($value);

        return match ($normalized) {
            'momo', 'mobile_money', 'mtn_momo' => 'MTN MoMo',
            'vodafone', 'vodafone_cash', 'telecel_cash' => 'Telecel Cash',
            'bank', 'card' => 'Bank',
            'cash_on_delivery', 'cod' => 'Cash On Delivery',
            default => self::humanize($value),
        };
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace('-', '_', $value);
        return preg_replace('/\s+/', '_', $value) ?? '';
    }

    private static function humanize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '-';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        return ucwords($value);
    }
}

