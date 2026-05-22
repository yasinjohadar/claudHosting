<?php

namespace App\Services\Domain;

class DomainPriceFormatter
{
    public static function format(array $priceArray, string $currencySuffix = ''): string
    {
        if (empty($priceArray) || ! is_array($priceArray)) {
            return '—';
        }
        $first = reset($priceArray);
        if (is_array($first)) {
            $first = reset($first);
        }
        if ($first === null || $first === false || $first === '') {
            return '—';
        }

        return (string) $first.$currencySuffix;
    }
}
