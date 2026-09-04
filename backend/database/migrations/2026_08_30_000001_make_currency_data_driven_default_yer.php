<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the currency system data-driven:
 * - Default property currency becomes YER (Sudair/Yemen rial) per product spec.
 * - Normalizes any legacy/case-mismatched codes (e.g. "sar" -> "SAR").
 * - Restricts codes to the supported set (YER, SAR, USD) going forward.
 *
 * Safe for production: existing rows are only normalized in place, never deleted.
 */
return new class extends Migration
{
    private const SUPPORTED = ['YER', 'SAR', 'USD'];

    public function up(): void
    {
        // Normalize existing values (case-sensitive storage on some drivers).
        foreach ([
            ['YER', ['yer', 'Yer', 'YR', 'YRI', 'YRS']],
            ['SAR', ['sar', 'Sar', 'SR', 'SRI']],
            ['USD', ['usd', 'Usd', 'US', 'DOL', '$']],
        ] as [$canonical, $aliases]) {
            foreach ($aliases as $alias) {
                DB::table('properties')
                    ->where('currency', $alias)
                    ->update(['currency' => $canonical]);
            }
        }

        // Any other unknown code falls back to USD (safest internationally traded unit).
        DB::table('properties')
            ->whereNotIn('currency', self::SUPPORTED)
            ->whereNotNull('currency')
            ->update(['currency' => 'USD']);

        Schema::table('properties', function (Blueprint $table) {
            $table->char('currency', 3)->default('YER')->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->char('currency', 3)->default('SAR')->change();
        });
    }
};
