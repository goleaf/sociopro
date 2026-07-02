<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class Money
{
    public static function toMinorUnits(mixed $amount): int
    {
        $amount = self::normalizeDecimalAmount($amount);

        if (preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new InvalidArgumentException('Money amount must be a non-negative decimal with at most two places.');
        }

        [$major, $minor] = array_pad(explode('.', $amount, 2), 2, '');
        $minor = str_pad($minor, 2, '0');

        return ((int) $major * 100) + (int) $minor;
    }

    private static function normalizeDecimalAmount(mixed $amount): string
    {
        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_float($amount)) {
            return rtrim(rtrim(sprintf('%.10F', $amount), '0'), '.');
        }

        if (is_string($amount)) {
            return trim($amount);
        }

        throw new InvalidArgumentException('Money amount must be a decimal string, integer, or float.');
    }
}
