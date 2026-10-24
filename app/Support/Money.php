<?php

namespace App\Support;

class Money
{
    /**
     * Format an integer amount in minor units (e.g. paisa) as a decimal string.
     *
     * Money(12345, 2) => "123.45", Money(-5, 2) => "-0.05", Money(500, 0) => "500".
     */
    public static function format(int $minor, int $minorUnits = 2): string
    {
        $minorUnits = max(0, $minorUnits);
        $negative = $minor < 0;
        $abs = (string) ($negative ? -$minor : $minor);

        if ($minorUnits === 0) {
            return ($negative ? '-' : '').$abs;
        }

        $abs = str_pad($abs, $minorUnits + 1, '0', STR_PAD_LEFT);
        $whole = substr($abs, 0, -$minorUnits);
        $fraction = substr($abs, -$minorUnits);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }
}
