{{-- modules/SkyOps/Resources/views/guide.blade.php --}}
@extends('skyops::layouts.app')
@section('title', __('skyops::skyops.guide_title'))

@section('skyops-content')
@php
    use Modules\SkyOps\Helpers\SkyOpsHelper;
    use Modules\SkyOps\Helpers\UnitHelper;
    use Carbon\Carbon;

    // Compute effective epoch (same logic as PilotStatsService)
    $epochConfig = config('skyops.epoch');
    $phpvmsStart = setting('general.start_date');
    if ($epochConfig) {
        $epochEffective = Carbon::parse($epochConfig)->toDateString();
        $epochSource = 'config';
    } elseif ($phpvmsStart) {
        $epochEffective = Carbon::parse($phpvmsStart)->toDateString();
        $epochSource = 'phpvms';
    } else {
        $epochEffective = '2000-01-01';
        $epochSource = 'fallback';
    }

    // Read live config for admin section
    $cfg = [
        'landing'       => config('skyops.landing', 'redirect'),
        'date_fmt'      => config('skyops.locale.date_format', 'auto'),
        'datetime_fmt'  => config('skyops.locale.date_time_format', 'auto'),
        'number_fmt'    => config('skyops.locale.number_format', 'auto'),
        'csv'           => config('skyops.csv_export', 'admin'),
        'pilot_name'    => config('skyops.pilot_name_format', 'short'),
        'health_mode'   => config('skyops.airline_health.mode', 'activity'),
        'health_active' => config('skyops.airline_health.active_days', 30),
        'health_inactive' => config('skyops.airline_health.inactive_days', 90),
        'health_noflight' => config('skyops.airline_health.no_flight', 'Red'),
        'health_bal_green'  => config('skyops.airline_health.balance_green', 0),
        'health_bal_yellow' => config('skyops.airline_health.balance_yellow', -50000),
        'dep_sort'      => config('skyops.departures.sort_mode', 'auto'),
        'dep_dpt'       => config('skyops.departures.show_dpt_time', 'auto'),
        'dep_arr'       => config('skyops.departures.show_arr_time', 'auto'),
        'dep_dist'      => config('skyops.departures.show_distance', true),
        'dep_ft'        => config('skyops.departures.show_flight_time', true),
        'dep_phpvms'    => config('skyops.departures.respect_phpvms_settings', false),
        'dep_bookable'  => config('skyops.departures.bookable_only', false),
        'dep_bookstat'  => config('skyops.departures.show_booking_status', true),
        'dep_types'     => config('skyops.departures.aircraft_type_source', 'flight_icao'),
        'dep_active'    => config('skyops.departures.aircraft_active_only', true),
        'cache_dash'    => config('skyops.cache_ttl.dashboard', 3),
        'cache_pilots'  => config('skyops.cache_ttl.pilot_stats', 5),
        'cache_airlines'=> config('skyops.cache_ttl.airline_overview', 10),
        'cache_fleet'   => config('skyops.cache_ttl.fleet_stats', 5),
        'cache_filters' => config('skyops.cache_ttl.filter_options', 15),
        'epoch'         => $epochConfig,
        'epoch_effective' => $epochEffective,
        'epoch_source'  => $epochSource,
        'phpvms_start'  => $phpvmsStart,
        'locale'        => app()->getLocale() ?? 'en',
        'currency'      => UnitHelper::currencySymbol(),
        'dist_unit'     => UnitHelper::label('distance'),
        'fuel_unit'     => UnitHelper::label('fuel'),
        // Theme
        'glass_mode'       => config('skyops.theme.glass_mode', true),
        'solid_bg_dark'    => config('skyops.theme.solid.card_bg_dark', '#1e293b'),
        'solid_bg_light'   => config('skyops.theme.solid.card_bg_light', '#ffffff'),
        'solid_inner_dark' => config('skyops.theme.solid.inner_bg_dark', '#151b2b'),
        'solid_inner_light'=> config('skyops.theme.solid.inner_bg_light', '#f8fafc'),
        'solid_border_dark'  => config('skyops.theme.solid.border_dark', 'rgba(255,255,255,0.08)'),
        'solid_border_light' => config('skyops.theme.solid.border_light', 'rgba(0,0,0,0.08)'),
    ];
@endphp

<style>
/* ── Guide — so-gd-* prefix ── */
/* All guide surfaces use CSS variables from _styles.blade.php */
/* Glass mode: automatic backdrop-filter via .so-glass selector in _styles */
/* Solid mode: uses configured colors from config.php */

.so-gd-hero{font-weight:800;font-size:1.5rem;letter-spacing:-.02em;color:var(--ap-text-head);display:flex;align-items:center;gap:10px;margin-bottom:4px}
.so-gd-sub{font-size:.72rem;color:var(--ap-muted);margin-bottom:20px}

