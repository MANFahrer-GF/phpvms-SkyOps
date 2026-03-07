{{-- modules/SkyOps/Resources/views/dashboard.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.module_name'))

@section('skyops-content')
@php
    use Modules\SkyOps\Helpers\PilotNameHelper;
    use Modules\SkyOps\Helpers\SkyOpsHelper;
    use Modules\SkyOps\Helpers\UnitHelper;
@endphp

<style>
/* ── Dashboard — so-db-* prefix ── */

/* Hero */
.so-db-hero{position:relative;border-radius:16px;padding:28px 30px 22px;margin-bottom:20px;overflow:hidden;border:1px solid var(--ap-border);background:var(--ap-surface)}
.so-db-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(129,140,248,.08),transparent 70%);pointer-events:none}
.so-db-hero-title{font-weight:800;font-size:1.7rem;letter-spacing:-.03em;color:var(--ap-text-head);position:relative;display:flex;align-items:center;gap:12px}
.so-db-hero-sub{font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--ap-muted);margin-top:6px;position:relative}

/* Live pulse */
.so-db-live{display:inline-flex;align-items:center;gap:8px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:6px 14px;font-size:.78rem;font-weight:700;color:#fca5a5;position:relative;margin-left:auto}
html.ap-light .so-db-live{color:#dc2626;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2)}
.so-db-live-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;animation:so-db-pulse 1.5s infinite}
@keyframes so-db-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
.so-db-live-zero{background:rgba(148,163,184,.1);border-color:var(--ap-border);color:var(--ap-muted)}
.so-db-live-zero .so-db-live-dot{background:var(--ap-muted);animation:none}

/* Quick Stats row */
.so-db-qs{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;position:relative}
.so-db-qs-item{font-size:.76rem;color:var(--ap-muted);display:flex;align-items:center;gap:4px}
.so-db-qs-item b{color:var(--ap-text-head);font-weight:700;font-variant-numeric:tabular-nums}
.so-db-qs-sep{color:var(--ap-border);font-size:.55rem}

/* Navigation Cards */
.so-db-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-bottom:20px}
.so-db-card{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:20px 22px;position:relative;overflow:hidden;transition:transform .2s,border-color .2s,box-shadow .2s;cursor:pointer;text-decoration:none;display:block;color:inherit}
.so-db-card:hover{transform:translateY(-3px);border-color:rgba(88,166,255,.4);box-shadow:0 8px 24px rgba(0,0,0,.15);color:inherit;text-decoration:none}
html.ap-light .so-db-card:hover{border-color:rgba(59,130,246,.35);box-shadow:0 8px 24px rgba(0,0,0,.06)}
.so-db-card::before{content:'';position:absolute;inset:0;background:var(--card-grad,linear-gradient(135deg,rgba(59,130,246,.1),transparent 60%));pointer-events:none;transition:opacity .2s}
.so-db-card:hover::before{opacity:.7}
.so-db-card[data-c="pireps"]{--card-grad:linear-gradient(135deg,rgba(59,130,246,.14),transparent 60%)}
.so-db-card[data-c="fleet"]{--card-grad:linear-gradient(135deg,rgba(34,211,238,.14),transparent 60%)}
.so-db-card[data-c="pilots"]{--card-grad:linear-gradient(135deg,rgba(129,140,248,.14),transparent 60%)}
.so-db-card[data-c="airlines"]{--card-grad:linear-gradient(135deg,rgba(74,222,128,.12),transparent 60%)}
.so-db-card[data-c="departures"]{--card-grad:linear-gradient(135deg,rgba(251,191,36,.12),transparent 60%)}
.so-db-card-icon{position:absolute;right:16px;top:16px;font-size:2rem;opacity:.12}
.so-db-card-title{font-weight:700;font-size:.92rem;color:var(--ap-text-head);margin-bottom:4px;position:relative}
.so-db-card-desc{font-size:.72rem;color:var(--ap-muted);margin-bottom:14px;position:relative;line-height:1.4}
.so-db-card-metrics{display:flex;gap:16px;flex-wrap:wrap;position:relative}
.so-db-metric{display:flex;flex-direction:column;gap:1px}
.so-db-metric-val{font-weight:800;font-size:1.15rem;color:var(--ap-text-head);line-height:1;font-variant-numeric:tabular-nums}
.so-db-metric-lbl{font-size:.6rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--ap-muted)}
.so-db-card-arrow{position:absolute;bottom:18px;right:18px;font-size:.8rem;color:var(--ap-muted);transition:transform .2s,color .2s}
.so-db-card:hover .so-db-card-arrow{transform:translateX(4px);color:var(--ap-blue)}

