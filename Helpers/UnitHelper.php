<?php

namespace Modules\SkyOps\Helpers;

use Modules\SkyOps\Helpers\SkyOpsHelper;

/**
 * UnitHelper — reads phpVMS unit settings and converts raw DB values
 * to the display unit chosen by the VA admin.
 *
 * WHEN TO USE THIS vs. Eloquent casting:
 *
 *   Eloquent (preferred):  $pirep->distance->local(0)  → "1,652 km"
 *   UnitHelper (fallback): UnitHelper::distance($sum)  → "12,450 km"
 *
 * Eloquent casting works on individual model instances (Pirep, Flight, etc.)
 * and should be used wherever possible. UnitHelper is needed only for:
 *   - Aggregated values: SUM(distance), AVG(fuel_used), etc.
 *   - Currency formatting: money(), currencySymbol()
 *   - Unit labels for JS/charts: label('distance') → "km"
 *
 * phpVMS stores internally:
 *   Distance → nautical miles (nmi)
 *   Weight   → pounds (lbs)
 *   Fuel     → pounds (lbs)
 *   Speed    → knots
 *   Altitude → feet
 */
class UnitHelper
{
    // ── Conversion factors (from internal unit → target) ──────────

    private static array $distanceFactors = [
        'nmi' => 1.0,
        'km'  => 1.852,
        'mi'  => 1.15078,
    ];

    private static array $distanceLabels = [
        'nmi' => 'NM',
        'km'  => 'km',
        'mi'  => 'mi',
    ];

    private static array $weightFactors = [
        'lbs' => 1.0,
        'kg'  => 0.453592,
    ];

    private static array $weightLabels = [
        'lbs' => 'lbs',
        'kg'  => 'kg',
    ];

    private static array $fuelFactors = [
        'lbs' => 1.0,
        'kg'  => 0.453592,
    ];

    private static array $fuelLabels = [
        'lbs' => 'lbs',
        'kg'  => 'kg',
    ];

    private static array $altitudeFactors = [
        'ft' => 1.0,
        'm'  => 0.3048,
    ];

    private static array $altitudeLabels = [
        'ft' => 'ft',
        'm'  => 'm',
    ];

    private static array $speedFactors = [
        'knot' => 1.0,
        'kts'  => 1.0,
        'km/h' => 1.852,
    ];

    private static array $speedLabels = [
        'knot' => 'kts',
        'kts'  => 'kts',
        'km/h' => 'km/h',
    ];

    // ── Cached settings ──────────────────────────────────────────

    private static ?array $settings = null;

    /**
     * Read phpVMS unit settings once per request.
     */
    protected static function settings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        // phpVMS provides the setting() helper globally
        if (function_exists('setting')) {
            self::$settings = [
                'distance'    => strtolower(trim(setting('units.distance') ?? 'nmi')),
                'weight'      => strtolower(trim(setting('units.weight')   ?? 'lbs')),
                'fuel'        => strtolower(trim(setting('units.fuel')     ?? 'lbs')),
                'altitude'    => strtolower(trim(setting('units.altitude') ?? 'ft')),
                'speed'       => strtolower(trim(setting('units.speed')    ?? 'knot')),
                'currency'    => strtoupper(trim(setting('units.currency') ?? 'EUR')),
            ];
        } else {
            // Fallback if setting() is not available (e.g. tests)
            self::$settings = [
                'distance' => 'nmi', 'weight' => 'lbs', 'fuel' => 'lbs',
                'altitude' => 'ft',  'speed'  => 'knot', 'currency' => 'EUR',
            ];
        }