/* TOC */
.so-gd-toc{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;margin-bottom:32px}
.so-gd-toc a{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:10px;text-decoration:none;color:var(--ap-text-head);font-size:.82rem;font-weight:600;transition:border-color .15s,transform .1s,box-shadow .15s}
.so-gd-toc a:hover{border-color:var(--ap-blue);transform:translateY(-1px);color:var(--ap-text-head);text-decoration:none}
html.ap-light .so-gd-toc a{box-shadow:0 1px 4px rgba(0,0,0,.04)}
html.ap-light .so-gd-toc a:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}
.so-gd-toc-icon{font-size:1rem;flex-shrink:0;width:22px;text-align:center}
.so-gd-toc-tag{font-size:.52rem;font-weight:700;padding:2px 6px;border-radius:4px;color:#fff;margin-left:auto;text-transform:uppercase;flex-shrink:0}
.so-gd-toc-tag-admin{background:var(--ap-amber)}
.so-gd-toc-tag-new{background:var(--ap-green)}

/* Sections */
.so-gd-section{margin-bottom:36px;scroll-margin-top:80px}
.so-gd-h2{font-weight:800;font-size:1.1rem;color:var(--ap-text-head);display:flex;align-items:center;gap:8px;margin-bottom:14px;padding:14px 18px;background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:12px}
.so-gd-box{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:12px;padding:16px 20px;margin-bottom:14px;font-size:.82rem;line-height:1.7;color:var(--ap-text)}
.so-gd-box p{margin:0 0 12px}
.so-gd-box p:last-child{margin-bottom:0}

/* Notes */
.so-gd-note{border-left:3px solid var(--ap-blue);padding:10px 14px;margin:12px 0;background:rgba(59,130,246,.05);border-radius:0 8px 8px 0;font-size:.78rem;color:var(--ap-muted)}
.so-gd-note strong{color:var(--ap-text-head)}
.so-gd-warn{border-left-color:var(--ap-amber);background:rgba(245,158,11,.05)}
.so-gd-tip{border-left-color:var(--ap-green);background:rgba(34,197,94,.05)}

/* Tables */
.so-gd-table{width:100%;border-collapse:collapse;font-size:.8rem;margin:12px 0}
.so-gd-table th{text-align:left;padding:6px 10px;border-bottom:2px solid var(--ap-border);color:var(--ap-muted);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.so-gd-table td{padding:7px 10px;border-bottom:1px solid var(--ap-border);color:var(--ap-text);line-height:1.5}
.so-gd-table tr:last-child td{border-bottom:none}
.so-gd-code{font-family:var(--ap-font-mono);font-size:.74rem;background:rgba(125,133,144,.1);padding:2px 6px;border-radius:4px;color:var(--ap-cyan)}

/* Live config value badge */
.so-gd-live-val{display:inline-flex;align-items:center;gap:4px;font-size:.66rem;font-weight:700;padding:2px 8px;border-radius:5px;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.2);color:var(--ap-blue);font-family:var(--ap-font-mono);white-space:nowrap;margin-left:4px}
html.ap-light .so-gd-live-val{background:rgba(59,130,246,.08)}

/* Steps */
.so-gd-steps{counter-reset:gdstep;margin:12px 0;padding:0}
.so-gd-step{display:flex;align-items:flex-start;gap:10px;padding:8px 0;font-size:.82rem;line-height:1.6}
.so-gd-step::before{counter-increment:gdstep;content:counter(gdstep);min-width:24px;height:24px;border-radius:50%;background:var(--ap-blue);color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}

/* Admin banner */
.so-gd-admin-banner{background:linear-gradient(135deg,rgba(245,158,11,.1),transparent 60%);border:1px solid rgba(245,158,11,.25);border-radius:12px;padding:14px 20px;margin:36px 0 20px;display:flex;align-items:center;gap:10px;font-size:.85rem;font-weight:700;color:var(--ap-amber)}

/* Config display */
.so-gd-cfg{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:12px;overflow:hidden;margin:12px 0}
html.ap-light .so-gd-cfg{box-shadow:0 1px 6px rgba(0,0,0,.05)}
.so-gd-cfg-header{padding:10px 16px;background:rgba(59,130,246,.06);border-bottom:1px solid var(--ap-border);font-size:.72rem;font-weight:700;color:var(--ap-muted);text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;gap:8px}
.so-gd-cfg-header .so-gd-cfg-live{font-size:.58rem;padding:2px 6px;border-radius:4px;background:var(--ap-green);color:#fff;font-weight:700;text-transform:uppercase;margin-left:auto}
.so-gd-cfg-row{display:flex;align-items:center;padding:8px 16px;border-bottom:1px solid var(--ap-border);font-size:.78rem;gap:8px}
.so-gd-cfg-row:last-child{border-bottom:none}
.so-gd-cfg-row:nth-child(even){background:rgba(125,133,144,.02)}
.so-gd-cfg-key{font-family:var(--ap-font-mono);font-size:.74rem;color:var(--ap-cyan);min-width:180px;flex-shrink:0}
.so-gd-cfg-val{font-family:var(--ap-font-mono);font-size:.74rem;font-weight:700;color:var(--ap-text-head);min-width:100px}
.so-gd-cfg-desc{color:var(--ap-muted);font-size:.74rem;flex:1;min-width:0}
</style>

{{-- PAGE HEADER CARD --}}
<div class="so-card so-page-header">
    <div class="so-page-title">📖 {{ __('skyops::skyops.guide_title') }}</div>
    <div class="so-page-subtitle">{{ __('skyops::skyops.guide_subtitle') }}</div>
</div>

{{-- TABLE OF CONTENTS --}}
<div class="so-gd-toc">
    <a href="#access"><span class="so-gd-toc-icon">🔑</span> {{ __('skyops::skyops.guide_access') }}</a>
    <a href="#dashboard"><span class="so-gd-toc-icon">🏠</span> {{ __('skyops::skyops.dashboard') }}</a>
    <a href="#pireps"><span class="so-gd-toc-icon">📋</span> {{ __('skyops::skyops.pirep_list') }}</a>
    <a href="#fleet"><span class="so-gd-toc-icon">🛩️</span> {{ __('skyops::skyops.fleet') }}</a>
    <a href="#pilots"><span class="so-gd-toc-icon">👨‍✈️</span> {{ __('skyops::skyops.pilot_stats') }}</a>
    <a href="#airlines"><span class="so-gd-toc-icon">🏢</span> {{ __('skyops::skyops.airlines') }}</a>
    <a href="#departures"><span class="so-gd-toc-icon">🛫</span> {{ __('skyops::skyops.departures') }}</a>
    <a href="#landing"><span class="so-gd-toc-icon">🎯</span> {{ __('skyops::skyops.guide_landing') }}</a>
    <a href="#units"><span class="so-gd-toc-icon">⚙️</span> {{ __('skyops::skyops.guide_units') }}</a>
    <a href="#faq"><span class="so-gd-toc-icon">❓</span> {{ __('skyops::skyops.guide_faq') }}</a>
    @if($isAdmin)
    <a href="#admin"><span class="so-gd-toc-icon">🔧</span> {{ __('skyops::skyops.guide_admin') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-links"><span class="so-gd-toc-icon">🔗</span> {{ __('skyops::skyops.guide_admin_links') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-general"><span class="so-gd-toc-icon">🏠</span> {{ __('skyops::skyops.guide_admin_general') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-locale"><span class="so-gd-toc-icon">🌍</span> {{ __('skyops::skyops.guide_admin_locale') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-health"><span class="so-gd-toc-icon">💊</span> {{ __('skyops::skyops.guide_admin_health') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-departures"><span class="so-gd-toc-icon">🛫</span> {{ __('skyops::skyops.guide_admin_dep') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-cache"><span class="so-gd-toc-icon">⚡</span> {{ __('skyops::skyops.guide_admin_cache') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-theme"><span class="so-gd-toc-icon">🎨</span> {{ __('skyops::skyops.guide_admin_theme') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    <a href="#admin-units"><span class="so-gd-toc-icon">📐</span> {{ __('skyops::skyops.guide_admin_units') }} <span class="so-gd-toc-tag so-gd-toc-tag-admin">Admin</span></a>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════
     PILOT GUIDE
     ═══════════════════════════════════════════════════════ --}}

{{-- ACCESS --}}
<div class="so-gd-section" id="access">
    <div class="so-gd-h2">🔑 {{ __('skyops::skyops.guide_access') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_access_intro') }}</p>
        <div class="so-gd-steps">
            <div class="so-gd-step">{{ __('skyops::skyops.guide_access_step1') }}</div>
            <div class="so-gd-step">{{ __('skyops::skyops.guide_access_step2') }}</div>
            <div class="so-gd-step">{{ __('skyops::skyops.guide_access_step3') }}</div>
        </div>
        <div class="so-gd-note">
            <strong>{{ __('skyops::skyops.guide_access_note_title') }}</strong> {{ __('skyops::skyops.guide_access_note') }}
        </div>
        <p>{{ __('skyops::skyops.guide_access_p2') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_access_tip') }}
        </div>
    </div>
</div>

{{-- DASHBOARD --}}
<div class="so-gd-section" id="dashboard">
    <div class="so-gd-h2">🏠 {{ __('skyops::skyops.dashboard') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_dash_p1') }}</p>
        <p>{{ __('skyops::skyops.guide_dash_p2') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_element') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><strong>{{ __('skyops::skyops.guide_dash_live') }}</strong></td><td>{{ __('skyops::skyops.guide_dash_live_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_dash_kpi') }}</strong></td><td>{{ __('skyops::skyops.guide_dash_kpi_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_dash_cards') }}</strong></td><td>{{ __('skyops::skyops.guide_dash_cards_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_dash_activity') }}</strong></td><td>{{ __('skyops::skyops.guide_dash_activity_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_dash_top') }}</strong></td><td>{{ __('skyops::skyops.guide_dash_top_desc') }}</td></tr>
            </tbody>
        </table>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_dash_tip') }}
        </div>
    </div>
</div>

{{-- PIREP LIST --}}
<div class="so-gd-section" id="pireps">
    <div class="so-gd-h2">📋 {{ __('skyops::skyops.pirep_list') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_pireps_intro') }}</p>
        <p><strong>{{ __('skyops::skyops.guide_pireps_what') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_pireps_what_desc') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_pireps_filter_title') }}</strong></p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_filter') }}</th><th>{{ __('skyops::skyops.guide_how') }}</th></tr></thead>
            <tbody>
                <tr><td><strong>{{ __('skyops::skyops.guide_pireps_search') }}</strong></td><td>{{ __('skyops::skyops.guide_pireps_search_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_pireps_date') }}</strong></td><td>{{ __('skyops::skyops.guide_pireps_date_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_pireps_source') }}</strong></td><td>{{ __('skyops::skyops.guide_pireps_source_desc') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.guide_pireps_sort') }}</strong></td><td>{{ __('skyops::skyops.guide_pireps_sort_desc') }}</td></tr>
            </tbody>
        </table>

        <div class="so-gd-note">
            <strong>{{ __('skyops::skyops.guide_pireps_live_title') }}</strong> {{ __('skyops::skyops.guide_pireps_live_desc') }}
        </div>

        <p><strong>{{ __('skyops::skyops.guide_pireps_columns_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_pireps_columns_desc') }}</p>

        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_pireps_tip') }}
        </div>
    </div>
</div>

{{-- FLEET --}}
<div class="so-gd-section" id="fleet">
    <div class="so-gd-h2">🛩️ {{ __('skyops::skyops.fleet') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_fleet_intro') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_fleet_status_title') }}</strong></p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_badge') }}</th><th>{{ __('skyops::skyops.guide_meaning') }}</th></tr></thead>
            <tbody>
                <tr><td><span style="color:var(--ap-green);font-weight:700;">● {{ __('skyops::skyops.fleet_status_active') }}</span></td><td>{{ __('skyops::skyops.guide_fleet_active') }}</td></tr>
                <tr><td><span style="color:var(--ap-amber);font-weight:700;">● {{ __('skyops::skyops.fleet_status_maintenance') }}</span></td><td>{{ __('skyops::skyops.guide_fleet_maint') }}</td></tr>
                <tr><td><span style="color:var(--ap-muted);font-weight:700;">● {{ __('skyops::skyops.fleet_status_storage') }}</span></td><td>{{ __('skyops::skyops.guide_fleet_storage') }}</td></tr>
                <tr><td><span style="color:var(--ap-red);font-weight:700;">● {{ __('skyops::skyops.fleet_status_retired') }}</span></td><td>{{ __('skyops::skyops.guide_fleet_retired') }}</td></tr>
                <tr><td><span style="color:var(--ap-red);font-weight:700;">● {{ __('skyops::skyops.fleet_status_scrapped') }}</span></td><td>{{ __('skyops::skyops.guide_fleet_scrapped') }}</td></tr>
            </tbody>
        </table>

        <p><strong>{{ __('skyops::skyops.guide_fleet_filter_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_fleet_filter_desc') }}</p>
        <p>{{ __('skyops::skyops.guide_fleet_p2') }}</p>
    </div>
</div>

{{-- PILOT STATISTICS --}}
<div class="so-gd-section" id="pilots">
    <div class="so-gd-h2">👨‍✈️ {{ __('skyops::skyops.pilot_stats') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_stats_intro') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_stats_period_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_stats_period_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_period') }}</th><th>{{ __('skyops::skyops.guide_what_shows') }}</th></tr></thead>
            <tbody>
                <tr><td><strong>{{ __('skyops::skyops.stats_period_month') }}</strong></td><td>{{ __('skyops::skyops.guide_stats_month') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.stats_period_quarter') }}</strong></td><td>{{ __('skyops::skyops.guide_stats_quarter') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.stats_period_year') }}</strong></td><td>{{ __('skyops::skyops.guide_stats_year') }}</td></tr>
                <tr><td><strong>{{ __('skyops::skyops.all') }}</strong></td><td>{{ __('skyops::skyops.guide_stats_all') }}</td></tr>
            </tbody>
        </table>

        <p><strong>{{ __('skyops::skyops.guide_stats_kpi_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_stats_kpi_desc') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_stats_lb_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_stats_lb_desc') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_stats_chart_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_stats_chart_desc') }}</p>

        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_stats_tip') }}
        </div>
    </div>
</div>

{{-- AIRLINES --}}
<div class="so-gd-section" id="airlines">
    <div class="so-gd-h2">🏢 {{ __('skyops::skyops.airlines') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_airlines_intro') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_airlines_health_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_airlines_health_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_badge') }}</th><th>{{ __('skyops::skyops.guide_meaning') }}</th></tr></thead>
            <tbody>
                <tr><td><span style="color:#86efac;font-weight:700;">● {{ __('skyops::skyops.health_green') }}</span></td><td>{{ __('skyops::skyops.guide_health_green') }}</td></tr>
                <tr><td><span style="color:#fde68a;font-weight:700;">◑ {{ __('skyops::skyops.health_yellow') }}</span></td><td>{{ __('skyops::skyops.guide_health_yellow') }}</td></tr>
                <tr><td><span style="color:#fca5a5;font-weight:700;">○ {{ __('skyops::skyops.health_red') }}</span></td><td>{{ __('skyops::skyops.guide_health_red') }}</td></tr>
            </tbody>
        </table>

        <div class="so-gd-warn so-gd-note">
            <strong>{{ __('skyops::skyops.guide_airlines_note_title') }}</strong> {{ __('skyops::skyops.guide_airlines_note') }}
        </div>

        <p><strong>{{ __('skyops::skyops.guide_airlines_finance_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_airlines_finance_desc') }}</p>

        <p>{{ __('skyops::skyops.guide_airlines_p2') }}</p>
    </div>
</div>

{{-- DEPARTURES --}}
<div class="so-gd-section" id="departures">
    <div class="so-gd-h2">🛫 {{ __('skyops::skyops.departures') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_dep_intro') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_dep_filter_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_dep_p1') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_dep_slider_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_dep_p2') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_dep_detail_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_dep_p3') }}</p>

        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_dep_tip') }}
        </div>
    </div>
</div>

{{-- LANDING RATES --}}
<div class="so-gd-section" id="landing">
    <div class="so-gd-h2">🎯 {{ __('skyops::skyops.guide_landing') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_landing_intro') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_range') }}</th><th>{{ __('skyops::skyops.guide_rating') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                @foreach(config('skyops.landing_thresholds', []) as $t)
                <tr>
                    <td style="font-variant-numeric:tabular-nums;">
                        @if($t['min'] === null) &lt; {{ $t['max'] }}
                        @elseif($t['max'] === null) &gt; {{ $t['min'] }}
                        @else {{ $t['min'] }} {{ __('skyops::skyops.guide_to') }} {{ $t['max'] }}
                        @endif fpm
                    </td>
                    <td>{{ $t['emoji'] }} <strong>{{ ucfirst($t['label']) }}</strong></td>
                    <td>{{ __('skyops::skyops.guide_lr_' . $t['label']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="so-gd-note">
            <strong>fpm</strong> = {{ __('skyops::skyops.guide_fpm_explain') }}
        </div>
        <div class="so-gd-warn so-gd-note">
            <strong>{{ __('skyops::skyops.guide_lr_warn_title') }}</strong> {{ __('skyops::skyops.guide_lr_warn') }}
        </div>
    </div>
</div>

{{-- UNITS & LANGUAGE --}}
<div class="so-gd-section" id="units">
    <div class="so-gd-h2">⚙️ {{ __('skyops::skyops.guide_units') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_units_p1') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_what') }}</th><th>{{ __('skyops::skyops.guide_your_va') }}</th></tr></thead>
            <tbody>
                <tr><td>{{ __('skyops::skyops.col_distance') }}</td><td><strong>{{ $cfg['dist_unit'] }}</strong></td></tr>
                <tr><td>{{ __('skyops::skyops.col_fuel') }}</td><td><strong>{{ $cfg['fuel_unit'] }}</strong></td></tr>
                <tr><td>{{ __('skyops::skyops.guide_currency') }}</td><td><strong>{{ $cfg['currency'] }}</strong></td></tr>
                <tr><td>{{ __('skyops::skyops.guide_language') }}</td><td><strong>{{ strtoupper($cfg['locale']) }}</strong></td></tr>
            </tbody>
        </table>
        <p>{{ __('skyops::skyops.guide_units_p2') }}</p>
    </div>
</div>

{{-- FAQ --}}
<div class="so-gd-section" id="faq">
    <div class="so-gd-h2">❓ {{ __('skyops::skyops.guide_faq') }}</div>
    <div class="so-gd-box">
        <p><strong>{{ __('skyops::skyops.guide_faq_q1') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_faq_a1') }}</p>
        <p><strong>{{ __('skyops::skyops.guide_faq_q2') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_faq_a2') }}</p>
        <p><strong>{{ __('skyops::skyops.guide_faq_q3') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_faq_a3') }}</p>
        <p><strong>{{ __('skyops::skyops.guide_faq_q4') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_faq_a4') }}</p>
        <p><strong>{{ __('skyops::skyops.guide_faq_q5') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_faq_a5') }}</p>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ADMIN GUIDE — only for admins
     ═══════════════════════════════════════════════════════ --}}
@if($isAdmin)
<div class="so-gd-admin-banner" id="admin">🔧 {{ __('skyops::skyops.guide_admin_banner') }}</div>

{{-- INTRO --}}
<div class="so-gd-section">
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_admin_intro_p1') }}</p>
        <p>{{ __('skyops::skyops.guide_admin_intro_p2') }}</p>
        <div class="so-gd-warn so-gd-note">
            <strong>{{ __('skyops::skyops.guide_admin_cache_warn_title') }}</strong> {{ __('skyops::skyops.guide_admin_cache_warn') }}
        </div>
    </div>
</div>

{{-- ── LINKS & NAVIGATION ── --}}
<div class="so-gd-section" id="admin-links">
    <div class="so-gd-h2">🔗 {{ __('skyops::skyops.guide_admin_links') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_links_intro') }}</p>

        <table class="so-gd-table">
            <thead><tr><th>URL</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">/skyops</span></td><td>{{ __('skyops::skyops.guide_adm_link_root') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/pireps</span></td><td>{{ __('skyops::skyops.guide_adm_link_pireps') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/fleet</span></td><td>{{ __('skyops::skyops.guide_adm_link_fleet') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/pilots</span></td><td>{{ __('skyops::skyops.guide_adm_link_pilots') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/airlines</span></td><td>{{ __('skyops::skyops.guide_adm_link_airlines') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/departures</span></td><td>{{ __('skyops::skyops.guide_adm_link_departures') }}</td></tr>
                <tr><td><span class="so-gd-code">/skyops/guide</span></td><td>{{ __('skyops::skyops.guide_adm_link_guide') }}</td></tr>
            </tbody>
        </table>

        <p><strong>{{ __('skyops::skyops.guide_adm_links_option_title') }}</strong></p>

        <p><strong>{{ __('skyops::skyops.guide_adm_links_opt_a') }}</strong> {{ __('skyops::skyops.guide_adm_links_opt_a_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_adm_links_opt_a_how') }}</strong> {{ __('skyops::skyops.guide_adm_links_opt_a_steps') }}
        </div>

        <p><strong>{{ __('skyops::skyops.guide_adm_links_opt_b') }}</strong> {{ __('skyops::skyops.guide_adm_links_opt_b_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_adm_links_opt_b_how') }}</strong> {{ __('skyops::skyops.guide_adm_links_opt_b_steps') }}
        </div>

        <p><strong>{{ __('skyops::skyops.guide_adm_links_opt_c') }}</strong> {{ __('skyops::skyops.guide_adm_links_opt_c_desc') }}</p>

        <div class="so-gd-note">
            <strong>{{ __('skyops::skyops.guide_adm_links_nav_title') }}</strong> {{ __('skyops::skyops.guide_adm_links_nav_desc') }}
        </div>
    </div>
</div>

{{-- ── GENERAL ── --}}
<div class="so-gd-section" id="admin-general">
    <div class="so-gd-h2">🏠 {{ __('skyops::skyops.guide_admin_general') }}</div>
    <div class="so-gd-box">
        <p><strong>landing</strong> — {{ __('skyops::skyops.guide_adm_landing_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_example') }}:</strong> {{ __('skyops::skyops.guide_adm_landing_ex') }}
        </div>

        <p><strong>pilot_name_format</strong> — {{ __('skyops::skyops.guide_adm_pilot_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_example') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">full</span></td><td>Thomas Kant</td><td>{{ __('skyops::skyops.guide_adm_name_full') }}</td></tr>
                <tr><td><span class="so-gd-code">short</span></td><td>Thomas K.</td><td>{{ __('skyops::skyops.guide_adm_name_short') }}</td></tr>
                <tr><td><span class="so-gd-code">callsign</span></td><td>GSG001</td><td>{{ __('skyops::skyops.guide_adm_name_callsign') }}</td></tr>
                <tr><td><span class="so-gd-code">id</span></td><td>GSG001 (Thomas K.)</td><td>{{ __('skyops::skyops.guide_adm_name_id') }}</td></tr>
            </tbody>
        </table>
        <div class="so-gd-warn so-gd-note">
            <strong>{{ __('skyops::skyops.guide_adm_gdpr_title') }}</strong> {{ __('skyops::skyops.guide_adm_gdpr_note') }}
        </div>

        <p><strong>csv_export</strong> — {{ __('skyops::skyops.guide_adm_csv_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">admin</span></td><td>{{ __('skyops::skyops.guide_adm_csv_admin') }}</td></tr>
                <tr><td><span class="so-gd-code">all</span></td><td>{{ __('skyops::skyops.guide_adm_csv_all') }}</td></tr>
                <tr><td><span class="so-gd-code">disabled</span></td><td>{{ __('skyops::skyops.guide_adm_csv_disabled') }}</td></tr>
            </tbody>
        </table>

        <p><strong>epoch</strong> — {{ __('skyops::skyops.guide_adm_epoch_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_example') }}:</strong> {{ __('skyops::skyops.guide_adm_epoch_ex') }}
        </div>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">{{ __('skyops::skyops.guide_admin_general') }} <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">landing</span><span class="so-gd-cfg-val">{{ $cfg['landing'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_landing') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">pilot_name_format</span><span class="so-gd-cfg-val">{{ $cfg['pilot_name'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_pilot_name') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">csv_export</span><span class="so-gd-cfg-val">{{ $cfg['csv'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_csv') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">epoch (config)</span><span class="so-gd-cfg-val">{{ $cfg['epoch'] ?? 'null' }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_epoch') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">phpVMS Start Date</span><span class="so-gd-cfg-val">{{ $cfg['phpvms_start'] ?? 'null' }}</span><span class="so-gd-cfg-desc">Admin → Settings → general.start_date</span></div>
        <div class="so-gd-cfg-row" style="background:rgba(34,197,94,.06);"><span class="so-gd-cfg-key">→ {{ __('skyops::skyops.guide_cfg_effective') }}</span><span class="so-gd-cfg-val" style="color:var(--ap-green);">{{ SkyOpsHelper::fmtDate($cfg['epoch_effective']) }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_source_' . $cfg['epoch_source']) }}</span></div>
    </div>
</div>

{{-- ── LOCALE ── --}}
<div class="so-gd-section" id="admin-locale">
    <div class="so-gd-h2">🌍 {{ __('skyops::skyops.guide_admin_locale') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_locale_intro') }}</p>

        <p><strong>date_format</strong> — {{ __('skyops::skyops.guide_adm_date_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_example') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">auto</span></td><td>{{ SkyOpsHelper::fmtDate(now()) }}</td><td>{{ __('skyops::skyops.guide_adm_auto_detect') }}</td></tr>
                <tr><td><span class="so-gd-code">d.m.Y</span></td><td>04.03.2026</td><td>{{ __('skyops::skyops.guide_adm_date_eu') }}</td></tr>
                <tr><td><span class="so-gd-code">m/d/Y</span></td><td>03/04/2026</td><td>{{ __('skyops::skyops.guide_adm_date_us') }}</td></tr>
                <tr><td><span class="so-gd-code">Y-m-d</span></td><td>2026-03-04</td><td>{{ __('skyops::skyops.guide_adm_date_iso') }}</td></tr>
            </tbody>
        </table>

        <p><strong>date_time_format</strong> — {{ __('skyops::skyops.guide_adm_datetime_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_adm_clock_title') }}</strong> {{ __('skyops::skyops.guide_adm_clock_desc') }}
        </div>

        <p><strong>number_format</strong> — {{ __('skyops::skyops.guide_adm_number_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_example') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">auto</span></td><td>{{ SkyOpsHelper::number(1234567.89, 2) }} ({{ __('skyops::skyops.guide_adm_auto_detect') }})</td></tr>
                <tr><td><span class="so-gd-code">de</span></td><td>1.234.567,89</td></tr>
                <tr><td><span class="so-gd-code">en</span></td><td>1,234,567.89</td></tr>
            </tbody>
        </table>

        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_adm_locale_tip') }}
        </div>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">Locale <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">date_format</span><span class="so-gd-cfg-val">{{ $cfg['date_fmt'] }}</span><span class="so-gd-cfg-desc">→ {{ SkyOpsHelper::fmtDate(now()) }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">date_time_format</span><span class="so-gd-cfg-val">{{ $cfg['datetime_fmt'] }}</span><span class="so-gd-cfg-desc">→ {{ SkyOpsHelper::fmtDateTime(now()) }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">number_format</span><span class="so-gd-cfg-val">{{ $cfg['number_fmt'] }}</span><span class="so-gd-cfg-desc">→ {{ SkyOpsHelper::number(1234567.89, 2) }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">phpVMS locale</span><span class="so-gd-cfg-val">{{ $cfg['locale'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_locale') }}</span></div>
    </div>
</div>

{{-- ── HEALTH ── --}}
<div class="so-gd-section" id="admin-health">
    <div class="so-gd-h2">💊 {{ __('skyops::skyops.guide_admin_health') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_health_intro') }}</p>

        <p><strong>{{ __('skyops::skyops.guide_adm_health_modes') }}</strong></p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_mode') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th><th>{{ __('skyops::skyops.guide_example') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">activity</span></td><td>{{ __('skyops::skyops.guide_adm_mode_activity') }}</td><td>{{ __('skyops::skyops.guide_adm_mode_activity_ex') }}</td></tr>
                <tr><td><span class="so-gd-code">financial</span></td><td>{{ __('skyops::skyops.guide_adm_mode_financial') }}</td><td>{{ __('skyops::skyops.guide_adm_mode_financial_ex') }}</td></tr>
                <tr><td><span class="so-gd-code">combined</span></td><td>{{ __('skyops::skyops.guide_adm_mode_combined') }}</td><td>{{ __('skyops::skyops.guide_adm_mode_combined_ex') }}</td></tr>
            </tbody>
        </table>

        <p><strong>{{ __('skyops::skyops.guide_adm_health_thresholds') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_adm_health_thresh_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_example') }}:</strong> {{ __('skyops::skyops.guide_adm_health_thresh_ex') }}
        </div>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">Airline Health <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">mode</span><span class="so-gd-cfg-val">{{ $cfg['health_mode'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_admin_health_' . $cfg['health_mode']) }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">active_days</span><span class="so-gd-cfg-val">{{ $cfg['health_active'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_active_days') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">inactive_days</span><span class="so-gd-cfg-val">{{ $cfg['health_inactive'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_inactive_days') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">no_flight</span><span class="so-gd-cfg-val">{{ $cfg['health_noflight'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_noflight') }}</span></div>
        @if($cfg['health_mode'] !== 'activity')
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">balance_green</span><span class="so-gd-cfg-val">{{ SkyOpsHelper::number($cfg['health_bal_green']) }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_bal_green') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">balance_yellow</span><span class="so-gd-cfg-val">{{ SkyOpsHelper::number($cfg['health_bal_yellow']) }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_bal_yellow') }}</span></div>
        @endif
    </div>
</div>

{{-- ── DEPARTURES ── --}}
<div class="so-gd-section" id="admin-departures">
    <div class="so-gd-h2">🛫 {{ __('skyops::skyops.guide_admin_dep') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_dep_intro') }}</p>

        <p><strong>sort_mode</strong> — {{ __('skyops::skyops.guide_adm_sort_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">auto</span></td><td>{{ __('skyops::skyops.guide_adm_sort_auto') }}</td></tr>
                <tr><td><span class="so-gd-code">time</span></td><td>{{ __('skyops::skyops.guide_adm_sort_time') }}</td></tr>
                <tr><td><span class="so-gd-code">flight_nr</span></td><td>{{ __('skyops::skyops.guide_adm_sort_flnr') }}</td></tr>
                <tr><td><span class="so-gd-code">route</span></td><td>{{ __('skyops::skyops.guide_adm_sort_route') }}</td></tr>
                <tr><td><span class="so-gd-code">distance</span></td><td>{{ __('skyops::skyops.guide_adm_sort_dist') }}</td></tr>
            </tbody>
        </table>

        <p><strong>{{ __('skyops::skyops.guide_adm_columns_title') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_adm_columns_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_example') }}:</strong> {{ __('skyops::skyops.guide_adm_columns_ex') }}
        </div>

        <p><strong>aircraft_type_source</strong> — {{ __('skyops::skyops.guide_adm_types_desc') }}</p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">flight_icao</span></td><td>{{ __('skyops::skyops.guide_adm_types_flight') }}</td></tr>
                <tr><td><span class="so-gd-code">aircraft_icao</span></td><td>{{ __('skyops::skyops.guide_adm_types_icao') }}</td></tr>
                <tr><td><span class="so-gd-code">subfleet_type</span></td><td>{{ __('skyops::skyops.guide_adm_types_sf') }}</td></tr>
                <tr><td><span class="so-gd-code">subfleet_segment:N</span></td><td>{{ __('skyops::skyops.guide_adm_types_seg') }}</td></tr>
            </tbody>
        </table>

        <p><strong>aircraft_active_only</strong> — {{ __('skyops::skyops.guide_adm_active_desc') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_adm_active_tip') }}
        </div>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">Departures <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">sort_mode</span><span class="so-gd-cfg-val">{{ $cfg['dep_sort'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_admin_dep_sort') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">show_dpt_time</span><span class="so-gd-cfg-val">{{ is_bool($cfg['dep_dpt']) ? ($cfg['dep_dpt'] ? 'true' : 'false') : $cfg['dep_dpt'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_admin_dep_dpt') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">show_arr_time</span><span class="so-gd-cfg-val">{{ is_bool($cfg['dep_arr']) ? ($cfg['dep_arr'] ? 'true' : 'false') : $cfg['dep_arr'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_admin_dep_arr') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">show_distance</span><span class="so-gd-cfg-val">{{ $cfg['dep_dist'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_show_dist') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">show_flight_time</span><span class="so-gd-cfg-val">{{ $cfg['dep_ft'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_show_ft') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">respect_phpvms_settings</span><span class="so-gd-cfg-val">{{ $cfg['dep_phpvms'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">Optional: honor pilots.only_flights_from_current, bids.disable_flight_on_bid, pireps.only_aircraft_at_dpt_airport and bids.block_aircraft.</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">bookable_only</span><span class="so-gd-cfg-val">{{ $cfg['dep_bookable'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">When enabled, shows only flights currently bookable under active phpVMS restrictions.</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">show_booking_status</span><span class="so-gd-cfg-val">{{ $cfg['dep_bookstat'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">Shows bid lock / aircraft availability badges on the departures board (requires respect_phpvms_settings=true).</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">aircraft_type_source</span><span class="so-gd-cfg-val">{{ $cfg['dep_types'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_admin_dep_types') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">aircraft_active_only</span><span class="so-gd-cfg-val">{{ $cfg['dep_active'] ? 'true' : 'false' }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_active_only') }}</span></div>
    </div>
</div>

{{-- ── CACHE ── --}}
<div class="so-gd-section" id="admin-cache">
    <div class="so-gd-h2">⚡ {{ __('skyops::skyops.guide_admin_cache') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_cache_intro') }}</p>
        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_example') }}:</strong> {{ __('skyops::skyops.guide_adm_cache_ex') }}
        </div>
        <div class="so-gd-warn so-gd-note">
            <strong>{{ __('skyops::skyops.guide_admin_cache_warn_title') }}</strong> {{ __('skyops::skyops.guide_admin_cache_warn') }}
        </div>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">Cache TTL ({{ __('skyops::skyops.guide_minutes') }}) <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">dashboard</span><span class="so-gd-cfg-val">{{ $cfg['cache_dash'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.dashboard') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">pilot_stats</span><span class="so-gd-cfg-val">{{ $cfg['cache_pilots'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.pilot_stats') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">airline_overview</span><span class="so-gd-cfg-val">{{ $cfg['cache_airlines'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.airlines') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">fleet_stats</span><span class="so-gd-cfg-val">{{ $cfg['cache_fleet'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.fleet') }}</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">filter_options</span><span class="so-gd-cfg-val">{{ $cfg['cache_filters'] }}</span><span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_filters') }}</span></div>
    </div>
</div>

{{-- ── THEME ── --}}
<div class="so-gd-section" id="admin-theme">
    <div class="so-gd-h2">🎨 {{ __('skyops::skyops.guide_admin_theme') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_theme_intro') }}</p>

        <p><strong>glass_mode</strong></p>
        <table class="so-gd-table">
            <thead><tr><th>{{ __('skyops::skyops.guide_setting') }}</th><th>{{ __('skyops::skyops.guide_description') }}</th></tr></thead>
            <tbody>
                <tr><td><span class="so-gd-code">true</span></td><td>{{ __('skyops::skyops.guide_adm_glass_true') }}</td></tr>
                <tr><td><span class="so-gd-code">false</span></td><td>{{ __('skyops::skyops.guide_adm_glass_false') }}</td></tr>
            </tbody>
        </table>

        <div class="so-gd-tip so-gd-note">
            <strong>{{ __('skyops::skyops.guide_tip') }}:</strong> {{ __('skyops::skyops.guide_adm_theme_tip') }}
        </div>

        @if(!$cfg['glass_mode'])
        <p><strong>{{ __('skyops::skyops.guide_adm_solid_colors') }}</strong></p>
        <p>{{ __('skyops::skyops.guide_adm_solid_desc') }}</p>
        @endif
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">Theme <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row" style="{{ $cfg['glass_mode'] ? 'background:rgba(34,197,94,.06);' : '' }}">
            <span class="so-gd-cfg-key">glass_mode</span>
            <span class="so-gd-cfg-val" style="{{ $cfg['glass_mode'] ? 'color:var(--ap-green);' : '' }}">{{ $cfg['glass_mode'] ? 'true (Glass)' : 'false (Solid)' }}</span>
            <span class="so-gd-cfg-desc">{{ $cfg['glass_mode'] ? __('skyops::skyops.guide_cfg_glass_on') : __('skyops::skyops.guide_cfg_glass_off') }}</span>
        </div>
        @if($cfg['glass_mode'])
        {{-- Glass Mode: show the fixed glass colors (not configurable) --}}
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">surface (dark)</span>
            <span class="so-gd-cfg-val"><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:rgba(255,255,255,0.04);vertical-align:middle;margin-right:6px;border:1px solid var(--ap-border);"></span>rgba(255,255,255,0.04)</span>
            <span class="so-gd-cfg-desc" style="opacity:.6;">{{ __('skyops::skyops.guide_cfg_glass_fixed') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">surface (light)</span>
            <span class="so-gd-cfg-val"><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:rgba(255,255,255,0.75);vertical-align:middle;margin-right:6px;border:1px solid var(--ap-border);"></span>rgba(255,255,255,0.75)</span>
            <span class="so-gd-cfg-desc" style="opacity:.6;">{{ __('skyops::skyops.guide_cfg_glass_fixed') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">border (dark)</span>
            <span class="so-gd-cfg-val">rgba(255,255,255,0.08)</span>
            <span class="so-gd-cfg-desc" style="opacity:.6;">{{ __('skyops::skyops.guide_cfg_glass_fixed') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">border (light)</span>
            <span class="so-gd-cfg-val">rgba(0,0,0,0.08)</span>
            <span class="so-gd-cfg-desc" style="opacity:.6;">{{ __('skyops::skyops.guide_cfg_glass_fixed') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">backdrop-filter</span>
            <span class="so-gd-cfg-val" style="color:var(--ap-cyan);">blur(16px)</span>
            <span class="so-gd-cfg-desc" style="opacity:.6;">{{ __('skyops::skyops.guide_cfg_glass_fixed') }}</span>
        </div>
        @else
        {{-- Solid Mode: show configurable colors --}}
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">card_bg_dark</span>
            <span class="so-gd-cfg-val"><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:{{ $cfg['solid_bg_dark'] }};vertical-align:middle;margin-right:6px;border:1px solid var(--ap-border);"></span>{{ $cfg['solid_bg_dark'] }}</span>
            <span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_card_dark') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">card_bg_light</span>
            <span class="so-gd-cfg-val"><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:{{ $cfg['solid_bg_light'] }};vertical-align:middle;margin-right:6px;border:1px solid var(--ap-border);"></span>{{ $cfg['solid_bg_light'] }}</span>
            <span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_card_light') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">border_dark</span>
            <span class="so-gd-cfg-val">{{ $cfg['solid_border_dark'] }}</span>
            <span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_border_dark') }}</span>
        </div>
        <div class="so-gd-cfg-row">
            <span class="so-gd-cfg-key">border_light</span>
            <span class="so-gd-cfg-val">{{ $cfg['solid_border_light'] }}</span>
            <span class="so-gd-cfg-desc">{{ __('skyops::skyops.guide_cfg_border_light') }}</span>
        </div>
        @endif
    </div>
</div>

{{-- ── UNITS ── --}}
<div class="so-gd-section" id="admin-units">
    <div class="so-gd-h2">📐 {{ __('skyops::skyops.guide_admin_units') }}</div>
    <div class="so-gd-box">
        <p>{{ __('skyops::skyops.guide_adm_units_desc') }}</p>
    </div>

    <div class="so-gd-cfg">
        <div class="so-gd-cfg-header">phpVMS Units <span class="so-gd-cfg-live">Live</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">distance</span><span class="so-gd-cfg-val">{{ $cfg['dist_unit'] }}</span><span class="so-gd-cfg-desc">Admin → Settings → Units</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">fuel</span><span class="so-gd-cfg-val">{{ $cfg['fuel_unit'] }}</span><span class="so-gd-cfg-desc">Admin → Settings → Units</span></div>
        <div class="so-gd-cfg-row"><span class="so-gd-cfg-key">currency</span><span class="so-gd-cfg-val">{{ $cfg['currency'] }}</span><span class="so-gd-cfg-desc">Admin → Settings → Units</span></div>
    </div>
</div>

@endif

@endsection