/* Bottom two-column layout */
.so-db-bottom{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}

/* Panel shared */
.so-db-panel{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:18px 20px;position:relative;overflow:hidden}
.so-db-panel::before{content:'';position:absolute;inset:0;background:var(--panel-grad,linear-gradient(135deg,rgba(148,163,184,.04),transparent 60%));pointer-events:none}
.so-db-panel-title{font-weight:700;font-size:.86rem;color:var(--ap-text-head);margin-bottom:14px;position:relative;display:flex;align-items:center;gap:8px}
.so-db-panel-title .so-db-panel-badge{font-size:.62rem;font-weight:600;padding:2px 8px;border-radius:6px;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.2);color:var(--ap-blue);margin-left:auto}

/* Activity Table — uses so-table base */
.so-db-panel-wide{grid-column:1 / -1}
.so-db-act-table{width:100%;border-collapse:collapse;font-size:.78rem}
.so-db-act-table th{font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ap-muted);padding:6px 8px;border-bottom:2px solid var(--ap-border);text-align:left;white-space:nowrap}
.so-db-act-table th.r{text-align:right}
.so-db-act-table td{padding:7px 8px;border-bottom:1px solid var(--ap-border);vertical-align:middle;white-space:nowrap}
.so-db-act-table tr:last-child td{border-bottom:none}
.so-db-act-table tr:hover{background:rgba(59,130,246,.04)}
.so-db-act-time{font-size:.68rem;color:var(--ap-muted);font-variant-numeric:tabular-nums}
.so-db-act-aln{font-size:.7rem;font-weight:700;color:var(--ap-cyan)}
.so-db-act-flt{font-weight:600;color:var(--ap-text-head);font-size:.78rem}
.so-db-act-route{font-weight:600;color:var(--ap-text-head)}
.so-db-act-ac{font-size:.72rem;color:var(--ap-muted);white-space:nowrap}
.so-db-act-mono{font-variant-numeric:tabular-nums;font-size:.76rem}
.so-db-act-pilot{color:var(--ap-muted);overflow:hidden;text-overflow:ellipsis;max-width:140px}
.so-db-act-lr{font-variant-numeric:tabular-nums;font-weight:600;font-size:.76rem}

/* Mini leaderboard */
.so-db-lb{list-style:none;margin:0;padding:0;position:relative}
.so-db-lb-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--ap-border);font-size:.78rem}
.so-db-lb-item:last-child{border-bottom:none}
.so-db-lb-rank{font-weight:700;color:var(--ap-muted);font-size:.72rem;min-width:18px;text-align:right;font-variant-numeric:tabular-nums}
.so-db-lb-name{font-weight:600;color:var(--ap-text-head);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.so-db-lb-stat{font-variant-numeric:tabular-nums;font-size:.76rem;color:var(--ap-muted);white-space:nowrap}
.so-db-lb-stat b{color:var(--ap-text-head);font-weight:700}

/* Route list */
.so-db-rt{list-style:none;margin:0;padding:0;position:relative}
.so-db-rt-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--ap-border);font-size:.78rem}
.so-db-rt-item:last-child{border-bottom:none}
.so-db-rt-rank{font-weight:700;color:var(--ap-muted);font-size:.72rem;min-width:18px;text-align:right;font-variant-numeric:tabular-nums}
.so-db-rt-route{font-weight:700;color:var(--ap-text-head);font-size:.82rem;white-space:nowrap}
.so-db-rt-arrow{color:var(--ap-muted);font-size:.65rem;margin:0 2px}
.so-db-rt-stat{font-variant-numeric:tabular-nums;font-size:.76rem;color:var(--ap-muted);margin-left:auto;white-space:nowrap}
.so-db-rt-stat b{color:var(--ap-text-head);font-weight:700}

