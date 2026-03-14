<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page — /skyops
    |--------------------------------------------------------------------------
    |
    |   'redirect'   /skyops redirects to /skyops/pireps (default)
    |                 Lean setup — no extra page, the tab navigation handles
    |                 everything. Best if SkyOps pages are linked individually
    |                 in your theme menu.
    |
    |   'dashboard'  /skyops shows a dashboard with live flight count,
    |                 key metrics from all 5 pages, and recent activity.
    |                 Best if you want a single "SkyOps" menu entry in your
    |                 theme that serves as a starting point.
    |
    */
    'landing' => 'dashboard',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'per_page' => [
        'pireps'   => 25,
        'fleet'    => 30,
        'pilots'   => 20,
        'airlines' => 50,
        'flights'  => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (minutes) — 0 = disabled
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => [
        'dashboard'        => 3,
        'pilot_stats'      => 5,
        'airline_overview'  => 10,
        'fleet_stats'       => 5,
        'filter_options'    => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Epoch Date — Earliest date for statistics
    |--------------------------------------------------------------------------
    | If null, uses phpVMS Start Date (Admin > Settings > general.start_date).
    | If that's also empty, falls back to 2000-01-01.
    | Override per VA via .env SKYOPS_EPOCH (format: YYYY-MM-DD).
    */
    'epoch' => env('SKYOPS_EPOCH', null),

    /*
    |--------------------------------------------------------------------------
    | Active Flights — Safety limit
    |--------------------------------------------------------------------------
    */
    'active_flights_limit' => 100,

    /*
    |--------------------------------------------------------------------------
    | Airport Datalist Limit (Abflugtafel)
    |--------------------------------------------------------------------------
    */
    'airport_options_limit' => 3000,

    /*
    |--------------------------------------------------------------------------
    | Pilot Name Format (GDPR)
    |--------------------------------------------------------------------------
    | Controls how pilot names are displayed publicly.
    | Options: 'full', 'short' (default), 'callsign', 'id'
    |
    |   full     → Thomas Kant
    |   short    → Thomas K.             (GDPR friendly)
    |   callsign → GSG001                (ident = airline ICAO + pilot_id)
    |   id       → GSG001 (Thomas K.)    (ident + short name)
    |
    | Note: 'callsign' and 'id' use phpVMS's computed 'ident' attribute
    | (airline ICAO + pilot_id), NOT the raw 'callsign' database field.
    */
    'pilot_name_format' => env('SKYOPS_PILOT_NAME', 'short'),

    /*
    |--------------------------------------------------------------------------
    | Source Mapping — source_code => [label, css_class]
    |--------------------------------------------------------------------------
    */
    'source_map' => [
        'ACARS'       => ['ACARS',        'so-badge-primary'],
        'OFFLINE'     => ['OFFLINE',      'so-badge-secondary'],
        'MANUAL'      => ['MANUAL',       'so-badge-secondary'],
        'API'         => ['API',          'so-badge-success'],
        'smartCARS 3' => ['smartCARS 3',  'so-badge-info'],
        'vmsacars'    => ['VMSACARS',     'so-badge-primary'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Mapping
    |--------------------------------------------------------------------------
    */
    'network_map' => [
        'OFFLINE' => ['Offline',  'so-badge-secondary'],
        'VATSIM'  => ['VATSIM',   'so-badge-vatsim'],
        'IVAO'    => ['IVAO',     'so-badge-ivao'],
        'POSCON'  => ['POSCON',   'so-badge-poscon'],
    ],

    /*
    |--------------------------------------------------------------------------
    | PIREP Phase Mapping — status_code => [label, emoji]
    |--------------------------------------------------------------------------
    */
    'phase_map' => [
        'INI' => ['Initiated',         "\xF0\x9F\x93\x8B"],
        'SCH' => ['Scheduled',          "\xF0\x9F\x93\x85"],
        'BST' => ['Boarding',           "\xF0\x9F\x9A\xB6"],
        'RDT' => ['Ready to Start',     "\xE2\x9C\x85"],
        'PBT' => ['Pushback',           "\xF0\x9F\x94\x99"],
        'OFB' => ['Departed',           "\xF0\x9F\x9B\xAB"],
        'TXI' => ['Taxi',               "\xF0\x9F\x9A\x95"],
        'TOF' => ['Takeoff',            "\xF0\x9F\x9B\xAB"],
        'ICL' => ['Initial Climb',      "\xF0\x9F\x93\x88"],
        'TKO' => ['Airborne',           "\xE2\x9C\x88\xEF\xB8\x8F"],
        'ENR' => ['Enroute',            "\xF0\x9F\x8C\x8D"],
        'DV'  => ['Diverted',           "\xE2\x86\xAA\xEF\xB8\x8F"],
        'TEN' => ['Approach (Descent)', "\xF0\x9F\x93\x89"],
        'APR' => ['Approach (Final)',   "\xF0\x9F\x8E\xAF"],
        'FIN' => ['Final Approach',     "\xF0\x9F\x8E\xAF"],
        'LDG' => ['Landing',            "\xF0\x9F\x9B\xAC"],
        'LAN' => ['Landed',             "\xF0\x9F\x9B\xAC"],
        'ONB' => ['At Gate',            "\xF0\x9F\x85\xBF\xEF\xB8\x8F"],
        'ARR' => ['Arrived',            "\xE2\x9C\x85"],
        'DX'  => ['Cancelled',          "\xE2\x9D\x8C"],
        'EMG' => ['Emergency Descent',  "\xF0\x9F\x9A\xA8"],
        'PSD' => ['Paused',             "\xE2\x8F\xB8\xEF\xB8\x8F"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Landing Rate Thresholds
    |--------------------------------------------------------------------------
    | Evaluated top-to-bottom: first match wins.
    | FIX: Previously -500..-300 and -300..-150 had same emoji.
    */
    'landing_thresholds' => [
        ['min' => null, 'max' => -500, 'emoji' => "\xF0\x9F\x92\xA5", 'class' => 'so-rate-crash',   'label' => 'crash'],
        ['min' => -500, 'max' => -300, 'emoji' => "\xE2\x9A\xA0\xEF\xB8\x8F", 'class' => 'so-rate-hard',    'label' => 'hard'],
        ['min' => -300, 'max' => -150, 'emoji' => "\xF0\x9F\x91\x8D", 'class' => 'so-rate-ok',      'label' => 'ok'],
        ['min' => -150, 'max' => -50,  'emoji' => "\xF0\x9F\xA7\x88", 'class' => 'so-rate-smooth',  'label' => 'smooth'],
        ['min' => -50,  'max' => 0,    'emoji' => "\xE2\x9C\xA8",     'class' => 'so-rate-butter',  'label' => 'butter'],
        ['min' => 0,    'max' => null,  'emoji' => "\xF0\x9F\xA4\x94", 'class' => 'so-rate-unknown', 'label' => 'unknown'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Airline Health Assessment
    |--------------------------------------------------------------------------
    |
    | Controls how the health status (Green / Yellow / Red) of each airline
    | is calculated on the Airlines overview page. Every airline gets exactly
    | one status badge — the logic behind it is fully configurable here.
    |
    |--------------------------------------------------------------------------
    | MODE  (string)
    |--------------------------------------------------------------------------
    |
    |   'activity'   Only the time since the last accepted PIREP matters.
    |                 An airline that flies regularly is Green, one that has
    |                 not flown for a while turns Yellow, then Red.
    |                 Financial data is ignored.
    |                 → This is the default and recommended for most VAs.
    |
    |   'financial'  Only the closing balance (revenue minus expenses) matters.
    |                 Airlines with positive or break-even balance are Green,
    |                 moderate losses are Yellow, heavy losses are Red.
    |                 Flight activity is ignored.
    |                 → Useful for VAs focused on economic simulation.
    |
    |   'combined'   Both activity AND financial health are evaluated
    |                 independently, and the WORSE result wins.
    |                 Example: Airline flew yesterday (activity = Green) but
    |                 has -80.000 € balance (financial = Red) → final = Red.
    |                 Example: Airline has +50.000 € (financial = Green) but
    |                 last flight was 4 months ago (activity = Red) → final = Red.
    |                 → Strictest mode — both conditions must be healthy.
    |
    |--------------------------------------------------------------------------
    | ACTIVITY THRESHOLDS  (only used in 'activity' and 'combined' mode)
    |--------------------------------------------------------------------------
    |
    |   active_days     Number of days since last PIREP to count as "active".
    |                   If the last flight was ≤ this many days ago → Green.
    |                   Default: 30
    |
    |   inactive_days   Upper bound for "inactive" status.
    |                   If the last flight was between active_days+1 and
    |                   inactive_days → Yellow.
    |                   If the last flight was more than inactive_days ago → Red.
    |                   Default: 90
    |
    |   no_flight       What status to assign when an airline has ZERO accepted
    |                   PIREPs at all. Typically 'Red', but you could set it to
    |                   'Yellow' if you want to be more lenient with new airlines.
    |                   Allowed values: 'Red', 'Yellow'
    |                   Default: 'Red'
    |
    |   Timeline visualization:
    |
    |   ◄── Green ──►◄──── Yellow ────►◄────── Red ──────►
    |   0          30d              90d              ∞
    |              ↑                 ↑
    |         active_days      inactive_days
    |
    |--------------------------------------------------------------------------
    | FINANCIAL THRESHOLDS  (only used in 'financial' and 'combined' mode)
    |--------------------------------------------------------------------------
    |
    |   balance_green   Minimum closing balance for Green status.
    |                   If balance ≥ this value → Green.
    |                   Default: 0 (break-even or profit = Green)
    |
    |   balance_yellow  Minimum closing balance for Yellow status.
    |                   If balance ≥ this value (but < balance_green) → Yellow.
    |                   If balance < this value → Red.
    |                   Default: -50000
    |
    |   Balance visualization (default thresholds):
    |
    |   ◄── Red ──────►◄──── Yellow ────►◄────── Green ──────►
    |   -∞         -50.000 €           0 €               +∞
    |                  ↑                ↑
    |           balance_yellow    balance_green
    |
    |   Note: Values are in FULL currency units (not cents).
    |   So 50000 means 50.000 € / $ / £ depending on your phpVMS currency.
    |
    |--------------------------------------------------------------------------
    | QUICK RECIPES
    |--------------------------------------------------------------------------
    |
    |   Default (activity only, 30/90 day window):
    |       'mode' => 'activity', 'active_days' => 30, 'inactive_days' => 90
    |
    |   Relaxed VA (longer grace periods):
    |       'mode' => 'activity', 'active_days' => 60, 'inactive_days' => 180
    |
    |   Strict VA (must fly every 2 weeks):
    |       'mode' => 'activity', 'active_days' => 14, 'inactive_days' => 45
    |
    |   Economy focused (only money matters):
    |       'mode' => 'financial', 'balance_green' => 0, 'balance_yellow' => -100000
    |
    |   Full simulation (both must be healthy):
    |       'mode' => 'combined', 'active_days' => 30, 'inactive_days' => 90,
    |       'balance_green' => 0, 'balance_yellow' => -50000
    |
    |   Lenient with new airlines (no flights = Yellow instead of Red):
    |       'mode' => 'activity', 'no_flight' => 'Yellow'
    |
    */
    'airline_health' => [
        'mode'           => 'activity',    // 'activity', 'financial', 'combined'
        'active_days'    => 30,            // Green if last PIREP ≤ N days ago
        'inactive_days'  => 90,            // Yellow if ≤ N days, Red if >
        'no_flight'      => 'Red',         // Status when airline has 0 PIREPs
        'balance_green'  => 0,             // Green if closing balance ≥ this
        'balance_yellow' => -50000,        // Yellow if ≥ this, Red if below
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale / Formatting
    |--------------------------------------------------------------------------
    |
    | Controls how dates, times, and numbers are displayed across all pages.
    | Set everything to 'auto' (default) and SkyOps will detect the correct
    | format from your phpVMS locale setting — no manual config needed.
    |
    | date_format:
    |   'auto'     Detects from phpVMS locale (recommended):
    |              de → 04.03.2026  |  en → 03/04/2026  |  fr → 04/03/2026
    |   'd.m.Y'    European:  04.03.2026
    |   'm/d/Y'    US:        03/04/2026
    |   'Y-m-d'    ISO:       2026-03-04
    |   'd M Y'    Readable:  04 Mar 2026
    |   Any valid PHP date() format string.
    |
    | date_time_format:
    |   'auto'     Detects from phpVMS locale (recommended):
    |              de → 04.03.2026 14:22  |  en → 03/04/2026 02:22 PM
    |              Automatically uses 24h clock for DE/FR/IT/ES/etc.
    |              and 12h clock (AM/PM) for EN.
    |   'd.m.Y H:i'      European 24h
    |   'm/d/Y h:i A'    US 12h with AM/PM
    |   'Y-m-d H:i'      ISO 24h
    |   Any valid PHP date() format string.
    |
    | number_format:
    |   'auto'     Detects from phpVMS locale (recommended):
    |              de → 1.234,56  |  en → 1,234.56
    |   'de'       Always German:  1.234,56
    |   'en'       Always English: 1,234.56
    |
    */
    'locale' => [
        'date_format'      => 'auto',
        'date_time_format' => 'auto',
        'number_format'    => 'auto',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Export Access
    |--------------------------------------------------------------------------
    |
    |   'admin'    Only users with admin role can see CSV export buttons.
    |              This is the recommended default for production VAs.
    |
    |   'all'      All logged-in users can export CSV data.
    |
    |   'disabled' CSV export buttons are hidden for everyone.
    |
    |   Affects: Airlines page, Pilot Statistics page.
    |
    */
    'csv_export' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Departures / Flight Board
    |--------------------------------------------------------------------------
    |
    | Controls how flights are displayed and sorted on the Departures page.
    | Many VAs do not maintain dpt_time/arr_time on their flights — these
    | settings let you adapt the page to your VA's data quality.
    |
    | sort_mode:
    |   'auto'       Automatically detects if your flights have departure
    |                times. If >50% have dpt_time set, sorts by time
    |                (next departures first). Otherwise falls back to
    |                flight_nr sorting.
    |   'time'       Sort by departure time (next departures first).
    |                Only useful if your flights have dpt_time values.
    |   'flight_nr'  Sort by airline ICAO + flight number (e.g. DLH100,
    |                DLH101, UAL200). Best for VAs without schedules.
    |   'route'      Sort by departure airport, then arrival airport.
    |   'distance'   Sort by flight distance (shortest first).
    |
    | show_dpt_time:
    |   true/false   Show the departure time column.
    |                Set to false if your VA doesn't maintain dpt_time.
    |   'auto'       Automatically hide if <10% of flights have dpt_time.
    |
    | show_arr_time:
    |   true/false   Show the arrival time column.
    |   'auto'       Automatically hide if <10% of flights have arr_time.
    |
    | show_distance:   Show the distance column (true/false).
    | show_flight_time: Show the duration column and flight-time slider
    |                   filter (true/false).
    |
    */
    'departures' => [
        'sort_mode'        => 'auto',
        'show_dpt_time'    => 'auto',
        'show_arr_time'    => 'auto',
        'show_distance'    => true,
        'show_flight_time' => true,

        /*
        | How to determine aircraft types shown in the flight detail panel.
        |
        |   'flight_icao'      Query ICAO types from aircraft assigned to
        |                       THIS SPECIFIC FLIGHT via flight → subfleets → aircraft.
        |                       Shows only what a pilot can actually book for this flight.
        |                       Example: Flight DLH400 has subfleet "A359-SL" assigned
        |                       → shows only "A359", not the entire airline fleet.
        |                       (recommended if your flights have subfleets assigned)
        |
        |   'aircraft_icao'     Query actual ICAO types from the aircraft table
        |                       via airline → subfleets → aircraft → icao column.
        |                       Shows ALL types the airline operates.
        |                       Example result: "A20N · A359 · B738"
        |
        |   'subfleet_type'     Show the full subfleet type string as-is.
        |                       Example: "AEE-A20N-PWG-SL · DLH-A359-RR-SL"
        |
        |   'subfleet_segment:N'  Split the subfleet type by dash and show the
        |                       Nth segment (0-based).
        |                       Example: 'subfleet_segment:1' applied to
        |                       "AEE-A20N-PWG-SL" → "A20N"
        |                       Only useful if your VA uses a consistent
        |                       naming convention like AIRLINE-AIRCRAFT-ENGINE.
        */
        'aircraft_type_source' => 'flight_icao',

        /*
        | Only show active aircraft?
        | When true (default), aircraft in maintenance, storage, retired,
        | or scrapped status are excluded from the type list.
        | When false, all aircraft are shown regardless of status.
        */
        'aircraft_active_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'show_network_badge'    => true,
        'show_source_badge'     => true,
        'show_landing_rate'     => true,
        'show_fuel'             => true,
        'featured_destinations' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme / Appearance
    |--------------------------------------------------------------------------
    |
    | Controls the visual appearance of SkyOps cards and components.
    |
    | glass_mode:
    |   true    Cards are semi-transparent with blur effect (default).
    |           Looks great on themes with background images or gradients.
    |           Card colors are not configurable — they adapt to the
    |           underlying background.
    |
    |   false   Cards are solid/opaque. Use this if your theme has a
    |           plain background or if the glass effect causes readability
    |           issues. Card colors are fully configurable below.
    |
    | solid (only used when glass_mode = false):
    |   card_bg_dark    Card background color in dark mode (hex).
    |   card_bg_light   Card background color in light mode (hex).
    |   border_dark     Card border color in dark mode (hex or rgba).
    |   border_light    Card border color in light mode (hex or rgba).
    |
    */
    'theme' => [
        'glass_mode' => true,

        // Solid mode colors (ignored when glass_mode = true)
        'solid' => [
            'card_bg_dark'   => '#1e293b',    // Card/nav background (dark)
            'card_bg_light'  => '#ffffff',    // Card/nav background (light)
            'inner_bg_dark'  => '#151b2b',    // Inner elements, stat boxes (dark)
            'inner_bg_light' => '#f8fafc',    // Inner elements, stat boxes (light)
            'border_dark'    => 'rgba(255,255,255,0.08)',
            'border_light'   => 'rgba(0,0,0,0.08)',
        ],
    ],
];
