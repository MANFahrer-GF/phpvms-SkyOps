{{-- modules/SkyOps/Resources/views/pirep-list.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.pirep_list'))

@php
    use Modules\SkyOps\Helpers\SkyOpsHelper;
    use Modules\SkyOps\Helpers\PilotNameHelper;

    // URL helpers (kept in view — these are purely presentational routing)
    $airlineUrl  = fn($icao) => trim((string)$icao) !== '' ? url('/dairlines/' . rawurlencode($icao)) : '#';
    $aircraftUrl = fn($reg)  => trim((string)$reg)  !== '' ? url('/daircraft/' . rawurlencode($reg))  : '#';
    $pilotUrl    = fn($id)   => ($id && (int)$id > 0) ? url('/profile/' . rawurlencode((string)$id)) : '#';

    $pirepUrl = function($id) {
        if (\Route::has('frontend.pireps.show')) return route('frontend.pireps.show', $id);
        if (\Route::has('frontend.pireps.show-public')) return route('frontend.pireps.show-public', $id);
        if (\Route::has('pireps.show')) return route('pireps.show', $id);
        return url('/pireps/' . $id);
    };

    // Formatting helpers
    $fmtDate = fn($dt) => $dt ? \Modules\SkyOps\Helpers\SkyOpsHelper::fmtDateTime($dt) : '—';
    $fmtHhmm = function($mins) {
        $m = max(0, (int)round($mins ?? 0));
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    };
    $fmtNum0 = fn($n) => is_null($n) ? '—' : \Modules\SkyOps\Helpers\SkyOpsHelper::number((float)$n);

    // Review state badge [text, css-class]
    $reviewBadge = fn($state) => match((int)($state ?? 0)) {
        1 => ['Pending',   'so-phase'],
        2 => ['Accepted',  'so-phase so-phase-accepted'],
        3 => ['Rejected',  'so-phase so-phase-rejected'],
        5 => ['Diverted',  'so-phase'],
        6 => ['Cancelled', 'so-phase so-phase-rejected'],
        default => ['—', 'so-phase so-phase-arrived'],
    };

    // Sort helpers
    $curSort = $filters['sort'] ?? 'datumzeit';
    $curDir  = $filters['dir'] ?? 'desc';
    $nextDir = fn($col) => ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
    $sortUrl = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nextDir($col)]);
    $sortIcon = fn($col) => ($curSort === $col) ? ($curDir === 'asc' ? ' ↑' : ' ↓') : '';

    // Date display
    $fromInput = $filters['from'] ?? now()->subDays(30)->format('Y-m-d');
    $toInput   = $filters['to'] ?? now()->format('Y-m-d');
    $displayFrom = \Modules\SkyOps\Helpers\SkyOpsHelper::fmtDate($fromInput);
    $displayTo   = \Modules\SkyOps\Helpers\SkyOpsHelper::fmtDate($toInput);
@endphp

@section('skyops-content')

{{-- PAGE HEADER --}}
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <div style="font-weight:800;font-size:1.6rem;letter-spacing:-.02em;color:var(--ap-text-head);display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;">
            ✈️ {{ __('skyops::skyops.pirep_list') }}
            <span style="font-size:.8rem;color:var(--ap-muted);font-weight:400;">{{ $displayFrom }} – {{ $displayTo }}</span>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2">
            <span style="font-size:.8rem;color:var(--ap-muted);">
                <strong style="color:var(--ap-text);">{{ $completedFlights->total() }}</strong> {{ __('skyops::skyops.col_flights') }}
            </span>
            @if($activeFlights->count() > 0)
                <span class="so-live-badge">
                    <span class="so-live-dot"></span> {{ $activeFlights->count() }} LIVE
                </span>
            @endif
        </div>
    </div>
</div>

