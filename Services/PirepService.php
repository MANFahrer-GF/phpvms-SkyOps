<?php

namespace Modules\SkyOps\Services;

use App\Models\Pirep;
use App\Models\Enums\PirepState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SkyOps\Helpers\SkyOpsHelper;

class PirepService
{
    protected int $perPage;

    /**
     * Relationships to eager-load for display.
     * Uses phpVMS 7 native Pirep model — no custom DB view needed.
     */
    protected array $eagerLoad = [
        'airline',
        'aircraft.subfleet',
        'user',
        'dpt_airport',
        'arr_airport',
        'field_values',
    ];

    public function __construct()
    {
        $this->perPage = config('skyops.per_page.pireps', 25);
    }

    /**
     * Active flights (in-progress PIREPs).
     * Status not ARR (arrived) or DX (cancelled).
     */
    public function getActiveFlights()
    {
        return Pirep::with($this->eagerLoad)
            ->whereNotIn('status', ['ARR', 'DX'])
            ->where('state', '!=', PirepState::ACCEPTED)
            ->orderByDesc('updated_at')
            ->limit(config('skyops.active_flights_limit', 100))
            ->get();
    }

    /**
     * Completed flights with filter, search, sort, pagination.
     * Fully Eloquent — unit casting (distance, fuel_used) works via ->local().
     */
    public function getCompletedFlights(array $f)
    {
        $from = SkyOpsHelper::safeParseDate($f['from'] ?? null, now()->subDays(30));
        $to   = SkyOpsHelper::safeParseDate($f['to'] ?? null, now());

        $q = Pirep::with($this->eagerLoad)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);

        // Full-text search across relationships
        if (!empty($f['q'])) {
            $s = $f['q'];
            $q->where(function ($w) use ($s) {
                $w->where(DB::raw("CONCAT((SELECT icao FROM " . DB::getTablePrefix() . "airlines WHERE id = airline_id), flight_number)"), 'like', "%{$s}%")
                  ->orWhereHas('dpt_airport', fn($a) => $a->where('icao', 'like', "%{$s}%"))
                  ->orWhereHas('arr_airport', fn($a) => $a->where('icao', 'like', "%{$s}%"))
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('aircraft', fn($a) => $a->where('registration', 'like', "%{$s}%"));
            });
        }

        // Source filter
        if (!empty($f['source'])) {
            $q->where('source_name', $f['source']);
        }

        // Network filter (via pirep_field_values)
        if (!empty($f['network'])) {
            $net = $f['network'];
            if (strtoupper($net) === 'OFFLINE') {
                // No network field value exists
                $q->whereDoesntHave('field_values', fn($fv) =>
                    $fv->where('slug', 'network-online')
                );
            } else {
                $q->whereHas('field_values', fn($fv) =>
                    $fv->where('slug', 'network-online')
                       ->where('value', $net)
                );
            }
        }

        // Sorting — relationship columns use subqueries
        $sortKey = $f['sort'] ?? 'datumzeit';
        $dir     = $f['dir'] ?? 'desc';

        $this->applySorting($q, $sortKey, $dir);

        return $q->paginate($this->perPage)->withQueryString();
    }

    /**
     * Apply sort to query — maps short sort keys to columns/subqueries.
     */
    protected function applySorting($q, string $key, string $dir): void
    {
        $pfx = DB::getTablePrefix();

        $directCols = [
            'datumzeit' => 'created_at',
            'air'       => 'flight_time',
            'landing'   => 'landing_rate',
            'fuel'      => 'fuel_used',
            'dist'      => 'distance',
            'review'    => 'state',
            'phase'     => 'status',
            'source'    => 'source_name',
        ];

        if (isset($directCols[$key])) {
            $q->orderBy($directCols[$key], $dir);
            return;
        }

        // Relationship-based sorting via subqueries (no JOINs needed)
        $subqueryMap = [
            'flight'  => "SELECT CONCAT(icao, {$pfx}pireps.flight_number) FROM {$pfx}airlines WHERE {$pfx}airlines.id = {$pfx}pireps.airline_id LIMIT 1",
            'dep'     => "SELECT icao FROM {$pfx}airports WHERE {$pfx}airports.id = {$pfx}pireps.dpt_airport_id LIMIT 1",
            'arr'     => "SELECT icao FROM {$pfx}airports WHERE {$pfx}airports.id = {$pfx}pireps.arr_airport_id LIMIT 1",
            'pilot'   => "SELECT name FROM {$pfx}users WHERE {$pfx}users.id = {$pfx}pireps.user_id LIMIT 1",
            'airline' => "SELECT icao FROM {$pfx}airlines WHERE {$pfx}airlines.id = {$pfx}pireps.airline_id LIMIT 1",
            'reg'     => "SELECT registration FROM {$pfx}aircraft WHERE {$pfx}aircraft.id = {$pfx}pireps.aircraft_id LIMIT 1",
        ];

        if (isset($subqueryMap[$key])) {
            $q->orderBy(DB::raw("({$subqueryMap[$key]})"), $dir);
            return;
        }

        // Block time — calculated, sort by block_off_time as proxy
        if ($key === 'block') {
            $q->orderBy('block_off_time', $dir);
            return;
        }

        // Network — sort by field value
        if ($key === 'network') {
            $q->orderBy(DB::raw("(SELECT value FROM {$pfx}pirep_field_values WHERE pirep_id = {$pfx}pireps.id AND slug = 'network-online' LIMIT 1)"), $dir);
            return;
        }

        // Fallback
        $q->orderByDesc('created_at');
    }

    /**
     * Get distinct filter options (sources, networks).
     */
    public function getFilterOptions(): array
    {
        $ttl = config('skyops.cache_ttl.filter_options', 15);

        return Cache::remember('skyops.filter_options', $ttl * 60, function () {
            $pfx = DB::getTablePrefix();

            return [
                'sources' => Pirep::select('source_name')
                    ->distinct()
                    ->whereNotNull('source_name')
                    ->where('state', PirepState::ACCEPTED)
                    ->pluck('source_name'),

                'networks' => DB::table('pirep_field_values')
                    ->where('slug', 'network-online')
                    ->select('value')
                    ->distinct()
                    ->pluck('value')
                    ->push('OFFLINE')
                    ->unique()
                    ->sort()
                    ->values(),
            ];
        });
    }
}
