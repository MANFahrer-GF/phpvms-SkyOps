{{-- modules/SkyOps/Resources/views/airline-overview.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.airlines'))

@section('skyops-content')
@php use Modules\SkyOps\Helpers\UnitHelper; use Modules\SkyOps\Helpers\SkyOpsHelper; @endphp

<style>
/* ── Airline Overview v3 — so-table based, glass KPIs ── */

/* KPI Strip — glass style like Pilot Stats */
.so-ao-kpi-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px}
.so-ao-kpi{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:14px 16px;position:relative;overflow:hidden;transition:transform .15s,border-color .2s}
.so-ao-kpi:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.16)}
html.ap-light .so-ao-kpi:hover{border-color:rgba(0,0,0,.15)}
.so-ao-kpi::before{content:'';position:absolute;inset:0;background:var(--kpi-grad,linear-gradient(135deg,rgba(59,130,246,.1),transparent 60%));pointer-events:none}
.so-ao-kpi[data-t="total"]{--kpi-grad:linear-gradient(135deg,rgba(99,102,241,.15),transparent 60%)}
.so-ao-kpi[data-t="green"]{--kpi-grad:linear-gradient(135deg,rgba(34,197,94,.18),transparent 60%)}
.so-ao-kpi[data-t="yellow"]{--kpi-grad:linear-gradient(135deg,rgba(245,158,11,.16),transparent 60%)}
.so-ao-kpi[data-t="red"]{--kpi-grad:linear-gradient(135deg,rgba(239,68,68,.15),transparent 60%)}
.so-ao-kpi[data-t="flights"]{--kpi-grad:linear-gradient(135deg,rgba(34,211,238,.14),transparent 60%)}
.so-ao-kpi[data-t="ac"]{--kpi-grad:linear-gradient(135deg,rgba(148,163,184,.12),transparent 60%)}
.so-ao-kpi-icon{position:absolute;right:14px;top:11px;opacity:.15;font-size:1.7rem}
.so-ao-kpi-label{font-size:.66rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--ap-muted);margin-bottom:5px}
.so-ao-kpi-num{font-weight:800;font-size:1.4rem;color:var(--ap-text-head);line-height:1;font-variant-numeric:tabular-nums}
.so-ao-kpi[data-t="green"] .so-ao-kpi-num{color:var(--ap-green)}
.so-ao-kpi[data-t="yellow"] .so-ao-kpi-num{color:var(--ap-amber)}
.so-ao-kpi[data-t="red"] .so-ao-kpi-num{color:var(--ap-red)}

/* Filter */
.so-ao-toggle{display:inline-flex;align-items:center;gap:.45rem;padding:.4rem .75rem;border-radius:999px;border:1px dashed var(--ap-border);cursor:pointer;user-select:none;font-size:.8rem;color:var(--ap-text)}
.so-ao-toggle input{accent-color:var(--ap-blue)}
.so-ao-chip{display:inline-flex;align-items:center;gap:.35rem;border:1px dashed var(--ap-border);border-radius:999px;padding:.15rem .55rem;font-size:.78rem;color:var(--ap-text)}
.so-ao-chip button{border:0;background:transparent;padding:0 .1rem;cursor:pointer;color:var(--ap-muted);font-size:.9rem}
.so-ao-chip button:hover{color:var(--ap-red)}

