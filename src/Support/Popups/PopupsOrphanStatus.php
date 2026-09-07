<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Popups;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Detects popup rows that remain after the Popups plugin is disabled/uninstalled.
 * Public render must no-op; admin surfaces show a clear warning.
 */
final class PopupsOrphanStatus
{
    public static function detected(): bool
    {
        if (Voodbuilder::modules()->isEnabled('popups')) {
            return false;
        }

        try {
            if (! Schema::hasTable('voodbuilder_popups')) {
                return false;
            }

            return DB::table('voodbuilder_popups')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function count(): int
    {
        if (! self::detected()) {
            return 0;
        }

        try {
            return (int) DB::table('voodbuilder_popups')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function adminTitle(): string
    {
        return (string) __('voodbuilder::popups.orphan.title');
    }

    public static function adminBody(): string
    {
        return (string) __('voodbuilder::popups.orphan.body', [
            'count' => self::count(),
        ]);
    }
}
