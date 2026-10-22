<?php
namespace Modules\SkyOps\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FleetService
{
    /**
     * Get aircraft list with PIREP count + total flight time.
     * Uses DB::table with JOINs + raw aggregates (COUNT, SUM).
     */
    public function getAircraftList(array $filters)
    {
        // v1.1.2: PIREPs are summed per aircraft FIRST, then joined. Joining every
        // PIREP row and grouping by 14 aircraft/airline columns took ~1.6 s on a
        // VA with ~1,650 aircraft; the pre-aggregated join returns the identical
        // rows in ~60 ms. Soft-deleted PIREPs stay excluded.
        $pt = DB::getTablePrefix() . 'pt';
        $pirepTotals = DB::table('pireps')
            ->whereNull('deleted_at')
            ->groupBy('aircraft_id')
            ->select('aircraft_id')
            ->selectRaw('COUNT(id) as cnt, SUM(COALESCE(flight_time,0)) as mins');

        $q = DB::table('aircraft')
            ->join('subfleets', 'subfleets.id', '=', 'aircraft.subfleet_id')
            ->join('airlines', 'airlines.id', '=', 'subfleets.airline_id')
            // Left join: aircraft with zero PIREPs must remain.
            ->leftJoinSub($pirepTotals, 'pt', 'pt.aircraft_id', '=', 'aircraft.id')
            // DB::table bypasses Eloquent's SoftDeletes scope, so exclude
            // soft-deleted aircraft/subfleets explicitly (matches the rest of the module).
            ->whereNull('aircraft.deleted_at')
            ->whereNull('subfleets.deleted_at')
            ->select([
                'aircraft.id', 'aircraft.registration', 'aircraft.name as ac_name',
                'aircraft.icao as ac_icao', 'airlines.id as al_id',
                'airlines.icao as al_icao', 'airlines.name as al_name', 'airlines.logo as al_logo',
                'subfleets.type as sf_type', 'subfleets.name as sf_name',
                'aircraft.airport_id as loc', 'aircraft.hub_id as hub',
                'aircraft.status as ac_status', 'aircraft.state as ac_state',
            ])
            // Raw expressions need the table prefix on the alias too (Laravel
            // prefixes `pt` in the join itself).
            ->selectRaw('COALESCE(' . $pt . '.cnt,0) as cnt')
            ->selectRaw('COALESCE(' . $pt . '.mins,0) as mins');

        $f_airline = strtoupper(trim($filters['airline'] ?? ''));
        $f_icao    = strtoupper(trim($filters['icao'] ?? ''));
        $f_subtype = trim($filters['subtype'] ?? '');
        $f_reg     = trim($filters['reg'] ?? '');
        $f_min     = max(0, (int)($filters['min'] ?? 0));

        if ($f_airline !== '') $q->where('airlines.icao', $f_airline);
        if ($f_icao !== '')    $q->where('aircraft.icao', 'like', '%' . $f_icao . '%');
        if ($f_subtype !== '') $q->where('subfleets.type', $f_subtype);
        if ($f_reg !== '')     $q->where('aircraft.registration', 'like', '%' . $f_reg . '%');
        if ($f_min > 0)        $q->whereRaw('COALESCE(' . $pt . '.cnt,0) >= ?', [$f_min]);

        $this->applySort($q, $filters['order'] ?? 'time_desc');
        $q->orderBy('al_icao')->orderBy('sf_type')->orderBy('aircraft.registration');

        return $q->get();
    }

    protected function applySort($q, string $order): void
    {
        $map = [
            'flights_desc'  => ['cnt', 'desc'],
            'flights_asc'   => ['cnt', 'asc'],
            'time_desc'     => ['mins', 'desc'],
            'time_asc'      => ['mins', 'asc'],
            'reg_asc'       => ['aircraft.registration', 'asc'],
            'reg_desc'      => ['aircraft.registration', 'desc'],
            'aircraft_asc'  => ['aircraft.name', 'asc'],
            'aircraft_desc' => ['aircraft.name', 'desc'],
            'airline_asc'   => ['al_icao', 'asc'],
            'airline_desc'  => ['al_icao', 'desc'],
            'subfleet_asc'  => ['sf_type', 'asc'],
            'subfleet_desc' => ['sf_type', 'desc'],
            'loc_asc'       => ['loc', 'asc'],
            'loc_desc'      => ['loc', 'desc'],
            'hub_asc'       => ['hub', 'asc'],
            'hub_desc'      => ['hub', 'desc'],
        ];
        $sort = $map[$order] ?? ['mins', 'desc'];
        $q->orderBy($sort[0], $sort[1]);
    }

    /**
     * Cached filter options for cascading datalists.
     */
    public function getFilterOptions(): array
    {
        $ttl = config('skyops.cache_ttl.fleet_stats', 5);
        return Cache::remember('skyops.fleet_filters', $ttl * 60, function () {
            return [
                'airlines' => DB::table('airlines')
                    ->select('id', 'icao', 'name')->orderBy('icao')->get(),
                'subtypes' => DB::table('subfleets')
                    ->select('type')->distinct()->orderBy('type')->pluck('type'),
                'pairs' => DB::table('subfleets')
                    ->join('airlines', 'airlines.id', '=', 'subfleets.airline_id')
                    ->distinct()->orderBy('airlines.icao')->orderBy('subfleets.type')
                    ->get(['airlines.icao as icao', 'subfleets.type as type']),
                'icaoTypes' => DB::table('aircraft')->select('icao')
                    ->whereNull('deleted_at')
                    ->whereNotNull('icao')->where('icao', '<>', '')
                    ->distinct()->orderBy('icao')->pluck('icao'),
                'registrations' => DB::table('aircraft as ac')
                    ->join('subfleets as sf', 'sf.id', '=', 'ac.subfleet_id')
                    ->join('airlines as al', 'al.id', '=', 'sf.airline_id')
                    ->whereNull('ac.deleted_at')
                    ->whereNull('sf.deleted_at')
                    ->orderBy('al.icao')->orderBy('ac.registration')
                    ->get(['ac.registration as reg', 'al.icao as icao', 'sf.type as subtype']),
                'airportNames' => DB::table('airports')
                    ->pluck('name', 'icao')->toArray(),
            ];
        });
    }

    /**
     * Resolve airline logo URL (matches original blade logic).
     */
    public static function logoUrl($row): string
    {
        if (!empty($row->al_logo)) {
            if (Str::startsWith($row->al_logo, ['http://', 'https://', '/'])) {
                return $row->al_logo;
            }
            try {
                return Storage::url($row->al_logo);
            } catch (\Throwable $e) {
                // fallthrough to default
            }
        }
        return asset('SPTheme/images/airlines/' . strtolower($row->al_icao ?? 'unknown') . '.png');
    }
}
