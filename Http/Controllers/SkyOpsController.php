<?php
namespace Modules\SkyOps\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\SkyOps\Http\Requests\PirepListRequest;
use Modules\SkyOps\Http\Requests\FleetRequest;
use Modules\SkyOps\Services\PirepService;
use Modules\SkyOps\Services\FleetService;
use Modules\SkyOps\Services\PilotStatsService;
use Modules\SkyOps\Services\AirlineService;
use Modules\SkyOps\Services\FlightBoardService;
use Modules\SkyOps\Services\DashboardService;

class SkyOpsController extends Controller
{
    public function __construct(
        protected PirepService $pirepService,
        protected FleetService $fleetService,
        protected PilotStatsService $pilotStatsService,
        protected AirlineService $airlineService,
        protected FlightBoardService $flightBoardService,
        protected DashboardService $dashboardService,
    ) {}

    /**
     * Landing page: redirect to PIREPs or show dashboard (config-driven).
     */
    public function index()
    {
        $mode = config('skyops.landing', 'dashboard');

        if ($mode === 'dashboard') {
            return view('skyops::dashboard', array_merge(
                $this->dashboardService->getSummary(),
                ['currentPage' => 'dashboard']
            ));
        }

        return redirect()->route('skyops.pireps');
    }

    public function pirepList(PirepListRequest $request)
    {
        $v = $request->validated();
        return view('skyops::pirep-list', [
            'activeFlights'    => $this->pirepService->getActiveFlights(),
            'completedFlights' => $this->pirepService->getCompletedFlights($v),
            'filters'          => $v,
            'sources'          => $this->pirepService->getFilterOptions()['sources'],
            'networks'         => $this->pirepService->getFilterOptions()['networks'],
            'currentPage'      => 'pireps',
        ]);
    }

    public function fleet(FleetRequest $request)
    {
        $v = $request->validated();
        $opts = $this->fleetService->getFilterOptions();
        return view('skyops::fleet', [
            'aircraft'      => $this->fleetService->getAircraftList($v),
            'filters'       => $v,
            'airlines'      => $opts['airlines'],
            'subtypes'      => $opts['subtypes'],
            'pairs'         => $opts['pairs'],
            'icaoTypes'     => $opts['icaoTypes'],
            'registrations' => $opts['registrations'],
            'airportNames'  => $opts['airportNames'],
            'currentPage'   => 'fleet',
        ]);
    }

    public function pilotStats(Request $request)
    {
        return view('skyops::pilot-stats', [
            'pilotStats'  => $this->pilotStatsService->getStatistics(),
            'currentPage' => 'pilots',
        ]);
    }

    public function airlineOverview(Request $request)
    {
        return view('skyops::airline-overview', array_merge(
            $this->airlineService->getOverview(),
            ['currentPage' => 'airlines']
        ));
    }

    public function flightBoard(Request $request)
    {
        $v = $request->validate([
            'airline'  => 'nullable|string|max:10',
            'dep'      => 'nullable|string|max:10',
            'arr'      => 'nullable|string|max:10',
            'type'     => 'nullable|string|max:10',
            'min_ft_h' => 'nullable|numeric|min:0|max:24',
            'max_ft_h' => 'nullable|numeric|min:0|max:24',
            'page'     => 'nullable|integer|min:1',
        ]);
        return view('skyops::flight-board', array_merge(
            $this->flightBoardService->getFlightBoard($v, $request->user()),
            ['filters' => $v, 'currentPage' => 'departures']
        ));
    }

    public function guide()
    {
        return view('skyops::guide', [
            'currentPage' => 'guide',
            'isAdmin'     => \Modules\SkyOps\Helpers\SkyOpsHelper::csvAllowed(), // reuse admin detection
        ]);
    }
}
