<?php

namespace Modules\SkyOps\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * FinanceLinkHelper — decides whether the "Finance Hub" tab (DynamicFares) appears in the SkyOps navigation.
 *
 *   features.show_finance_link = true   → always shown (manual override, old behaviour)
 *   features.finance_link_auto = true   → shown automatically when DynamicFares is active and the current
 *                                          user may open its finance page (default)
 *   features.finance_link_auto = false  → automatic detection off; only the manual switch counts
 *
 * The access rule mirrors DynamicFares (DF_FrontendFinanceController): the page is open to every logged-in
 * pilot unless the DynamicFares setting "frontend_finance_enabled" is off — then only admins/staff.
 */
class FinanceLinkHelper
{
    public const ROUTE = 'dynamicfares.frontend.finance';

    public static function visible($user = null): bool
    {
        if (filter_var(config('skyops.features.show_finance_link', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        $auto = config('skyops.features.finance_link_auto', true);
        if ($auto !== null && $auto !== '' && !filter_var($auto, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }
        if (!Route::has(self::ROUTE)) {
            return false;
        }
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        return self::visibleToAllPilots() || self::isStaff($user);
    }

    public static function url(): string
    {
        return Route::has(self::ROUTE) ? route(self::ROUTE) : url('/dynamicfares/finance');
    }

    /** Same default as DynamicFares: missing table/column/row or empty value counts as "on". */
    protected static function visibleToAllPilots(): bool
    {
        try {
            if (!Schema::hasTable('dynamic_fares_settings') || !Schema::hasColumn('dynamic_fares_settings', 'frontend_finance_enabled')) {
                return true;
            }
            $value = DB::table('dynamic_fares_settings')->whereNull('airline_id')->value('frontend_finance_enabled');

            return $value === null ? true : (bool) $value;
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected static function isStaff($user): bool
    {
        try {
            return (bool) ($user->hasRole('admin') || $user->hasPermission('admin-access'));
        } catch (\Throwable $e) {
            return false;
        }
    }
}
