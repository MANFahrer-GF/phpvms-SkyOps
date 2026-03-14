<?php

namespace Modules\SkyOps\Helpers;

/**
 * PilotNameHelper — GDPR-compliant pilot name formatting.
 *
 * Reads config('skyops.pilot_name_format') to determine display style:
 *   'full'     → "Thomas Kant"           (no privacy)
 *   'short'    → "Thomas K."             (default, GDPR friendly)
 *   'callsign' → "GSG001"                (ident = airline ICAO + pilot_id)
 *   'id'       → "GSG001 (Thomas K.)"    (ident + short name)
 *
 * Note: phpVMS User model has these computed attributes:
 *   - ident: airline->icao + pilot_id (e.g. "GSG001")
 *   - atc:   airline->icao + callsign (e.g. "GSG1TK")
 *   - callsign: raw alphanumeric part only (e.g. "1TK")
 *
 * For 'callsign' and 'id' modes, we use the computed 'ident' attribute,
 * NOT the raw 'callsign' field.
 */
class PilotNameHelper
{
    /**
     * Format a pilot name from a User object according to config.
     *
     * @param mixed $user  User object (or null)
     * @return string
     */
    public static function formatUser($user): string
    {
        if (!$user) {
            return '—';
        }

        $mode = config('skyops.pilot_name_format', 'short');
        
        // Get the full name
        $name = trim($user->name ?? '');
        
        // Get the ident (computed: airline ICAO + pilot_id, e.g. "THY978")
        // This is what phpVMS displays as the pilot identifier
        $ident = '';
        if (isset($user->ident) && $user->ident) {
            $ident = trim($user->ident);
        } elseif (isset($user->pilot_id) && $user->pilot_id) {
            // Fallback: build ident manually if attribute not available
            $airlineIcao = $user->airline->icao ?? '';
            $ident = $airlineIcao . $user->pilot_id;
        }

        return match ($mode) {
            'full'     => $name ?: ($ident ?: '—'),
            'short'    => self::shorten($name) ?: ($ident ?: '—'),
            'callsign' => $ident ?: (self::shorten($name) ?: '—'),
            'id'       => $ident
                ? ($name ? ($ident . ' (' . self::shorten($name) . ')') : $ident)
                : (self::shorten($name) ?: '—'),
            default    => self::shorten($name) ?: ($ident ?: '—'),
        };
    }

    /**
     * Legacy format method for backwards compatibility.
     * Prefer formatUser() with the full User object for correct ident handling.
     *
     * @param string|null $fullName  Full name from DB (e.g. "Thomas Kant")
     * @param string|null $ident     Ident string (e.g. "THY978") — NOT raw callsign!
     * @deprecated Use formatUser() instead
     */
    public static function format(?string $fullName, ?string $ident = null): string
    {
        $mode = config('skyops.pilot_name_format', 'short');
        $name = trim($fullName ?? '');
        $id = trim($ident ?? '');

        return match ($mode) {
            'full'     => $name ?: ($id ?: '—'),
            'short'    => self::shorten($name) ?: ($id ?: '—'),
            'callsign' => $id ?: (self::shorten($name) ?: '—'),
            'id'       => $id
                ? ($name ? ($id . ' (' . self::shorten($name) . ')') : $id)
                : (self::shorten($name) ?: '—'),
            default    => self::shorten($name) ?: ($id ?: '—'),
        };
    }

    /**
     * Shorten a full name: "Thomas Kant" → "Thomas K."
     * Only uses FIRST name + last name initial: "Ömer Faruk Şahin" → "Ömer Ş."
     */
    public static function shorten(?string $name): string
    {
        $name = trim($name ?? '');
        if ($name === '') return '';

        $parts = preg_split('/\s+/', $name);
        if (count($parts) <= 1) return $name;

        $first = $parts[0];                    // First name only
        $last = $parts[count($parts) - 1];     // Last name

        return $first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
    }
}

