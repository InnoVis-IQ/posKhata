<?php

namespace App\Support;

class Money
{
    /**
     * Round an amount in major currency units (e.g. PKR rupees) to the shop decimal places.
     *
     * Money::round(2195.456) => 2195.46
     */
    public static function round(float|int|string $amount, int $decimals = 2): float
    {
        return round((float) $amount, max(0, $decimals));
    }

    /**
     * Format a major-unit amount as a fixed decimal string.
     *
     * Money::format(2195) => "2195.00", Money::format(-0.5) => "-0.50"
     */
    public static function format(float|int|string $amount, int $decimals = 2): string
    {
        return number_format((float) $amount, max(0, $decimals), '.', '');
    }
}