.so-db-empty{color:var(--ap-muted);font-size:.78rem;text-align:center;padding:20px 0;position:relative}

@media(max-width:900px){
    .so-db-bottom{grid-template-columns:1fr}
    .so-db-panel-wide{grid-column:auto}
}
@media(max-width:640px){
    .so-db-hero{padding:20px 16px}
    .so-db-hero-title{font-size:1.3rem;flex-wrap:wrap}
    .so-db-live{margin-left:0;margin-top:8px}
    .so-db-grid{grid-template-columns:1fr}
}
</style>

{{-- HERO --}}
<div class="so-db-hero">
    <div class="so-db-hero-title">
        ✈️ {{ __('skyops::skyops.module_name') }}
        <div class="so-db-live {{ $liveCount > 0 ? '' : 'so-db-live-zero' }}">
            <span class="so-db-live-dot"></span>
            {{ $liveCount }} {{ __('skyops::skyops.dash_live_flights') }}
        </div>
    </div>
    <div class="so-db-hero-sub">{{ __('skyops::skyops.dash_subtitle') }}</div>
    <div class="so-db-qs">
        <span class="so-db-qs-item">📋 <b>{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($pirepsToday) }}</b> {{ __('skyops::skyops.dash_today') }}</span>
        <span class="so-db-qs-sep">·</span>
        <span class="so-db-qs-item">📆 <b>{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($pirepsWeek) }}</b> {{ __('skyops::skyops.dash_this_week') }}</span>
        <span class="so-db-qs-sep">·</span>
        <span class="so-db-qs-item">⏱️ <b>{{ $hoursMonth }}h</b> {{ __('skyops::skyops.dash_this_month') }}</span>
        <span class="so-db-qs-sep">·</span>
        <span class="so-db-qs-item">👨‍✈️ <b>{{ $pilotsMonth }}</b> {{ __('skyops::skyops.dash_active_pilots') }}</span>
    </div>
</div>

{{-- NAVIGATION CARDS --}}
<div class="so-db-grid">
    <a href="{{ route('skyops.pireps') }}" class="so-db-card" data-c="pireps">
        <div class="so-db-card-icon">📋</div>
        <div class="so-db-card-title">{{ __('skyops::skyops.pirep_list') }}</div>
        <div class="so-db-card-desc">{{ __('skyops::skyops.dash_pireps_desc') }}</div>
        <div class="so-db-card-metrics">
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($pirepsTotal) }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_total_pireps') }}</div>
            </div>
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ $pirepsToday }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_today') }}</div>
            </div>
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ $liveCount }}</div>
                <div class="so-db-metric-lbl">Live</div>
            </div>
        </div>
        <span class="so-db-card-arrow">→</span>
    </a>
    <a href="{{ route('skyops.fleet') }}" class="so-db-card" data-c="fleet">
        <div class="so-db-card-icon">🛩️</div>
        <div class="so-db-card-title">{{ __('skyops::skyops.fleet') }}</div>
        <div class="so-db-card-desc">{{ __('skyops::skyops.dash_fleet_desc') }}</div>
        <div class="so-db-card-metrics">
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($acTotal) }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_aircraft') }}</div>
            </div>
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($acActive) }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_active') }}</div>
            </div>
        </div>
        <span class="so-db-card-arrow">→</span>
    </a>
    <a href="{{ route('skyops.pilots') }}" class="so-db-card" data-c="pilots">
        <div class="so-db-card-icon">👨‍✈️</div>
        <div class="so-db-card-title">{{ __('skyops::skyops.pilot_stats') }}</div>
        <div class="so-db-card-desc">{{ __('skyops::skyops.dash_pilots_desc') }}</div>
        <div class="so-db-card-metrics">
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ $pilotsMonth }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_this_month') }}</div>
            </div>
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($pilotsTotal) }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_total_pilots') }}</div>
            </div>
        </div>
        <span class="so-db-card-arrow">→</span>
    </a>
    <a href="{{ route('skyops.airlines') }}" class="so-db-card" data-c="airlines">
        <div class="so-db-card-icon">🏢</div>
        <div class="so-db-card-title">{{ __('skyops::skyops.airlines') }}</div>
        <div class="so-db-card-desc">{{ __('skyops::skyops.dash_airlines_desc') }}</div>
        <div class="so-db-card-metrics">
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ $airlinesTotal }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.airlines_total') }}</div>
            </div>
        </div>
        <span class="so-db-card-arrow">→</span>
    </a>
    <a href="{{ route('skyops.departures') }}" class="so-db-card" data-c="departures">
        <div class="so-db-card-icon">🛫</div>
        <div class="so-db-card-title">{{ __('skyops::skyops.departures') }}</div>
        <div class="so-db-card-desc">{{ __('skyops::skyops.dash_departures_desc') }}</div>
        <div class="so-db-card-metrics">
            <div class="so-db-metric">
                <div class="so-db-metric-val">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($flightsTotal) }}</div>
                <div class="so-db-metric-lbl">{{ __('skyops::skyops.dash_scheduled') }}</div>
            </div>
        </div>
        <span class="so-db-card-arrow">→</span>
    </a>
