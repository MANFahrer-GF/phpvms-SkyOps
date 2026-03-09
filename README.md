# SkyOps — Sky Operations Center for phpVMS 7

**SkyOps** is a read-only operations center module for [phpVMS 7](https://phpvms.net). It gives virtual airline staff and pilots a unified view of PIREPs, fleet, pilot statistics, airline health, and scheduled departures — all in one place, with zero database migrations.

---

## Features

**7 Pages — one module:**

| Page | What it shows |
|---|---|
| **Dashboard** | Live flight count, KPI tiles, module cards, recent activity, top pilots & routes |
| **PIREP List** | Live flights + completed PIREPs with filters (date, source, network), search, sort, CSV export |
| **Fleet** | All aircraft with subfleet, airline, hub, status badge, flight count, total hours |
| **Pilot Statistics** | Leaderboards, KPI tiles with trend arrows, period comparison (month/quarter/year/all) |
| **Airlines** | Health status (activity/financial/combined), revenue, expenses, balance, fleet count |
| **Departures** | Flight board with airline/airport/type filters, flight time slider, aircraft type details |
| **Pilot Guide** | Interactive help page with live config display for admins |

**Technical highlights:**

- Pure Eloquent + `DB::table()` — no raw SQL, no migrations, no new tables
- Cross-database: MySQL, PostgreSQL, SQLite
- Auto-detects table names and prefixes via phpVMS models
- 9 languages: DE, EN, ES, FR, IT, JA, PT-PT, PT-BR, TR
- Auto locale detection (date format, number format, 24h/12h clock)
- Configurable caching (3–15 min per page)
- GDPR-aware pilot name display (full, short, callsign, ident)
- CSV export (admin-only, all users, or disabled)
- Responsive design with dark/light theme support
- Glass mode (default) with blur effect — or switch to solid backgrounds
- Read-only — SkyOps never writes to your database

---

## Requirements

- phpVMS 7 (v7.0.0 or later)
- PHP 8.1+
- No additional Composer packages required

---

## Installation

1. Download the latest release from the [Releases](../../releases) page
2. Extract and copy the `SkyOps` folder to your phpVMS `modules/` directory:
   ```
   your-phpvms/modules/SkyOps/
   ```
3. Go to **Admin → Addons → Modules** and enable **SkyOps**
4. Visit `/update` to clear the config cache
5. Navigate to `/skyops` — done!

> **Note:** If you see cached old pages after an update, delete the compiled views in `storage/framework/views/` and visit `/update` again.

---

## Configuration

All settings are in a single file — no `.env` variables needed:

```
modules/SkyOps/Config/config.php
```

After changing values, clear the cache: visit `/update` or run `php artisan config:cache`.

> **Want to customize colors and fonts?** See the [Theming Guide](THEMING.md) — step-by-step instructions for matching SkyOps to your VA design.

### Key settings

| Setting | Default | Description |
|---|---|---|
| `landing` | `dashboard` | What `/skyops` shows: `dashboard` (overview) or `redirect` (PIREP list) |
| `pilot_name_format` | `short` | How pilot names are displayed: `full`, `short`, `callsign`, `ident` |
| `csv_export` | `admin` | Who can export CSV: `admin`, `all`, `disabled` |
| `epoch` | `null` | Optional start date for statistics (ignores older data) |
| `locale.*` | `auto` | Date/time/number format — auto-detects from phpVMS language |
| `health_mode` | `activity` | Airline health: `activity`, `financial`, `combined` |
| `departures.aircraft_type_source` | `flight_icao` | Aircraft types: `flight_icao`, `aircraft_icao`, `subfleet_type` |
| `cache_ttl.*` | `3–15` | Cache duration in minutes per page (0 = disabled) |

---

## Adding SkyOps to your theme menu

**Option A — Single link:**
Add `/skyops` to your menu. Pilots land on the Dashboard and navigate from there.

**Option B — Multiple links:**
Add individual links: `/skyops/pireps`, `/skyops/fleet`, `/skyops/pilots`, `/skyops/airlines`, `/skyops/departures`

**Option C — Dropdown:**
Create a "SkyOps" dropdown with the individual pages as sub-items.

> Every SkyOps page has a tab navigation at the top — pilots can switch between all pages regardless of your menu setup.

---

## Languages

| Language | Coverage | Notes |
|---|---|---|
| 🇩🇪 German (DE) | 98% | Common anglicisms kept (Dashboard, Airline, etc.) |
| 🇬🇧 English (EN) | 100% | Reference language |
| 🇪🇸 Spanish (ES) | 100% | |
| 🇫🇷 French (FR) | 99% | |
| 🇮🇹 Italian (IT) | 100% | |
| 🇯🇵 Japanese (JA) | 44% | UI complete, guide texts in English |
| 🇵🇹 Portuguese EU (PT-PT) | 44% | UI complete, guide texts in English |
| 🇧🇷 Portuguese BR (PT-BR) | 44% | UI complete, guide texts in English |
| 🇹🇷 Turkish (TR) | 100% | |

SkyOps auto-detects the language from your phpVMS configuration — no manual setup needed.

---

## Compatibility

- **phpVMS:** v7.0.0+ (tested with v7.0.5)
- **PHP:** 8.1, 8.2, 8.3
- **Database:** MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL / SQLite
- **Themes:** Works with any phpVMS 7 theme (default, Disposable Theme, etc.)
- **Addons:** Fully compatible with DisposableBasic, DisposableSpecial, smartCARS 3, and other phpVMS 7 modules

---

## Updating

1. Download the new version
2. Overwrite the files in `modules/SkyOps/`
3. Delete all files inside `storage/framework/views/`
4. Visit `/update`

---

## Support

- **Issues:** [GitHub Issues](../../issues)
- **Discussions:** [GitHub Discussions](../../discussions)

---

## ☕ Support the Project

If you find SkyOps useful, consider buying me a coffee:

[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.paypal.com/donate/?hosted_button_id=7QEUD3PZLZPV2)

---

## License

Modified BSD 3-Clause — see [LICENSE](LICENSE) for details.

Copyright © 2025 Thomas Kant

---

## Credits

Crafted with ♥ in Germany by Thomas Kant.
Built for [phpVMS 7](https://phpvms.net) by Nabeel Shahzad.
Inspired by the phpVMS community and [Disposable Hero Addons](https://github.com/FatihKoz) by B.Fatih KOZ.

Special thanks to **ProAvia** for extensive testing, bug reports, and valuable feedback during development.
