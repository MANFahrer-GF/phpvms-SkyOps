<?php

namespace Modules\SkyOps\Services;

use Carbon\Carbon;
use App\Models\Flight;
use App\Models\Bid;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Aircraft;
use App\Models\Subfleet;
use App\Models\User;
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
    public function getFlightBoard(array $filters, ?User $user = null): array
    {
        $now  = Carbon::now('UTC');
        $from = $now->format('H:i:s');
        $cfg  = config('skyops.departures', []);
        $flightModel = new Flight();
        $flightTable = $flightModel->getTable();
        $bidTable = (new Bid())->getTable();
        $userId = (int) ($user?->id ?? 0);
        $currAirport = strtoupper(trim((string) ($user?->curr_airport_id ?? '')));

        $fltAirline = strtoupper(trim($filters['airline'] ?? ''));
        $fltDep     = strtoupper(trim($filters['dep'] ?? ''));
        $fltArr     = strtoupper(trim($filters['arr'] ?? ''));
        $fltType    = strtoupper(trim($filters['type'] ?? ''));
        $cargoTypes = $this->cargoFlightTypes();
        $paxTypes   = $this->paxFlightTypes($cargoTypes);
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

        // Optional compatibility mode with phpVMS operational settings.
        // Off by default to preserve current SkyOps behavior.
        $respectPhpvmsSettings = (bool) ($cfg['respect_phpvms_settings'] ?? false);
        $bookableOnly = $respectPhpvmsSettings && (bool) ($cfg['bookable_only'] ?? false);
        $showBookingStatus = $respectPhpvmsSettings && (bool) ($cfg['show_booking_status'] ?? true);
        $limitFromCurrent = $respectPhpvmsSettings && (bool) setting('pilots.only_flights_from_current', false);
        $bidLockEnabled = $respectPhpvmsSettings && (bool) setting('bids.disable_flight_on_bid', false);
        $restrictAircraftAtDeparture = $respectPhpvmsSettings && (bool) setting('pireps.only_aircraft_at_dpt_airport', false);
        $restrictBookedAircraft = $respectPhpvmsSettings && (bool) setting('bids.block_aircraft', false);

        // Dedup subquery: one flight per airline+number+dpt_time
        $idsSub = Flight::query()
            ->selectRaw('MIN(id) as id')
            ->where('active', 1)
            ->where('visible', 1)
            ->when($limitFromCurrent && $currAirport !== '', fn($q) => $q->where('dpt_airport_id', $currAirport))
            ->when($fltDep, fn($q) => $q->where('dpt_airport_id', $fltDep))
            ->when($fltArr, fn($q) => $q->where('arr_airport_id', $fltArr))
            ->when($fltAirline, function ($q) use ($fltAirline) {
                $q->whereHas('airline', fn($qa) => $qa->where('icao', $fltAirline)->orWhere('iata', $fltAirline));
            })
            ->when($fltType === 'PAX', fn($q) => $q->whereIn('flight_type', $paxTypes))
            ->when($fltType === 'CARGO', fn($q) => $q->whereIn('flight_type', $cargoTypes))
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

        if ($bookableOnly && $bidLockEnabled) {
            $flights->whereNotExists(function ($q) use ($bidTable, $flightTable, $userId) {
                $q->select(DB::raw(1))
                    ->from($bidTable)
                    ->whereColumn("{$bidTable}.flight_id", "{$flightTable}.id");
                if ($userId > 0) {
                    $q->where("{$bidTable}.user_id", '<>', $userId);
                }
            });
        }

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

        // Optional bid lock state per flight (to expose phpVMS bid constraints in the board).
        $blockedByBid = [];
        $ownBid = [];
        if ($showBookingStatus && $bidLockEnabled && $flights->getCollection()->isNotEmpty()) {
            $flightIds = $flights->getCollection()->pluck('id')->all();
            $bidRows = DB::table($bidTable)
                ->whereIn('flight_id', $flightIds)
                ->select('flight_id', 'user_id')
                ->get();
            foreach ($bidRows as $bidRow) {
                $fid = (string) $bidRow->flight_id;
                if ($userId > 0 && (int) $bidRow->user_id === $userId) {
                    $ownBid[$fid] = true;
                    continue;
                }
                $blockedByBid[$fid] = true;
            }
        }

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
            // PIVOT-FIRST approach (matches phpVMS FlightController + DH Basic pattern):
            // Step 1: pivot → subfleet → aircraft ICAO (what types exist per subfleet?)
            // Step 2: pivot → flights → airline_id + flight_number (which flight number has this subfleet?)
            // Step 3: combine by airline_id|flight_number → [icao, ...]
            //
            // NO filter on flights.active/visible/deleted — the pivot table is the source of truth.
            // phpVMS and DH both query flight_subfleet without any flights-table filter.

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

                // Step 1: Which subfleet_ids have which aircraft ICAO codes?
                $sfIcaoQuery = DB::table($ac)
                    ->whereNull("{$ac}.deleted_at")
                    ->whereNotNull("{$ac}.icao")
                    ->where("{$ac}.icao", '!=', '');
                if ($activeOnly) {
                    $sfIcaoQuery->where("{$ac}.status", 'A');
                }
                $sfIcaoRows = $sfIcaoQuery
                    ->select("{$ac}.subfleet_id", "{$ac}.icao")
                    ->distinct()->get();

                // Build map: subfleet_id → [icao, ...]
                $icaoBySubfleet = [];
                foreach ($sfIcaoRows as $r) {
                    $icaoBySubfleet[$r->subfleet_id][] = $r->icao;
                }

                if (!empty($icaoBySubfleet)) {
                    // Step 2: Which flight_ids are linked to which subfleet_ids?
                    $pivotRows = DB::table($pivotTable)
                        ->whereIn($pivotSubfleetKey, array_keys($icaoBySubfleet))
                        ->select($pivotFlightKey, $pivotSubfleetKey)
                        ->distinct()->get();

                    // Build map: flight_id → [subfleet_id, ...]
                    $sfByFlightId = [];
                    $allFlightIds = [];
                    foreach ($pivotRows as $r) {
                        $fid = (string) $r->{$pivotFlightKey};
                        $sfByFlightId[$fid][] = $r->{$pivotSubfleetKey};
                        $allFlightIds[$fid] = true;
                    }

                    if (!empty($allFlightIds)) {
                        // Step 3: Look up airline_id + flight_number for these flight_ids
                        // NO WHERE active/visible/deleted — just the lookup
                        $flightRows = DB::table($ft)
                            ->whereIn('id', array_keys($allFlightIds))
                            ->select('id', 'airline_id', 'flight_number')
                            ->get();

                        // Step 4: Combine → airline_id|flight_number → [icao codes]
                        foreach ($flightRows as $fr) {
                            $key = $fr->airline_id . '|' . $fr->flight_number;
                            $fid = (string) $fr->id;
                            if (isset($sfByFlightId[$fid])) {
                                foreach ($sfByFlightId[$fid] as $sfId) {
                                    if (isset($icaoBySubfleet[$sfId])) {
                                        foreach ($icaoBySubfleet[$sfId] as $icao) {
                                            $acByKey[$key][] = $icao;
                                        }
                                    }
                                }
                                $keysWithSf[$key] = true;
                            }
                        }

                        foreach ($acByKey as &$types) {
                            $types = collect($types)->unique()->sort()->values();
                        }
                        unset($types);
                    }
                }

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
                        $flight->aircraft_types = collect($acByKey[$key] ?? []);
                    } else {
                        $flight->aircraft_types = collect($acByAirline[$flight->airline_id] ?? []);
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
                    $flight->aircraft_types = collect($acTypes[$flight->airline_id] ?? []);
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
                        $flight->aircraft_types = collect();
                    }
                    return $flight;
                }
                $types = $flight->airline->subfleets->pluck('type')->filter();
                if ($segment !== null) {
                    $types = $types->map(fn($t) => explode('-', $t)[$segment] ?? null)->filter();
                }
                $flight->aircraft_types = $types->unique()->sort()->values();
                return $flight;
            });
        }

        // Optional aircraft-availability refinement for phpVMS compatibility mode.
        // This mirrors the departure-airport/block-aircraft constraints at a board level.
        if (
            $showBookingStatus
            && ($restrictAircraftAtDeparture || $restrictBookedAircraft)
            && in_array($typeSource, ['flight_icao', 'aircraft_icao'], true)
            && $flights->getCollection()->isNotEmpty()
        ) {
            $typeInfo = $this->resolveAircraftTypesByFlightAvailability(
                $flights->getCollection(),
                $activeOnly,
                $restrictAircraftAtDeparture,
                $restrictBookedAircraft,
                $userId
            );

            $typesByFlight = $typeInfo['types_by_flight'];
            $hasBookableAircraft = $typeInfo['has_bookable_aircraft'];

            $flights->getCollection()->transform(function ($flight) use ($typesByFlight, $hasBookableAircraft) {
                $fid = (string) $flight->id;
                if (array_key_exists($fid, $typesByFlight)) {
                    $flight->aircraft_types = collect($typesByFlight[$fid]);
                }
                if (array_key_exists($fid, $hasBookableAircraft)) {
                    $flight->so_has_bookable_aircraft = $hasBookableAircraft[$fid];
                }
                return $flight;
            });

            if ($bookableOnly) {
                $filtered = $flights->getCollection()
                    ->filter(fn($flight) => ($flight->so_has_bookable_aircraft ?? true) === true)
                    ->values();
                $flights->setCollection($filtered);
            }
        }

        // Attach booking-state flags for the UI (optional, compatibility mode only).
        if ($showBookingStatus && $flights->getCollection()->isNotEmpty()) {
            $flights->getCollection()->transform(function ($flight) use ($blockedByBid, $ownBid) {
                $fid = (string) $flight->id;
                $flight->so_bid_blocked = isset($blockedByBid[$fid]);
                $flight->so_bid_own = isset($ownBid[$fid]);
                $flight->so_bookable = !$flight->so_bid_blocked && (($flight->so_has_bookable_aircraft ?? true) === true);
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
            'respectPhpvmsSettings' => $respectPhpvmsSettings,
            'bookableOnly'          => $bookableOnly,
            'showBookingStatus'     => $showBookingStatus,
            'limitFromCurrent'      => $limitFromCurrent,
            'currAirport'           => $currAirport,
            'bidLockEnabled'        => $bidLockEnabled,
            'restrictAircraftAtDeparture' => $restrictAircraftAtDeparture,
            'restrictBookedAircraft'      => $restrictBookedAircraft,
        ];
    }

    /**
     * Resolve available aircraft ICAO types per displayed flight while honoring
     * optional phpVMS aircraft restrictions.
     *
     * Returns:
     * - types_by_flight: flight_id => [ICAO,...]
     * - has_bookable_aircraft: flight_id => bool
     */
    protected function resolveAircraftTypesByFlightAvailability(
        $flightCollection,
        bool $activeOnly,
        bool $restrictAircraftAtDeparture,
        bool $restrictBookedAircraft,
        int $userId
    ): array {
        $flightIds = $flightCollection->pluck('id')->map(fn($id) => (string) $id)->values()->all();
        if (empty($flightIds)) {
            return ['types_by_flight' => [], 'has_bookable_aircraft' => []];
        }

        $flightModel = new Flight();
        $pivotTable = null;
        $pivotFlightKey = 'flight_id';
        $pivotSubfleetKey = 'subfleet_id';

        foreach (['subfleets', 'subfleet'] as $tryName) {
            if (!method_exists($flightModel, $tryName)) {
                continue;
            }
            try {
                $rel = $flightModel->{$tryName}();
                $pivotTable       = $rel->getTable();
                $pivotFlightKey   = $rel->getForeignPivotKeyName();
                $pivotSubfleetKey = $rel->getRelatedPivotKeyName();
                break;
            } catch (\Throwable $e) {
                // fall through to next relation name
            }
        }

        if (!$pivotTable) {
            return ['types_by_flight' => [], 'has_bookable_aircraft' => []];
        }

        $pivotRows = DB::table($pivotTable)
            ->whereIn($pivotFlightKey, $flightIds)
            ->select($pivotFlightKey, $pivotSubfleetKey)
            ->distinct()
            ->get();

        $subfleetsByFlight = [];
        $allSubfleetIds = [];
        foreach ($pivotRows as $row) {
            $fid = (string) $row->{$pivotFlightKey};
            $sid = (int) $row->{$pivotSubfleetKey};
            $subfleetsByFlight[$fid][] = $sid;
            $allSubfleetIds[$sid] = true;
        }

        if (empty($allSubfleetIds)) {
            return ['types_by_flight' => [], 'has_bookable_aircraft' => []];
        }

        $acTable = (new Aircraft())->getTable();
        $bidTable = (new Bid())->getTable();
        $acQuery = DB::table($acTable)
            ->leftJoin($bidTable, "{$bidTable}.aircraft_id", '=', "{$acTable}.id")
            ->whereIn("{$acTable}.subfleet_id", array_keys($allSubfleetIds))
            ->whereNull("{$acTable}.deleted_at")
            ->whereNotNull("{$acTable}.icao")
            ->where("{$acTable}.icao", '!=', '');
        if ($activeOnly) {
            $acQuery->where("{$acTable}.status", 'A');
        }

        $acRows = $acQuery->select(
            "{$acTable}.subfleet_id",
            "{$acTable}.icao",
            "{$acTable}.airport_id",
            "{$bidTable}.user_id as bid_user_id"
        )->get();

        $aircraftBySubfleet = [];
        foreach ($acRows as $row) {
            $aircraftBySubfleet[(int) $row->subfleet_id][] = $row;
        }

        $depByFlight = [];
        foreach ($flightCollection as $flight) {
            $depByFlight[(string) $flight->id] = strtoupper((string) $flight->dpt_airport_id);
        }

        $typesByFlight = [];
        $hasBookableAircraft = [];
        foreach ($depByFlight as $fid => $depIcao) {
            if (!isset($subfleetsByFlight[$fid])) {
                continue;
            }

            $types = [];
            foreach ($subfleetsByFlight[$fid] as $sfid) {
                foreach ($aircraftBySubfleet[$sfid] ?? [] as $aircraft) {
                    if ($restrictAircraftAtDeparture && strtoupper((string) $aircraft->airport_id) !== $depIcao) {
                        continue;
                    }
                    if ($restrictBookedAircraft && !empty($aircraft->bid_user_id) && (int) $aircraft->bid_user_id !== $userId) {
                        continue;
                    }
                    $types[] = strtoupper((string) $aircraft->icao);
                }
            }

            $types = collect($types)->unique()->sort()->values()->all();
            $typesByFlight[$fid] = $types;
            $hasBookableAircraft[$fid] = !empty($types);
        }

        return [
            'types_by_flight' => $typesByFlight,
            'has_bookable_aircraft' => $hasBookableAircraft,
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
     * Resolve cargo flight type codes from phpVMS enum constants.
     * Falls back to known defaults for older/newer setups.
     */
    protected function cargoFlightTypes(): array
    {
        $fallback = ['F', 'A', 'H', 'M', 'Q', 'R', 'L'];
        $enumClass = '\App\Models\Enums\FlightType';

        if (!class_exists($enumClass)) {
            return $fallback;
        }

        $constants = [
            'SCHED_CARGO',
            'ADDITIONAL_CARGO',
            'CHARTER_CARGO_MAIL',
            'MAIL_SERVICE',
            'CARGO_IN_CABIN',
            'ADDTL_CARGO_IN_CABIN',
            'CHARTER_CARGO_IN_CABIN',
        ];

        $resolved = [];
        foreach ($constants as $constant) {
            $fqcn = $enumClass . '::' . $constant;
            if (defined($fqcn)) {
                $val = constant($fqcn);
                if (is_string($val) && $val !== '') {
                    $resolved[] = strtoupper($val);
                }
            }
        }

        $resolved = array_values(array_unique($resolved));
        return !empty($resolved) ? $resolved : $fallback;
    }

    /**
     * Resolve passenger-ish codes for the PAX chip.
     */
    protected function paxFlightTypes(array $cargoTypes): array
    {
        $fallback = ['J', 'C', 'G', 'E', 'I', 'N', 'D', 'S', 'B', 'O'];
        $enumClass = '\App\Models\Enums\FlightType';

        if (!class_exists($enumClass)) {
            return array_values(array_diff($fallback, $cargoTypes));
        }

        $constants = [
            'SCHED_PAX',
            'CHARTER_PAX_ONLY',
            'ADDTL_PAX',
            'VIP',
            'AMBULANCE',
            'AIR_TAXI',
            'GENERAL_AVIATION',
            'SHUTTLE',
            'ADDTL_SHUTTLE',
            'CHARTER_SPECIAL',
        ];

        $resolved = [];
        foreach ($constants as $constant) {
            $fqcn = $enumClass . '::' . $constant;
            if (defined($fqcn)) {
                $val = constant($fqcn);
                if (is_string($val) && $val !== '') {
                    $resolved[] = strtoupper($val);
                }
            }
        }

        if (empty($resolved)) {
            $resolved = $fallback;
        }

        return array_values(array_unique(array_diff($resolved, $cargoTypes)));
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
