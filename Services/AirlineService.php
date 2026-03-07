<?php

namespace Modules\SkyOps\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Airline;
use App\Models\Pirep;
use App\Models\Enums\PirepState;
use Modules\SkyOps\Helpers\UnitHelper;
use Modules\SkyOps\Helpers\SkyOpsHelper;

class AirlineService
{
    /**
     * Get airline overview data.
     * CRITICAL FIX: Original was ~1500 queries (5 per airline x N airlines).
     * Now: 3 aggregated queries + 1 financial query + 1 aircraft count query.
     */
    public function getOverview(): array
    {
        $ttl = config('skyops.cache_ttl.airline_overview', 10);

        return Cache::remember('skyops.airline_overview', $ttl * 60, function () {
            return $this->buildOverview();
        });
    }

    protected function buildOverview(): array
    {
        $hasLR   = Schema::hasColumn('pireps', 'landing_rate');
        $hasDist = Schema::hasColumn('pireps', 'distance');
        $currency = UnitHelper::currencySymbol();

        $airlines = Airline::orderBy('icao')->limit(1000)->get();

        // Single aggregated PIREP query (replaces N+1)
        $selectCols = [
            'airline_id',
            DB::raw('COUNT(*) as flights_total'),
            DB::raw('SUM(flight_time) as minutes_total'),
            DB::raw('MAX(created_at) as last_flight'),
        ];
        if ($hasDist) {
            $selectCols[] = DB::raw('SUM(distance) as distance_total');
        }
        if ($hasLR) {
            $selectCols[] = DB::raw('AVG(CASE WHEN landing_rate IS NOT NULL THEN landing_rate END) as avg_lr');
        }

        $pirepStats = Pirep::query()
            ->select($selectCols)
            ->where('state', PirepState::ACCEPTED)
            ->groupBy('airline_id')
            ->get()
            ->keyBy('airline_id');

        // Aircraft count per airline (via subfleets)
        $aircraftCounts = DB::table('aircraft as a')
            ->join('subfleets as s', 'a.subfleet_id', '=', 's.id')
            ->whereNull('a.deleted_at')
            ->whereNull('s.deleted_at')
            ->select('s.airline_id', DB::raw('COUNT(*) as ac_count'))
            ->groupBy('s.airline_id')
            ->pluck('ac_count', 'airline_id');

        // Financial data: single query with SUM
        $financials = collect();
        try {
            $pfx = DB::getTablePrefix();
            $financials = DB::table('journal_transactions as jt')
                ->join('journals as j', 'jt.journal_id', '=', 'j.id')
                ->where('j.morphed_type', 'like', '%Airline%')
                ->select(
                    'j.morphed_id as airline_id',
                    DB::raw("SUM(CASE WHEN {$pfx}jt.credit > 0 THEN {$pfx}jt.credit ELSE 0 END) as total_credit"),
                    DB::raw("SUM(CASE WHEN {$pfx}jt.debit > 0 THEN {$pfx}jt.debit ELSE 0 END) as total_debit")
                )
                ->groupBy('j.morphed_id')
                ->get()
                ->keyBy('airline_id');
        } catch (\Throwable $e) {
            \Log::warning('[SkyOps] Financials query failed: ' . $e->getMessage());
        }

        $now = Carbon::now();

        $rows = $airlines->map(function ($airline) use ($pirepStats, $aircraftCounts, $financials, $hasLR, $now) {
            $stats = $pirepStats->get($airline->id);
            $fin   = $financials->get($airline->id);

            $flights  = (int) ($stats->flights_total ?? 0);
            $minutes  = (int) ($stats->minutes_total ?? 0);
            $distance = (int) ($stats->distance_total ?? 0);
            $lastRaw  = $stats->last_flight ?? null;
            $lastDt   = $lastRaw ? Carbon::parse($lastRaw) : null;
            $daysSince = $lastDt ? $lastDt->diffInDays($now) : null;

            $avgLr = $hasLR && isset($stats->avg_lr) && $stats->avg_lr !== null
                ? (string) round($stats->avg_lr)
                : 'n/a';

            $revenue  = (float) (($fin->total_credit ?? 0) / 100);
            $expenses = (float) (($fin->total_debit ?? 0) / 100);
            $closing  = $revenue - $expenses;

            $health = $this->assessHealth($daysSince, $closing);

            [$cc, $countryName] = $this->normalizeCountry($airline->country ?? '');

            return [
                'icao'           => $airline->icao,
                'name'           => $airline->name,
                'country_name'   => $countryName,
                'country_code'   => $cc,
                'aircraft_total' => (int) ($aircraftCounts[$airline->id] ?? 0),
                'flights_total'  => $flights,
                'minutes_total'  => $minutes,
                'distance_total' => $distance,
                'hours_hhmm'     => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60),
                'avg_lr'         => $avgLr,
                'last_raw'       => $lastRaw ? Carbon::parse($lastRaw)->toDateString() : null,
                'last_fmt'       => $lastDt ? SkyOpsHelper::fmtDate($lastDt) : 'n/a',
                'health'         => $health,
                'revenue'        => $revenue,
                'expenses'       => $expenses,
                'closing'        => $closing,
            ];
        });

