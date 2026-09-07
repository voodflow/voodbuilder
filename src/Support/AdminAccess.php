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

        $panels = self::candidatePanels($panelId);

        if ($panels === []) {
            // Panel registry empty (misconfig / early boot). Filament's default without
            // FilamentUser is "allow all panels" — keep editor usable on Shield-less installs.
            return self::allowsAuthenticatedWithoutPanelContext($user);
        }

        foreach ($panels as $panel) {
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

        // Filament allows every panel when FilamentUser is not implemented.
        // Do NOT require getCurrentPanel() — the visual editor runs outside /admin
        // (e.g. /voodbuilder/chrome-layouts/{id}/editor?edit=1) on Shield-less installs.
        return true;
    }

    /**
     * When no Panel instances are resolvable, still allow Shield-less Community installs
     * that never implemented FilamentUser (Filament itself allows all panels in that case).
     */
    protected static function allowsAuthenticatedWithoutPanelContext(Authenticatable $user): bool
    {
        if ($user instanceof FilamentUser) {
            // Need a Panel instance to evaluate canAccessPanel().
            return false;
        }

        if (AdminAuthorization::usesPermissionAuthorizer()) {
            return false;
        }

        $driver = (string) config('voodbuilder.authorization.driver', 'auto');

        return in_array($driver, ['auto', 'panel'], true);
    }

    protected static function resolvePanel(?string $panelId): ?Panel
    {
        if (! class_exists(Filament::class)) {
            return null;
        }

        $panelId ??= (string) config('voodbuilder.admin_panel_id', 'admin');

        try {
            return Filament::getPanel($panelId, isStrict: false);
        } catch (\Throwable) {
            return null;
        }
    }
}
