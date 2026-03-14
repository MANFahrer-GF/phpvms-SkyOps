<?php

namespace Modules\SkyOps\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Pirep;
use App\Models\User;
use App\Models\Airline;
use Modules\SkyOps\Helpers\PilotNameHelper;
use Modules\SkyOps\Helpers\UnitHelper;

class PilotStatsService
{
    /**
     * Cross-DB date expressions for period grouping.
     * Supports MySQL/MariaDB, PostgreSQL, and SQLite.
     */
    protected function periodExpr(string $type): string
    {
        $driver = DB::getDriverName();

        return match ($type) {
            'month' => match ($driver) {
                'pgsql'  => "to_char(created_at, 'YYYY-MM')",
                'sqlite' => "strftime('%Y-%m', created_at)",
                default  => "DATE_FORMAT(created_at,'%Y-%m')",
            },
            'quarter' => match ($driver) {
                'pgsql'  => "to_char(created_at, 'YYYY') || '-Q' || EXTRACT(QUARTER FROM created_at)::int",
                'sqlite' => "strftime('%Y', created_at) || '-Q' || ((cast(strftime('%m', created_at) as integer) - 1) / 3 + 1)",
                default  => "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))",
            },
            'year' => match ($driver) {
                'pgsql'  => "EXTRACT(YEAR FROM created_at)::int",
                'sqlite' => "cast(strftime('%Y', created_at) as integer)",
                default  => "YEAR(created_at)",
            },
            default => throw new \InvalidArgumentException("Unknown period type: {$type}"),
        };
    }
    protected Carbon $epoch;

    public function __construct()
    {
        $epoch = config('skyops.epoch');
        if ($epoch) {
            $this->epoch = Carbon::parse($epoch)->startOfDay();
        } else {
            // Fallback to phpVMS start_date setting, then 2000-01-01
            $startDate = setting('general.start_date');
            $this->epoch = $startDate
                ? Carbon::parse($startDate)->startOfDay()
                : Carbon::parse('2000-01-01')->startOfDay();
        }
    }

    /**
     * Build the complete statistics payload consumed by the JS frontend.
     * Returns the same structure as window.PILOT_STATS.
     */
    public function getStatistics(): array
    {
        $ttl = config('skyops.cache_ttl.pilot_stats', 5);

        return Cache::remember('skyops.pilot_stats', $ttl * 60, function () {
            return $this->buildPayload();
        });
    }

