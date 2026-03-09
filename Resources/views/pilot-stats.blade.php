{{-- modules/SkyOps/Resources/views/pilot-stats.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.pilot_stats'))

@section('skyops-content')

{{-- Pilot-Stats-specific styles (use ap-* from shared _styles, prefix so-ps-*) --}}
<style>
/* ── Glass Card ── */
.so-ps-glass{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;transition:border-color .2s;margin-bottom:16px;overflow:hidden}
.so-ps-glass:hover{border-color:rgba(255,255,255,.16)}
html.ap-light .so-ps-glass:hover{border-color:rgba(0,0,0,.15)}
.so-ps-glass-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:14px 18px;border-bottom:1px solid var(--ap-border);background:var(--ap-surface)}

/* ── KPI Strip ── */
.so-ps-kpi-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
.so-ps-kpi{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:15px 17px;position:relative;overflow:hidden;transition:transform .15s,border-color .2s}
.so-ps-kpi:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.16)}
html.ap-light .so-ps-kpi:hover{border-color:rgba(0,0,0,.15)}
.so-ps-kpi::before{content:'';position:absolute;inset:0;background:var(--kpi-grad,linear-gradient(135deg,rgba(59,130,246,.12),transparent 60%));pointer-events:none}
.so-ps-kpi[data-t="flights"]{--kpi-grad:linear-gradient(135deg,rgba(59,130,246,.15),transparent 60%)}
.so-ps-kpi[data-t="time"]{--kpi-grad:linear-gradient(135deg,rgba(129,140,248,.15),transparent 60%)}
.so-ps-kpi[data-t="dist"]{--kpi-grad:linear-gradient(135deg,rgba(34,211,238,.15),transparent 60%)}
.so-ps-kpi[data-t="lr"]{--kpi-grad:linear-gradient(135deg,rgba(74,222,128,.12),transparent 60%)}
.so-ps-kpi[data-t="score"]{--kpi-grad:linear-gradient(135deg,rgba(251,191,36,.12),transparent 60%)}
.so-ps-kpi-icon{position:absolute;right:14px;top:12px;opacity:.15;font-size:1.7rem}
.so-ps-kpi-label{font-size:.68rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--ap-muted);margin-bottom:6px}
.so-ps-kpi-value{font-size:1.45rem;font-weight:800;color:var(--ap-text-head);line-height:1}

