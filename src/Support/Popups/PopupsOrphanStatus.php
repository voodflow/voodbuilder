<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Popups;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Modules\Popups\PopupsModule;

/**
 * Detects popup rows that remain after the Popups module/plugin is disabled.
 * Public render must no-op; admin surfaces show a clear warning.
 */
final class PopupsOrphanStatus
{
    public static function detected(): bool
    {
        if (PopupsModule::isEnabled()) {
            return false;
        }

        try {
            if (! Schema::hasTable('voodbuilder_popups')) {
                return false;
            }

            return BuilderPopup::query()->exists();
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
            return (int) BuilderPopup::query()->count();
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