{{-- FILTER CARD --}}
<div class="so-card" style="margin-bottom:1.25rem;">
    <div class="so-card-title" style="display:flex;align-items:center;gap:8px;">
        🔍 {{ __('skyops::skyops.filter') }}
    </div>
    <form method="get" id="soFilterForm">
        @csrf
        <div class="so-filter-row" style="margin-bottom:.75rem;">
            <div class="so-filter-group">
                <span class="so-filter-label">{{ __('skyops::skyops.from') }}</span>
                <input type="date" class="so-input" name="from" value="{{ e($fromInput) }}" style="width:150px;">
            </div>
            <div class="so-filter-group">
                <span class="so-filter-label">{{ __('skyops::skyops.to') }}</span>
                <input type="date" class="so-input" name="to" value="{{ e($toInput) }}" style="width:150px;">
            </div>
            <div class="so-filter-group" style="flex:1;min-width:200px;">
                <span class="so-filter-label">{{ __('skyops::skyops.search') }}</span>
                <input type="text" class="so-input" name="q" value="{{ e($filters['q'] ?? '') }}" placeholder="{{ __('skyops::skyops.search_placeholder') }}">
            </div>
            <div class="so-filter-group">
                <span class="so-filter-label">{{ __('skyops::skyops.source') }}</span>
                <select name="source" class="so-select" style="min-width:120px;">
                    <option value="">{{ __('skyops::skyops.all') }}</option>
                    @foreach($sources as $s)
                        <option value="{{ $s }}" @selected(($filters['source'] ?? '') === (string)$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="so-filter-group">
                <span class="so-filter-label">{{ __('skyops::skyops.network') }}</span>
                <select name="network" class="so-select" style="min-width:120px;">
                    <option value="">{{ __('skyops::skyops.all') }}</option>
                    @foreach($networks as $n)
                        <option value="{{ $n }}" @selected(($filters['network'] ?? '') === (string)$n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="so-btn so-btn-primary" type="submit">{{ __('skyops::skyops.apply') }}</button>
            <a class="so-btn so-btn-ghost" href="{{ route('skyops.pireps') }}">{{ __('skyops::skyops.reset') }}</a>
            <button type="button" class="so-quick-btn" id="soToday">{{ __('skyops::skyops.today') }}</button>
            <button type="button" class="so-quick-btn" id="soLast30">{{ __('skyops::skyops.last_30') }}</button>
            <button type="button" class="so-btn so-btn-ghost" id="soCsv" style="margin-left:auto;color:var(--ap-green);border-color:rgba(63,185,80,.3);">
                📥 CSV Export
            </button>
        </div>
        <input type="hidden" name="sort" value="{{ e($curSort) }}">
        <input type="hidden" name="dir"  value="{{ e($curDir) }}">
    </form>
</div>

{{-- LIVE FLIGHTS --}}
@if($activeFlights->count() > 0)
<div class="so-card so-card-live" style="margin-bottom:1.25rem;">
    <div class="so-card-title" style="display:flex;align-items:center;gap:8px;">
        <span class="so-live-badge"><span class="so-live-dot"></span> LIVE</span>
        {{ __('skyops::skyops.live_flights') }}
        <span class="so-count">{{ $activeFlights->count() }}</span>
    </div>
    <div class="so-table-wrap">
        <table class="so-table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('skyops::skyops.col_date') }}</th>
                    <th>{{ __('skyops::skyops.col_flight') }}</th>
                    <th>{{ __('skyops::skyops.col_dep') }}</th>
                    <th>{{ __('skyops::skyops.col_arr') }}</th>
                    <th>{{ __('skyops::skyops.col_pilot') }}</th>
                    <th>{{ __('skyops::skyops.col_airline') }}</th>
                    <th>{{ __('skyops::skyops.col_reg') }}</th>
                    <th>{{ __('skyops::skyops.col_status') }}</th>
                    <th style="text-align:center;">{{ __('skyops::skyops.col_block') }}</th>
                    <th style="text-align:center;">{{ __('skyops::skyops.col_air') }}</th>
                    <th style="text-align:center;">{{ __('skyops::skyops.col_source') }}</th>
                    <th style="text-align:center;">{{ __('skyops::skyops.col_network') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeFlights as $r)
                <tr class="so-row-live">
                    <td><a href="{{ $pirepUrl($r->id) }}" target="_blank" class="so-pl-link" title="{{ __('skyops::skyops.open_pirep') }}">↗</a></td>
                    <td style="font-size:.75rem;">{{ $fmtDate($r->created_at ?? null) }}</td>
                    <td><a href="{{ $pirepUrl($r->id) }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;font-size:.78rem;">{{ SkyOpsHelper::flightNumber($r) }}</a></td>
                    <td><span class="so-badge so-badge-warning" style="font-size:.72rem;">{{ $r->dpt_airport->icao ?? '—' }}</span></td>
                    <td><span class="so-badge so-badge-warning" style="font-size:.72rem;">{{ $r->arr_airport->icao ?? '—' }}</span></td>
                    <td><a href="{{ $pilotUrl($r->user_id) }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;">{{ PilotNameHelper::format($r->user->name ?? null, $r->user->callsign ?? null) }}</a></td>
                    <td>
                        <a href="{{ $airlineUrl($r->airline->icao ?? '') }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;" title="{{ $r->airline->name ?? '' }}">
                            {{ $r->airline->icao ?? '—' }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ $aircraftUrl($r->aircraft->registration ?? '') }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;" title="{{ $r->aircraft->subfleet->type ?? '' }}">
                            {{ $r->aircraft->registration ?? '—' }}
                        </a>
                    </td>
                    <td><span class="so-phase so-phase-live">{{ SkyOpsHelper::phaseEmoji($r->status ?? '') }} {{ SkyOpsHelper::phaseLabel($r->status ?? '') }}</span></td>
                    @php
                        // Block: if no block times, calculate elapsed since departure
                        $blockVal = SkyOpsHelper::blockMinutes($r);
                        if ($blockVal === 0 && $r->created_at) {
                            $blockVal = (int) \Carbon\Carbon::parse($r->created_at)->diffInMinutes(now());
                        }
                        $airVal = (int)($r->flight_time ?? 0);
                    @endphp
                    <td style="text-align:center;font-size:.75rem;font-weight:700;">{{ $fmtHhmm($blockVal) }}</td>
                    <td style="text-align:center;font-size:.75rem;font-weight:700;">{{ $airVal > 0 ? $fmtHhmm($airVal) : '—' }}</td>
                    <td style="text-align:center;"><span class="so-badge so-badge-info">{{ $r->source_name ?? '—' }}</span></td>
                    @php $net = SkyOpsHelper::network($r); @endphp
                    <td style="text-align:center;"><span class="so-badge {{ SkyOpsHelper::networkClass($net) }}">{{ SkyOpsHelper::networkLabel($net) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- COMPLETED FLIGHTS --}}
<div class="so-card">
    <div class="so-card-title" style="display:flex;align-items:center;gap:8px;">
        📋 {{ __('skyops::skyops.completed_flights') }}
        <span class="so-count">{{ $completedFlights->total() }}</span>
    </div>
    <div class="so-table-wrap">
        <table class="so-table" id="soMainTable">
            <thead>
                <tr>
                    <th></th>
                    <th><a href="{{ $sortUrl('datumzeit') }}" class="{{ $curSort==='datumzeit' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_date') }}{!! $sortIcon('datumzeit') !!}</a></th>
                    <th><a href="{{ $sortUrl('flight') }}" class="{{ $curSort==='flight' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_flight') }}{!! $sortIcon('flight') !!}</a></th>
                    <th><a href="{{ $sortUrl('dep') }}" class="{{ $curSort==='dep' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_dep') }}{!! $sortIcon('dep') !!}</a></th>
                    <th><a href="{{ $sortUrl('arr') }}" class="{{ $curSort==='arr' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_arr') }}{!! $sortIcon('arr') !!}</a></th>
                    <th><a href="{{ $sortUrl('pilot') }}" class="{{ $curSort==='pilot' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_pilot') }}{!! $sortIcon('pilot') !!}</a></th>
                    <th><a href="{{ $sortUrl('airline') }}" class="{{ $curSort==='airline' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_airline') }}{!! $sortIcon('airline') !!}</a></th>
                    <th><a href="{{ $sortUrl('reg') }}" class="{{ $curSort==='reg' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_reg') }}{!! $sortIcon('reg') !!}</a></th>
                    <th><a href="{{ $sortUrl('phase') }}" class="{{ $curSort==='phase' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_status') }}{!! $sortIcon('phase') !!}</a></th>
                    <th><a href="{{ $sortUrl('review') }}" class="{{ $curSort==='review' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_review') }}{!! $sortIcon('review') !!}</a></th>
                    <th style="text-align:center;"><a href="{{ $sortUrl('block') }}" class="{{ $curSort==='block' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_block') }}{!! $sortIcon('block') !!}</a></th>
                    <th style="text-align:center;"><a href="{{ $sortUrl('air') }}" class="{{ $curSort==='air' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_air') }}{!! $sortIcon('air') !!}</a></th>
                    <th style="text-align:right;"><a href="{{ $sortUrl('landing') }}" class="{{ $curSort==='landing' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_landing') }}{!! $sortIcon('landing') !!}</a></th>
                    <th style="text-align:right;"><a href="{{ $sortUrl('fuel') }}" class="{{ $curSort==='fuel' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_fuel') }}{!! $sortIcon('fuel') !!}</a></th>
                    <th style="text-align:right;"><a href="{{ $sortUrl('dist') }}" class="{{ $curSort==='dist' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_distance') }}{!! $sortIcon('dist') !!}</a></th>
                    <th style="text-align:center;"><a href="{{ $sortUrl('source') }}" class="{{ $curSort==='source' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_source') }}{!! $sortIcon('source') !!}</a></th>
                    <th style="text-align:center;"><a href="{{ $sortUrl('network') }}" class="{{ $curSort==='network' ? 'so-sort-active' : '' }}">{{ __('skyops::skyops.col_network') }}{!! $sortIcon('network') !!}</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse($completedFlights as $r)
                <tr>
                    <td><a href="{{ $pirepUrl($r->id) }}" target="_blank" class="so-pl-link" title="{{ __('skyops::skyops.open_pirep') }}">↗</a></td>
                    <td style="font-size:.75rem;">{{ $fmtDate($r->created_at ?? null) }}</td>
                    <td><a href="{{ $pirepUrl($r->id) }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;font-size:.78rem;letter-spacing:.03em;">{{ SkyOpsHelper::flightNumber($r) }}</a></td>
                    <td><span class="so-badge so-badge-warning" style="font-size:.72rem;" title="{{ $r->dpt_airport->name ?? '' }}">{{ $r->dpt_airport->icao ?? '—' }}</span></td>
                    <td><span class="so-badge so-badge-warning" style="font-size:.72rem;" title="{{ $r->arr_airport->name ?? '' }}">{{ $r->arr_airport->icao ?? '—' }}</span></td>
                    <td><a href="{{ $pilotUrl($r->user_id) }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;" title="{{ $r->user->callsign ?? '' }}">{{ PilotNameHelper::format($r->user->name ?? null, $r->user->callsign ?? null) }}</a></td>
                    <td>
                        <a href="{{ $airlineUrl($r->airline->icao ?? '') }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;" title="{{ $r->airline->name ?? '' }}">
                            {{ $r->airline->icao ?? '—' }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ $aircraftUrl($r->aircraft->registration ?? '') }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;" title="{{ $r->aircraft->subfleet->type ?? '' }}">
                            {{ $r->aircraft->registration ?? '—' }}
                        </a>
                    </td>
                    <td><span class="so-phase so-phase-arrived">{{ SkyOpsHelper::phaseEmoji($r->status ?? '') }} {{ SkyOpsHelper::phaseLabel($r->status ?? '') }}</span></td>
                    <td>@php [$rvTxt,$rvCls] = $reviewBadge($r->state ?? 0); @endphp<span class="{{ $rvCls }}">{{ $rvTxt }}</span></td>
                    @php
                        $bm = SkyOpsHelper::blockMinutes($r);
                        $am = (int)($r->flight_time ?? 0);
                        if ($bm > 0 && $am > 0 && $bm < $am) {
                            [$bm, $am] = [$am, $bm];
                        }
                    @endphp
                    <td style="text-align:center;font-size:.75rem;font-weight:700;">{{ $fmtHhmm($bm) }}</td>
                    <td style="text-align:center;font-size:.75rem;font-weight:700;">{{ $fmtHhmm($am) }}</td>
                    @php $lr = SkyOpsHelper::landingRate((float)($r->landing_rate ?? 0)); @endphp
                    <td class="{{ $lr['class'] }}" style="text-align:right;">
                        <span class="so-pl-landing">
                            <span class="so-pl-lr-icon">{{ $lr['emoji'] }}</span>
                            <span class="so-pl-lr-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number((float)($r->landing_rate ?? 0)) }}</span>
                            <span class="so-pl-lr-unit">fpm</span>
                        </span>
                    </td>
                    <td style="text-align:right;font-size:.75rem;">{{ $r->fuel_used ? $r->fuel_used->local(0) : '—' }}</td>
                    <td style="text-align:right;font-size:.75rem;">{{ $r->distance ? $r->distance->local(0) : '—' }}</td>
                    <td style="text-align:center;"><span class="so-badge so-badge-info">{{ $r->source_name ?? '—' }}</span></td>
                    @php $net = SkyOpsHelper::network($r); @endphp
                    <td style="text-align:center;"><span class="so-badge {{ SkyOpsHelper::networkClass($net) }}">{{ SkyOpsHelper::networkLabel($net) }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="so-empty">
                        ✈️ {{ __('skyops::skyops.no_flights_period') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($completedFlights->hasPages())
    <div class="so-pagination" style="padding:1rem;border-top:1px solid var(--ap-border);">
        {{ $completedFlights->links() }}
    </div>
    @endif
</div>

{{-- JavaScript --}}
<script>
(function() {
    const form = document.getElementById('soFilterForm');
    if (!form) return;
    const iso = d => d.toISOString().slice(0,10);

    document.getElementById('soToday')?.addEventListener('click', () => {
        const n = new Date();
        form.querySelector('[name=from]').value = iso(n);
        form.querySelector('[name=to]').value   = iso(n);
        form.submit();
    });
    document.getElementById('soLast30')?.addEventListener('click', () => {
        const n = new Date(), s = new Date();
        s.setDate(n.getDate() - 29);
        form.querySelector('[name=from]').value = iso(s);
        form.querySelector('[name=to]').value   = iso(n);
        form.submit();
    });
    document.getElementById('soCsv')?.addEventListener('click', () => {
        const tbl = document.getElementById('soMainTable');
        if (!tbl) return;
        const rows = [];
        const headers = Array.from(tbl.tHead.rows[0].cells)
            .map(th => th.innerText.trim().replace(/[\n↑↓]/g,'').trim());
        rows.push(headers.join(';'));
        tbl.querySelectorAll('tbody tr').forEach(tr => {
            const cells = Array.from(tr.cells)
                .map((td,i) => i===0 ? '' : td.innerText.trim().replace(/\n/g,' ').replace(/;/g,','));
            rows.push(cells.join(';'));
        });
        const blob = new Blob([rows.join('\n')], {type:'text/csv;charset=utf-8;'});
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url;
        a.download = 'pireps_' + iso(new Date()) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
})();
</script>
@endsection