</div>

{{-- BOTTOM SECTION --}}
<div class="so-db-bottom">

    {{-- ACTIVITY FEED — full width --}}
    <div class="so-db-panel so-db-panel-wide" style="--panel-grad:linear-gradient(135deg,rgba(59,130,246,.06),transparent 60%);">
        <div class="so-db-panel-title">
            📡 {{ __('skyops::skyops.dash_recent_activity') }}
            <span class="so-db-panel-badge">{{ __('skyops::skyops.dash_last_10') }}</span>
        </div>
        @if($latestPireps->isEmpty())
            <div class="so-db-empty">{{ __('skyops::skyops.dash_no_activity') }}</div>
        @else
            <div class="so-table-wrap">
                <table class="so-db-act-table">
                    <thead>
                        <tr>
                            <th>{{ __('skyops::skyops.col_date') }}</th>
                            <th>{{ __('skyops::skyops.col_airline') }}</th>
                            <th>{{ __('skyops::skyops.col_flight') }}</th>
                            <th>{{ __('skyops::skyops.col_dep') }}</th>
                            <th>{{ __('skyops::skyops.col_arr') }}</th>
                            <th>{{ __('skyops::skyops.dash_aircraft') }}</th>
                            <th class="r">{{ __('skyops::skyops.col_time') }}</th>
                            @if($hasDist)<th class="r">{{ __('skyops::skyops.col_distance') }}</th>@endif
                            @if(config('skyops.features.show_fuel', true))<th class="r">{{ __('skyops::skyops.col_fuel') }}</th>@endif
                            @if($hasLR)<th class="r">{{ __('skyops::skyops.col_landing') }}</th>@endif
                            <th>{{ __('skyops::skyops.col_pilot') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestPireps as $p)
                            @php
                                // Time — flight_time is raw minutes (int), no phpVMS cast available
                                $blockMin = (int)($p->flight_time ?? 0);
                                $blockFmt = sprintf('%d:%02d', intdiv($blockMin, 60), $blockMin % 60);

                                // Landing rate — use config-driven thresholds via SkyOpsHelper
                                $lrRaw = $hasLR ? ($p->landing_rate ?? null) : null;
                                $lr = ($lrRaw !== null) ? SkyOpsHelper::landingRate((float)$lrRaw) : null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="so-db-act-time">{{ $p->created_at ? $p->created_at->diffForHumans(null, true, true) : '—' }}</span>
                                </td>
                                <td>
                                    <span class="so-db-act-aln">{{ $p->airline->icao ?? '—' }}</span>
                                </td>
                                <td>
                                    <a href="{{ url('/pireps/' . $p->id) }}" class="so-db-act-flt" style="text-decoration:none;">
                                        {{ SkyOpsHelper::flightNumber($p) }}
                                    </a>
                                </td>
                                <td><span class="so-db-act-route">{{ $p->dpt_airport->icao ?? '?' }}</span></td>
                                <td><span class="so-db-act-route">{{ $p->arr_airport->icao ?? '?' }}</span></td>
                                <td>
                                    <span class="so-db-act-ac">
                                        <a href="{{ url('/daircraft/' . rawurlencode($p->aircraft->registration ?? '')) }}" style="color:var(--ap-text);text-decoration:none;font-weight:600;font-size:.76rem;">{{ $p->aircraft->registration ?? '—' }}</a>
                                        @if($p->aircraft->name ?? null)
                                            <span style="color:var(--ap-cyan);font-size:.66rem;margin-left:2px;">"{{ $p->aircraft->name }}"</span>
                                        @endif
                                        <span style="color:var(--ap-muted);font-size:.66rem;margin-left:2px;">{{ $p->aircraft->icao ?? '' }}</span>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <span class="so-db-act-mono">{{ $blockFmt }}</span>
                                </td>
                                {{-- Distance — native phpVMS unit cast via ->local() --}}
                                @if($hasDist)
                                <td style="text-align:right;">
                                    <span class="so-db-act-mono" style="color:var(--ap-muted);">{{ $p->distance ? $p->distance->local(0) : '—' }}</span>
                                </td>
                                @endif
                                {{-- Fuel — native phpVMS unit cast via ->local() --}}
                                @if(config('skyops.features.show_fuel', true))
                                <td style="text-align:right;">
                                    <span class="so-db-act-mono" style="color:var(--ap-muted);">{{ $p->fuel_used ? $p->fuel_used->local(0) : '—' }}</span>
                                </td>
                                @endif
                                {{-- Landing Rate — config-driven thresholds --}}
                                @if($hasLR)
                                <td style="text-align:right;">
                                    @if($lr)
                                        <span class="so-db-act-lr {{ $lr['class'] }}">{{ $lr['emoji'] }} {{ \Modules\SkyOps\Helpers\SkyOpsHelper::number((float)$lrRaw) }} fpm</span>
                                    @else
                                        <span style="color:var(--ap-muted);font-size:.72rem;">—</span>
                                    @endif
                                </td>
                                @endif
                                <td>
                                    <span class="so-db-act-pilot">
                                        {{ PilotNameHelper::format($p->user->name ?? null, $p->user->callsign ?? null) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- TOP PILOTS --}}
    <div class="so-db-panel" style="--panel-grad:linear-gradient(135deg,rgba(129,140,248,.08),transparent 60%);">
        <div class="so-db-panel-title">
            🏆 {{ __('skyops::skyops.dash_top_pilots') }}
            <span class="so-db-panel-badge">{{ __('skyops::skyops.dash_this_month') }}</span>
        </div>
        @if($topPilots->isEmpty())
            <div class="so-db-empty">{{ __('skyops::skyops.dash_no_activity') }}</div>
        @else
            <ul class="so-db-lb">
                @foreach($topPilots as $i => $tp)
                    <li class="so-db-lb-item">
                        <span class="so-db-lb-rank">{{ $i + 1 }}</span>
                        <span class="so-db-lb-name">{{ $tp['name'] }}</span>
                        <span class="so-db-lb-stat">
                            <b>{{ $tp['flights'] }}</b> {{ __('skyops::skyops.col_flights') }}
                            · {{ $tp['hours'] }}h
                            @if($tp['dist'] !== null)
                                · {{ UnitHelper::distance($tp['dist']) }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- TOP ROUTES --}}
    <div class="so-db-panel" style="--panel-grad:linear-gradient(135deg,rgba(34,211,238,.08),transparent 60%);">
        <div class="so-db-panel-title">
            🗺️ {{ __('skyops::skyops.dash_top_routes') }}
            <span class="so-db-panel-badge">{{ __('skyops::skyops.dash_this_month') }}</span>
        </div>
        @if($topRoutes->isEmpty())
            <div class="so-db-empty">{{ __('skyops::skyops.dash_no_activity') }}</div>
        @else
            <ul class="so-db-rt">
                @foreach($topRoutes as $i => $rt)
                    <li class="so-db-rt-item">
                        <span class="so-db-rt-rank">{{ $i + 1 }}</span>
                        <span class="so-db-rt-route">
                            {{ $rt['dep'] }} <span class="so-db-rt-arrow">→</span> {{ $rt['arr'] }}
                        </span>
                        <span class="so-db-rt-stat">
                            <b>{{ $rt['flights'] }}×</b> · {{ $rt['hours'] }}h
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>

@endsection
