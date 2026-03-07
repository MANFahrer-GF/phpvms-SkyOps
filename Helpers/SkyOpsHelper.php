<?php
namespace Modules\SkyOps\Helpers;

use Carbon\Carbon;

class SkyOpsHelper
{
    // ── Locale / Formatting ────────────────────────────────────────

    /**
     * Resolve number format separators from config.
     * Returns [decimal_separator, thousands_separator]
     */
    public static function numberSeparators(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $mode = config('skyops.locale.number_format', 'auto');

        if ($mode === 'auto') {
            $locale = app()->getLocale() ?? 'en';
            $mode = in_array(substr($locale, 0, 2), ['de', 'fr', 'it', 'es', 'pt', 'nl', 'pl', 'cs', 'hu', 'tr', 'ro', 'bg', 'hr', 'sk', 'sl', 'sv', 'da', 'fi', 'nb', 'nn']) ? 'de' : 'en';
        }

        return $cache = match ($mode) {
            'de'    => [',', '.'],   // 1.234,56
            default => ['.', ','],   // 1,234.56
        };
    }

    /**
     * Format a number with locale-aware separators.
     */
    public static function number(float|int $value, int $decimals = 0): string
    {
        [$dec, $thou] = self::numberSeparators();
        return number_format((float) $value, $decimals, $dec, $thou);
    }

    /**
     * Resolve date format from config — supports 'auto' detection.
     */
    protected static function resolveDateFormat(): string
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $fmt = config('skyops.locale.date_format', 'auto');
        if ($fmt !== 'auto') return $cache = $fmt;

        $locale = substr(app()->getLocale() ?? 'en', 0, 2);