/* ── Filter Pill ── */
.so-ps-filter-pill{display:flex;gap:4px;background:#1e293b;border-radius:10px;padding:4px;border:1px solid var(--ap-border)}
.so-ps-filter-pill button{border:none;background:transparent;color:var(--ap-muted);font-size:.78rem;font-weight:600;padding:5px 13px;border-radius:7px;cursor:pointer;transition:all .15s}
.so-ps-filter-pill button:hover{color:var(--ap-text-head);background:var(--ap-surface)}
.so-ps-filter-pill button.active{background:var(--ap-blue);color:#fff}
html.ap-light .so-ps-filter-pill{background:#ffffff}

/* ── Tags ── */
.so-ps-tag{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:500;background:var(--ap-surface);color:var(--ap-text);border:1px solid var(--ap-border);border-radius:6px;padding:3px 8px;white-space:nowrap}

/* ── Buttons ── */
.so-ps-btn{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;padding:6px 14px;border-radius:8px;cursor:pointer;border:1px solid var(--ap-border);background:#1e293b;color:var(--ap-text);transition:all .15s;text-decoration:none;line-height:1.3}
html.ap-light .so-ps-btn{background:#ffffff}
.so-ps-btn:hover{border-color:rgba(255,255,255,.16);color:var(--ap-text-head)}
.so-ps-btn-primary{background:var(--ap-blue);border-color:var(--ap-blue);color:#fff}
.so-ps-btn-primary:hover{background:#2563eb}
.so-ps-btn-csv{background:rgba(34,197,94,.15);color:#86efac;border-color:rgba(34,197,94,.3)}
.so-ps-btn-csv:hover{background:rgba(63,185,80,.22)}
html.ap-light .so-ps-btn-csv{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.25)}

/* ── Leader Tab Buttons ── */
.so-ps-leader-tabs .so-ps-tab-btn{background:var(--ap-surface);border:1px solid var(--ap-border);color:var(--ap-muted);font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:7px;cursor:pointer;transition:all .15s}
.so-ps-leader-tabs .so-ps-tab-btn:hover{color:var(--ap-text-head);border-color:rgba(255,255,255,.16)}
.so-ps-leader-tabs .so-ps-tab-btn.active{background:var(--ap-blue);border-color:var(--ap-blue);color:#fff}
html.ap-light .so-ps-leader-tabs .so-ps-tab-btn:hover{border-color:rgba(0,0,0,.2)}

/* ── Table ── */
.so-ps-table-wrap{overflow-x:auto;scrollbar-width:thin}
.so-ps-table{width:100%;border-collapse:collapse;font-size:.8rem;table-layout:fixed}
.so-ps-table thead{background:var(--ap-surface);position:sticky;top:0;z-index:10;border-bottom:2px solid var(--ap-border)}
.so-ps-table thead th{padding:10px 10px;text-align:left;white-space:nowrap;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ap-muted)}
.so-ps-table thead th.th-r{text-align:right}
.so-ps-table tbody tr{border-bottom:1px solid var(--ap-border);transition:background .1s;cursor:pointer}
.so-ps-table tbody tr:last-child{border-bottom:none}
.so-ps-table tbody tr:hover{background:rgba(88,166,255,.04)}
.so-ps-table tbody td{padding:8px 10px;color:var(--ap-text);vertical-align:middle}
.so-ps-table tbody td.td-r{text-align:right;font-size:.76rem;font-variant-numeric:tabular-nums}

/* Rank */
.so-ps-rk-gold{color:#F59E0B;font-weight:800}
.so-ps-rk-silver{color:#94A3B8;font-weight:800}
.so-ps-rk-bronze{color:#B87333;font-weight:800}

/* Airline Stackbar */
.so-ps-airstack{height:10px;margin-top:.3rem;background:rgba(125,133,144,.15);border-radius:5px;overflow:hidden}
.so-ps-airseg{display:inline-block;height:10px}

/* Airline Multi-Select */
.so-ps-air-item{display:flex;align-items:center;gap:.5rem;padding:.28rem .5rem;border-radius:.35rem;cursor:pointer;color:var(--ap-text);font-size:.82rem}
.so-ps-air-item:hover{background:rgba(88,166,255,.04)}

/* Chips */
.so-ps-chips{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.35rem}
.so-ps-chip{--chip-bg:#3B82F6;background:linear-gradient(160deg,var(--chip-bg),color-mix(in srgb,var(--chip-bg) 65%,#000));color:#fff;padding:.15rem .55rem;border-radius:999px;font-size:.78rem;display:inline-flex;align-items:center;gap:.3rem;font-weight:600;border:1px solid rgba(0,0,0,.15)}
.so-ps-chip .chip-x{background:transparent;border:0;color:rgba(255,255,255,.75);cursor:pointer;padding:0 .1rem;line-height:1}
html.ap-light .so-ps-chip .chip-x{color:rgba(0,0,0,.6)}
.so-ps-chip .chip-x:hover{color:#fff}
.so-ps-chip-more{background:var(--ap-surface);color:var(--ap-muted);border:1px dashed var(--ap-border)}

/* Tooltip airline overlay */
.so-ps-airmix-tip{max-width:440px;white-space:normal;font-size:.9rem;line-height:1.3}
.so-ps-airmix-title{font-weight:700;margin-bottom:.35rem;color:var(--ap-text-head);border-bottom:1px dashed var(--ap-border);padding-bottom:.25rem}
.so-ps-airrow-grid{display:grid;grid-template-columns:14px 1fr auto;column-gap:.5rem;align-items:center;padding:.1rem 0}
.so-ps-airdot{width:11px;height:11px;border-radius:50%;display:inline-block;flex-shrink:0}
.so-ps-airname{color:var(--ap-text);overflow:hidden;text-overflow:ellipsis;font-size:.85rem}
.so-ps-airmeta{color:var(--ap-muted);font-size:.75rem;white-space:nowrap;font-variant-numeric:tabular-nums}
.so-ps-info-btn{color:var(--ap-muted);text-decoration:none}
.so-ps-info-btn:hover{color:var(--ap-text-head)}

/* Detail Row */
.so-ps-row-detail td{background:#1a2332!important}
html.ap-light .so-ps-row-detail td{background:#f1f5f9!important}
.so-ps-detail-scroll{overflow-x:auto;padding:.5rem}
.so-ps-detail-table{width:100%;border-collapse:collapse;font-size:.8rem}
.so-ps-detail-table th{padding:6px 10px;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ap-muted);border-bottom:1px solid var(--ap-border);background:var(--ap-surface)}
.so-ps-detail-table td{padding:6px 10px;color:var(--ap-text);border-bottom:1px solid var(--ap-border);font-size:.8rem}
.so-ps-detail-table td:not(:first-child){text-align:right;font-variant-numeric:tabular-nums}

/* Chart */
.so-ps-chart-wrap{height:210px}
@media(max-width:576px){.so-ps-chart-wrap{height:180px}}

/* Columns */
.col-rank{width:44px}.col-pilot{width:26%}.col-num{width:106px}

/* Period Portal */
.so-ps-period-opt{padding:8px 14px;font-size:.82rem;color:var(--ap-text);cursor:pointer;transition:background .1s;font-variant-numeric:tabular-nums}
.so-ps-period-opt:hover{background:rgba(88,166,255,.06)}
.so-ps-period-opt.active{color:var(--ap-blue);font-weight:700;background:rgba(59,130,246,.1)}

/* Stagger animation */
.so-ps-stagger>*{animation:so-ps-up .35s both}
@keyframes so-ps-up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.so-ps-stagger>*:nth-child(1){animation-delay:.04s}.so-ps-stagger>*:nth-child(2){animation-delay:.08s}.so-ps-stagger>*:nth-child(3){animation-delay:.12s}

/* Portal containers */
#soPeriodPortal,#soAirPortal{background:#1e293b;border:1px solid var(--ap-border);border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.6)}
#soAirPortalList{background:#1e293b;border:1px solid var(--ap-border);border-radius:8px}
html.ap-light #soPeriodPortal,html.ap-light #soAirPortal{background:#ffffff;box-shadow:0 12px 40px rgba(0,0,0,.15)}
html.ap-light #soAirPortalList{background:#f8fafc}
</style>

{{-- Period Portal --}}
<div id="soPeriodPortal" style="display:none;position:fixed;z-index:99999;overflow-y:auto;min-width:160px;max-height:320px;padding:0;"></div>

{{-- Airline Portal --}}
<div id="soAirPortal" style="display:none;position:fixed;z-index:99999;padding:.5rem;min-width:320px;">
    <div class="d-flex gap-2 mb-2">
        <button class="so-ps-btn" data-action="all" style="font-size:.72rem;padding:4px 9px;">{{ __('skyops::skyops.filter_all') }}</button>
        <button class="so-ps-btn" data-action="top10" style="font-size:.72rem;padding:4px 9px;">Top 10</button>
        <button class="so-ps-btn" data-action="none" style="font-size:.72rem;padding:4px 9px;">{{ __('skyops::skyops.filter_none') }}</button>
    </div>
    <input type="text" class="so-input w-100 mb-2" id="soAirSearch" placeholder="{{ __('skyops::skyops.search') }}…" style="font-size:.8rem;padding:5px 10px;">
    <div id="soAirPortalList" style="max-height:260px;overflow:auto;padding:.25rem;"></div>
</div>

{{-- PAGE HEADER CARD --}}
<div class="so-card so-page-header">
    <div class="so-page-header-row">
        <div>
            <div class="so-page-title">
                📊 {{ __('skyops::skyops.pilot_stats') }}
            </div>
            <div class="so-page-subtitle">
                {{ __('skyops::skyops.stats_since', ['date' => \Modules\SkyOps\Helpers\SkyOpsHelper::fmtDate($pilotStats['epoch'])]) }}
            </div>
        </div>
        <div class="so-ps-filter-pill" id="statsTabs">
            <button class="active" data-scope="month">📅 {{ __('skyops::skyops.stats_month') }}</button>
            <button data-scope="quarter">📆 {{ __('skyops::skyops.stats_quarter') }}</button>
            <button data-scope="year">📋 {{ __('skyops::skyops.stats_year') }}</button>
            <button data-scope="all">♾️ {{ __('skyops::skyops.stats_all') }}</button>
        </div>
    </div>
</div>

{{-- Scope Containers --}}
@foreach(['month','quarter','year','all'] as $sk)
    <div class="stats-block so-ps-stagger" data-scope="{{ $sk }}" style="{{ $loop->first ? '' : 'display:none' }}"></div>
@endforeach

{{-- Template --}}
<template id="tpl-block">

    {{-- Filter + KPIs --}}
    <div class="so-ps-glass" style="overflow:visible;backdrop-filter:none;-webkit-backdrop-filter:none;">

        {{-- Row 1: Period + Airline selectors --}}
        <div style="padding:14px 18px;border-bottom:1px solid var(--ap-border);background:var(--ap-surface);border-radius:14px 14px 0 0;position:relative;z-index:100;">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <div>
                    <div style="font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ap-muted);margin-bottom:4px;" data-role="period-label">{{ __('skyops::skyops.stats_period') }}</div>
                    <button class="so-ps-btn" data-role="period-btn" type="button" style="height:34px;min-width:140px;justify-content:space-between;">
                        <span data-role="period-btn-label">—</span> ▾
                    </button>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ap-muted);margin-bottom:4px;">Airlines</div>
                    <button class="so-ps-btn" id="airDropBtn" type="button" style="height:34px;">
                        ✈️ {{ __('skyops::skyops.stats_select_airlines') }} ▾
                    </button>
                </div>
                <div class="so-ps-chips" data-role="air-chips" style="flex:1;min-width:0;max-height:56px;overflow-y:auto;overflow-x:hidden;"></div>
            </div>
            <div style="font-size:.7rem;color:var(--ap-muted);margin-top:6px;">{{ __('skyops::skyops.stats_airline_hint') }}</div>
        </div>

        {{-- Row 2: KPI Strip --}}
        <div style="padding:14px 18px;">
            <div class="so-ps-kpi-strip" style="margin-bottom:0;">
                <div class="so-ps-kpi" data-t="flights">
                    <div class="so-ps-kpi-icon">🛫</div>
                    <div class="so-ps-kpi-label">{{ __('skyops::skyops.col_flights') }}</div>
                    <div class="so-ps-kpi-value"><span data-kpi="flights">0</span> <span data-trend="flights" style="font-size:.7rem;"></span></div>
                </div>
                <div class="so-ps-kpi" data-t="time">
                    <div class="so-ps-kpi-icon">⏱️</div>
                    <div class="so-ps-kpi-label">{{ __('skyops::skyops.stats_total_time') }}</div>
                    <div class="so-ps-kpi-value" style="font-size:1.2rem;font-variant-numeric:tabular-nums;"><span data-kpi="time">00h 00m</span> <span data-trend="time" style="font-size:.7rem;"></span></div>
                </div>
                <div class="so-ps-kpi kpi-nm d-none" data-t="dist">
                    <div class="so-ps-kpi-icon">📏</div>
                    <div class="so-ps-kpi-label">{{ __('skyops::skyops.col_distance') }} ({{ $pilotStats['distLabel'] ?? 'NM' }})</div>
                    <div class="so-ps-kpi-value" style="font-size:1.2rem;font-variant-numeric:tabular-nums;"><span data-kpi="nm">0</span> <span data-trend="nm" style="font-size:.7rem;"></span></div>
                </div>
                <div class="so-ps-kpi kpi-lr d-none" data-t="lr">
                    <div class="so-ps-kpi-icon">🎯</div>
                    <div class="so-ps-kpi-label">{{ __('skyops::skyops.stats_avg_landing') }} (fpm)</div>
                    <div class="so-ps-kpi-value" style="font-variant-numeric:tabular-nums;"><span data-kpi="lr">—</span></div>
                </div>
                <div class="so-ps-kpi kpi-score d-none" data-t="score">
                    <div class="so-ps-kpi-icon">⭐</div>
                    <div class="so-ps-kpi-label">{{ __('skyops::skyops.stats_avg_score') }}</div>
                    <div class="so-ps-kpi-value"><span data-kpi="score">—</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Leaderboard --}}
    <div class="so-ps-glass">
        <div class="so-ps-glass-header">
            <div style="font-weight:700;font-size:.9rem;color:var(--ap-text-head);display:flex;align-items:center;gap:8px;">🏆 {{ __('skyops::skyops.stats_leaderboard') }}</div>
            <div class="d-flex flex-wrap gap-1 so-ps-leader-tabs" data-role="leader-tabs">
                <button class="so-ps-tab-btn active" data-key="flights">{{ __('skyops::skyops.col_flights') }}</button>
                <button class="so-ps-tab-btn" data-key="minutes">{{ __('skyops::skyops.col_time') }}</button>
                <button class="so-ps-tab-btn" data-key="nm">{{ __('skyops::skyops.stats_distance') }}</button>
                <button class="so-ps-tab-btn" data-key="lr">{{ __('skyops::skyops.stats_avg_landing') }}</button>
                <button class="so-ps-tab-btn" data-key="lr_softest">{{ __('skyops::skyops.stats_soft_land') }}</button>
                <button class="so-ps-tab-btn" data-key="lr_hardest">{{ __('skyops::skyops.stats_hard_land') }}</button>
                <button class="so-ps-tab-btn" data-key="score">{{ __('skyops::skyops.stats_avg_score') }}</button>
                <button class="so-ps-tab-btn" data-key="mins_avg">{{ __('skyops::skyops.stats_avg_time') }}</button>
                <button class="so-ps-tab-btn" data-key="nm_avg">{{ __('skyops::skyops.stats_avg_dist') }}</button>
            </div>
        </div>

        <div class="so-ps-table-wrap">
            <table class="so-ps-table">
                <thead>
                    <tr data-role="thead">
                        <th class="col-rank th-r">#</th>
                        <th class="col-pilot">{{ __('skyops::skyops.col_pilot') }}</th>
                        <th class="th-r col-num">{{ __('skyops::skyops.col_flights') }}</th>
                        <th class="th-r col-num">{{ __('skyops::skyops.col_time') }}</th>
                    </tr>
                </thead>
                <tbody data-role="tbody"></tbody>
            </table>
        </div>

        <div style="padding:12px 18px;border-top:1px solid var(--ap-border);display:flex;justify-content:flex-end;">
            @if(\Modules\SkyOps\Helpers\SkyOpsHelper::csvAllowed())
            <button type="button" class="so-ps-btn so-ps-btn-csv" data-role="csv">
                📥 {{ __('skyops::skyops.stats_csv_export') }}
            </button>
            @endif
        </div>
    </div>

    {{-- Chart --}}
    <div class="so-ps-glass">
        <div class="so-ps-glass-header">
            <div style="font-weight:700;font-size:.9rem;color:var(--ap-text-head);display:flex;align-items:center;gap:8px;">📊 Top 8 — <span data-role="chart-label">{{ __('skyops::skyops.col_flights') }}</span></div>
        </div>
        <div style="padding:16px 18px;"><div class="so-ps-chart-wrap"><canvas data-role="chart"></canvas></div></div>
    </div>

</template>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.PILOT_STATS = @json($pilotStats);
var JS_LOCALE = '{!! \Modules\SkyOps\Helpers\SkyOpsHelper::jsLocale() !!}';
var JS_DEC_SEP = JS_LOCALE === 'de-DE' ? ',' : '.';

/* Helpers */
function fmtHM(m){m=Math.max(0,parseInt(m||0,10));var h=Math.floor(m/60),mn=m%60;return(h<10?'0':'')+h+'h '+(mn<10?'0':'')+mn+'m';}
function delta(curr,prev,suffix){if(prev==null)return'';var d=(curr||0)-(prev||0),c=d>0?'#4ade80':(d<0?'#f87171':'#94a3b8'),ic=d>0?'\u2191':(d<0?'\u2193':'\u00b1');return '<span style="font-size:.7rem;font-variant-numeric:tabular-nums;color:'+c+';">'+ic+(d>0?'+':'')+Math.abs(d).toLocaleString(JS_LOCALE)+(suffix||'')+'</span>';}
function deltaT(c,p){if(p==null)return'';var d=(c||0)-(p||0),cl=d>0?'#4ade80':(d<0?'#f87171':'#94a3b8'),ic=d>0?'\u2191':(d<0?'\u2193':'\u00b1'),a=Math.abs(d),h=Math.floor(a/60),m=a%60;return '<span style="font-size:.7rem;font-variant-numeric:tabular-nums;color:'+cl+';">'+ic+(h?h+'h ':'')+m+'m</span>';}

var SORT_DESC={flights:1,minutes:1,nm:1,score:1,mins_avg:1,nm_avg:1,lr:1,lr_softest:1,lr_hardest:0};
var DIST_LBL=@json($pilotStats['distLabel'] ?? 'NM');
var LABELS={flights:'{!! __("skyops::skyops.col_flights") !!}',minutes:'{!! __("skyops::skyops.col_time") !!}',nm:'{!! __("skyops::skyops.stats_distance") !!} ('+DIST_LBL+')',lr:'{!! __("skyops::skyops.stats_avg_landing") !!}',lr_softest:'{!! __("skyops::skyops.stats_soft_land") !!}',lr_hardest:'{!! __("skyops::skyops.stats_hard_land") !!}',score:'{!! __("skyops::skyops.stats_avg_score") !!}',mins_avg:'{!! __("skyops::skyops.stats_avg_time") !!}',nm_avg:'{!! __("skyops::skyops.stats_avg_dist") !!}'};
var PALETTE=['#3b82f6','#818cf8','#0ea5e9','#f59e0b','#ef4444','#22c55e','#ec4899','#22d3ee'];
var AIR_PAL=['#60A5FA','#A78BFA','#34D399','#FBBF24','#F87171','#22D3EE','#F472B6','#4ADE80','#C084FC','#F59E0B'];
function ck(k){var h=0;for(var i=0;i<k.length;i++){h=((h*31)+k.charCodeAt(i))>>>0;}return AIR_PAL[h%AIR_PAL.length];}
function ca(a){return ck((a.code||'')+'|'+(a.label||''));}
function vFor(r,k){if(k==='lr_softest')return r.lr_softest!=null?r.lr_softest:Number.NEGATIVE_INFINITY;if(k==='lr_hardest')return r.lr_hardest!=null?r.lr_hardest:Number.POSITIVE_INFINITY;return r[k]||0;}
function srt(rows,k){var d=SORT_DESC[k]!==0;return rows.slice().sort(function(a,b){return d?(vFor(b,k)-vFor(a,k)):(vFor(a,k)-vFor(b,k));});}

var AIR_OPT=window.PILOT_STATS.airline_options||[];
var AIR_FILTER=new Set();
var TOUCH='ontouchstart' in window||navigator.maxTouchPoints>0;
function airOK(f){if(!f||!f.length)return[];if(!AIR_FILTER.size)return f;return f.filter(function(a){return AIR_FILTER.has(a.id);});}

function airTip(list){
    if(!list||!list.length)return'';
    return '<div class="so-ps-airmix-tip"><div class="so-ps-airmix-title">Airlines</div>'+list.map(function(a){return '<div class="so-ps-airrow-grid"><span class="so-ps-airdot" style="background:'+ca(a)+'"></span><span class="so-ps-airname">'+a.label+(a.code?' <span style="color:var(--ap-muted)">('+a.code+')</span>':'')+'</span><span class="so-ps-airmeta">'+fmtHM(a.minutes||0)+' \u00b7 '+(a.pct!=null?a.pct:0).toLocaleString(JS_LOCALE)+'%</span></div>';}).join('')+'</div>';
}
function airBar(list){
    if(!list||!list.length)return'';
    var tot=list.reduce(function(s,a){return s+(a.pct||0);},0)||100;
    return '<div class="so-ps-airstack">'+list.map(function(a){return '<span class="so-ps-airseg" style="width:'+Math.max(1,Math.round((a.pct||0)/tot*100))+'%;background:'+ca(a)+'" title="'+a.label+(a.code?' ('+a.code+')':'')+' \u00b7 '+(a.pct!=null?a.pct:0)+'%"></span>';}).join('')+'</div>';
}

function isDark(){return!document.body.classList.contains('light-mode')&&document.body.getAttribute('data-bs-theme')!=='light'&&document.documentElement.getAttribute('data-bs-theme')!=='light';}

/* renderScope */
function renderScope(scope){
    var pack=window.PILOT_STATS[scope],has=window.PILOT_STATS.has;
    var host=document.querySelector('.stats-block[data-scope="'+scope+'"]');
    if(!host.dataset.ready){
        host.appendChild(document.getElementById('tpl-block').content.cloneNode(true));
        host.dataset.ready='1';host.dataset.sortKey='flights';

        var lbl={month:'{!! __("skyops::skyops.stats_period_month") !!}',quarter:'{!! __("skyops::skyops.stats_period_quarter") !!}',year:'{!! __("skyops::skyops.stats_period_year") !!}',all:'{!! __("skyops::skyops.stats_all") !!}'};
        host.querySelector('[data-role="period-label"]').textContent=lbl[scope]||'{!! __("skyops::skyops.stats_period") !!}';

        var thead=host.querySelector('[data-role="thead"]');
        if(has.dist){thead.insertAdjacentHTML('beforeend','<th class="th-r col-num">'+LABELS.nm+'</th>');host.querySelector('.kpi-nm').classList.remove('d-none');}
        if(has.lr){thead.insertAdjacentHTML('beforeend','<th class="th-r col-num">{!! __("skyops::skyops.stats_avg_landing") !!}</th><th class="th-r col-num">{!! __("skyops::skyops.stats_soft_land") !!}</th><th class="th-r col-num">{!! __("skyops::skyops.stats_hard_land") !!}</th>');host.querySelector('.kpi-lr').classList.remove('d-none');}
        if(has.score){thead.insertAdjacentHTML('beforeend','<th class="th-r col-num">{!! __("skyops::skyops.stats_avg_score") !!}</th>');host.querySelector('.kpi-score').classList.remove('d-none');}
        thead.insertAdjacentHTML('beforeend','<th class="th-r col-num">{!! __("skyops::skyops.stats_avg_time") !!}</th>');
        if(has.dist) thead.insertAdjacentHTML('beforeend','<th class="th-r col-num">\u00d8-Str./Flug</th>');

        var periods=pack.periods||[];
        var initPeriod=periods.length?periods[periods.length-1]:'{!! __("skyops::skyops.stats_all") !!}';
        host.dataset.curPeriod=initPeriod;
        var periodBtn=host.querySelector('[data-role="period-btn"]');
        var periodBtnLabel=host.querySelector('[data-role="period-btn-label"]');
        if(periodBtnLabel)periodBtnLabel.textContent=initPeriod;
        if(scope==='all'&&periodBtn)periodBtn.disabled=true;
        if(periodBtn){periodBtn.addEventListener('click',function(e){e.stopPropagation();periodPortalOpen?closePeriodPortal():openPeriodPortal(periodBtn,host,scope);});}
        var csvBtn=host.querySelector('[data-role="csv"]');
        if(csvBtn)csvBtn.addEventListener('click',function(){exportCSV(scope);});
        host.querySelectorAll('[data-role="leader-tabs"] .so-ps-tab-btn').forEach(function(btn){btn.addEventListener('click',function(){
            host.querySelectorAll('[data-role="leader-tabs"] .so-ps-tab-btn').forEach(function(b){b.classList.remove('active');});
            btn.classList.add('active');host.dataset.sortKey=btn.dataset.key;draw(scope);
        });});
        buildAirMS(host);
    }
    draw(scope);
}

/* Period Portal */
var periodPortal=document.getElementById('soPeriodPortal');
var periodPortalOpen=false,activePeriodHost=null,activePeriodScope=null;

function closeAllPortals(){closePeriodPortal();closeAirPortal();}

function openPeriodPortal(btn,host,scope){
    closeAirPortal();activePeriodHost=host;activePeriodScope=scope;
    var pack=window.PILOT_STATS[scope];
    var curVal=host.dataset.curPeriod||(pack.periods&&pack.periods.length?pack.periods[pack.periods.length-1]:'{!! __("skyops::skyops.stats_all") !!}');
    periodPortal.innerHTML='';
    (pack.periods||[]).slice().reverse().forEach(function(p){
        var item=document.createElement('div');
        item.className='so-ps-period-opt'+(p==curVal?' active':'');
        item.textContent=p;
        item.addEventListener('click',function(){host.dataset.curPeriod=p;host.querySelector('[data-role="period-btn-label"]').textContent=p;closePeriodPortal();draw(scope);});
        periodPortal.appendChild(item);
    });
    var rect=btn.getBoundingClientRect();
    periodPortal.style.display='block';periodPortal.style.left=rect.left+'px';periodPortal.style.top=(rect.bottom+4)+'px';
    periodPortalOpen=true;
}
function closePeriodPortal(){periodPortal.style.display='none';periodPortalOpen=false;}
document.addEventListener('click',function(e){if(!periodPortalOpen)return;if(!periodPortal.contains(e.target)&&!e.target.closest('[data-role="period-btn"]'))closePeriodPortal();});

/* Airline Portal */
var airPortal=document.getElementById('soAirPortal');
var airSearch=document.getElementById('soAirSearch');
var airPortalList=document.getElementById('soAirPortalList');
var airPortalOpen=false,activeAirHost=null;

function openAirPortal(btn,host){
    closePeriodPortal();activeAirHost=host;
    var rect=btn.getBoundingClientRect();
    var spaceBelow=window.innerHeight-rect.bottom;
    airPortal.style.display='block';airPortal.style.left=rect.left+'px';
    if(spaceBelow>340){airPortal.style.top=(rect.bottom+6)+'px';airPortal.style.bottom='auto';}
    else{airPortal.style.bottom=(window.innerHeight-rect.top+6)+'px';airPortal.style.top='auto';}
    airPortalOpen=true;renderPortalList('');airSearch.value='';airSearch.focus();
}
function closeAirPortal(){airPortal.style.display='none';airPortalOpen=false;}
function renderPortalList(f){
    airPortalList.innerHTML='';var q=f.trim().toLowerCase();
    AIR_OPT.filter(function(a){return!q||(a.label+' '+(a.code||'')).toLowerCase().includes(q);}).forEach(function(a){
        var item=document.createElement('label');
        item.className='so-ps-air-item';
        item.innerHTML='<input type="checkbox" '+(AIR_FILTER.has(a.id)?'checked':'')+' data-id="'+a.id+'"> <span>'+a.label+(a.code?' ('+a.code+')':'')+'</span>';
        item.querySelector('input').addEventListener('change',function(e){e.target.checked?AIR_FILTER.add(a.id):AIR_FILTER.delete(a.id);if(activeAirHost)renderChipsFor(activeAirHost);});
        airPortalList.appendChild(item);
    });
}
airSearch.addEventListener('input',function(){renderPortalList(airSearch.value);});
airPortal.querySelectorAll('[data-action]').forEach(function(btn){btn.addEventListener('click',function(){
    var act=btn.dataset.action;
    if(act==='all')AIR_FILTER=new Set(AIR_OPT.map(function(x){return x.id;}));
    if(act==='top10')AIR_FILTER=new Set(AIR_OPT.slice(0,10).map(function(x){return x.id;}));
    if(act==='none')AIR_FILTER.clear();
    renderPortalList(airSearch.value);if(activeAirHost)renderChipsFor(activeAirHost);
});});
document.addEventListener('click',function(e){if(!airPortalOpen)return;if(!airPortal.contains(e.target)&&!e.target.closest('#airDropBtn'))closeAirPortal();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeAirPortal();});

function renderChipsFor(host){
    var chips=host.querySelector('[data-role="air-chips"]');if(!chips)return;chips.innerHTML='';
    AIR_OPT.filter(function(a){return AIR_FILTER.has(a.id);}).slice(0,20).forEach(function(a){
        var chip=document.createElement('span');chip.className='so-ps-chip';
        chip.style.setProperty('--chip-bg',ck((a.code||'')+'|'+(a.label||'')));
        chip.innerHTML=a.label+(a.code?' ('+a.code+')':'')+'<button type="button" class="chip-x">\u00d7</button>';
        chip.querySelector('button').addEventListener('click',function(){
            AIR_FILTER.delete(a.id);
            airPortalList.querySelectorAll('input').forEach(function(cb){if(parseInt(cb.dataset.id)===a.id)cb.checked=false;});
            renderChipsFor(host);
        });
        chips.appendChild(chip);
    });
    var extra=AIR_OPT.filter(function(a){return AIR_FILTER.has(a.id);}).length-20;
    if(extra>0){var m=document.createElement('span');m.className='so-ps-chip so-ps-chip-more';m.textContent='+'+extra;chips.appendChild(m);}
    var scope=host.closest('.stats-block')&&host.closest('.stats-block').dataset.scope;
    if(scope)draw(scope);
}

function buildAirMS(host){
    var btn=host.querySelector('#airDropBtn')||document.getElementById('airDropBtn');
    if(btn){btn.addEventListener('click',function(e){e.stopPropagation();airPortalOpen?closeAirPortal():openAirPortal(btn,host);});}
    renderChipsFor(host);
}

/* draw */
function draw(scope){
    var pack=window.PILOT_STATS[scope],has=window.PILOT_STATS.has;
    var host=document.querySelector('.stats-block[data-scope="'+scope+'"]');
    var p=host.dataset.curPeriod||(pack.periods?pack.periods[0]:'{!! __("skyops::skyops.stats_all") !!}');
    var key=host.dataset.sortKey||'flights';

    var prev=null;if(pack.periods&&pack.periods.length){var idx=pack.periods.indexOf(p);if(idx>0)prev=pack.summary[pack.periods[idx-1]];}
    var sum=pack.summary[p]||{flights:0,minutes:0,nm:0,lr:null,score:null,humantime:'00h 00m'};
    host.querySelector('[data-kpi="flights"]').innerText=(sum.flights||0).toLocaleString(JS_LOCALE);
    host.querySelector('[data-trend="flights"]').innerHTML=prev?delta(sum.flights,prev.flights):'';
    host.querySelector('[data-kpi="time"]').innerText=sum.humantime;
    host.querySelector('[data-trend="time"]').innerHTML=prev?deltaT(sum.minutes,prev.minutes):'';
    if(has.dist){host.querySelector('[data-kpi="nm"]').innerText=(sum.nm||0).toLocaleString(JS_LOCALE);host.querySelector('[data-trend="nm"]').innerHTML=prev?delta(sum.nm,prev.nm,' '+DIST_LBL):'';}
    if(has.lr)host.querySelector('[data-kpi="lr"]').innerText=sum.lr!=null?sum.lr:'\u2014';
    if(has.score)host.querySelector('[data-kpi="score"]').innerText=sum.score!=null?sum.score:'\u2014';

    drawLeader(host,srt(pack.leader[p]||[],key),has);

    var canvas=host.querySelector('[data-role="chart"]');
    var top8=srt(pack.leader[p]||[],key).slice(0,8);
    host.querySelector('[data-role="chart-label"]').textContent=LABELS[key]||'Fl\u00fcge';
    var dark=isDark(),tc=dark?'#9CA3AF':'#4B5563',gc=dark?'rgba(255,255,255,.06)':'rgba(0,0,0,.06)';
    Chart.defaults.color=tc;Chart.defaults.borderColor=gc;
    window._charts=window._charts||{};
    if(window._charts[canvas])window._charts[canvas].destroy();
    window._charts[canvas]=new Chart(canvas,{
        type:'bar',
        data:{labels:top8.map(function(r){return r.pilot;}),datasets:[{data:top8.map(function(r){return(key==='minutes'||key==='mins_avg')?(r[key]||0):(r[key]!=null?r[key]:0);}),backgroundColor:PALETTE.slice(0,top8.length),borderRadius:6,borderSkipped:false}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:dark?'#1e293b':'#fff',titleColor:dark?'#e2e8f0':'#0f172a',bodyColor:tc,borderColor:gc,borderWidth:1}},scales:{x:{ticks:{maxRotation:0,autoSkip:false},grid:{display:false}},y:{beginAtZero:true}}}
    });
    requestAnimationFrame(function(){window._charts[canvas].resize();});
}

function drawLeader(host,rows,has){
    var tbody=host.querySelector('[data-role="tbody"]');tbody.innerHTML='';
    rows.slice(0,25).forEach(function(r,i){
        var list=airOK(r.air_full||[]);
        var tot=Math.max(1,list.reduce(function(s,a){return s+(a.minutes||0);},0));
        var lPct=list.map(function(a){return Object.assign({},a,{pct:Math.round((a.minutes/tot)*1000)/10});});
        var tip=lPct.length?airTip(lPct):'';
        var bar=lPct.length?airBar(lPct.slice(0,8)):'';
        var iBtn=tip?'<button type="button" class="so-ps-info-btn btn btn-link p-0 ms-1 align-baseline" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="'+tip.replace(/"/g,'&quot;')+'" aria-label="Airlines"><i class="ph ph-info"></i></button>':'';
        var rc=i===0?'so-ps-rk-gold':i===1?'so-ps-rk-silver':i===2?'so-ps-rk-bronze':'';

        var tr=document.createElement('tr');
        tr.innerHTML='<td class="td-r"><span class="'+rc+'">'+(i+1)+'</span></td>'
            +'<td><div class="d-flex flex-column"><span class="fw-semibold d-inline-flex align-items-center" style="font-size:.85rem;">'+r.pilot+iBtn+'</span>'+bar+'</div></td>'
            +'<td class="td-r">'+(r.flights||0).toLocaleString(JS_LOCALE)+'</td>'
            +'<td class="td-r">'+fmtHM(r.minutes||0)+'</td>'
            +(has.dist?'<td class="td-r">'+(r.nm||0).toLocaleString(JS_LOCALE)+'</td>':'')
            +(has.lr?'<td class="td-r">'+(r.lr!=null?r.lr:'\u2014')+'</td><td class="td-r">'+(r.lr_softest!=null?r.lr_softest:'\u2014')+'</td><td class="td-r">'+(r.lr_hardest!=null?r.lr_hardest:'\u2014')+'</td>':'')
            +(has.score?'<td class="td-r">'+(r.score!=null?r.score:'\u2014')+'</td>':'')
            +'<td class="td-r">'+fmtHM(r.mins_avg||0)+'</td>'
            +(has.dist?'<td class="td-r">'+(r.nm_avg||0).toLocaleString(JS_LOCALE)+'</td>':'');
        tbody.appendChild(tr);

        var det=document.createElement('tr');det.className='so-ps-row-detail d-none';
        det.innerHTML='<td colspan="'+tr.children.length+'">'+detTable(lPct)+'</td>';
        tbody.appendChild(det);

        tr.addEventListener('click',function(ev){
            if(ev.target.closest('.so-ps-info-btn'))return;
            var open=!det.classList.contains('d-none');
            det.classList.toggle('d-none',open);
            var ic=tr.querySelector('.so-ps-info-btn');
            if(ic){if(!open){if(ic._tt){ic._tt.dispose();ic._tt=null;}}else if(!TOUCH){if(ic._tt)ic._tt.dispose();ic._tt=new bootstrap.Tooltip(ic);}}
        });
        if(!TOUCH){var ic=tr.querySelector('.so-ps-info-btn');if(ic){if(ic._tt)ic._tt.dispose();ic._tt=new bootstrap.Tooltip(ic);}}
    });
}

function detTable(list){
    if(!list.length)return '<div class="so-ps-detail-scroll"><p style="color:var(--ap-muted);font-size:.8rem;padding:.5rem .25rem;">{!! __("skyops::skyops.no_flights_period") !!}</p></div>';
    return '<div class="so-ps-detail-scroll"><table class="so-ps-detail-table"><thead><tr><th>{!! __("skyops::skyops.col_airline") !!}</th><th>{!! __("skyops::skyops.col_flights") !!}</th><th>{!! __("skyops::skyops.col_time") !!}</th><th>%</th></tr></thead><tbody>'+list.map(function(a){return '<tr><td><span class="so-ps-airdot" style="background:'+ca(a)+';display:inline-block;margin-right:6px;"></span>'+a.label+(a.code?' <span style="color:var(--ap-muted)">('+a.code+')</span>':'')+'</td><td>'+(a.flights!=null?a.flights.toLocaleString(JS_LOCALE):'\u2014')+'</td><td>'+fmtHM(a.minutes||0)+'</td><td>'+(a.pct!=null?a.pct:0).toLocaleString(JS_LOCALE)+'\u0025</td></tr>';}).join('')+'</tbody></table></div>';
}

function exportCSV(scope){
    var pack=window.PILOT_STATS[scope],has=window.PILOT_STATS.has;
    var host=document.querySelector('.stats-block[data-scope="'+scope+'"]');
    var p=host.dataset.curPeriod||(pack.periods?pack.periods[0]:'{!! __("skyops::skyops.stats_all") !!}');
    var key=host.dataset.sortKey||'flights';
    var rows=srt(pack.leader[p]||[],key);
    var H=['#','{!! __("skyops::skyops.col_pilot") !!}','{!! __("skyops::skyops.col_flights") !!}','{!! __("skyops::skyops.col_time") !!}'];if(has.dist)H.push('{!! __("skyops::skyops.stats_distance") !!}');if(has.lr)H.push('{!! __("skyops::skyops.stats_avg_landing") !!}','{!! __("skyops::skyops.stats_soft_land") !!}','{!! __("skyops::skyops.stats_hard_land") !!}');if(has.score)H.push('{!! __("skyops::skyops.stats_avg_score") !!}');H.push('{!! __("skyops::skyops.stats_avg_time") !!}');if(has.dist)H.push('{!! __("skyops::skyops.stats_avg_dist") !!}');H.push('{!! __("skyops::skyops.col_airline") !!}');
    var lines=[H.join(';')];
    rows.forEach(function(r,i){var l=airOK(r.air_full||[]);var tot=Math.max(1,l.reduce(function(s,a){return s+(a.minutes||0);},0));var as=l.map(function(a){return a.label+(a.code?' ('+a.code+')':'')+' '+fmtHM(a.minutes)+' ('+Math.round((a.minutes/tot)*1000)/10+'%)';}).join(' | ');var row=[i+1,r.pilot,r.flights,fmtHM(r.minutes)];if(has.dist)row.push(r.nm!=null?r.nm:'');if(has.lr)row.push(r.lr!=null?r.lr:'',r.lr_softest!=null?r.lr_softest:'',r.lr_hardest!=null?r.lr_hardest:'');if(has.score)row.push(r.score!=null?r.score:'');row.push(fmtHM(r.mins_avg||0));if(has.dist)row.push(r.nm_avg!=null?r.nm_avg:'');row.push(as);lines.push(row.join(';'));});
    var blob=new Blob([lines.join('\n')],{type:'text/csv;charset=utf-8;'});
    var url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='pilot_stats_'+scope+'_'+p+'_'+(LABELS[key]||key)+'.csv';a.click();setTimeout(function(){URL.revokeObjectURL(url);},1e3);
}

/* Boot */
document.addEventListener('DOMContentLoaded',function(){
    renderScope('month');
    document.querySelectorAll('#statsTabs button[data-scope]').forEach(function(btn){btn.addEventListener('click',function(){
        document.querySelectorAll('#statsTabs button').forEach(function(b){b.classList.remove('active');});btn.classList.add('active');
        var s=btn.dataset.scope;
        document.querySelectorAll('.stats-block').forEach(function(b){b.style.display='none';});
        document.querySelector('.stats-block[data-scope="'+s+'"]').style.display='';
        renderScope(s);
    });});
});
</script>

@endsection
