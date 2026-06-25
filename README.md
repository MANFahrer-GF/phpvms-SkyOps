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
| **Departures** | Flight board with airline / airport / flight-type / aircraft-type filters, flight-time slider, aircraft-type details |
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

## Configuration / Konfiguration

🇬🇧 **English** — All settings live in **one file** (no `.env` required):

```
modules/SkyOps/Config/config.php
```

SkyOps is **read-only** and runs **zero migrations** — this file only changes *how things are displayed and behave*, never your data. It is **install-owned**: a module update will **not** overwrite it, so your values survive upgrades. After editing, clear the cache (visit `/update`, or run `php artisan config:cache && php artisan cache:clear`).

🇩🇪 **Deutsch** — Alle Einstellungen stehen in **einer Datei** (kein `.env` nötig):

```
modules/SkyOps/Config/config.php
```

SkyOps ist **read-only** und führt **keine Migrationen** aus — diese Datei steuert nur *Anzeige und Verhalten*, nie eure Daten. Sie ist **install-eigen**: Ein Modul-Update überschreibt sie **nicht**, eure Werte bleiben erhalten. Nach Änderungen den Cache leeren (`/update` aufrufen oder `php artisan config:cache && php artisan cache:clear`).

> **Colors & fonts / Farben & Schriften:** see the [Theming Guide](THEMING.md) — step-by-step instructions for matching SkyOps to your VA design. / siehe den [Theming-Leitfaden](THEMING.md) — Schritt für Schritt zur eigenen VA-Optik.

> **Most VAs need to change nothing** — the defaults are sensible out of the box. The three knobs worth a look are `airline_health` (how strict the status badge is), `pilot_name_format` (privacy), and the `departures` block (match it to your data quality). / **Die meisten VAs müssen nichts ändern** — die Defaults passen ab Werk. Lohnenswert sind drei Stellschrauben: `airline_health` (wie streng die Ampel), `pilot_name_format` (Datenschutz) und der `departures`-Block (an die eigene Datenqualität anpassen).

<details>
<summary><b>🇬🇧 Full settings reference (English)</b></summary>

| Setting | Default | What it does |
|---|---|---|
| `landing` | `dashboard` | Page shown at `/skyops`: `dashboard` (overview start page) or `redirect` (jump straight to the PIREP list). |
| `per_page.*` | `20`–`50` | Rows per page for each list (PIREPs, fleet, pilots, airlines, flights). |
| `cache_ttl.*` | `3`–`15` | Minutes computed values are cached (`0` = off). Higher = faster pages, slightly staler numbers. |
| `epoch` | `null` | Earliest date counted in statistics. `null` = use the phpVMS start date. Override via `.env SKYOPS_EPOCH=YYYY-MM-DD`. Handy to exclude pre-launch test data. |
| `active_flights_limit` | `100` | Safety cap on simultaneously shown live flights. |
| `airport_options_limit` | `3000` | How many airports load into the Departures airport autocomplete. |
| `pilot_name_format` | `short` | Public pilot name (set via `.env SKYOPS_PILOT_NAME`): `full` (Thomas Kant), `short` (Thomas K. — GDPR-friendly), `callsign` (GSG001), `id` (GSG001 (Thomas K.)). |
| `source_map` / `network_map` / `phase_map` | — | Cosmetic lookups: map raw codes to label + colored badge + emoji (ACARS source, VATSIM/IVAO network, flight phases). Edit only to rename or add your own. |
| `landing_thresholds` | — | fpm bands for the landing-rate rating (crash / hard / ok / smooth / butter). Shift the boundaries to rate stricter or softer. |
| `airline_health.mode` | `activity` | Green/Yellow/Red badge logic on the Airlines page: `activity` (time since last PIREP — recommended), `financial` (closing balance), `combined` (worse of both wins). |
| `airline_health.active_days` / `inactive_days` | `30` / `90` | ≤ active_days = Green; up to inactive_days = Yellow; beyond = Red. |
| `airline_health.no_flight` | `Red` | Status for an airline with **zero** PIREPs (`Red`, or `Yellow` to be lenient with new airlines). |
| `airline_health.balance_green` / `balance_yellow` | `0` / `-50000` | Money thresholds in full currency units (only used in `financial` / `combined`). |
| `locale.*` | `auto` | Date / time / number format. `auto` follows your phpVMS language (DE → `04.03.2026`, `1.234,56`, 24h; EN → `03/04/2026`, `1,234.56`, AM/PM). |
| `csv_export` | `admin` | Who sees CSV export buttons (Airlines + Pilot Statistics): `admin`, `all`, `disabled`. |
| `departures.sort_mode` | `auto` | Board order: `auto` (by time if >50% have `dpt_time`, else by flight no.), `time`, `flight_nr`, `route`, `distance`. |
| `departures.show_dpt_time` / `show_arr_time` | `auto` | Time columns; `auto` hides a column when <10% of flights carry that time. |
| `departures.show_distance` / `show_flight_time` | `true` | Distance / duration columns (duration also enables the flight-time slider filter). |
| `departures.respect_phpvms_settings` | `false` | `false` = informational board showing all flights. `true` = optionally honor phpVMS booking rules: `pilots.only_flights_from_current`, `bids.disable_flight_on_bid`, `pireps.only_aircraft_at_dpt_airport`, `bids.block_aircraft`. |
| `departures.bookable_only` | `false` | With `true` (and sync on): show only bookable flights. |
| `departures.show_booking_status` | `true` | Show bid / aircraft-availability badges in sync mode. |
| `departures.aircraft_type_source` | `flight_icao` | Aircraft types in the detail panel: `flight_icao` (types of the subfleets assigned to **that flight** — recommended), `aircraft_icao` (all types the airline operates), `subfleet_type` (full subfleet names), `subfleet_segment:N` (Nth dash-segment of the subfleet name). |
| `departures.aircraft_active_only` | `true` | Exclude aircraft not in active status (maintenance / stored / retired). Also governs the **Aircraft Type** filter and its dropdown. |
| `features.*` | mixed | Toggles: `show_network_badge`, `show_source_badge`, `show_landing_rate`, `show_fuel` (on); `featured_destinations`, `show_finance_link` (off — set `true` to show a finance link in the layout). |
| `theme.glass_mode` | `true` | `true` = translucent, blurred cards (great over background images/gradients). `false` = solid cards using the `theme.solid` colors (card/inner background + border, per dark/light). |