        return self::$settings;
    }

    // ── Currency ─────────────────────────────────────────────────

    private static array $currencySymbols = [
        'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF',
        'CAD' => 'C$', 'AUD' => 'A$', 'JPY' => '¥', 'CNY' => '¥',
        'BRL' => 'R$', 'INR' => '₹', 'RUB' => '₽', 'KRW' => '₩',
        'TRY' => '₺', 'PLN' => 'zł', 'SEK' => 'kr', 'NOK' => 'kr',
        'DKK' => 'kr', 'CZK' => 'Kč', 'HUF' => 'Ft',
    ];

    /**
     * Currency symbol from phpVMS setting.
     */
    public static function currencySymbol(): string
    {
        $code = self::settings()['currency'];
        return self::$currencySymbols[$code] ?? $code;
    }

    // ── Generic converter ────────────────────────────────────────

    /**
     * Convert a raw DB value to display unit.
     *
     * @param float  $rawValue     Value in internal DB unit
     * @param string $type         One of: distance, weight, fuel, altitude, speed
     * @param int    $decimals     Number of decimal places
     */
    public static function convert(float $rawValue, string $type, int $decimals = 0): float
    {
        $unit = self::settings()[$type] ?? null;
        $factors = match ($type) {
            'distance' => self::$distanceFactors,
            'weight'   => self::$weightFactors,
            'fuel'     => self::$fuelFactors,
            'altitude' => self::$altitudeFactors,
            'speed'    => self::$speedFactors,
            default    => [],
        };
        $factor = $factors[$unit] ?? 1.0;
        return round($rawValue * $factor, $decimals);
    }

    /**
     * Get the display label for a unit type.
     */
    public static function label(string $type): string
    {
        $unit = self::settings()[$type] ?? null;
        $labels = match ($type) {
            'distance' => self::$distanceLabels,
            'weight'   => self::$weightLabels,
            'fuel'     => self::$fuelLabels,
            'altitude' => self::$altitudeLabels,
            'speed'    => self::$speedLabels,
            default    => [],
        };
        return $labels[$unit] ?? $unit ?? '';
    }

    // ── Locale-aware number formatting ─────────────────────────────

    /**
     * Format a number with locale-aware separators from config.
     */
    protected static function fmt(float $val, int $decimals = 0): string
    {
        return SkyOpsHelper::number($val, $decimals);
    }

    // ── Convenience formatters ───────────────────────────────────

    /**
     * Format distance: "1,082 NM" or "2.004 km"
     */
    public static function distance(float $nm, int $decimals = 0): string
    {
        $val = self::convert($nm, 'distance', $decimals);
        return self::fmt($val, $decimals) . ' ' . self::label('distance');
    }

    /**
     * Format fuel. Internal unit is lbs.
     * Supports auto-scaling to tonnes if >= 1000 kg or >= 2205 lbs.
     */
    public static function fuel(float $lbs): string
    {
        $val = self::convert($lbs, 'fuel', 0);
        $lbl = self::label('fuel');

        if ($lbl === 'kg' && $val >= 1000) {
            return self::fmt($val / 1000, 1) . ' t';
        }
        if ($lbl === 'lbs' && $val >= 2205) {
            return self::fmt($val / 2205, 1) . ' t';
        }
        return self::fmt($val) . ' ' . $lbl;
    }

    /**
     * Format weight (same conversion as fuel but separate label context).
     */
    public static function weight(float $lbs): string
    {
        $val = self::convert($lbs, 'weight', 0);
        return self::fmt($val) . ' ' . self::label('weight');
    }

    /**
     * Format altitude: "35,000 ft" or "10.668 m"
     */
    public static function altitude(float $ft): string
    {
        $val = self::convert($ft, 'altitude', 0);
        return self::fmt($val) . ' ' . self::label('altitude');
    }

    /**
     * Format speed: "450 kts" or "833 km/h"
     */
    public static function speed(float $kts): string
    {
        $val = self::convert($kts, 'speed', 0);
        return self::fmt($val) . ' ' . self::label('speed');
    }

    /**
     * Format money using phpVMS currency setting.
     * Values from journal_transactions are stored in cents.
     */
    public static function money(float $amount, int $decimals = 2): string
    {
        $symbol = self::currencySymbol();
        return self::fmt($amount, $decimals) . ' ' . $symbol;
    }
}