/* Table overrides for Airlines */
.so-ao-table td{white-space:nowrap}
.so-ao-table .so-ao-icao{font-weight:700;font-size:.82rem;color:var(--ap-cyan);text-decoration:none;font-variant-numeric:tabular-nums}
.so-ao-table .so-ao-icao:hover{color:var(--ap-blue);text-decoration:underline}
.so-ao-table .so-ao-name{font-weight:600;color:var(--ap-text-head);font-size:.82rem}
.so-ao-table .so-ao-country{display:inline-flex;align-items:center;gap:5px;font-size:.78rem;color:var(--ap-muted)}
.so-ao-table .td-mono{font-variant-numeric:tabular-nums;font-size:.8rem}
.so-ao-table .td-fin{font-weight:600;font-variant-numeric:tabular-nums;font-size:.78rem}
.so-ao-table .td-fin-green{color:#4ade80!important}
.so-ao-table .td-fin-red{color:#f87171!important}
html.ap-light .so-ao-table .td-fin-green{color:#16a34a!important}
html.ap-light .so-ao-table .td-fin-red{color:#dc2626!important}

/* Section dividers via left border */
.so-ao-table .td-stats{border-left:1px solid var(--ap-border);padding-left:.9rem!important}
.so-ao-table .td-finance{border-left:1px solid var(--ap-border);padding-left:.9rem!important}
.so-ao-table .th-stats{border-left:1px solid var(--ap-border);padding-left:.9rem!important}
.so-ao-table .th-finance{border-left:1px solid var(--ap-border);padding-left:.9rem!important}

/* Health Badge */
.so-ao-badge{display:inline-flex;align-items:center;gap:4px;font-size:.64rem;font-weight:700;padding:3px 9px;border-radius:6px;white-space:nowrap;font-variant-numeric:tabular-nums}
.so-ao-badge-green{background:rgba(34,197,94,.15)!important;border:1px solid rgba(34,197,94,.3);color:#86efac!important}
.so-ao-badge-yellow{background:rgba(245,158,11,.15)!important;border:1px solid rgba(245,158,11,.3);color:#fde68a!important}
.so-ao-badge-red{background:rgba(239,68,68,.15)!important;border:1px solid rgba(239,68,68,.3);color:#fca5a5!important}
html.ap-light .so-ao-badge-green{color:#166534!important;background:rgba(34,197,94,.1)!important;border-color:rgba(34,197,94,.25)}
html.ap-light .so-ao-badge-yellow{color:#92400e!important;background:rgba(245,158,11,.1)!important;border-color:rgba(245,158,11,.25)}
html.ap-light .so-ao-badge-red{color:#991b1b!important;background:rgba(239,68,68,.1)!important;border-color:rgba(239,68,68,.25)}

/* Vis badge */
#soAoVisCount{font-size:.72rem;color:var(--ap-muted);padding:4px 10px;border:1px solid var(--ap-border);border-radius:8px;background:var(--ap-surface);font-variant-numeric:tabular-nums}
</style>

{{-- PAGE HEADER CARD --}}
<div class="so-card so-page-header">
    <div class="so-page-title">
        🏢 {{ __('skyops::skyops.airlines') }}
    </div>
    <div class="so-page-subtitle">
        {{ __('skyops::skyops.airlines_subtitle') }}
    </div>
</div>

{{-- KPI STRIP — glass style --}}
<div class="so-ao-kpi-strip">
    <div class="so-ao-kpi" data-t="total">
        <div class="so-ao-kpi-icon">🏢</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_total') }}</div>
        <div class="so-ao-kpi-num">{{ $total }}</div>
    </div>
    <div class="so-ao-kpi" data-t="green">
        <div class="so-ao-kpi-icon">✅</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_active') }}</div>
        <div class="so-ao-kpi-num">{{ $green }}</div>
    </div>
    <div class="so-ao-kpi" data-t="yellow">
        <div class="so-ao-kpi-icon">⏸️</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_inactive') }}</div>
        <div class="so-ao-kpi-num">{{ $yellow }}</div>
    </div>
    <div class="so-ao-kpi" data-t="red">
        <div class="so-ao-kpi-icon">💤</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_dormant') }}</div>
        <div class="so-ao-kpi-num">{{ $red }}</div>
    </div>
    <div class="so-ao-kpi" data-t="flights">
        <div class="so-ao-kpi-icon">✈️</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_flights_total') }}</div>
        <div class="so-ao-kpi-num">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($totalFlights) }}</div>
    </div>
    <div class="so-ao-kpi" data-t="ac">
        <div class="so-ao-kpi-icon">🛩️</div>
        <div class="so-ao-kpi-label">{{ __('skyops::skyops.airlines_ac_total') }}</div>
        <div class="so-ao-kpi-num">{{ \Modules\SkyOps\Helpers\SkyOpsHelper::number($totalAC) }}</div>
    </div>
</div>

{{-- FILTER --}}
<div class="so-card" style="margin-bottom:12px;">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4 col-lg-3">
            <div class="so-filter-label">{{ __('skyops::skyops.airlines_search') }}</div>
            <input id="soAoQ" type="text" class="so-input" placeholder="e.g. DLH, Lufthansa, Germany">
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <div class="so-filter-label">{{ __('skyops::skyops.airlines_health') }}</div>
            <select id="soAoHealth" class="so-input so-select">
                <option value="">{{ __('skyops::skyops.all') }}</option>
                <option value="Green">{{ __('skyops::skyops.health_green') }}</option>
                <option value="Yellow">{{ __('skyops::skyops.health_yellow') }}</option>
                <option value="Red">{{ __('skyops::skyops.health_red') }}</option>
            </select>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <div class="so-filter-label">{{ __('skyops::skyops.airlines_min_flights') }}</div>
            <input id="soAoMinAll" type="number" min="0" step="1" class="so-input" placeholder="0">
        </div>
        <div class="col-6 col-md-2 col-lg-2 d-flex align-items-end pb-1">
            <label class="so-ao-toggle">
                <input type="checkbox" id="soAoOnlyZero"> {{ __('skyops::skyops.airlines_no_flights') }}
            </label>
        </div>
        <div class="col-6 col-md-2 col-lg-3">
            <div class="d-flex gap-2 flex-wrap">
                @if(SkyOpsHelper::csvAllowed())
                <button id="soAoCsvFiltered" class="so-btn so-btn-primary" style="font-size:.76rem;">📥 {{ __('skyops::skyops.airlines_csv_filtered') }}</button>
                <button id="soAoCsvAll" class="so-btn so-btn-ghost" style="font-size:.76rem;">{{ __('skyops::skyops.airlines_csv_all') }}</button>
                @endif
                <button id="soAoReset" class="so-btn so-btn-ghost" style="font-size:.76rem;">✕ Reset</button>
            </div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
        <span id="soAoVisCount">0</span>
        <div id="soAoChips" class="d-flex flex-wrap gap-2"></div>
    </div>
</div>

{{-- TABLE --}}
<div class="so-card" style="padding:0;overflow:hidden;">
    <div class="so-table-wrap">
        <table class="so-table so-ao-table" id="soAoTable">
            <thead>
                <tr>
                    <th style="text-align:right;width:32px;" data-sort="idx">{{ __('skyops::skyops.col_nr') }}</th>
                    <th style="width:50px;" data-sort="icao">ICAO</th>
                    <th style="min-width:120px;" data-sort="name">{{ __('skyops::skyops.col_airline') }}</th>
                    <th style="min-width:100px;" data-sort="country">{{ __('skyops::skyops.col_country') }}</th>
                    {{-- Stats group --}}
                    <th class="th-stats" style="text-align:right;width:50px;" data-sort="fltot">{{ __('skyops::skyops.col_flights') }}</th>
                    <th style="text-align:right;width:56px;" data-sort="mins">{{ __('skyops::skyops.col_hours') }}</th>
                    <th style="text-align:right;width:80px;" data-sort="dist">{{ __('skyops::skyops.col_distance') }}</th>
                    <th style="text-align:right;width:52px;" data-sort="lr">{{ __('skyops::skyops.col_avg_lr') }}</th>
                    <th style="text-align:right;width:76px;" data-sort="last">{{ __('skyops::skyops.col_last_flight') }}</th>
                    <th style="text-align:right;width:30px;" data-sort="ac">{{ __('skyops::skyops.col_ac_count') }}</th>
                    {{-- Finance group --}}
                    <th class="th-finance" style="text-align:right;width:90px;" data-sort="rev">{{ __('skyops::skyops.col_revenue') }}</th>
                    <th style="text-align:right;width:90px;" data-sort="exp">{{ __('skyops::skyops.col_expenses') }}</th>
                    <th style="text-align:right;width:90px;" data-sort="closing">{{ __('skyops::skyops.col_closing') }}</th>
                    {{-- Status --}}
                    <th style="width:70px;" data-sort="health">{{ __('skyops::skyops.col_health') }}</th>
                </tr>
            </thead>
            <tbody id="soAoTbody">
                @foreach($rows as $r)
                    @php
                        $hc = strtolower($r['health'] ?? 'red');
                        if(!in_array($hc, ['green','yellow','red'])) $hc = 'red';
                    @endphp
                    <tr class="so-ao-row"
                        data-idx="{{ $loop->iteration }}"
                        data-icao="{{ $r['icao'] }}"
                        data-name="{{ e($r['name']) }}"
                        data-country="{{ e($r['country_name'] ?? '') }}"
                        data-health="{{ $r['health'] }}"
                        data-ac="{{ $r['aircraft_total'] }}"
                        data-fltot="{{ $r['flights_total'] }}"
                        data-mins="{{ $r['minutes_total'] }}"
                        data-dist="{{ $r['distance_total'] }}"
                        data-lr="{{ $r['avg_lr'] }}"
                        data-last="{{ $r['last_raw'] ?? '' }}"
                        data-rev="{{ $r['revenue'] }}"
                        data-exp="{{ $r['expenses'] }}"
                        data-closing="{{ $r['closing'] }}">
                        <td style="text-align:right;color:var(--ap-muted);font-size:.72rem;">{{ $loop->iteration }}</td>
                        <td><a class="so-ao-icao" href="{{ url('/dairlines/' . $r['icao']) }}">{{ $r['icao'] }}</a></td>
                        <td><span class="so-ao-name">{{ $r['name'] }}</span></td>
                        <td>
                            <span class="so-ao-country" title="{{ $r['country_name'] ?? '' }}">
                                @if(!empty($r['country_code']))
                                    <span class="fi fi-{{ $r['country_code'] }}"></span>
                                @endif
                                {{ $r['country_name'] ?? '' }}
                            </span>
                        </td>
                        {{-- Stats --}}
                        <td class="td-stats" style="text-align:right;"><span class="td-mono" style="font-weight:700;">{{ $r['flights_total'] }}</span></td>
                        <td style="text-align:right;"><span class="td-mono">{{ $r['hours_hhmm'] }}</span></td>
                        <td style="text-align:right;"><span class="td-mono" style="color:var(--ap-muted);">{{ UnitHelper::distance($r['distance_total'] ?? 0) }}</span></td>
                        <td style="text-align:right;"><span class="td-mono">{{ $r['avg_lr'] }}</span></td>
                        <td style="text-align:right;"><span class="td-mono" style="font-size:.74rem;color:var(--ap-muted);">{{ $r['last_fmt'] }}</span></td>
                        <td style="text-align:right;"><span class="td-mono">{{ $r['aircraft_total'] }}</span></td>
                        {{-- Finance --}}
                        <td class="td-finance" style="text-align:right;"><span class="td-fin td-fin-green">{{ UnitHelper::money($r['revenue']) }}</span></td>
                        <td style="text-align:right;"><span class="td-fin td-fin-red">{{ UnitHelper::money($r['expenses']) }}</span></td>
                        <td style="text-align:right;">
                            @php $closingVal = (float)($r['closing'] ?? 0); @endphp
                            <span class="td-fin {{ $closingVal >= 0 ? 'td-fin-green' : 'td-fin-red' }}">
                                {{ UnitHelper::money($closingVal) }}
                            </span>
                        </td>
                        <td>
                            <span class="so-ao-badge so-ao-badge-{{ $hc }}">
                                @if($hc === 'green') ● {{ __('skyops::skyops.health_green') }}
                                @elseif($hc === 'yellow') ◑ {{ __('skyops::skyops.health_yellow') }}
                                @else ○ {{ __('skyops::skyops.health_red') }}
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div id="soAoEmpty" style="padding:40px;text-align:center;color:var(--ap-muted);display:none;">
        🔍 {{ __('skyops::skyops.no_results') }}
    </div>
</div>

<div style="font-size:.65rem;color:var(--ap-muted);margin-top:8px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
    @php
        $hCfg = config('skyops.airline_health', []);
        $hMode = $hCfg['mode'] ?? 'activity';
        $aDays = $hCfg['active_days'] ?? 30;
        $iDays = $hCfg['inactive_days'] ?? 90;
        $bGreen = SkyOpsHelper::number($hCfg['balance_green'] ?? 0);
        $bYellow = SkyOpsHelper::number($hCfg['balance_yellow'] ?? -50000);
        $cur = UnitHelper::currencySymbol();
    @endphp
    <span>ℹ️ Health ({{ __('skyops::skyops.health_mode_' . $hMode) }}):</span>
    @if($hMode === 'activity')
        <span class="so-ao-badge so-ao-badge-green" style="font-size:.6rem;">● {{ __('skyops::skyops.health_green') }}</span> ≤ {{ $aDays }}d ·
        <span class="so-ao-badge so-ao-badge-yellow" style="font-size:.6rem;">◑ {{ __('skyops::skyops.health_yellow') }}</span> {{ $aDays + 1 }}–{{ $iDays }}d ·
        <span class="so-ao-badge so-ao-badge-red" style="font-size:.6rem;">○ {{ __('skyops::skyops.health_red') }}</span> &gt; {{ $iDays }}d
    @elseif($hMode === 'financial')
        <span class="so-ao-badge so-ao-badge-green" style="font-size:.6rem;">● {{ __('skyops::skyops.health_green') }}</span> ≥ {{ $bGreen }} {{ $cur }} ·
        <span class="so-ao-badge so-ao-badge-yellow" style="font-size:.6rem;">◑ {{ __('skyops::skyops.health_yellow') }}</span> ≥ {{ $bYellow }} {{ $cur }} ·
        <span class="so-ao-badge so-ao-badge-red" style="font-size:.6rem;">○ {{ __('skyops::skyops.health_red') }}</span> &lt; {{ $bYellow }} {{ $cur }}
    @else
        <span class="so-ao-badge so-ao-badge-green" style="font-size:.6rem;">● {{ __('skyops::skyops.health_green') }}</span> ≤ {{ $aDays }}d + ≥ {{ $bGreen }} {{ $cur }} ·
        <span class="so-ao-badge so-ao-badge-yellow" style="font-size:.6rem;">◑ {{ __('skyops::skyops.health_yellow') }}</span> ≤ {{ $iDays }}d + ≥ {{ $bYellow }} {{ $cur }} ·
        <span class="so-ao-badge so-ao-badge-red" style="font-size:.6rem;">○ {{ __('skyops::skyops.health_red') }}</span> {{ __('skyops::skyops.health_worst_wins') }}
    @endif
</div>

<script>
(function(){
    var JS_LOCALE = '{!! \Modules\SkyOps\Helpers\SkyOpsHelper::jsLocale() !!}';
    var sel = function(s){return document.querySelector(s);};
    var selAll = function(s){return Array.from(document.querySelectorAll(s));};

    var table   = sel('#soAoTable');
    var tBody   = sel('#soAoTbody');
    var tbody   = selAll('#soAoTbody .so-ao-row');
    var q       = sel('#soAoQ');
    var health  = sel('#soAoHealth');
    var minAll  = sel('#soAoMinAll');
    var reset   = sel('#soAoReset');
    var onlyZero= sel('#soAoOnlyZero');
    var chips   = sel('#soAoChips');
    var visBadge= sel('#soAoVisCount');
    var empty   = sel('#soAoEmpty');

    function renumber(){
        var n=1;
        tbody.forEach(function(r){
            if(r.style.display==='none')return;
            r.dataset.idx=String(n);
            var c=r.querySelector('td');
            if(c)c.textContent=n;
            n++;
        });
    }

    function renderCounts(){
        var vis=tbody.filter(function(r){return r.style.display!=='none';}).length;
        visBadge.textContent=vis+' / '+tbody.length;
        empty.style.display=vis===0?'block':'none';
    }

    function renderChips(){
        chips.innerHTML='';
        var addChip=function(html,clr){
            var c=document.createElement('span');c.className='so-ao-chip';
            c.innerHTML=html+(clr?' <button title="\u00d7">\u00d7</button>':'');
            if(clr)c.querySelector('button').addEventListener('click',clr);
            chips.appendChild(c);
        };
        var term=(q.value||'').trim();
        if(term)addChip('<strong>{!! __("skyops::skyops.search") !!}:</strong> \u201e'+term+'\u201c',function(){q.value='';apply();});
        if(health.value)addChip('<strong>Health:</strong> '+health.value,function(){health.value='';apply();});
        if(minAll.value&&!onlyZero.checked)addChip('<strong>Min:</strong> '+minAll.value,function(){minAll.value='';apply();});
        if(onlyZero.checked)addChip('<strong>{!! __("skyops::skyops.airlines_no_flights") !!}</strong>',function(){onlyZero.checked=false;minAll.disabled=false;apply();});
        renderCounts();
    }

    function apply(){
        var needle=(q.value||'').trim().toLowerCase();
        var h=health.value;
        var min=Number(minAll.value)||0;
        var zero=onlyZero.checked;
        tbody.forEach(function(r){
            var okTxt=(r.dataset.icao||'').toLowerCase().includes(needle)||(r.dataset.name||'').toLowerCase().includes(needle)||(r.dataset.country||'').toLowerCase().includes(needle);
            var okH=!h||r.dataset.health===h;
            var fl=Number(r.dataset.fltot||0);
            var okMin=zero?fl===0:fl>=min;
            r.style.display=(okTxt&&okH&&okMin)?'':'none';
        });
        minAll.disabled=zero;
        renderChips();
        renumber();
    }

    q.addEventListener('input',apply);
    health.addEventListener('change',apply);
    minAll.addEventListener('input',apply);
    onlyZero.addEventListener('change',apply);
    reset.addEventListener('click',function(){q.value='';health.value='';minAll.value='';onlyZero.checked=false;minAll.disabled=false;apply();});

    /* Sorting — clickable column headers like Fleet */
    var sortKey=null,sortDir=1;
    var valOf=function(r,k){
        switch(k){
            case'idx':return Number(r.dataset.idx||0);case'icao':return r.dataset.icao||'';
            case'name':return(r.dataset.name||'').toLowerCase();case'country':return(r.dataset.country||'').toLowerCase();
            case'health':return{Green:1,Yellow:2,Red:3}[r.dataset.health]||4;
            case'ac':return Number(r.dataset.ac||0);case'lr':return r.dataset.lr==='n/a'?-99999:Number(r.dataset.lr);
            case'last':return r.dataset.last||'';case'fltot':return Number(r.dataset.fltot||0);
            case'mins':return Number(r.dataset.mins||0);case'dist':return Number(r.dataset.dist||0);
            case'rev':return Number(r.dataset.rev||0);case'exp':return Number(r.dataset.exp||0);
            case'closing':return Number(r.dataset.closing||0);
        }return'';
    };

    selAll('#soAoTable thead th[data-sort]').forEach(function(th){
        th.style.cursor='pointer';
        th.addEventListener('click',function(){
            var k=th.dataset.sort;
            /* Reset all th states */
            selAll('#soAoTable thead th').forEach(function(t){
                t.classList.remove('so-sort-active');
                var old=t.querySelector('.so-ao-arrow');
                if(old)old.remove();
            });
            if(sortKey===k){sortDir=-sortDir;}else{sortKey=k;sortDir=1;}
            /* Mark active */
            th.classList.add('so-sort-active');
            var arrow=document.createElement('span');
            arrow.className='so-ao-arrow';
            arrow.textContent=sortDir===1?' \u2191':' \u2193';
            arrow.style.fontSize='.6rem';
            th.appendChild(arrow);
            /* Sort rows */
            var rows=selAll('#soAoTbody .so-ao-row');
            rows.sort(function(a,b){
                var va=valOf(a,k),vb=valOf(b,k);
                return(typeof va==='number'&&typeof vb==='number')?(va-vb)*sortDir:(va>vb?1:va<vb?-1:0)*sortDir;
            });
            rows.forEach(function(r){tBody.appendChild(r);});
            renumber();
        });
    });

    /* CSV Export */
    function buildRows(all){
        var trs=all?selAll('#soAoTbody .so-ao-row'):selAll('#soAoTbody .so-ao-row').filter(function(r){return r.style.display!=='none';});
        var out=[['{!! __("skyops::skyops.col_nr") !!}','ICAO','{!! __("skyops::skyops.col_airline") !!}','{!! __("skyops::skyops.col_country") !!}','{!! __("skyops::skyops.col_flights") !!}','{!! __("skyops::skyops.col_hours") !!}','{!! __("skyops::skyops.col_distance") !!}','{!! __("skyops::skyops.col_avg_lr") !!}','{!! __("skyops::skyops.col_last_flight") !!}','{!! __("skyops::skyops.col_ac_count") !!}','{!! __("skyops::skyops.col_revenue") !!}','{!! __("skyops::skyops.col_expenses") !!}','{!! __("skyops::skyops.col_closing") !!}','{!! __("skyops::skyops.col_health") !!}']];
        var i=1;
        trs.forEach(function(r){
            out.push([i++,r.dataset.icao,r.dataset.name,r.dataset.country,r.dataset.fltot,Math.floor(Number(r.dataset.mins||0)/60)+':'+String(Number(r.dataset.mins||0)%60).padStart(2,'0'),Number(r.dataset.dist||0),r.dataset.lr,r.dataset.last,r.dataset.ac,Number(r.dataset.rev||0).toLocaleString(JS_LOCALE,{minimumFractionDigits:2}),Number(r.dataset.exp||0).toLocaleString(JS_LOCALE,{minimumFractionDigits:2}),Number(r.dataset.closing||0).toLocaleString(JS_LOCALE,{minimumFractionDigits:2}),r.dataset.health]);
        });
        return out;
    }

    function dlCSV(rows,f){
        var csv='sep=;\n'+rows.map(function(r){return r.map(function(v){return '"'+String(v!=null?v:'').replace(/"/g,'""')+'"';}).join(';');}).join('\n');
        var a=document.createElement('a');
        a.href=URL.createObjectURL(new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8;'}));
        var d=new Date(),p=function(n){return String(n).padStart(2,'0');};
        a.download=f.replace('STAMP',d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'_'+p(d.getHours())+p(d.getMinutes()));
        a.click();URL.revokeObjectURL(a.href);
    }

    if(sel('#soAoCsvFiltered'))sel('#soAoCsvFiltered').addEventListener('click',function(){dlCSV(buildRows(false),'airlines_STAMP.csv');});
    if(sel('#soAoCsvAll'))sel('#soAoCsvAll').addEventListener('click',function(){dlCSV(buildRows(true),'airlines_all_STAMP.csv');});

    apply();
})();
</script>

@endsection
