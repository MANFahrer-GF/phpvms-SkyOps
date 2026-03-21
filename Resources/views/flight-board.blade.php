{{-- modules/SkyOps/Resources/views/flight-board.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.departures'))

@php
    use Modules\SkyOps\Services\FlightBoardService;
    $typeMap   = [
        'J' => 'PAX', 'C' => 'PAX', 'G' => 'PAX', 'E' => 'VIP', 'I' => 'AMB',
        'F' => 'CARGO', 'A' => 'CARGO', 'H' => 'CARGO', 'M' => 'MAIL', 'Q' => 'CARGO', 'R' => 'CARGO', 'L' => 'CARGO',
        'S' => 'SHUTTLE', 'B' => 'SHUTTLE',
        'K' => 'TRAIN', 'P' => 'POS', 'T' => 'TEST', 'X' => 'TECH',
    ];
    $typeColor = [
        'J' => 'blue', 'C' => 'blue', 'G' => 'blue', 'E' => 'violet', 'I' => 'cyan',
        'F' => 'amber', 'A' => 'amber', 'H' => 'amber', 'M' => 'amber', 'Q' => 'amber', 'R' => 'amber', 'L' => 'amber',
        'S' => 'green', 'B' => 'green',
        'K' => 'green', 'P' => 'cyan', 'T' => 'cyan', 'X' => 'cyan',
    ];
@endphp

@section('skyops-content')
<!-- SkyOps Debug: type_source={{ $_debug_type_source ?? 'n/a' }} | pivot={{ $_debug_pivot_table ?? 'n/a' }} | combos={{ $_debug_assigned ?? 0 }} | sample={{ $_debug_sample ?? 'n/a' }} -->

@php
    $curAirline = strtoupper(trim((string) request('airline', '')));
    $curDep     = strtoupper(trim((string) request('dep', '')));
    $curArr     = strtoupper(trim((string) request('arr', '')));

    $hasMin = $activeMin !== null && $activeMin !== '' && (float) $activeMin > 0;
    $hasMax = $activeMax !== null && $activeMax !== '';

    $activeFilterLabels = [];
    if ($curAirline !== '') $activeFilterLabels[] = 'Airline: ' . $curAirline;
    if ($curDep !== '')     $activeFilterLabels[] = __('skyops::skyops.dep_departure') . ': ' . $curDep;
    if ($curArr !== '')     $activeFilterLabels[] = __('skyops::skyops.dep_arrival') . ': ' . $curArr;
    if ($fltType !== '')    $activeFilterLabels[] = __('skyops::skyops.dep_flight_type') . ': ' . $fltType;
    if ($hasMin && $hasMax) $activeFilterLabels[] = __('skyops::skyops.dep_flight_time') . ': ' . $activeMin . 'h–' . $activeMax . 'h';
    elseif ($hasMin)        $activeFilterLabels[] = __('skyops::skyops.dep_flight_time') . ': ≥' . $activeMin . 'h';
    elseif ($hasMax)        $activeFilterLabels[] = __('skyops::skyops.dep_flight_time') . ': ≤' . $activeMax . 'h';

    $hasActiveFilters = !empty($activeFilterLabels);
@endphp

<style>
/* ── Flight Board — so-fb-* prefix, ap-* theme vars ── */

/* Slider */
.so-fb-ft-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.so-fb-range{flex:1;min-width:80px;-webkit-appearance:none;appearance:none;height:5px;border-radius:99px;outline:none;cursor:pointer;background:linear-gradient(to right,var(--ap-blue) 0%,var(--ap-blue) var(--ft-slider-pct,0%),rgba(125,133,144,.12) var(--ft-slider-pct,0%),rgba(125,133,144,.12) 100%)}
.so-fb-range::-webkit-slider-thumb{-webkit-appearance:none;width:17px;height:17px;border-radius:50%;background:var(--ap-blue);border:2px solid #fff;cursor:pointer;box-shadow:0 2px 6px rgba(59,130,246,.4);transition:transform .1s}
.so-fb-range::-webkit-slider-thumb:hover{transform:scale(1.15)}
.so-fb-range::-moz-range-thumb{width:17px;height:17px;border-radius:50%;background:var(--ap-blue);border:2px solid #fff;cursor:pointer}

.so-fb-h-field{display:flex;align-items:center;gap:0;background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:8px;overflow:hidden;flex-shrink:0}
.so-fb-h-field span{padding:0 8px 0 4px;font-size:.72rem;color:var(--ap-muted);font-weight:600;user-select:none;white-space:nowrap}
.so-fb-h-num{width:46px;background:transparent;border:none;color:var(--ap-text);font-size:.88rem;font-weight:700;text-align:center;padding:6px 2px;outline:none;font-variant-numeric:tabular-nums}
.so-fb-h-sep{font-size:.72rem;color:var(--ap-muted);font-weight:700;padding:0 2px;flex-shrink:0;align-self:center}

/* Tags */
.so-fb-tag{display:inline-flex;align-items:center;gap:3px;font-size:.63rem;font-weight:600;padding:2px 7px;border-radius:5px;white-space:nowrap;background:rgba(125,133,144,.06);color:var(--ap-text);border:1px solid var(--ap-border)}
.so-fb-tag-blue{background:rgba(59,130,246,.15);border-color:rgba(59,130,246,.3);color:#93c5fd}
.so-fb-tag-amber{background:rgba(210,153,34,.15);border-color:rgba(210,153,34,.3);color:#fbbf24}
.so-fb-tag-cyan{background:rgba(14,165,233,.15);border-color:rgba(14,165,233,.3);color:#67e8f9}
.so-fb-tag-green{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:#86efac}
.so-fb-tag-violet{background:rgba(129,140,248,.15);border-color:rgba(129,140,248,.3);color:#c4b5fd}

/* Flight board tags — light mode */
html.ap-light .so-fb-tag-blue{color:#1d4ed8;background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2)}
html.ap-light .so-fb-tag-amber{color:#92400e;background:rgba(210,153,34,.1);border-color:rgba(210,153,34,.2)}
html.ap-light .so-fb-tag-cyan{color:#0369a1;background:rgba(14,165,233,.1);border-color:rgba(14,165,233,.2)}
html.ap-light .so-fb-tag-green{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.2)}
html.ap-light .so-fb-tag-violet{color:#5b21b6;background:rgba(129,140,248,.1);border-color:rgba(129,140,248,.2)}

/* PAX / Cargo chips */
.so-fb-chip-pax{background:rgba(59,130,246,.15)!important;border-color:rgba(59,130,246,.3)!important;color:#93c5fd!important}
.so-fb-chip-cargo{background:rgba(210,153,34,.15)!important;border-color:rgba(210,153,34,.3)!important;color:#fbbf24!important}
html.ap-light .so-fb-chip-pax{color:#1d4ed8!important;background:rgba(59,130,246,.1)!important;border-color:rgba(59,130,246,.2)!important}
html.ap-light .so-fb-chip-cargo{color:#92400e!important;background:rgba(210,153,34,.1)!important;border-color:rgba(210,153,34,.2)!important}

/* Board */
.so-fb-board{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;overflow:hidden}
.so-fb-cols{display:grid;grid-template-columns:36px 98px minmax(110px,1.5fr) minmax(180px,2fr) {{ $showDptTime ? '68px' : '' }} {{ $showArrTime ? '68px' : '' }} {{ $showFlightTime ? '66px' : '' }} {{ $showDistance ? '74px' : '' }} 66px 76px;align-items:center}
.so-fb-thead{background:var(--ap-surface);border-bottom:2px solid var(--ap-border);padding:0 4px}
.so-fb-th{font-size:.61rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--ap-muted);padding:8px 5px}
.so-fb-th.r{text-align:right}.so-fb-th.c{text-align:center}

.so-fb-row{padding:0 4px;min-height:58px;cursor:pointer;border-bottom:1px solid var(--ap-border);transition:background .1s}
.so-fb-row:last-of-type{border-bottom:none}
.so-fb-row:nth-child(odd) .so-fb-cols{background:rgba(125,133,144,.015)}
.so-fb-row:hover .so-fb-cols,.so-fb-row.open .so-fb-cols{background:rgba(88,166,255,.06)}
.so-fb-td{padding:8px 6px;overflow:hidden}
.so-fb-td.r{text-align:right}.so-fb-td.c{text-align:center}

/* Logo */
.so-fb-logo{width:90px;height:38px;border-radius:6px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden}
.so-fb-logo img{max-width:90%;max-height:84%;object-fit:contain}

/* Airline name + flight number */
.so-fb-aname{font-weight:600;font-size:.92rem;color:var(--ap-text-head);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.so-fb-aname a{color:inherit;text-decoration:none}
.so-fb-aname a:hover{color:var(--ap-cyan)}
.so-fb-fn{font-size:.76rem;color:var(--ap-muted);margin-top:2px;font-variant-numeric:tabular-nums}

/* Route badges */
.so-fb-route-badge{display:grid;grid-template-columns:1fr 1fr;align-items:start;gap:0 10px;width:100%}
.so-fb-ap-badge{display:flex;flex-direction:column;align-items:flex-start;min-width:0}
.so-fb-ap-code{display:inline-block;font-weight:700;font-size:1rem;color:var(--ap-text-head);letter-spacing:.06em;line-height:1.2;font-variant-numeric:tabular-nums}
.so-fb-ap-name{font-size:.68rem;color:var(--ap-text);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;opacity:.8}

/* Chevron expand */
.so-fb-chev{width:28px;height:28px;border-radius:8px;background:rgba(125,133,144,.08);border:1.5px solid rgba(125,133,144,.2);display:inline-flex;align-items:center;justify-content:center;transition:all .2s;user-select:none;cursor:pointer;flex-shrink:0;color:rgba(255,255,255,.6);font-size:1rem;line-height:1}
html.ap-light .so-fb-chev{color:rgba(0,0,0,.5)}
.so-fb-chev::after{content:'\203A';display:block;transition:transform .2s;font-weight:400}
.so-fb-row.open .so-fb-chev{background:var(--ap-blue);border-color:var(--ap-blue);color:#fff}
.so-fb-row.open .so-fb-chev::after{transform:rotate(90deg)}
.so-fb-chev:hover{background:rgba(125,133,144,.15);border-color:rgba(125,133,144,.35);color:var(--ap-text-head)}

/* Times */
.so-fb-time{font-weight:700;font-size:.96rem;color:var(--ap-cyan);font-variant-numeric:tabular-nums}
.so-fb-arr-time{color:var(--ap-text)}
.so-fb-utc{font-size:.58rem;color:var(--ap-muted)}
.so-fb-dur{font-size:.84rem;color:var(--ap-text);font-variant-numeric:tabular-nums}
.so-fb-dist{font-size:.82rem;color:var(--ap-text);font-variant-numeric:tabular-nums}

/* Detail Row */
.so-fb-detail{display:none;border-bottom:1px solid var(--ap-border);background:rgba(88,166,255,.03);padding:10px 14px 12px calc(36px + 94px + 8px + 14px)}
.so-fb-detail.open{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px 20px}
.so-fb-dg-label{font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--ap-muted);margin-bottom:2px}
.so-fb-dg-val{font-size:.76rem;color:var(--ap-text-head);font-variant-numeric:tabular-nums}
.so-fb-dg-val a{color:var(--ap-cyan);text-decoration:none}
.so-fb-dg-val a:hover{text-decoration:underline}

/* Empty */
.so-fb-empty{padding:40px;text-align:center;color:var(--ap-muted)}
.so-fb-empty i{font-size:2rem;opacity:.25;display:block;margin-bottom:10px}

/* Pagination */
.pagination .page-link{background:var(--ap-surface);border-color:var(--ap-border);color:var(--ap-text);font-size:.76rem;border-radius:7px!important;margin:0 2px}
.pagination .page-link:hover{background:rgba(88,166,255,.06);border-color:var(--ap-blue);color:var(--ap-text-head)}
.pagination .active .page-link{background:var(--ap-blue);border-color:var(--ap-blue);color:#fff}

/* Chip active state */
.so-fb-chip-on{background:var(--ap-blue)!important;border-color:var(--ap-blue)!important;color:#fff!important}
.so-fb-type-chip.so-fb-chip-on{background:var(--ap-blue)!important;border-color:var(--ap-blue)!important;color:#fff!important;box-shadow:inset 0 0 0 1px rgba(255,255,255,.2)}
html.ap-light .so-fb-type-chip.so-fb-chip-on{background:var(--ap-blue)!important;border-color:var(--ap-blue)!important;color:#fff!important;box-shadow:inset 0 0 0 1px rgba(255,255,255,.35)}
.so-fb-input-on{border-color:var(--ap-blue)!important;box-shadow:0 0 0 2px rgba(59,130,246,.18)}
.so-fb-type-stack{display:flex;flex-direction:column;gap:4px;align-items:center}

@media(max-width:800px){
    .so-fb-thead{display:none}
    .so-fb-cols{grid-template-columns:28px 64px 1fr 60px;grid-template-rows:auto auto}
    .so-fb-td:nth-child(3){grid-column:3;grid-row:1}
    .so-fb-td:nth-child(4){grid-column:3;grid-row:2}
    .so-fb-td:nth-child(5),.so-fb-td:nth-child(6),.so-fb-td:nth-child(7),.so-fb-td:nth-child(8),.so-fb-td:nth-child(9){display:none}
    .so-fb-td:nth-child(10){grid-column:4;grid-row:1/span 2}
    .so-fb-detail{padding:10px 12px}
}
</style>

{{-- PAGE HEADER CARD --}}
<div class="so-card so-page-header">
    <div class="so-page-title">
        🛫 {{ __('skyops::skyops.departures') }}
        <span class="so-page-badge">{{ $flights->count() }} {{ __('skyops::skyops.col_flights') }}</span>
    </div>
    @if($showDptTime || $showArrTime)
    <div class="so-page-subtitle">
        {{ __('skyops::skyops.dep_utc_time', ['time' => \Carbon\Carbon::now('UTC')->format('H:i')]) }}
    </div>
    @endif
</div>

{{-- FILTER --}}
<div class="so-card mb-3">
    <form method="GET" id="soFbForm">
        @csrf

        @if($respectPhpvmsSettings)
        <div class="d-flex gap-1 flex-wrap align-items-center mb-2">
            <span class="so-fb-tag so-fb-tag-green">phpVMS Sync</span>
            @if($limitFromCurrent && $currAirport !== '')
                <span class="so-fb-tag so-fb-tag-blue">Current DEP: {{ $currAirport }}</span>
            @endif
            @if($bidLockEnabled)
                <span class="so-fb-tag so-fb-tag-amber">Bid Lock</span>
            @endif
            @if($restrictAircraftAtDeparture)
                <span class="so-fb-tag so-fb-tag-cyan">AC @ DEP</span>
            @endif
            @if($restrictBookedAircraft)
                <span class="so-fb-tag so-fb-tag-cyan">AC Not Booked</span>
            @endif
            @if($bookableOnly)
                <span class="so-fb-tag so-fb-tag-violet">Bookable Only</span>
            @endif
        </div>
        @endif

        <div class="row g-2 align-items-end">

            {{-- Airline --}}
            <div class="col-6 col-md-3 col-lg-2">
                <div class="so-filter-label">Airline</div>
                <input class="so-input {{ $curAirline !== '' ? 'so-fb-input-on' : '' }}" name="airline" list="so-airline-list"
                       value="{{ old('airline', request('airline')) }}" placeholder="DLH / QTR">
                <datalist id="so-airline-list">
                    @foreach($airlineOptions as $ao)
                        @php $code = $ao->icao ?: $ao->iata; @endphp
                        @if($code)<option value="{{ $code }}">{{ $code }} — {{ $ao->name }}</option>@endif
                    @endforeach
                </datalist>
            </div>

            {{-- Departure --}}
            <div class="col-6 col-md-2 col-lg-2">
                <div class="so-filter-label">{{ __('skyops::skyops.dep_departure') }}</div>
                <input class="so-input {{ $curDep !== '' ? 'so-fb-input-on' : '' }}" name="dep" list="so-airport-list"
                       value="{{ old('dep', request('dep')) }}" placeholder="EDDF">
            </div>

            {{-- Arrival --}}
            <div class="col-6 col-md-2 col-lg-2">
                <div class="so-filter-label">{{ __('skyops::skyops.dep_arrival') }}</div>
                <input class="so-input {{ $curArr !== '' ? 'so-fb-input-on' : '' }}" name="arr" list="so-airport-list"
                       value="{{ old('arr', request('arr')) }}" placeholder="EGLL">
                <datalist id="so-airport-list">
                    @foreach($airportOptions as $ap)
                        <option value="{{ $ap->id }}">{{ $ap->id }} — {{ $ap->name }}</option>
                    @endforeach
                </datalist>
            </div>

            {{-- Flight time slider --}}
            @if($showFlightTime)
            <div class="col-12 col-md-4 col-lg-4">
                <div class="so-filter-label" style="margin-bottom:8px;">{{ __('skyops::skyops.dep_flight_time') }}</div>
                <div class="so-fb-ft-row">
                    <input type="range" id="soFbMinSlider" name="min_ft_h"
                           min="0" max="18" step="0.5" value="{{ $minHVal }}"
                           class="so-fb-range" style="min-width:80px;flex:1;">
                    <div class="so-fb-h-field">
                        <input type="number" id="soFbMinNum" class="so-fb-h-num {{ $hasMin ? 'so-fb-input-on' : '' }}"
                               min="0" max="18" step="0.5"
                               value="{{ $minHVal > 0 ? $minHVal : '' }}" placeholder="0">
                        <span>h min</span>
                    </div>
                    <span class="so-fb-h-sep">—</span>
                    <div class="so-fb-h-field">
                        <input type="number" id="soFbMaxNum" name="max_ft_h" class="so-fb-h-num {{ $hasMax ? 'so-fb-input-on' : '' }}"
                               min="0" max="24" step="0.5"
                               value="{{ $maxHVal !== '' ? $maxHVal : '' }}" placeholder="∞">
                        <span>h max</span>
                    </div>
                </div>

                <div class="d-flex gap-1 mt-2 flex-wrap">
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ !$activeMin ? 'so-fb-chip-on' : '' }}" data-min="0" data-max="" style="padding:2px 9px;font-size:.68rem;">{{ __('skyops::skyops.dep_all') }}</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ $activeMin==1 ? 'so-fb-chip-on' : '' }}" data-min="1" data-max="" style="padding:2px 9px;font-size:.68rem;">≥1h</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ $activeMin==2 ? 'so-fb-chip-on' : '' }}" data-min="2" data-max="" style="padding:2px 9px;font-size:.68rem;">≥2h</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ $activeMin==4 ? 'so-fb-chip-on' : '' }}" data-min="4" data-max="" style="padding:2px 9px;font-size:.68rem;">≥4h</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ $activeMin==6 ? 'so-fb-chip-on' : '' }}" data-min="6" data-max="" style="padding:2px 9px;font-size:.68rem;">≥6h</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ ($activeMin==2 && $activeMax==4) ? 'so-fb-chip-on' : '' }}" data-min="2" data-max="4" style="padding:2px 9px;font-size:.68rem;">2–4h</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-chip {{ ($activeMin==4 && $activeMax==8) ? 'so-fb-chip-on' : '' }}" data-min="4" data-max="8" style="padding:2px 9px;font-size:.68rem;">4–8h</button>
                </div>
            </div>
            @endif

        </div>

        {{-- Type filter + submit --}}
        <div class="row g-2 mt-1">
            <div class="col-12">
                <div class="so-filter-label" style="margin-bottom:8px;">{{ __('skyops::skyops.dep_flight_type') }}</div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="hidden" name="type" id="soFbTypeInput" value="{{ old('type', request('type')) }}">
                    <button type="button" class="so-btn so-btn-ghost so-fb-type-chip {{ $fltType === '' ? 'so-fb-chip-on' : '' }}" data-type="" style="padding:4px 14px;font-size:.78rem;">{{ __('skyops::skyops.dep_all') }}</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-type-chip {{ $fltType === 'PAX' ? 'so-fb-chip-on' : '' }} so-fb-chip-pax" data-type="PAX" style="padding:4px 14px;font-size:.78rem;">✈ PAX</button>
                    <button type="button" class="so-btn so-btn-ghost so-fb-type-chip {{ $fltType === 'CARGO' ? 'so-fb-chip-on' : '' }} so-fb-chip-cargo" data-type="CARGO" style="padding:4px 14px;font-size:.78rem;">📦 Cargo</button>
                    <span style="width:1px;height:24px;background:var(--ap-border);margin:0 6px;align-self:center;"></span>
                    <button class="so-btn so-btn-primary" type="submit" style="padding:6px 20px;font-size:.82rem;font-weight:700;">
                        ✓ {{ __('skyops::skyops.search_btn') }}
                    </button>
                    <a class="so-btn so-btn-ghost" href="{{ url()->current() }}" style="padding:6px 16px;font-size:.82rem;font-weight:600;">
                        ✕ Reset
                    </a>
                </div>
            </div>
        </div>

        @if($hasActiveFilters)
        <div class="row g-2 mt-1">
            <div class="col-12">
                <div class="d-flex gap-1 flex-wrap align-items-center">
                    @foreach($activeFilterLabels as $lbl)
                        <span class="so-fb-tag so-fb-tag-blue">{{ $lbl }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </form>
</div>

{{-- FIDS BOARD --}}
<div class="so-fb-board">

    {{-- Header --}}
    <div class="so-fb-cols so-fb-thead">
        <div class="so-fb-th"></div>
        <div class="so-fb-th">{{ __('skyops::skyops.col_logo') }}</div>
        <div class="so-fb-th">{{ __('skyops::skyops.dep_col_airline') }}</div>
        <div class="so-fb-th">{{ __('skyops::skyops.dep_col_route') }}</div>
        @if($showDptTime)<div class="so-fb-th">{{ __('skyops::skyops.dep_col_dep') }}</div>@endif
        @if($showArrTime)<div class="so-fb-th">{{ __('skyops::skyops.dep_col_arr') }}</div>@endif
        @if($showFlightTime)<div class="so-fb-th r">{{ __('skyops::skyops.dep_col_duration') }}</div>@endif
        @if($showDistance)<div class="so-fb-th r">{{ __('skyops::skyops.dep_col_distance') }}</div>@endif
        <div class="so-fb-th c">{{ __('skyops::skyops.dep_col_type') }}</div>
        <div class="so-fb-th c">{{ __('skyops::skyops.dep_col_details') }}</div>
    </div>

    @forelse($flights as $f)
        @php
            $aln      = $f->airline;
            $dep      = $airportsById[$f->dpt_airport_id] ?? null;
            $arr      = $airportsById[$f->arr_airport_id] ?? null;
            $alnCode  = $aln ? ($aln->icao ?: $aln->iata) : '';
            $typeTxt  = $typeMap[$f->flight_type ?? ''] ?? ($f->flight_type ?? null);
            $typeCol  = $typeColor[$f->flight_type ?? ''] ?? 'blue';
            $dist_txt = FlightBoardService::distStr($f->distance);
            $ft_txt   = FlightBoardService::ftStr($f->flight_time);
            $showUrl  = $hasShow ? route('frontend.flights.show', [$f->id]) : url('/flights/' . $f->id);
            $alnUrl   = ($aln && $airlineShow) ? route('frontend.airlines.show', [$aln->id]) : null;
            $fn       = $alnCode . $f->flight_number . ($f->route_code ? '/C.' . $f->route_code : '') . ($f->route_leg ? '/L.' . $f->route_leg : '');
            $uid      = 'r' . $loop->index;
        @endphp

        <div class="so-fb-row" id="{{ $uid }}" onclick="soFbToggle('{{ $uid }}')">
            <div class="so-fb-cols">

                <div class="so-fb-td c">
                    <div class="so-fb-chev"></div>
                </div>

                <div class="so-fb-td">
                    <div class="so-fb-logo">
                        @if($aln && $aln->logo)
                            <img src="{{ $aln->logo }}" alt="{{ $aln->name }}" loading="lazy">
                        @else
                            ✈️
                        @endif
                    </div>
                </div>

                <div class="so-fb-td">
                    <div class="so-fb-aname">
                        @if($alnUrl)<a href="{{ $alnUrl }}" onclick="event.stopPropagation()">{{ $aln?->name ?? '—' }}</a>
                        @else{{ $aln?->name ?? '—' }}@endif
                    </div>
                    <div class="so-fb-fn">{{ $fn }}</div>
                </div>

                <div class="so-fb-td">
                    <div class="so-fb-route-badge">
                        <div class="so-fb-ap-badge">
                            <div class="so-fb-ap-code">
                                {{ $f->dpt_airport_id }}
                                @if($dep && $dep->country)
                                    <span class="fi fi-{{ strtolower($dep->country) }}" style="font-size:.6rem;vertical-align:middle;margin-left:2px;"></span>
                                @endif
                            </div>
                            @if($dep)<div class="so-fb-ap-name">{{ $dep->name }}</div>@endif
                        </div>
                        <div class="so-fb-ap-badge">
                            <div class="so-fb-ap-code">
                                {{ $f->arr_airport_id }}
                                @if($arr && $arr->country)
                                    <span class="fi fi-{{ strtolower($arr->country) }}" style="font-size:.6rem;vertical-align:middle;margin-left:2px;"></span>
                                @endif
                            </div>
                            @if($arr)<div class="so-fb-ap-name">{{ $arr->name }}</div>@endif
                        </div>
                    </div>
                </div>

                @if($showDptTime)
                <div class="so-fb-td">
                    <div class="so-fb-time">{{ FlightBoardService::fmtTime($f->dpt_time) }}</div>
                    <div class="so-fb-utc">UTC</div>
                </div>
                @endif

                @if($showArrTime)
                <div class="so-fb-td">
                    <div class="so-fb-time so-fb-arr-time">{{ FlightBoardService::fmtTime($f->arr_time) }}</div>
                    <div class="so-fb-utc">UTC</div>
                </div>
                @endif

                @if($showFlightTime)<div class="so-fb-td r"><div class="so-fb-dur">{{ $ft_txt ?? '—' }}</div></div>@endif
                @if($showDistance)<div class="so-fb-td r"><div class="so-fb-dist">{{ $dist_txt ?? '—' }}</div></div>@endif

                <div class="so-fb-td c">
                    <div class="so-fb-type-stack">
                        @if($typeTxt)<span class="so-fb-tag so-fb-tag-{{ $typeCol }}">{{ $typeTxt }}</span>
                        @else<span style="color:var(--ap-muted);">—</span>@endif

                        @if($showBookingStatus && !empty($f->so_bid_blocked))
                            <span class="so-fb-tag so-fb-tag-amber" style="font-size:.58rem;">Bid locked</span>
                        @elseif($showBookingStatus && !empty($f->so_bid_own))
                            <span class="so-fb-tag so-fb-tag-green" style="font-size:.58rem;">Your bid</span>
                        @endif

                        @if($showBookingStatus && isset($f->so_has_bookable_aircraft) && $f->so_has_bookable_aircraft === false)
                            <span class="so-fb-tag so-fb-tag-cyan" style="font-size:.58rem;">No AC @ DEP</span>
                        @endif
                    </div>
                </div>

                <div class="so-fb-td c">
                    <a class="so-btn so-btn-primary" href="{{ $showUrl }}" onclick="event.stopPropagation()" style="padding:4px 9px;font-size:.7rem;">
                        ↗ {{ __('skyops::skyops.dep_view') }}
                    </a>
                </div>

            </div>
        </div>

        {{-- Detail Row --}}
        <div class="so-fb-detail" id="{{ $uid }}-d">
            @if($aln)
                <div>
                    <div class="so-fb-dg-label">Airline</div>
                    <div class="so-fb-dg-val">
                        @if($alnUrl)<a href="{{ $alnUrl }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">{{ $aln->name }}</a>
                        @else{{ $aln->name }}@endif
                    </div>
                </div>
            @endif
            @if($dep)
                <div>
                    <div class="so-fb-dg-label">{{ __('skyops::skyops.dep_departure') }}</div>
                    <div class="so-fb-dg-val">{{ $dep->name }} <span style="color:var(--ap-muted);margin-left:5px;">({{ $f->dpt_airport_id }})</span></div>
                </div>
            @endif
            @if($arr)
                <div>
                    <div class="so-fb-dg-label">{{ __('skyops::skyops.dep_arrival') }}</div>
                    <div class="so-fb-dg-val">{{ $arr->name }} <span style="color:var(--ap-muted);margin-left:5px;">({{ $f->arr_airport_id }})</span></div>
                </div>
            @endif
            @if($aln)
                <div>
                    <div class="so-fb-dg-label">{{ __('skyops::skyops.col_aircraft') }}</div>
                    @if(isset($f->aircraft_types) && $f->aircraft_types->isNotEmpty())
                        <div class="so-fb-dg-val">{{ $f->aircraft_types->implode(' · ') }}</div>
                    @else
                        <div class="so-fb-dg-val" style="color:var(--ap-muted);">{{ __('skyops::skyops.dep_no_types') }}</div>
                    @endif
                </div>
            @endif
            @if($f->route_code || $f->route_leg)
                <div>
                    <div class="so-fb-dg-label">Route-Code / Leg</div>
                    <div class="so-fb-dg-val">
                        @if($f->route_code)<span>C.{{ $f->route_code }}</span>@endif
                        @if($f->route_code && $f->route_leg)<span style="color:var(--ap-muted);margin:0 4px;">·</span>@endif
                        @if($f->route_leg)<span>L.{{ $f->route_leg }}</span>@endif
                    </div>
                </div>
            @endif
            @if($typeTxt)
                <div>
                    <div class="so-fb-dg-label">{{ __('skyops::skyops.dep_flight_type') }}</div>
                    <div class="so-fb-dg-val">
                        <span class="so-fb-tag so-fb-tag-{{ $typeCol }}" style="font-size:.72rem;">{{ $typeTxt }}</span>
                    </div>
                </div>
            @endif
            @if($showBookingStatus && $respectPhpvmsSettings)
                <div>
                    <div class="so-fb-dg-label">Booking</div>
                    <div class="so-fb-dg-val">
                        @if(!empty($f->so_bid_blocked))
                            <span class="so-fb-tag so-fb-tag-amber" style="font-size:.72rem;">Bid locked by another pilot</span>
                        @elseif(!empty($f->so_bid_own))
                            <span class="so-fb-tag so-fb-tag-green" style="font-size:.72rem;">You already have a bid</span>
                        @else
                            <span class="so-fb-tag so-fb-tag-green" style="font-size:.72rem;">Bid available</span>
                        @endif
                        @if(isset($f->so_has_bookable_aircraft) && $f->so_has_bookable_aircraft === false)
                            <span class="so-fb-tag so-fb-tag-cyan" style="font-size:.72rem;margin-left:4px;">No eligible aircraft at departure</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    @empty
        <div class="so-fb-empty">
            🛬 {{ __('skyops::skyops.no_results') }}
        </div>
    @endforelse
</div>

@if($flights->hasPages())
    <div class="mt-3 d-flex justify-content-center">{{ $flights->links() }}</div>
@endif
<div style="font-size:.65rem;color:var(--ap-muted);margin-top:8px;">
    ℹ️ {{ __('skyops::skyops.sort_hint') }}
</div>

<script>
/* Row Expand */
function soFbToggle(uid){
    var row=document.getElementById(uid);
    var det=document.getElementById(uid+'-d');
    var open=det.classList.contains('open');
    det.classList.toggle('open',!open);
    row.classList.toggle('open',!open);
}

/* Flight-time slider sync */
(function(){
    var slider=document.getElementById('soFbMinSlider');
    var minNum=document.getElementById('soFbMinNum');
    var maxNum=document.getElementById('soFbMaxNum');

    function updateGradient(val){
        var pct=(val/18)*100;
        slider.style.setProperty('--ft-slider-pct',pct.toFixed(1)+'%');
    }

    slider.addEventListener('input',function(){
        var v=parseFloat(slider.value)||0;
        minNum.value=v>0?v:'';
        updateGradient(v);
        syncChips(v,parseFloat(maxNum.value)||null);
    });

    minNum.addEventListener('input',function(){
        var v=Math.max(0,Math.min(18,parseFloat(minNum.value)||0));
        slider.value=v;
        updateGradient(v);
        syncChips(v,parseFloat(maxNum.value)||null);
    });

    maxNum.addEventListener('input',function(){
        syncChips(parseFloat(minNum.value)||0,parseFloat(maxNum.value)||null);
    });

    document.querySelectorAll('.so-fb-chip').forEach(function(chip){
        chip.addEventListener('click',function(){
            var min=parseFloat(chip.dataset.min)||0;
            var max=chip.dataset.max!==''?parseFloat(chip.dataset.max):'';
            slider.value=min;
            minNum.value=min>0?min:'';
            maxNum.value=max!==''?max:'';
            updateGradient(min);
            syncChips(min,max!==''?parseFloat(max):null);
            document.getElementById('soFbForm').submit();
        });
    });

    function syncChips(min,max){
        document.querySelectorAll('.so-fb-chip').forEach(function(c){
            var cm=parseFloat(c.dataset.min)||0;
            var cx=c.dataset.max!==''?parseFloat(c.dataset.max):null;
            var active=(cm===min&&(cx===null?max===null||max===undefined||isNaN(max):cx===max));
            c.classList.toggle('so-fb-chip-on',active);
        });
    }

    /* Type chips */
    document.querySelectorAll('.so-fb-type-chip').forEach(function(chip){
        chip.addEventListener('click',function(){
            var t=chip.dataset.type;
            document.getElementById('soFbTypeInput').value=t;
            document.querySelectorAll('.so-fb-type-chip').forEach(function(c){c.classList.toggle('so-fb-chip-on',c.dataset.type===t);});
            var url=new URL(window.location.href);
            if(t)url.searchParams.set('type',t);else url.searchParams.delete('type');
            window.location.href=url.toString();
        });
    });

    var initMin=parseFloat('{{ $minHVal }}')||0;
    updateGradient(initMin);
    syncChips(initMin,{{ $maxHVal !== '' ? $maxHVal : 'null' }});
})();
</script>

@endsection
