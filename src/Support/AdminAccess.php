<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

final class AdminAccess
{
    public static function userCanAccessPanel(?string $panelId = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof FilamentUser || ! class_exists(Filament::class)) {
            return false;
        }

        $panel = self::resolvePanel($panelId);

        if (! $panel instanceof Panel) {
            return false;
        }

        return $user->canAccessPanel($panel);
    }

    public static function panelUrl(?string $panelId = null): ?string
    {
        return self::resolvePanel($panelId)?->getUrl();
    }

    protected static function resolvePanel(?string $panelId): ?Panel
    {
        if (! class_exists(Filament::class)) {
            return null;
        }

        $panelId ??= (string) config('voodbuilder.admin_panel_id', 'admin');

        try {
            return Filament::getPanel($panelId);
        } catch (\Throwable) {
            return null;
        }
    }
}