        // KPI aggregates
        $total       = $rows->count();
        $green       = $rows->where('health', 'Green')->count();
        $yellow      = $rows->where('health', 'Yellow')->count();
        $red         = $rows->where('health', 'Red')->count();
        $totalFlights = $rows->sum('flights_total');
        $totalAC     = $rows->sum('aircraft_total');

        return [
            'rows'         => $rows,
            'total'        => $total,
            'green'        => $green,
            'yellow'       => $yellow,
            'red'          => $red,
            'totalFlights' => $totalFlights,
            'totalAC'      => $totalAC,
            'currency'     => $currency,
            'hasLR'        => $hasLR,
        ];
    }

    /**
     * Assess airline health based on config mode.
     *
     * @param int|null $daysSince  Days since last accepted PIREP (null = no flights)
     * @param float    $closing    Closing balance (revenue - expenses)
     */
    protected function assessHealth(?int $daysSince, float $closing): string
    {
        $cfg  = config('skyops.airline_health', []);
        $mode = $cfg['mode'] ?? 'activity';

        $activity  = $this->healthByActivity($daysSince, $cfg);
        $financial = $this->healthByFinancial($closing, $cfg);

        return match ($mode) {
            'financial' => $financial,
            'combined'  => $this->worstOf($activity, $financial),
            default     => $activity,      // 'activity'
        };
    }

    /**
     * Health based on days since last PIREP.
     */
    protected function healthByActivity(?int $daysSince, array $cfg): string
    {
        if (is_null($daysSince)) {
            return $cfg['no_flight'] ?? 'Red';
        }

        $green  = (int) ($cfg['active_days']   ?? 30);
        $yellow = (int) ($cfg['inactive_days']  ?? 90);

        if ($daysSince <= $green) return 'Green';
        if ($daysSince <= $yellow) return 'Yellow';
        return 'Red';
    }

    /**
     * Health based on closing balance.
     */
    protected function healthByFinancial(float $closing, array $cfg): string
    {
        $greenThreshold  = (float) ($cfg['balance_green']  ?? 0);
        $yellowThreshold = (float) ($cfg['balance_yellow'] ?? -50000);

        if ($closing >= $greenThreshold) return 'Green';
        if ($closing >= $yellowThreshold) return 'Yellow';
        return 'Red';
    }

    /**
     * Return the worst status of two assessments.
     * Green < Yellow < Red
     */
    protected function worstOf(string $a, string $b): string
    {
        $rank = ['Green' => 1, 'Yellow' => 2, 'Red' => 3];
        $ra = $rank[$a] ?? 3;
        $rb = $rank[$b] ?? 3;
        return $ra >= $rb ? $a : $b;
    }

    /**
     * Normalize country to [code, name].
     */
    protected function normalizeCountry(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [null, null];

        if (strlen($raw) === 2 && ctype_alpha($raw)) {
            $cc = strtolower($raw);
            $name = strtoupper($raw);
            if (function_exists('locale_get_display_region')) {
                $locale = app()->getLocale() ?? 'en';
                $n = locale_get_display_region('-' . strtoupper($raw), $locale);
                if (!empty($n)) $name = $n;
            }
            return [$cc, $name];
        }

        return [null, $raw];
    }
}
