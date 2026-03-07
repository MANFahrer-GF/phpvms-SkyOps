<?php

namespace Modules\SkyOps\Services;

use Carbon\Carbon;
use App\Models\Flight;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Aircraft;
use App\Models\Subfleet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\SkyOps\Helpers\UnitHelper; // Only used as fallback for raw numeric values

class FlightBoardService
{
    /**
     * Build the flight board data.
     * Replicates the original query logic: dedup by airline_id+flight_number+dpt_time,
     * flight-time range filter, type filter, with configurable sort order.
     */
    public function getFlightBoard(array $filters): array
    {
        $now  = Carbon::now('UTC');
        $from = $now->format('H:i:s');
        $cfg  = config('skyops.departures', []);

        $fltAirline = strtoupper(trim($filters['airline'] ?? ''));
        $fltDep     = strtoupper(trim($filters['dep'] ?? ''));
        $fltArr     = strtoupper(trim($filters['arr'] ?? ''));
        $fltType    = strtoupper(trim($filters['type'] ?? ''));
        $minFtM     = $this->toMinutes($filters['min_ft_h'] ?? null);
        $maxFtM     = $this->toMinutes($filters['max_ft_h'] ?? null);

        // Auto-detect: what percentage of active flights have dpt_time/arr_time?
        $timeStats = $this->detectTimeColumns();

        // Resolve sort mode
        $sortMode = $cfg['sort_mode'] ?? 'auto';
        if ($sortMode === 'auto') {
            $sortMode = $timeStats['has_dpt_time'] ? 'time' : 'flight_nr';
        }

        // Resolve column visibility
        $showDptTime = $this->resolveAutoFlag($cfg['show_dpt_time'] ?? 'auto', $timeStats['has_dpt_time']);
        $showArrTime = $this->resolveAutoFlag($cfg['show_arr_time'] ?? 'auto', $timeStats['has_arr_time']);
        $showDistance   = (bool) ($cfg['show_distance'] ?? true);
        $showFlightTime = (bool) ($cfg['show_flight_time'] ?? true);

        // Resolve aircraft type source early (needed for eager loading decision)
        $typeSource = $cfg['aircraft_type_source'] ?? 'flight_icao';

        // Dedup subquery: one flight per airline+number+dpt_time
        $idsSub = Flight::query()
            ->selectRaw('MIN(id) as id')
            ->where('active', 1)
            ->where('visible', 1)
            ->when($fltDep, fn($q) => $q->where('dpt_airport_id', $fltDep))
            ->when($fltArr, fn($q) => $q->where('arr_airport_id', $fltArr))
            ->when($fltAirline, function ($q) use ($fltAirline) {
                $q->whereHas('airline', fn($qa) => $qa->where('icao', $fltAirline)->orWhere('iata', $fltAirline));
            })
            ->when($fltType === 'PAX', fn($q) => $q->whereIn('flight_type', ['P', 'J']))
            ->when($fltType === 'CARGO', fn($q) => $q->where('flight_type', 'C'))
            ->when(!is_null($minFtM) || !is_null($maxFtM), function ($q) use ($minFtM, $maxFtM) {
                $q->whereNotNull('flight_time');
                $mn = $minFtM;
                $mx = $maxFtM;
                if (!is_null($mn) && is_null($mx)) {
                    $q->where('flight_time', '>=', $mn);
                } elseif (is_null($mn) && !is_null($mx)) {
                    $q->where('flight_time', '<=', $mx);
                } else {
                    if ($mx < $mn) { [$mn, $mx] = [$mx, $mn]; }
                    $q->whereBetween('flight_time', [$mn, $mx]);
                }
            })
            ->groupBy('airline_id', 'flight_number', 'dpt_time');

        $flights = Flight::query()
            ->select([
                'id', 'airline_id', 'flight_number', 'route_code', 'route_leg',
                'dpt_airport_id', 'arr_airport_id', 'dpt_time', 'arr_time',
                'flight_type', 'active', 'distance', 'flight_time',
            ])
            ->whereIn('id', $idsSub)
            ->with(match($typeSource) {
                'flight_icao', 'aircraft_icao' => ['airline:id,icao,iata,name,logo'],
                default => ['airline:id,icao,iata,name,logo', 'airline.subfleets'],
            });

        // Apply configurable sort order
        switch ($sortMode) {
            case 'time':
                $flights->orderByRaw("CASE WHEN dpt_time IS NOT NULL AND dpt_time >= ? THEN 0 WHEN dpt_time IS NOT NULL THEN 1 ELSE 2 END", [$from])
                        ->orderBy('dpt_time')
                        ->orderBy('flight_number');
                break;
            case 'route':
                $flights->orderBy('dpt_airport_id')
                        ->orderBy('arr_airport_id')
                        ->orderBy('flight_number');
                break;
            case 'distance':
                $flights->orderByRaw('COALESCE(distance, 999999)')
                        ->orderBy('flight_number');
                break;
            default: // 'flight_nr'
                $pfx = DB::getTablePrefix();
                $flights->orderByRaw("(SELECT icao FROM {$pfx}airlines WHERE {$pfx}airlines.id = {$pfx}flights.airline_id LIMIT 1)")
                        ->orderBy('flight_number');
                break;
        }

        $flights = $flights->simplePaginate(100)->withQueryString();

        // Batch-load airport names
        $airportIds = collect([
            $flights->pluck('dpt_airport_id'),
            $flights->pluck('arr_airport_id'),
        ])->flatten()->unique()->values();
        $airportsById = Airport::whereIn('id', $airportIds)
            ->select(['id', 'name', 'country'])
            ->get()
            ->keyBy('id');

        // Enrich with aircraft types (config-driven)
        $activeOnly = $cfg['aircraft_active_only'] ?? true;
        $_debugPivot = 'n/a';
        $_debugRelation = 'n/a';
        $_debugAssigned = 0;
        $acByKey = [];

        if ($typeSource === 'flight_icao') {
            // SIMPLE: One JOIN query across flights → pivot → aircraft.
            // Groups by airline_id + flight_number so dedup ID mismatch doesn't matter.
            $pivotTable = null;
            $pivotFlightKey = 'flight_id';
            $pivotSubfleetKey = 'subfleet_id';
            $flightModel = new Flight;
            foreach (['subfleets', 'subfleet'] as $tryName) {
                if (method_exists($flightModel, $tryName)) {
                    $_debugRelation = $tryName;
                    try {
                        $rel = $flightModel->{$tryName}();
                        $pivotTable       = $rel->getTable();
                        $pivotFlightKey   = $rel->getForeignPivotKeyName();
                        $pivotSubfleetKey = $rel->getRelatedPivotKeyName();
                        $_debugPivot = "{$pivotTable} (fk={$pivotFlightKey}, rk={$pivotSubfleetKey})";
                    } catch (\Throwable $e) {
                        $_debugPivot = 'ERROR: ' . $e->getMessage();
                    }
                    break;
                }
            }

            $acByKey = [];      // "airline_id|flight_number" => [icao, ...]
            $acByAirline = [];  // airline_id => [icao, ...]
            $keysWithSf = [];

            if ($pivotTable) {
                $ft = $flightModel->getTable();
                $ac = (new Aircraft)->getTable();

                // ONE query: flights JOIN pivot JOIN aircraft → grouped by airline+flightnr
                $q = DB::table($ft)
                    ->join($pivotTable, "{$ft}.id", '=', "{$pivotTable}.{$pivotFlightKey}")
                    ->join($ac, "{$ac}.subfleet_id", '=', "{$pivotTable}.{$pivotSubfleetKey}")
                    ->where("{$ft}.active", 1)
                    ->whereNull("{$ft}.deleted_at")
                    ->whereNull("{$ac}.deleted_at")
                    ->whereNotNull("{$ac}.icao")
                    ->where("{$ac}.icao", '!=', '');
                if ($activeOnly) {
                    $q->where("{$ac}.status", 'A');
                }
                $rows = $q->select("{$ft}.airline_id", "{$ft}.flight_number", "{$ac}.icao")
                    ->distinct()->get();

                foreach ($rows as $r) {
                    $key = $r->airline_id . '|' . $r->flight_number;
                    $acByKey[$key][] = $r->icao;
                    $keysWithSf[$key] = true;
                }
                foreach ($acByKey as &$types) {
                    $types = collect($types)->unique()->sort()->values();
                }
                unset($types);
                $_debugAssigned = count($keysWithSf);
            }

            // Airline-wide fallback
            $airlineIds = $flights->getCollection()->pluck('airline_id')->unique()->filter()->toArray();
            if (!empty($airlineIds)) {
                $ac = (new Aircraft)->getTable();
                $sf = (new Subfleet)->getTable();
                $q = DB::table($ac)
                    ->join($sf, "{$ac}.subfleet_id", '=', "{$sf}.id")
                    ->whereIn("{$sf}.airline_id", $airlineIds)
                    ->whereNull("{$ac}.deleted_at")
                    ->whereNull("{$sf}.deleted_at")
                    ->whereNotNull("{$ac}.icao")
                    ->where("{$ac}.icao", '!=', '');
                if ($activeOnly) {
                    $q->where("{$ac}.status", 'A');
                }
                $rows = $q->select("{$sf}.airline_id", "{$ac}.icao")->distinct()->get();
                foreach ($rows as $r) {
                    $acByAirline[$r->airline_id][] = $r->icao;
                }
                foreach ($acByAirline as &$types) {
                    $types = collect($types)->unique()->sort()->values();
                }
                unset($types);
            }

            // Assign — match by airline_id + flight_number
            $flights->getCollection()->transform(function ($flight) use ($acByKey, $acByAirline, $keysWithSf) {
                if ($flight->airline) {
                    $key = $flight->airline_id . '|' . $flight->flight_number;
                    if (isset($keysWithSf[$key])) {
                        $flight->airline->aircraft_types = collect($acByKey[$key] ?? []);
                    } else {
                        $flight->airline->aircraft_types = collect($acByAirline[$flight->airline_id] ?? []);
                    }
                }
                return $flight;
            });
        } elseif ($typeSource === 'aircraft_icao') {
            // Query actual ICAO codes from aircraft table — all types per airline
            $airlineIds = $flights->getCollection()->pluck('airline_id')->unique()->filter()->toArray();
            $acTypes = [];
            if (!empty($airlineIds)) {
                $acTable = (new Aircraft)->getTable();
                $sfTable = (new Subfleet)->getTable();

                $q = DB::table($acTable)
                    ->join($sfTable, "{$acTable}.subfleet_id", '=', "{$sfTable}.id")
                    ->whereIn("{$sfTable}.airline_id", $airlineIds)
                    ->whereNull("{$acTable}.deleted_at")
                    ->whereNull("{$sfTable}.deleted_at")
                    ->whereNotNull("{$acTable}.icao")
                    ->where("{$acTable}.icao", '!=', '');
                if ($activeOnly) {
                    $q->where("{$acTable}.status", 'A');
                }
                $rows = $q->select("{$sfTable}.airline_id", "{$acTable}.icao")
                    ->distinct()
                    ->get();
                foreach ($rows as $r) {
                    $acTypes[$r->airline_id][] = $r->icao;
                }
                foreach ($acTypes as &$types) {
                    $types = collect($types)->unique()->sort()->values();
                }
                unset($types);
            }
            $flights->getCollection()->transform(function ($flight) use ($acTypes) {
                if ($flight->airline) {
                    $flight->airline->aircraft_types = collect($acTypes[$flight->airline_id] ?? []);
                }
                return $flight;
            });
        } else {
            // Subfleet-based modes (subfleets already eager-loaded via with())
            $segment = null;
            if (str_starts_with($typeSource, 'subfleet_segment:')) {
                $segment = (int) substr($typeSource, strlen('subfleet_segment:'));
            }
            $flights->getCollection()->transform(function ($flight) use ($typeSource, $segment) {
                if (!$flight->airline || !$flight->airline->subfleets) {
                    if ($flight->airline) {
                        $flight->airline->aircraft_types = collect();
                    }
                    return $flight;
                }
                $types = $flight->airline->subfleets->pluck('type')->filter();
                if ($segment !== null) {
                    $types = $types->map(fn($t) => explode('-', $t)[$segment] ?? null)->filter();
                }
                $flight->airline->aircraft_types = $types->unique()->sort()->values();
                return $flight;
            });
        }

        // Filter options for datalists
        $airlineOptions = Airline::query()
            ->select(['id', 'icao', 'iata', 'name'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $airportOptions = Airport::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->limit(config('skyops.airport_options_limit', 3000))
            ->get();

        // Route detection
        $hasShow     = Route::has('frontend.flights.show');
        $airlineShow = Route::has('frontend.airlines.show');

        // Active filter values for slider JS
        $activeMin = $filters['min_ft_h'] ?? null;
        $activeMax = $filters['max_ft_h'] ?? null;
        $minHVal   = is_numeric($activeMin) ? (float) $activeMin : 0;
        $maxHVal   = is_numeric($activeMax) ? (float) $activeMax : '';

        return [
            'flights'        => $flights,
            'airportsById'   => $airportsById,
            'airlineOptions' => $airlineOptions,
            'airportOptions' => $airportOptions,
            'hasShow'        => $hasShow,
            'airlineShow'    => $airlineShow,
            '_debug_type_source' => $typeSource,
            '_debug_pivot_table' => $_debugPivot,
            '_debug_relation'    => $_debugRelation,
            '_debug_assigned'    => $_debugAssigned,
            '_debug_sample'      => $typeSource === 'flight_icao'
                ? implode(' | ', array_map(
                    fn($k, $v) => $k . '=[' . (is_object($v) ? $v->implode(',') : implode(',', (array)$v)) . ']',
                    array_keys(array_slice($acByKey ?? [], 0, 3, true)),
                    array_values(array_slice($acByKey ?? [], 0, 3, true))
                )) : 'n/a',
            'fltAirline'     => $fltAirline,
            'fltDep'         => $fltDep,
            'fltArr'         => $fltArr,
            'fltType'        => $fltType,
            'minHVal'        => $minHVal,
            'maxHVal'        => $maxHVal,
            'activeMin'      => $activeMin,
            'activeMax'      => $activeMax,
            'showDptTime'    => $showDptTime,
            'showArrTime'    => $showArrTime,
            'showDistance'    => $showDistance,
            'showFlightTime' => $showFlightTime,
            'sortMode'       => $sortMode,
        ];
    }

    /**
     * Convert hours to minutes for flight_time filter.
     */
    protected function toMinutes($h): ?int
    {
        if ($h === null || $h === '') return null;
        $h = (float) $h;
        if (!is_finite($h)) return null;
        $h = max(0, min(24, $h));
        $h = round($h * 2) / 2;
        return (int) round($h * 60);
    }

    /**
     * Detect what percentage of active flights have time columns populated.
     * Cached for the request lifetime to avoid repeated queries.
     */
    protected function detectTimeColumns(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $total = Flight::where('active', true)->where('visible', true)->count();
        if ($total === 0) {
            return $cache = ['has_dpt_time' => false, 'has_arr_time' => false, 'pct_dpt' => 0, 'pct_arr' => 0];
        }

        $withDpt = Flight::where('active', true)->where('visible', true)->whereNotNull('dpt_time')->count();
        $withArr = Flight::where('active', true)->where('visible', true)->whereNotNull('arr_time')->count();

        $pctDpt = $total > 0 ? ($withDpt / $total) : 0;
        $pctArr = $total > 0 ? ($withArr / $total) : 0;

        return $cache = [
            'has_dpt_time' => $pctDpt > 0.10,  // >10% have departure times
            'has_arr_time' => $pctArr > 0.10,   // >10% have arrival times
            'pct_dpt'      => round($pctDpt * 100),
            'pct_arr'      => round($pctArr * 100),
        ];
    }

    /**
     * Resolve 'auto', true, or false config flag.
     */
    protected function resolveAutoFlag($value, bool $autoResult): bool
    {
        if ($value === 'auto') return $autoResult;
        return (bool) $value;
    }

    /**
     * Format time string to H:i.
     */
    public static function fmtTime($timeStr): string
    {
        if (!$timeStr) return '—';
        $s = trim((string) $timeStr);
        if (preg_match('/^\d{4}$/', $s)) {
            $s = substr($s, 0, 2) . ':' . substr($s, 2, 2);
        }
        foreach (['H:i:s', 'H:i'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $s, 'UTC')->format('H:i'); } catch (\Throwable $e) {}
        }
        try { return Carbon::parse($s, 'UTC')->format('H:i'); } catch (\Throwable $e) { return $timeStr; }
    }

    /**
     * Format distance for display.
     *
     * Prefers phpVMS Eloquent unit casting (->local()) which automatically
     * respects the admin's unit settings. Falls back to UnitHelper only
     * for raw numeric values (shouldn't happen with Eloquent models).
     */
    public static function distStr($distance): ?string
    {
        if ($distance === null || $distance === '') return null;

        // Eloquent unit object (e.g. $flight->distance) — use native ->local()
        if (is_object($distance) && method_exists($distance, 'local')) {
            return $distance->local(0);
        }

        // Stringified object (shouldn't happen, but defensive)
        if (is_object($distance)) {
            $s = (string) $distance;
            return trim($s) !== '' ? $s : null;
        }

        // Raw numeric fallback (aggregated queries, etc.)
        if (is_numeric($distance)) {
            return UnitHelper::distance((float) $distance);
        }

        return null;
    }

    /**
     * Format flight time (minutes) to HH:MM.
     */
    public static function ftStr($mins): ?string
    {
        if (!is_numeric($mins)) return null;
        $mins = (int) $mins;
        return sprintf('%d:%02d', intdiv($mins, 60), $mins % 60);
    }
}
