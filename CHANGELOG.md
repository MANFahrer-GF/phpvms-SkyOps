# Changelog

All notable changes to SkyOps will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.4] — 2026-06-21

Consolidated stable release (supersedes the unreleased `1.0.4-beta.1`/`beta.2`).
The new **phpVMS sync mode is off by default** (master switch `respect_phpvms_settings=false`),
so its operational restrictions stay dormant until you opt in. Note the **one intentional,
always-on change**: the Departures PAX/Cargo chip classification was corrected to match the
phpVMS flight-type enums (see "Changed" below) — this is visible on the board without enabling
sync mode.

### Added
- **Optional phpVMS Sync Mode for Departures** (default **OFF**):
  New config options under `departures`:
  - `respect_phpvms_settings` (default `false`) — master switch; while `false`, the
    departures board behaves exactly as in `1.0.3`.
  - `bookable_only` (default `false`)
  - `show_booking_status` (default `true`, only effective when the master switch is on)
- When enabled, the departures board optionally honors these phpVMS operational settings
  (each only if that phpVMS setting is itself on):
  - `pilots.only_flights_from_current`
  - `bids.disable_flight_on_bid`
  - `pireps.only_aircraft_at_dpt_airport`
  - `bids.block_aircraft`
- Optional booking/availability badges on departures rows when sync mode is enabled.

### Fixed
- **Fleet list showed soft-deleted aircraft**: `FleetService::getAircraftList()` and the
  fleet filter datalists (ICAO types, registrations) used `DB::table()`, which bypasses
  Eloquent's `SoftDeletes` scope, so deleted aircraft (and aircraft under deleted subfleets)
  appeared in the "All Aircraft" list and filter dropdowns. Added explicit
  `whereNull(deleted_at)` guards on aircraft and subfleets, matching the rest of the module
  (`AirlineService`, `FlightBoardService`, `DashboardService`). The PIREP count/flight-time
  left join now also ignores soft-deleted PIREPs so totals stay accurate.
- **Departures Filter UI**: Active filter state is now clearly visible for chips and input
  filters; added active-filter summary badges.
- **`bookable_only` no longer corrupts pagination** (sync mode): the aircraft-availability
  constraint is now applied in SQL *before* `simplePaginate`, instead of filtering the
  already-paginated page in PHP — so page counts and the "next" link stay correct. The
  "no subfleet assigned ⇒ bookable by default" semantics are preserved exactly.
- **`bookable_only` decoupled from `show_booking_status` and `aircraft_type_source`** (sync
  mode): it previously did nothing unless `show_booking_status` was on and the type source was
  `flight_icao`/`aircraft_icao`. It now filters independently of both.

### Changed
- **Departures PAX/Cargo chip classification corrected** (always on): the PAX and Cargo filter
  chips now follow the phpVMS flight-type enums. Real scheduled cargo (`F`, plus mail/cargo
  codes) appears under **Cargo**; passenger charter (`C`) now appears under **PAX** instead of
  Cargo; positioning (`P`) is no longer grouped under PAX. The full board ("All") is unchanged.
- **Dashboard "recent activity" row hardened**: the relative-time / distance / fuel cells now
  resolve their values defensively (handles both value objects and plain numerics, guards with
  `try/catch`, and respects `features.show_fuel`) instead of calling `->local()` inline.

### Documentation
- Updated `README.md` with a new section for the optional Departures phpVMS sync settings.
- Updated the in-app Guide (Admin → Departures) to show the new config values.

---

## [1.0.3] — 2026-03-19

### Added
- **Finance Hub Link**: New navigation tab linking to the DynamicFares finance page (`/dynamicfares/finance`)
- **Config Toggle**: `features.show_finance_link` — disabled by default, enable it when [DynamicFares](https://github.com/MANFahrer-GF/phpvms-dynamicfares) is installed

### Translations
- New key: `finance` ("Finance Hub") — added to all 9 languages

### Note
- Requires the [DynamicFares](https://github.com/MANFahrer-GF/phpvms-dynamicfares) module for the Finance Hub page to work

---

## [1.0.2] — 2026-03-14

### Fixed
- **Pilot Name Display**: `callsign` and `id` modes now correctly use phpVMS's computed `ident` attribute (airline ICAO + pilot_id, e.g. "GSG001") instead of the raw `callsign` database field
- **Short Name Format**: Now displays only first name + last initial (e.g. "Thomas K." instead of "Thomas Michael K.")

### Changed
- **PilotNameHelper**: New `formatUser($user)` method for proper ident handling
- **Eager Loading**: User's airline relation now loaded for correct ident calculation

### Thanks
- **Disposable Hero** for identifying the callsign/ident issue

---

## [1.0.1] — 2026-03-09

### Added
- **Theme System**: New `glass_mode` config option to switch between Glass (transparent + blur) and Solid (opaque) card styles
- **Solid Mode Colors**: Configurable card background and border colors when using Solid mode
- **Page Header Cards**: All pages now have consistent header cards with title, subtitle, badges, and stats
- **Guide Theme Section**: New admin section showing active theme configuration with color previews
- **Epoch Fallback**: Statistics now use phpVMS `Start Date` as fallback when no custom epoch is set

### Changed
- **All Page Headers**: Unified design across PIREP List, Fleet, Pilot Statistics, Airlines, Departures, and Guide
- **Guide Section Headers**: Now rendered as cards for visual consistency
- **Filter Buttons**: "Alle/Keine" buttons in Pilot Statistics are now translatable
- **"Gesamt" Label**: Now uses translation key `stats_all` instead of hardcoded German

### Fixed
- **Turkish Translation**: Fixed unescaped apostrophe causing syntax error in `tr/skyops.php`
- **Glass Blur**: Now properly applied to all surface elements (Guide boxes, Dashboard cards, KPI tiles, etc.)
- **Light Mode Shadows**: Consistent shadows across all card-style elements

### Translations
- New keys: `filter_all`, `filter_none`, `stats_all`
- New theme-related keys for Guide admin section
- All 9 languages updated

---

## [1.0.0] — 2026-03-07

### Initial Release

**7 Pages:**
- Dashboard — Live flights, KPIs, module cards, recent activity
- PIREP List — Live flights + completed PIREPs with filters, search, CSV export
- Fleet — All aircraft with subfleet, airline, status, flight count, hours
- Pilot Statistics — Leaderboards, KPIs, period comparison (month/quarter/year/all)
- Airlines — Health status, revenue, expenses, balance, fleet
- Departures — Flight board with airline/airport/type filters
- Pilot Guide — Interactive help with live config display for admins

**Features:**
- Pure Eloquent + DB::table() — no raw SQL, no migrations
- Cross-database: MySQL, PostgreSQL, SQLite
- 9 languages: DE, EN, ES, FR, IT, JA, PT-PT, PT-BR, TR
- Auto locale detection (date, number, time format)
- Configurable caching (3–15 min per page)
- GDPR-aware pilot name display
- CSV export (admin-only, all users, or disabled)
- Glass mode with backdrop blur
- Dark/light theme auto-detection
- Read-only — never writes to database
