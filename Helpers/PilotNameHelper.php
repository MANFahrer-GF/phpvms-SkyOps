<?php

namespace Modules\SkyOps\Helpers;

/**
 * PilotNameHelper — GDPR-compliant pilot name formatting.
 *
 * Reads config('skyops.pilot_name_format') to determine display style:
 *   'full'     → "Thomas Kant"           (no privacy)
 *   'short'    → "Thomas K."             (default, GDPR friendly)
 *   'callsign' → "GSG001"               (callsign only)
 *   'id'       → "GSG001 (Thomas K.)"   (callsign + short name)
 */
class PilotNameHelper
{
    /**
     * Format a pilot name according to config.
     *
     * @param string|null $fullName  Full name from DB (e.g. "Thomas Kant")
     * @param string|null $callsign  Callsign from DB (e.g. "GSG001")
     */
    public static function format(?string $fullName, ?string $callsign = null): string
    {
        $mode = config('skyops.pilot_name_format', 'short');
        $name = trim($fullName ?? '');
        $call = trim($callsign ?? '');

        return match ($mode) {
            'full'     => $name ?: ($call ?: '—'),
            'short'    => self::shorten($name) ?: ($call ?: '—'),
            'callsign' => $call ?: (self::shorten($name) ?: '—'),
            'id'       => $call
                ? ($name ? ($call . ' (' . self::shorten($name) . ')') : $call)
                : (self::shorten($name) ?: '—'),
            default    => self::shorten($name) ?: ($call ?: '—'),
        };
    }

    /**
     * Shorten a full name: "Thomas Kant" → "Thomas K."
     * Handles multi-part last names: "Anna Maria Schmidt" → "Anna Maria S."
     */
    public static function shorten(?string $name): string
    {
        $name = trim($name ?? '');
        if ($name === '') return '';

        $parts = preg_split('/\s+/', $name);
        if (count($parts) <= 1) return $name;

        $last = array_pop($parts);
        $firstParts = implode(' ', $parts);

        return $firstParts . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
    }
}