> **Aircraft Type filter:** the Departures **Aircraft Type** dropdown and filter are ICAO-based and honor `aircraft_active_only`. A flight whose airline + flight number is tied to subfleets matches only when an assigned subfleet operates the chosen type (true per-flight restriction); a flight with no assignment falls back to airline-wide. It pairs best with the `flight_icao` / `aircraft_icao` display modes (both ICAO-based), so the detail panel shows exactly the types you filter by.

</details>

<details>
<summary><b>🇩🇪 Vollständige Einstellungs-Referenz (Deutsch)</b></summary>

| Einstellung | Default | Was sie bewirkt |
|---|---|---|
| `landing` | `dashboard` | Seite bei `/skyops`: `dashboard` (Übersichts-Startseite) oder `redirect` (direkt zur PIREP-Liste). |
| `per_page.*` | `20`–`50` | Einträge pro Seite je Liste (PIREPs, Flotte, Piloten, Airlines, Flüge). |
| `cache_ttl.*` | `3`–`15` | Minuten, die berechnete Werte gecacht werden (`0` = aus). Höher = schnellere Seiten, Zahlen hinken kurz hinterher. |
| `epoch` | `null` | Frühestes Datum in den Statistiken. `null` = phpVMS-Gründungsdatum. Per `.env SKYOPS_EPOCH=JJJJ-MM-TT` überschreibbar. Praktisch, um Vor-Launch-Testdaten auszuschließen. |
| `active_flights_limit` | `100` | Sicherheitsdeckel für gleichzeitig angezeigte Live-Flüge. |
| `airport_options_limit` | `3000` | Wie viele Flughäfen in die Abflugtafel-Autovervollständigung geladen werden. |
| `pilot_name_format` | `short` | Öffentliche Namensanzeige (per `.env SKYOPS_PILOT_NAME`): `full` (Thomas Kant), `short` (Thomas K. — DSGVO-freundlich), `callsign` (GSG001), `id` (GSG001 (Thomas K.)). |
| `source_map` / `network_map` / `phase_map` | — | Kosmetik-Tabellen: ordnen Roh-Codes ein Label + Farb-Badge + Emoji zu (ACARS-Quelle, VATSIM/IVAO, Flugphasen). Nur zum Umbenennen/Ergänzen anfassen. |
| `landing_thresholds` | — | fpm-Schwellen der Landeraten-Bewertung (crash / hard / ok / smooth / butter). Grenzen verschieben = strenger/lockerer bewerten. |
| `airline_health.mode` | `activity` | Ampel-Logik (Grün/Gelb/Rot) auf der Airlines-Seite: `activity` (Zeit seit letztem PIREP — empfohlen), `financial` (Kontostand), `combined` (der schlechtere Wert gewinnt). |
| `airline_health.active_days` / `inactive_days` | `30` / `90` | ≤ active_days = Grün; bis inactive_days = Gelb; darüber = Rot. |
| `airline_health.no_flight` | `Red` | Status für Airline mit **0** PIREPs (`Red`, oder `Yellow` um neue Airlines zu schonen). |
| `airline_health.balance_green` / `balance_yellow` | `0` / `-50000` | Geld-Schwellen in vollen Währungseinheiten (nur in `financial` / `combined`). |
| `locale.*` | `auto` | Datums-/Zeit-/Zahlenformat. `auto` folgt eurer phpVMS-Sprache (DE → `04.03.2026`, `1.234,56`, 24h; EN → `03/04/2026`, `1,234.56`, AM/PM). |
| `csv_export` | `admin` | Wer CSV-Export-Buttons sieht (Airlines + Pilotenstatistik): `admin`, `all`, `disabled`. |
| `departures.sort_mode` | `auto` | Sortierung der Tafel: `auto` (nach Zeit, wenn >50 % `dpt_time` haben, sonst Flugnummer), `time`, `flight_nr`, `route`, `distance`. |
| `departures.show_dpt_time` / `show_arr_time` | `auto` | Zeitspalten; `auto` blendet eine Spalte aus, wenn <10 % der Flüge die Zeit tragen. |
| `departures.show_distance` / `show_flight_time` | `true` | Strecken- / Dauer-Spalte (Dauer schaltet auch den Flugzeit-Schieberegler). |
| `departures.respect_phpvms_settings` | `false` | `false` = informative Tafel mit allen Flügen. `true` = optional echte phpVMS-Buchungsregeln beachten: `pilots.only_flights_from_current`, `bids.disable_flight_on_bid`, `pireps.only_aircraft_at_dpt_airport`, `bids.block_aircraft`. |
| `departures.bookable_only` | `false` | Mit `true` (und Sync an): nur buchbare Flüge. |
| `departures.show_booking_status` | `true` | Im Sync-Modus Bid-/Flugzeug-Verfügbarkeits-Badges zeigen. |
| `departures.aircraft_type_source` | `flight_icao` | Flugzeugtypen im Detail-Panel: `flight_icao` (Typen der **diesem Flug** zugewiesenen Subfleets — empfohlen), `aircraft_icao` (alle Typen der Airline), `subfleet_type` (komplette Subfleet-Namen), `subfleet_segment:N` (N-tes Bindestrich-Segment des Namens). |
| `departures.aircraft_active_only` | `true` | Nur aktive Flugzeuge (Wartung / Lager / ausgemustert raus). Gilt auch für den **Flugzeugtyp**-Filter und sein Dropdown. |
| `features.*` | gemischt | Schalter: `show_network_badge`, `show_source_badge`, `show_landing_rate`, `show_fuel` (an); `featured_destinations`, `show_finance_link` (aus — `true` zeigt einen Finanz-Link im Layout). |
| `theme.glass_mode` | `true` | `true` = halbtransparente Karten mit Blur (passt zu Hintergrundbildern/Verläufen). `false` = solide Karten mit den `theme.solid`-Farben (Karten-/Innen-Hintergrund + Rahmen, je dark/light). |

> **Flugzeugtyp-Filter:** Das **Flugzeugtyp**-Dropdown und der Filter der Abflugtafel arbeiten ICAO-basiert und respektieren `aircraft_active_only`. Ein Flug, dessen Airline + Flugnummer Subfleets zugewiesen ist, matcht nur, wenn ein zugewiesener Subfleet den gewählten Typ betreibt (echte Per-Flug-Eingrenzung); ein Flug ohne Zuweisung fällt auf airline-weit zurück. Am stimmigsten mit den Anzeige-Modi `flight_icao` / `aircraft_icao` (beide ICAO-basiert) — dann zeigt das Detail genau die Typen, nach denen man filtert.

</details>

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