    protected function buildPayload(): array
    {
        // Translation label for "All Time"
        $labelAll = __('skyops::skyops.stats_all');

        // Detect available columns
        $hasLR     = Schema::hasColumn('pireps', 'landing_rate');
        $hasScore  = Schema::hasColumn('pireps', 'score');
        $hasDist   = Schema::hasColumn('pireps', 'distance');
        $hasAirCol = Schema::hasColumn('pireps', 'airline_id');

        $has = ['dist' => $hasDist, 'score' => $hasScore, 'lr' => $hasLR, 'airmix' => $hasAirCol];

        // Build period arrays
        $now = Carbon::now();
        $ym  = fn(Carbon $c) => $c->format('Y-m');
        $qstr = fn(Carbon $c) => $c->format('Y') . '-Q' . ceil($c->month / 3);

        $periodMonths = [];
        for ($c = $this->epoch->copy()->startOfMonth(); $c->lte($now); $c->addMonth()) {
            $periodMonths[] = $ym($c);
        }

        $periodQuarters = [];
        for ($c = $this->epoch->copy()->startOfQuarter(); $c->lte($now); $c->addQuarter()) {
            $periodQuarters[] = $qstr($c);
        }

        $periodYears = [];
        for ($c = $this->epoch->copy()->startOfYear(); $c->lte($now); $c->addYear()) {
            $periodYears[] = $c->year;
        }

        // Base query (state=2 = completed, after epoch)
        $qBase = Pirep::query();
        if (Schema::hasColumn('pireps', 'state')) {
            $qBase->where('state', 2);
        }
        $qBase->where('created_at', '>=', $this->epoch->toDateTimeString());

        // Common SELECT columns
        $selectCommon = [
            'user_id',
            DB::raw('COUNT(*) AS flights'),
            DB::raw('SUM(flight_time) AS minutes_total'),
        ];
        if ($hasDist)  $selectCommon[] = DB::raw('SUM(distance) AS nm_total');
        if ($hasScore) $selectCommon[] = DB::raw('AVG(score) AS score_avg');
        if ($hasLR) {
            $selectCommon[] = DB::raw('AVG(landing_rate) AS lr_avg');
            $selectCommon[] = DB::raw('MIN(CASE WHEN landing_rate < 0 THEN landing_rate END) AS lr_hardest');
            $selectCommon[] = DB::raw('MAX(CASE WHEN landing_rate < 0 THEN landing_rate END) AS lr_softest');
        }

        // Aggregate per period per user
        $rowsMonth = (clone $qBase)->select(array_merge(
            [DB::raw($this->periodExpr('month') . " AS period")],
            $selectCommon
        ))->groupBy('period', 'user_id')->get();

        $rowsQuarter = (clone $qBase)->select(array_merge(
            [DB::raw($this->periodExpr('quarter') . " AS period")],
            $selectCommon
        ))->groupBy('period', 'user_id')->get();

        $rowsYear = (clone $qBase)->select(array_merge(
            [DB::raw($this->periodExpr('year') . " AS period")],
            $selectCommon
        ))->groupBy('period', 'user_id')->get();

        $rowsAll = (clone $qBase)->select($selectCommon)->groupBy('user_id')
            ->get()
            ->map(fn($r) => tap($r, fn($x) => $x->period = $labelAll));

        // Airline data
        $airlines = $hasAirCol ? Airline::query()->get()->keyBy('id') : collect();
        $airIdsUsed = collect();

        $mixMonth = $mixQuarter = $mixYear = $mixAll = [];

        if ($hasAirCol) {
            $makeMix = function (string $groupExpr) use ($qBase, &$airIdsUsed) {
                $tmp = (clone $qBase)->select([
                    DB::raw($groupExpr . ' AS period'),
                    'user_id', 'airline_id',
                    DB::raw('SUM(flight_time) AS minutes_air'),
                    DB::raw('COUNT(*) AS flights_air'),
                ])->groupBy('period', 'user_id', 'airline_id')->get();

                $out = [];
                foreach ($tmp as $r) {
                    $p = (string) $r->period;
                    $out[$p][$r->user_id][$r->airline_id] = [
                        'minutes' => (int) $r->minutes_air,
                        'flights' => (int) $r->flights_air,
                    ];
                    $airIdsUsed->push($r->airline_id);
                }
                return $out;
            };

            $mixMonth   = $makeMix($this->periodExpr('month'));
            $mixQuarter = $makeMix($this->periodExpr('quarter'));
            $mixYear    = $makeMix($this->periodExpr('year'));

            $tmp = (clone $qBase)->select([
                'user_id', 'airline_id',
                DB::raw('SUM(flight_time) AS minutes_air'),
                DB::raw('COUNT(*) AS flights_air'),
            ])->groupBy('user_id', 'airline_id')->get();

            foreach ($tmp as $r) {
                $mixAll[$labelAll][$r->user_id][$r->airline_id] = [
                    'minutes' => (int) $r->minutes_air,
                    'flights' => (int) $r->flights_air,
                ];
                $airIdsUsed->push($r->airline_id);
            }
        }

        $airIdsUsed = $airIdsUsed->unique()->values();

        // User names
        $userIds = $rowsMonth->pluck('user_id')
            ->merge($rowsQuarter->pluck('user_id'))
            ->merge($rowsYear->pluck('user_id'))
            ->merge($rowsAll->pluck('user_id'))
            ->unique()->values();
        $users = User::with('airline')->whereIn('id', $userIds)->get(['id', 'name', 'callsign', 'pilot_id', 'airline_id'])->keyBy('id');
        $names = $users->mapWithKeys(fn($u) => [
            $u->id => PilotNameHelper::formatUser($u),
        ]);

        // Airline info helper
        $airlineInfoOf = function ($air) {
            if (!$air) return ['id' => 0, 'code' => null, 'name' => null, 'label' => 'Unbekannt'];
            $code = $air->iata ?: ($air->icao ?: ($air->code ?? null));
            $name = $air->name ?: ($code ?: ('#' . $air->id));
            return ['id' => $air->id, 'code' => $code, 'name' => $name, 'label' => $name];
        };

        // Format helper
        $fmtHM = function ($mins) {
            $mins = (int) max(0, $mins ?? 0);
            return sprintf('%02dh %02dm', intdiv($mins, 60), $mins % 60);
        };

        // Build period data
        $build = function ($rows, $periodList, $mixSource) use (
            $names, $fmtHM, $hasDist, $hasScore, $hasLR, $hasAirCol, $airlines, $airlineInfoOf
        ) {
            $byPeriod = [];
            foreach ($periodList as $p) {
                $byPeriod[$p] = [];
            }

            foreach ($rows as $r) {
                $p = (string) $r->period;
                if (!array_key_exists($p, $byPeriod)) continue;

                $flights = (int) $r->flights;
                $minutes = (int) $r->minutes_total;
                $nm = $hasDist ? (int) round($r->nm_total ?? 0) : null;

                $air_full = [];
                $air_mix = [];

                if ($hasAirCol && isset($mixSource[$p][$r->user_id])) {
                    $minsByAir = $mixSource[$p][$r->user_id];
                    uasort($minsByAir, fn($a, $b) => ($b['minutes'] <=> $a['minutes']));
                    $total = max(1, array_sum(array_column($minsByAir, 'minutes')));

                    foreach ($minsByAir as $aid => $vals) {
                        $info = $airlineInfoOf($airlines->get($aid));
                        $air_full[] = [
                            'id'      => (int) $aid,
                            'code'    => $info['code'],
                            'name'    => $info['name'],
                            'label'   => $info['label'],
                            'minutes' => (int) $vals['minutes'],
                            'flights' => (int) $vals['flights'],
                            'pct'     => round(($vals['minutes'] / $total) * 100, 1),
                        ];
                    }

                    $top5 = array_slice($air_full, 0, 5);
                    if (count($air_full) > 5) {
                        $restM = array_sum(array_column(array_slice($air_full, 5), 'minutes'));
                        $air_mix = $top5;
                        $air_mix[] = [
                            'id' => 0, 'code' => null, 'name' => 'Sonstige', 'label' => 'Sonstige',
                            'minutes' => $restM, 'flights' => null,
                            'pct' => round(($restM / $total) * 100, 1),
                        ];
                    } else {
                        $air_mix = $top5;
                    }
                }

                $byPeriod[$p][] = [
                    'pilot'      => $names[$r->user_id] ?? ('#' . $r->user_id),
                    'user_id'    => (int) $r->user_id,
                    'flights'    => $flights,
                    'minutes'    => $minutes,
                    'nm'         => $nm,
                    'score'      => $hasScore ? round((float) $r->score_avg, 1) : null,
                    'lr'         => $hasLR ? round((float) $r->lr_avg, 1) : null,
                    'lr_softest' => $hasLR ? ($r->lr_softest !== null ? round((float) $r->lr_softest, 1) : null) : null,
                    'lr_hardest' => $hasLR ? ($r->lr_hardest !== null ? round((float) $r->lr_hardest, 1) : null) : null,
                    'mins_avg'   => $flights > 0 ? (int) round($minutes / $flights) : 0,
                    'nm_avg'     => ($hasDist && $flights > 0) ? (int) round($nm / $flights) : null,
                    'air_full'   => $air_full,
                    'air_mix'    => $air_mix,
                ];
            }

            $leader = [];
            $summary = [];

            foreach ($byPeriod as $period => $rowsP) {
                usort($rowsP, function ($a, $b) {
                    return ($b['flights'] <=> $a['flights'])
                        ?: ($b['minutes'] <=> $a['minutes'])
                        ?: ((int) ($b['nm'] ?? 0) <=> (int) ($a['nm'] ?? 0));
                });
                $leader[$period] = array_values($rowsP);

                $summary[$period] = [
                    'flights'   => array_sum(array_column($rowsP, 'flights')),
                    'minutes'   => array_sum(array_column($rowsP, 'minutes')),
                    'nm'        => $hasDist ? array_sum(array_column($rowsP, 'nm')) : null,
                    'lr'        => $hasLR ? (count($rowsP) ? round(array_sum(array_column($rowsP, 'lr')) / max(1, count($rowsP)), 1) : null) : null,
                    'score'     => $hasScore ? (count($rowsP) ? round(array_sum(array_column($rowsP, 'score')) / max(1, count($rowsP)), 1) : null) : null,
                    'humantime' => $fmtHM(array_sum(array_column($rowsP, 'minutes'))),
                ];
            }

            return [
                'leader'  => $leader,
                'summary' => $summary,
                'periods' => array_values($periodList),
            ];
        };

        // Airline filter options
        $airlineOptions = [];
        foreach ($airIdsUsed as $aid) {
            $a = $airlines->get($aid);
            if ($a) {
                $info = $airlineInfoOf($a);
                $airlineOptions[] = ['id' => $info['id'], 'code' => $info['code'], 'label' => $info['label']];
            }
        }
        usort($airlineOptions, fn($a, $b) => strnatcasecmp($a['label'], $b['label']));

        return [
            'month'           => $build($rowsMonth,   $periodMonths,   $mixMonth),
            'quarter'         => $build($rowsQuarter, $periodQuarters, $mixQuarter),
            'year'            => $build($rowsYear,    $periodYears,    $mixYear),
            'all'             => $build($rowsAll,     [$labelAll],     $mixAll),
            'has'             => $has,
            'epoch'           => $this->epoch->toDateString(),
            'airline_options' => $airlineOptions,
            'distLabel'       => UnitHelper::label('distance'),
        ];
    }
}
