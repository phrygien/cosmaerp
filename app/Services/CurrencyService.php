<?php

namespace App\Services;

use Illuminate\Support\Number;

class CurrencyService
{
    public function __construct(
        private string $currency = "EUR",
    )
    {
        Number::useCurrency($this->currency);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function format(?float $amount): string
    {
        if ($amount === null) {
            return '-';
        }

        return Number::currency($amount);
    }

    public function formatIn(?float $amount, string $currency): string
    {
        if ($amount === null) {
            return '-';
        }

        return Number::currency($amount, in: $currency);
    }
}
