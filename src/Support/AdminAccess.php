<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Admin Access.
 */
final class AdminAccess
{
    public static function userCanAccessPanel(?string $panelId = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof Authenticatable || ! class_exists(Filament::class)) {
            return false;
        }

        foreach (self::candidatePanels($panelId) as $panel) {
            if (self::userMayAccess($user, $panel)) {
                return true;
            }
        }

        return false;
    }

    public static function panelUrl(?string $panelId = null): ?string
    {
        return self::resolvePanel($panelId)?->getUrl();
    }

    /**
     * Prefer the panel currently being rendered, then configured id, then any panel.
     *
     * @return list<Panel>
     */
    protected static function candidatePanels(?string $panelId): array
    {
        $panels = [];

        try {
            $current = Filament::getCurrentPanel();
            if ($current instanceof Panel) {
                $panels[] = $current;
            }
        } catch (\Throwable) {
            //
        }

        $resolved = self::resolvePanel($panelId);
        if ($resolved instanceof Panel) {
            $panels[] = $resolved;
        }

        try {
            foreach (Filament::getPanels() as $panel) {
                if ($panel instanceof Panel) {
                    $panels[] = $panel;
                }
            }
        } catch (\Throwable) {
            //
        }

        $unique = [];
        foreach ($panels as $panel) {
            $unique[$panel->getId()] = $panel;
        }

        return array_values($unique);
    }

    protected static function userMayAccess(Authenticatable $user, Panel $panel): bool
    {
        if ($user instanceof FilamentUser) {
            return $user->canAccessPanel($panel);
        }

        // Already authenticated inside a Filament request without FilamentUser —
        // treat as allowed for package resource visibility (non-Shield installs).
        return Filament::getCurrentPanel()?->getId() === $panel->getId();
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
