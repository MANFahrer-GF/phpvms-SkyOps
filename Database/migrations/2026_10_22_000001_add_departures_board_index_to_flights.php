<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index for the departures board's de-duplication
 * (`MIN(id) … WHERE active AND visible AND deleted_at IS NULL
 *  GROUP BY airline_id, flight_number, dpt_time`).
 *
 * Measured on a copy of a production VA (190,460 flights): the de-duplication
 * drops from ~274 ms to ~105 ms, the unfiltered board query from ~509 ms to
 * ~431 ms. `deleted_at` MUST be part of the index — without it MariaDB ignores
 * the index and scans the whole table (measured: no gain at all).
 *
 * Adds an index only; no data is changed. Skipped if an index of that name
 * already exists. Built online (InnoDB secondary index, ~0.6 s on 190k rows).
 */
return new class extends Migration
{
    private const NAME = 'idx_flights_board_dedup';

    public function up(): void
    {
        if (!Schema::hasTable('flights') || $this->exists()) {
            return;
        }
        Schema::table('flights', function ($table) {
            $table->index(['active', 'visible', 'deleted_at', 'airline_id', 'flight_number', 'dpt_time'], self::NAME);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('flights') || !$this->exists()) {
            return;
        }
        Schema::table('flights', function ($table) {
            $table->dropIndex(self::NAME);
        });
    }

    private function exists(): bool
    {
        $table = DB::getTablePrefix() . 'flights';

        return DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [self::NAME]) !== [];
    }
};
