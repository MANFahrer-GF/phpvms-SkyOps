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
        $tp = DB::getTablePrefix();
        $tblPireps = $tp . 'pireps';

        $q = DB::table('aircraft')
            ->join('subfleets', 'subfleets.id', '=', 'aircraft.subfleet_id')
            ->join('airlines', 'airlines.id', '=', 'subfleets.airline_id')
            ->leftJoin('pireps', 'pireps.aircraft_id', '=', 'aircraft.id')
            ->select([
                'aircraft.id', 'aircraft.registration', 'aircraft.name as ac_name',
                'aircraft.icao as ac_icao', 'airlines.id as al_id',
                'airlines.icao as al_icao', 'airlines.name as al_name', 'airlines.logo as al_logo',
                'subfleets.type as sf_type', 'subfleets.name as sf_name',
                'aircraft.airport_id as loc', 'aircraft.hub_id as hub',
                'aircraft.status as ac_status', 'aircraft.state as ac_state',
            ])
            ->selectRaw('COUNT(' . $tblPireps . '.id) as cnt')
            ->selectRaw('COALESCE(SUM(COALESCE(' . $tblPireps . '.flight_time,0)),0) as mins')
            ->groupBy([
                'aircraft.id', 'aircraft.registration', 'aircraft.name', 'aircraft.icao',
                'airlines.id', 'airlines.icao', 'airlines.name', 'airlines.logo',
                'subfleets.type', 'subfleets.name',
                'aircraft.airport_id', 'aircraft.hub_id', 'aircraft.status', 'aircraft.state',
            ]);

        $f_airline = strtoupper(trim($filters['airline'] ?? ''));
        $f_icao    = strtoupper(trim($filters['icao'] ?? ''));
        $f_subtype = trim($filters['subtype'] ?? '');
        $f_reg     = trim($filters['reg'] ?? '');
        $f_min     = max(0, (int)($filters['min'] ?? 0));

        if ($f_airline !== '') $q->where('airlines.icao', $f_airline);
        if ($f_icao !== '')    $q->where('aircraft.icao', 'like', '%' . $f_icao . '%');
        if ($f_subtype !== '') $q->where('subfleets.type', $f_subtype);
        if ($f_reg !== '')     $q->where('aircraft.registration', 'like', '%' . $f_reg . '%');
        if ($f_min > 0)        $q->having('cnt', '>=', $f_min);

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
                    ->whereNotNull('icao')->where('icao', '<>', '')
                    ->distinct()->orderBy('icao')->pluck('icao'),
                'registrations' => DB::table('aircraft as ac')
                    ->join('subfleets as sf', 'sf.id', '=', 'ac.subfleet_id')
                    ->join('airlines as al', 'al.id', '=', 'sf.airline_id')
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
