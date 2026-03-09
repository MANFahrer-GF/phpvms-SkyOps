{{-- modules/SkyOps/Resources/views/fleet.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.fleet'))

@php
    use Modules\SkyOps\Services\FleetService;

    // --- View-only helpers (presentational, no DB) ---

    $logoUrlFor = fn($row) => FleetService::logoUrl($row);
    $defaultLogo = asset('SPTheme/images/airlines/_default.png');

    $statusBadge = function($code) {
        return match(strtoupper((string)$code)) {
            'A' => [__('skyops::skyops.fleet_status_active'),      'so-ft-badge-active'],
            'M' => [__('skyops::skyops.fleet_status_maintenance'), 'so-ft-badge-maint'],
            'S' => [__('skyops::skyops.fleet_status_storage'),     'so-ft-badge-storage'],
            'R' => [__('skyops::skyops.fleet_status_retired'),     'so-ft-badge-retired'],
            'C' => [__('skyops::skyops.fleet_status_scrapped'),    'so-ft-badge-retired'],
            'X' => [__('skyops::skyops.fleet_status_scrapped'),    'so-ft-badge-retired'],
            default => ['—',       'so-ft-badge-soft'],
        };
    };

    $stateBadge = function($n) {
        if ($n === 0 || $n === '0') return ['On Ground', 'so-ft-badge-ground'];
        if ($n === 1 || $n === '1') return ['On Ground', 'so-ft-badge-ground'];
        if ($n === 2 || $n === '2') return ['In Air',     'so-ft-badge-inflight'];
        return ['—', 'so-ft-badge-soft'];
    };

    $airportTip = fn($icao) => $icao ? e($airportNames[$icao] ?? '') : '';

    // Filter state
    $f_airline = strtoupper(trim($filters['airline'] ?? ''));
    $f_icao    = strtoupper(trim($filters['icao'] ?? ''));
    $f_subtype = trim($filters['subtype'] ?? '');
    $f_reg     = trim($filters['reg'] ?? '');
    $f_min     = max(0, (int)($filters['min'] ?? 0));
    $f_order   = $filters['order'] ?? 'time_desc';

    // Sort URL helpers
    $baseParams = request()->except('order', 'page');
    $sortUrl = function(string $key) use ($baseParams, $f_order) {
        $dir = ($f_order === $key . '_asc') ? 'desc' : 'asc';
        return url()->current() . '?' . http_build_query(array_merge($baseParams, ['order' => $key . '_' . $dir]));
    };
    $sortIcon = function(string $key) use ($f_order) {
        if ($f_order === $key . '_asc')  return ' ↑';
        if ($f_order === $key . '_desc') return ' ↓';
        return '';
    };
    $sortActive = fn(string $key) => str_starts_with($f_order, $key) ? 'so-sort-active' : '';

    $activeFilters = collect([$f_airline, $f_icao, $f_subtype, $f_reg])->filter(fn($v) => $v !== '')->count()
        + ($f_min > 0 ? 1 : 0);
@endphp

@section('skyops-content')

{{-- Fleet-specific styles (extend shared so-* system) --}}
<style>
/* Fleet rank badges */
/* Fleet badges */

/* Fleet status badges */
.so-ft-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-family:var(--ap-font-mono);font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;border:1px solid transparent}
.so-ft-badge-active{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:#86efac}
.so-ft-badge-maint{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.3);color:#fde68a}
.so-ft-badge-storage{background:rgba(125,133,144,.15);border-color:rgba(125,133,144,.3);color:var(--ap-muted)}
.so-ft-badge-retired{background:rgba(71,85,105,.2);border-color:rgba(71,85,105,.3);color:var(--ap-muted)}
.so-ft-badge-inflight{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.3);color:#fde68a}
.so-ft-badge-ground{background:rgba(14,165,233,.15);border-color:rgba(14,165,233,.3);color:#67e8f9}
.so-ft-badge-soft{background:rgba(125,133,144,.1);border-color:rgba(125,133,144,.2);color:var(--ap-muted)}

/* Fleet badges — light mode */
html.ap-light .so-ft-badge-active{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.25)}
html.ap-light .so-ft-badge-maint{color:#92400e;background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.25)}
html.ap-light .so-ft-badge-storage{color:#475569;background:rgba(100,116,139,.08);border-color:rgba(100,116,139,.2)}
html.ap-light .so-ft-badge-retired{color:#64748b;background:rgba(71,85,105,.08);border-color:rgba(71,85,105,.2)}
html.ap-light .so-ft-badge-inflight{color:#92400e;background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.25)}
html.ap-light .so-ft-badge-ground{color:#0369a1;background:rgba(14,165,233,.1);border-color:rgba(14,165,233,.25)}
html.ap-light .so-ft-badge-soft{color:#64748b;background:rgba(100,116,139,.06);border-color:rgba(100,116,139,.15)}

/* Fleet registration link */
.so-ft-reg-link{
    display:inline-flex;align-items:center;gap:5px;white-space:nowrap;
    background:var(--ap-surface);border:1px solid var(--ap-border);
    color:var(--ap-text-head);border-radius:6px;padding:4px 10px;
    text-decoration:none;font-size:.75rem;font-weight:600;transition:all .15s;
    font-variant-numeric:tabular-nums;font-family:var(--ap-font-mono);
}
.so-ft-reg-link:hover{background:rgba(88,166,255,.1);border-color:var(--ap-blue);color:var(--ap-blue)}
.so-ft-reg-cell{cursor:pointer;white-space:nowrap}

/* Airline cell */
.so-ft-aircell{display:flex;align-items:center;gap:10px}
.so-ft-airlogo-box{width:80px;height:32px;flex-shrink:0;background:#fff;border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden}
html.ap-light .so-ft-airlogo-box{border:1px solid rgba(0,0,0,.08)}
.so-ft-airlogo-box img{max-width:72px;max-height:26px;object-fit:contain}
.so-ft-aircode{font-family:var(--ap-font-mono);font-size:.75rem;font-weight:600;letter-spacing:.05em}
.so-ft-airline-name{font-size:.67rem;color:var(--ap-muted);display:block;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px}

/* ICAO tooltip */
.so-ft-icao-tip{cursor:help;font-size:.75rem;border-bottom:1px dotted var(--ap-muted);color:var(--ap-cyan)}
html.ap-light .so-ft-icao-tip{color:#0369a1}

/* Mono cells */
.so-ft-mono{font-variant-numeric:tabular-nums lining-nums;font-size:.8rem}
</style>

{{-- PAGE HEADER CARD --}}
<div class="so-card so-page-header">
    <div class="so-page-title">
        ✈️ {{ __('skyops::skyops.fleet') }}
    </div>
    <div class="so-page-stats">
        <span><strong>{{ $aircraft->count() }}</strong> {{ __('skyops::skyops.col_aircraft') }}</span>
        @if($activeFilters > 0)
            <a href="{{ url()->current() }}" class="so-btn so-btn-ghost so-btn-sm" style="color:var(--ap-red);border-color:rgba(248,81,73,.3);">
                ✕ Filter zurücksetzen
                @if($activeFilters > 1)({{ $activeFilters }})@endif
            </a>
        @endif
    </div>
</div>

{{-- FILTER CARD --}}
<div class="so-card" style="margin-bottom:1.25rem;">
    <div class="so-card-title" style="display:flex;align-items:center;gap:8px;">
        {{ __('skyops::skyops.filter') }}
    </div>
    <form method="get" id="soFleetForm" autocomplete="off">
        @csrf
        <input type="hidden" name="order" value="{{ $f_order }}">
        <div class="so-filter-row" style="margin-bottom:.75rem;">
            <div class="so-filter-group">
                <label class="so-filter-label">Airline</label>
                <input class="so-input" list="dl-airlines" name="airline"
                       value="{{ $f_airline }}" placeholder="e.g. DLH" style="width:140px;">
                <datalist id="dl-airlines">
                    @foreach($airlines as $a)<option value="{{ $a->icao }}">{{ $a->name }}</option>@endforeach
                </datalist>
            </div>
            <div class="so-filter-group">
                <label class="so-filter-label">{{ __('skyops::skyops.fleet_icao_type') }}</label>
                <input class="so-input" list="dl-icaos" name="icao"
                       value="{{ $f_icao }}" placeholder="e.g. A20N" style="width:120px;">
                <datalist id="dl-icaos">
                    @foreach($icaoTypes as $ic)<option value="{{ $ic }}"></option>@endforeach
                </datalist>
            </div>
            <div class="so-filter-group">
                <label class="so-filter-label">Subfleet</label>
                <input class="so-input" list="dl-subtypes" name="subtype"
                       value="{{ $f_subtype }}" placeholder="e.g. A321-IAE" style="width:160px;">
                <datalist id="dl-subtypes">
                    @foreach($subtypes as $s)<option value="{{ $s }}"></option>@endforeach
                </datalist>
            </div>
            <div class="so-filter-group">
                <label class="so-filter-label">{{ __('skyops::skyops.fleet_registration') }}</label>
                <input class="so-input" list="dl-regs" name="reg"
                       value="{{ $f_reg }}" placeholder="e.g. D-AIXP" style="width:140px;">
                <datalist id="dl-regs"></datalist>
            </div>
            <div class="so-filter-group">
                <label class="so-filter-label">{{ __('skyops::skyops.fleet_min_flights') }}</label>
                <input type="number" class="so-input" name="min"
                       value="{{ $f_min }}" min="0" style="width:80px;">
            </div>
            <div class="so-filter-group" style="justify-content:flex-end;">
                <button class="so-btn so-btn-primary" type="submit">✓ {{ __('skyops::skyops.search_btn') }}</button>
            </div>
            <div class="so-filter-group" style="justify-content:flex-end;">
                <a class="so-btn so-btn-ghost" href="{{ url()->current() }}">✕ Reset</a>
            </div>
        </div>
    </form>
    <div style="font-size:.68rem;color:var(--ap-muted);margin-top:.5rem;">
        ℹ️ {{ __('skyops::skyops.sort_hint') }}
    </div>
</div>

{{-- FLEET TABLE --}}
<div class="so-card">
    <div class="so-card-title" style="display:flex;align-items:center;gap:8px;">
        ✈️ {{ __('skyops::skyops.fleet_all') }}
        <span class="so-count">{{ $aircraft->count() }}</span>
    </div>
    <div class="so-table-wrap">
        <table class="so-table" id="fleetTable">
            <thead>
                <tr>
                    <th style="text-align:right;width:40px;">#</th>
                    <th style="white-space:nowrap;"><a href="{{ $sortUrl('reg') }}" class="{{ $sortActive('reg') }}">Reg{!! $sortIcon('reg') !!}</a></th>
                    <th style="min-width:140px;"><a href="{{ $sortUrl('aircraft') }}" class="{{ $sortActive('aircraft') }}">Aircraft{!! $sortIcon('aircraft') !!}</a></th>
                    <th style="min-width:200px;"><a href="{{ $sortUrl('airline') }}" class="{{ $sortActive('airline') }}">Airline{!! $sortIcon('airline') !!}</a></th>
                    <th style="min-width:140px;"><a href="{{ $sortUrl('subfleet') }}" class="{{ $sortActive('subfleet') }}">Subfleet{!! $sortIcon('subfleet') !!}</a></th>
                    <th style="text-align:right;width:60px;"><a href="{{ $sortUrl('flights') }}" class="{{ $sortActive('flights') }}">{{ __('skyops::skyops.col_flights') }}{!! $sortIcon('flights') !!}</a></th>
                    <th style="text-align:right;width:90px;"><a href="{{ $sortUrl('time') }}" class="{{ $sortActive('time') }}">{{ __('skyops::skyops.col_time') }}{!! $sortIcon('time') !!}</a></th>
                    <th style="width:150px;">Status</th>
                    <th style="width:60px;"><a href="{{ $sortUrl('loc') }}" class="{{ $sortActive('loc') }}">Loc{!! $sortIcon('loc') !!}</a></th>
                    <th style="width:60px;"><a href="{{ $sortUrl('hub') }}" class="{{ $sortActive('hub') }}">Hub{!! $sortIcon('hub') !!}</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse($aircraft as $i => $r)
                    @php
                        $logo = $logoUrlFor($r);
                        [$st_text,  $st_cls]  = $statusBadge($r->ac_status);
                        [$st2_text, $st2_cls] = $stateBadge($r->ac_state);
                        $rankClass = '';
                    @endphp
                    <tr>
                        <td style="text-align:right;color:var(--ap-muted);font-size:.72rem;">
                            <span class="{{ $rankClass }}">{{ $i + 1 }}</span>
                        </td>
                        <td class="so-ft-reg-cell">
                            <a href="{{ url('/daircraft/' . $r->registration) }}" class="so-ft-reg-link">
                                ↗ {{ $r->registration }}
                            </a>
                        </td>
                        <td title="{{ $r->ac_name }}">
                            <span style="font-weight:600;color:var(--ap-text-head);">{{ $r->ac_name }}</span>
                            @if($r->ac_icao)
                                <span style="font-size:.68rem;color:var(--ap-muted);display:block;">{{ $r->ac_icao }}</span>
                            @endif
                        </td>
                        <td title="{{ $r->al_name }}">
                            <div class="so-ft-aircell">
                                <div class="so-ft-airlogo-box">
                                    <img loading="lazy" src="{{ $logo }}" alt="{{ $r->al_icao }}"
                                         onerror="this.onerror=null;this.src='{{ $defaultLogo }}'">
                                </div>
                                <div>
                                    <span class="so-ft-aircode">{{ $r->al_icao }}</span>
                                    <span class="so-ft-airline-name">{{ $r->al_name }}</span>
                                </div>
                            </div>
                        </td>
                        <td title="{{ $r->sf_name }}">
                            <span class="so-ft-mono" style="font-weight:500;">{{ $r->sf_type }}</span>
                            @if($r->sf_name && $r->sf_name !== $r->sf_type)
                                <span style="font-size:.67rem;color:var(--ap-muted);display:block;">{{ $r->sf_name }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;" class="so-ft-mono">
                            <strong>{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($r->cnt) }}</strong>
                        </td>
                        <td style="text-align:right;" class="so-ft-mono">
                            <strong>{{ sprintf('%d:%02d', intdiv((int)$r->mins, 60), (int)$r->mins % 60) }}</strong>
                        </td>
                        <td style="white-space:nowrap;">
                            <span class="so-ft-badge {{ $st_cls }}" style="min-width:46px;text-align:center;display:inline-block;">{{ $st_text }}</span>
                            <span class="so-ft-badge {{ $st2_cls }}" style="min-width:74px;text-align:center;display:inline-block;margin-left:4px;">{{ $st2_text }}</span>
                        </td>
                        <td>
                            @if($r->loc)
                                <span class="so-ft-icao-tip" data-bs-toggle="tooltip" data-bs-title="{{ $airportTip($r->loc) }}">{{ $r->loc }}</span>
                            @else
                                <span style="color:var(--ap-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($r->hub)
                                <span class="so-ft-icao-tip" data-bs-toggle="tooltip" data-bs-title="{{ $airportTip($r->hub) }}">{{ $r->hub }}</span>
                            @else
                                <span style="color:var(--ap-muted);">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="so-empty">
                            ✈️ Keine Flugzeuge gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Cascading Datalist JS --}}
<script>
(function(){
    var AIR_SF  = @json($pairs);
    var REGLIST = @json($registrations);
    var subtypeOptions = @json($subtypes);

    var $air = document.querySelector('#soFleetForm input[name="airline"]');
    var $sf  = document.querySelector('#soFleetForm input[name="subtype"]');
    var $dlS = document.getElementById('dl-subtypes');
    var $dlR = document.getElementById('dl-regs');

    function rebuildSubfleets(){
        if (!$dlS) return;
        var selA = ($air && $air.value || '').trim().toUpperCase();
        var ok = new Set((AIR_SF || []).filter(function(p){ return !selA || p.icao === selA; }).map(function(p){ return p.type; }));
        $dlS.innerHTML = '';
        (subtypeOptions || []).forEach(function(val){
            var o = document.createElement('option');
            o.value = val;
            if (ok.size && !ok.has(val)) o.disabled = true;
            $dlS.appendChild(o);
        });
    }

    function rebuildRegs(){
        if (!$dlR) return;
        var selA = ($air && $air.value || '').trim().toUpperCase();
        var selS = ($sf && $sf.value || '').trim();
        $dlR.innerHTML = '';
        (REGLIST || []).forEach(function(r){
            if ((!selA || r.icao === selA) && (!selS || r.subtype === selS)) {
                var o = document.createElement('option');
                o.value = r.reg;
                o.label = r.icao + ' \u00b7 ' + r.reg + ' \u00b7 ' + r.subtype;
                $dlR.appendChild(o);
            }
        });
    }

    if ($air) $air.addEventListener('input', function(){ rebuildSubfleets(); rebuildRegs(); });
    if ($sf)  $sf.addEventListener('input', function(){ rebuildRegs(); });

    document.addEventListener('DOMContentLoaded', function(){
        rebuildSubfleets();
        rebuildRegs();
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
                new bootstrap.Tooltip(el, { trigger: 'hover' });
            });
        }
    });

    document.querySelectorAll('.so-ft-reg-cell').forEach(function(td){
        td.addEventListener('click', function(){ var a = td.querySelector('a.so-ft-reg-link'); if(a) a.click(); });
    });
})();
</script>

@endsection
