<?php

namespace Modules\SkyOps\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Pirep;
use App\Models\Flight;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\User;
use App\Models\Enums\PirepState;
use Modules\SkyOps\Helpers\PilotNameHelper;

class DashboardService
{
    public function getSummary(): array
    {
        $ttl = config('skyops.cache_ttl.dashboard', 3) * 60;

        return Cache::remember('skyops.dashboard', $ttl, function () {
            return $this->buildSummary();
        });
    }

    protected function buildSummary(): array
    {
        $now   = Carbon::now();
        $today = $now->copy()->startOfDay();
        $month = $now->copy()->startOfMonth();
        $week  = $now->copy()->subDays(7);

        $hasLR   = Schema::hasColumn('pireps', 'landing_rate');
        $hasDist = Schema::hasColumn('pireps', 'distance');

        // ── Live flights ──
        $liveCount = Pirep::where('state', PirepState::IN_PROGRESS)->count();

        // ── PIREPs today / this week / total ──
        $pirepsToday = Pirep::where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $today)->count();

        $pirepsWeek = Pirep::where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $week)->count();

        $pirepsTotal = Pirep::where('state', PirepState::ACCEPTED)->count();

        // ── Hours this month ──
        $minutesMonth = (int) Pirep::where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $month)->sum('flight_time');

        // ── Fleet ──
        $acTotal  = Aircraft::whereNull('deleted_at')->count();
        $acActive = Aircraft::whereNull('deleted_at')
            ->where('status', 'A')->count();

        // ── Airlines ──
        $airlinesTotal = Airline::count();

        // ── Active pilots (flew this month) ──
        $pilotsMonth = Pirep::where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $month)
            ->distinct('user_id')->count('user_id');

        $pilotsTotal = User::whereNull('deleted_at')->count();

        // ── Scheduled flights ──
        $flightsTotal = Flight::where('active', true)->count();

        // ── Latest 10 accepted PIREPs (activity feed) ──
        $latestPireps = Pirep::with(['user', 'airline', 'dpt_airport', 'arr_airport', 'aircraft'])
            ->where('state', PirepState::ACCEPTED)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── Top 5 Pilots this month ──
        $topPilotsQuery = Pirep::query()
            ->select('user_id',
                DB::raw('COUNT(*) as flights'),
                DB::raw('SUM(flight_time) as total_mins'))
            ->where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $month)
            ->groupBy('user_id')
            ->orderByDesc('flights')
            ->limit(5);

        if ($hasDist) {
            $topPilotsQuery->addSelect(DB::raw('SUM(distance) as total_dist'));
        }

        $topPilotsRaw = $topPilotsQuery->get();

        // Hydrate with user data
        $userIds = $topPilotsRaw->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $topPilots = $topPilotsRaw->map(function ($row) use ($users, $hasDist) {
            $user = $users->get($row->user_id);
            $mins = (int) ($row->total_mins ?? 0);
            return [
                'name'    => PilotNameHelper::format($user->name ?? null, $user->callsign ?? null),
                'user_id' => $row->user_id,
                'flights' => (int) $row->flights,
                'hours'   => sprintf('%d:%02d', intdiv($mins, 60), $mins % 60),
                'dist'    => $hasDist ? (int) ($row->total_dist ?? 0) : null,
            ];
        });

        // ── Top 5 Routes this month ──
        $topRoutes = Pirep::query()
            ->select('dpt_airport_id', 'arr_airport_id',
                DB::raw('COUNT(*) as flights'),
                DB::raw('SUM(flight_time) as total_mins'))
            ->where('state', PirepState::ACCEPTED)
            ->where('created_at', '>=', $month)
            ->groupBy('dpt_airport_id', 'arr_airport_id')
            ->orderByDesc('flights')
            ->limit(5)
            ->get();

        // Hydrate airport ICAOs
        $airportIds = $topRoutes->pluck('dpt_airport_id')
            ->merge($topRoutes->pluck('arr_airport_id'))
            ->unique()->toArray();

        $airports = DB::table('airports')
            ->whereIn('id', $airportIds)
            ->pluck('icao', 'id');

        $topRoutes = $topRoutes->map(function ($row) use ($airports) {
            $mins = (int) ($row->total_mins ?? 0);
            return [
                'dep'     => $airports[$row->dpt_airport_id] ?? '?',
                'arr'     => $airports[$row->arr_airport_id] ?? '?',
                'flights' => (int) $row->flights,
                'hours'   => sprintf('%d:%02d', intdiv($mins, 60), $mins % 60),
            ];
        });

        return [
            'liveCount'     => $liveCount,
            'pirepsToday'   => $pirepsToday,
            'pirepsWeek'    => $pirepsWeek,
            'pirepsTotal'   => $pirepsTotal,
            'minutesMonth'  => $minutesMonth,
            'hoursMonth'    => sprintf('%d:%02d', intdiv($minutesMonth, 60), $minutesMonth % 60),
            'acTotal'       => $acTotal,
            'acActive'      => $acActive,
            'airlinesTotal' => $airlinesTotal,
            'pilotsMonth'   => $pilotsMonth,
            'pilotsTotal'   => $pilotsTotal,
            'flightsTotal'  => $flightsTotal,
            'latestPireps'  => $latestPireps,
            'topPilots'     => $topPilots,
            'topRoutes'     => $topRoutes,
            'hasLR'         => $hasLR,
            'hasDist'       => $hasDist,
        ];
    }
}
