<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Per-shop document number generator. Must be called inside a DB transaction. */
final class NumberSequence
{
    /** Returns e.g. "S-20261025-0001" for prefix "S-20261025-". */
    public static function next(int $shopId, string $table, string $column, string $prefix, int $pad = 4): string
    {
        // Serialise number generation per shop (row lock on shops).
        DB::table('shops')->where('id', $shopId)->lockForUpdate()->value('id');

        $last = DB::table($table)
            ->where('shop_id', $shopId)
            ->where($column, 'like', $prefix.'%')
            ->orderByRaw("LENGTH({$column}) DESC, {$column} DESC")
            ->value($column);

        $n = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