        return $cache = match ($locale) {
            'de', 'tr'          => 'd.m.Y',       // 04.03.2026
            'fr', 'it', 'es', 'pt' => 'd/m/Y',   // 04/03/2026
            'ja', 'zh', 'ko'    => 'Y/m/d',       // 2026/03/04
            default              => 'm/d/Y',       // 03/04/2026 (US/EN)
        };
    }

    /**
     * Resolve datetime format from config — supports 'auto' detection.
     */
    protected static function resolveDateTimeFormat(): string
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $fmt = config('skyops.locale.date_time_format', 'auto');
        if ($fmt !== 'auto') return $cache = $fmt;

        $locale = substr(app()->getLocale() ?? 'en', 0, 2);

        // 24h clock: DE, FR, IT, ES, PT, NL, PL, SE, JP, etc.
        // 12h clock: EN (US/UK/AU)
        $uses24h = !in_array($locale, ['en']);
        $datePart = self::resolveDateFormat();
        $timePart = $uses24h ? 'H:i' : 'h:i A';

        return $cache = $datePart . ' ' . $timePart;
    }

    /**
     * Format a date using config date_format (supports 'auto').
     */
    public static function fmtDate($dt): string
    {
        if (!$dt) return '—';
        return Carbon::parse($dt)->format(self::resolveDateFormat());
    }

    /**
     * Format a datetime using config date_time_format (supports 'auto').
     */
    public static function fmtDateTime($dt): string
    {
        if (!$dt) return '—';
        return Carbon::parse($dt)->format(self::resolveDateTimeFormat());
    }

    /**
     * Return JS locale string for toLocaleString().
     * e.g. 'de-DE', 'en-US'
     */
    public static function jsLocale(): string
    {
        $mode = config('skyops.locale.number_format', 'auto');
        if ($mode === 'auto') {
            $locale = app()->getLocale() ?? 'en';
            $short  = substr($locale, 0, 2);

            // Map phpVMS locale to JavaScript Intl locale (for toLocaleString)
            return match (true) {
                $locale === 'pt-br'      => 'pt-BR',
                $locale === 'pt-pt'      => 'pt-PT',
                $short === 'de'          => 'de-DE',
                $short === 'fr'          => 'fr-FR',
                $short === 'es'          => 'es-ES',
                $short === 'it'          => 'it-IT',
                $short === 'ja'          => 'ja-JP',
                $short === 'tr'          => 'tr-TR',
                $short === 'pt'          => 'pt-PT',
                in_array($short, ['nl','pl','cs','hu','ro','bg','hr','sk','sl','sv','da','fi','nb','nn']) => 'de-DE',
                default                  => 'en-US',
            };
        }
        return match ($mode) {
            'de'    => 'de-DE',
            default => 'en-US',
        };
    }

    // ── CSV Export Access ───────────────────────────────────────────

    /**
     * Check if CSV export is allowed for the current user.
     * Reads config('skyops.csv_export'): 'all', 'admin', 'disabled'
     *
     * Admin detection tries multiple phpVMS-compatible methods:
     * - User::hasRole('admin') if available
     * - User->role_id check (phpVMS 7 uses role_id 1 for admin)
     * - Fallback: checks if user can access /admin routes
     */
    public static function csvAllowed(): bool
    {
        $mode = config('skyops.csv_export', 'admin');

        if ($mode === 'disabled') return false;
        if ($mode === 'all') return true;

        // 'admin' mode — check if current user is an admin
        $user = auth()->user();
        if (!$user) return false;

        // Method 1: phpVMS hasRole() / isAdmin() / is_admin
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) return true;
        if (isset($user->is_admin) && $user->is_admin) return true;

        // Method 2: phpVMS 7 role_id (1 = super admin, 2 = admin in most setups)
        if (isset($user->role_id) && in_array((int)$user->role_id, [1, 2], true)) return true;

        // Method 3: Laravel Gate
        try { if ($user->can('admin')) return true; } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Get source badge label and CSS class.
     */
    public static function sourceLabel($code): string
    {
        $map = config('skyops.source_map', []);
        $key = trim((string)($code ?? ''));
        return $map[$key][0] ?? $key;
    }

    public static function sourceClass($code): string
    {
        $map = config('skyops.source_map', []);
        $key = trim((string)($code ?? ''));
        return $map[$key][1] ?? 'so-badge-secondary';
    }

    /**
     * Get network badge label and CSS class.
     * Normalizes input to uppercase to match config keys.
     */
    public static function networkLabel(string $name): string
    {
        $map = config('skyops.network_map', []);
        $s = strtoupper(trim($name));
        if ($s === '' || $s === 'NULL') return '—';
        return $map[$s][0] ?? $s;
    }

    public static function networkClass(string $name): string
    {
        $map = config('skyops.network_map', []);
        $s = strtoupper(trim($name));
        if ($s === '' || $s === 'NULL') $s = 'OFFLINE';
        return $map[$s][1] ?? 'so-badge-secondary';
    }

    /**
     * Get phase label from status code.
     * Accepts phpVMS short codes (ENR, ARR, etc.) from Eloquent model.
     */
    public static function phaseLabel(string $statusCode): string
    {
        $map = config('skyops.phase_map', []);
        $code = trim($statusCode);
        if (isset($map[$code])) return $map[$code][0];
        return $code ?: '—';
    }

    /**
     * Get phase emoji from status code.
     * Accepts phpVMS short codes (ENR, ARR, etc.) from Eloquent model.
     */
    public static function phaseEmoji(string $statusCode): string
    {
        $map = config('skyops.phase_map', []);
        $code = trim($statusCode);
        return isset($map[$code]) ? $map[$code][1] : '';
    }

    /**
     * Extract network string from a Pirep Eloquent model.
     * Reads from eager-loaded field_values relationship.
     *
     * Usage: SkyOpsHelper::network($pirep) → "VATSIM" or "OFFLINE"
     */
    public static function network($pirep): string
    {
        // field_values must be eager-loaded: ->with(['field_values'])
        // or a constrained load: 'field_values' => fn($q) => $q->where('slug', 'network-online')
        if (method_exists($pirep, 'getRelation') && $pirep->relationLoaded('field_values')) {
            $fv = $pirep->field_values->firstWhere('slug', 'network-online');
            return $fv ? strtoupper(trim($fv->value)) : 'OFFLINE';
        }

        return 'OFFLINE';
    }

    /**
     * Build flight number string from Pirep model.
     * Example: "GSG123" from airline ICAO + flight_number.
     */
    public static function flightNumber($pirep): string
    {
        $icao = $pirep->airline->icao ?? '';
        $num  = $pirep->flight_number ?? '';
        return trim($icao . $num) ?: '—';
    }

    /**
     * Calculate block time in minutes from Pirep model.
     */
    public static function blockMinutes($pirep): int
    {
        if ($pirep->block_off_time && $pirep->block_on_time) {
            return (int) $pirep->block_off_time->diffInMinutes($pirep->block_on_time);
        }
        // For active flights: elapsed since departure
        if ($pirep->status !== 'ARR' && $pirep->status !== 'DX') {
            return 0;
        }
        // Fallback for completed: flight_time + taxi estimate
        return ((int) ($pirep->flight_time ?? 0)) + 24;
    }

    /**
     * Format landing rate with emoji and CSS class.
     * FIX: Each threshold now has unique emoji (was: -500..-300 and -300..-150 same emoji).
     */
    public static function landingRate(float $fpm): array
    {
        $thresholds = config('skyops.landing_thresholds', []);
        foreach ($thresholds as $t) {
            $min = $t['min'];
            $max = $t['max'];
            if (($min === null || $fpm >= $min) && ($max === null || $fpm < $max)) {
                return [
                    'text'  => $t['emoji'] . ' ' . number_format($fpm, 0) . ' fpm',
                    'class' => $t['class'],
                    'label' => $t['label'],
                    'emoji' => $t['emoji'],
                ];
            }
        }
        return ['text' => number_format($fpm, 0) . ' fpm', 'class' => '', 'label' => '', 'emoji' => ''];
    }

    /**
     * Format minutes as Xh Ym.
     */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) return '0m';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    /**
     * Safe date parsing with fallback.
     */
    public static function safeParseDate(?string $date, ?Carbon $fallback = null): ?Carbon
    {
        if (empty($date)) return $fallback;
        try {
            return Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            return $fallback;
        }
    }

    /**
     * Get all SkyOps navigation items for the current page.
     */
    public static function navigation(string $currentPage): array
    {
        return [
            ['route' => 'skyops.pireps',     'key' => 'pireps',     'icon' => 'list',      'label_de' => 'PIREP-Liste',       'label_en' => 'PIREP List'],
            ['route' => 'skyops.fleet',      'key' => 'fleet',      'icon' => 'airplane',   'label_de' => 'Flotte',            'label_en' => 'Fleet'],
            ['route' => 'skyops.pilots',     'key' => 'pilots',     'icon' => 'people',     'label_de' => 'Pilot-Statistik',   'label_en' => 'Pilot Stats'],
            ['route' => 'skyops.airlines',   'key' => 'airlines',   'icon' => 'building',   'label_de' => 'Airlines',          'label_en' => 'Airlines'],
            ['route' => 'skyops.departures', 'key' => 'departures', 'icon' => 'signpost',   'label_de' => 'Abflugtafel',       'label_en' => 'Departures'],
        ];
    }
}
