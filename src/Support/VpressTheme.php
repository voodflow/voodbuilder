<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\VpressSettings;

final class VpressTheme
{
    public static function showToggle(): bool
    {
        return (bool) VpressSettings::get('show_theme_toggle', true);
    }

    public static function defaultMode(): string
    {
        $mode = (string) VpressSettings::get('theme_mode', 'system');

        return in_array($mode, ['system', 'light', 'dark'], true) ? $mode : 'system';
    }

    public static function resolveIsDark(?string $storedPreference, bool $prefersDark = false): bool
    {
        $showToggle = static::showToggle();
        $defaultMode = static::defaultMode();

        if (! $showToggle) {
            return $defaultMode === 'dark';
        }

        if ($storedPreference === 'dark') {
            return true;
        }

        if ($storedPreference === 'light') {
            return false;
        }

        return static::modeToDark($defaultMode, $prefersDark);
    }

    /**
     * HTML first paint before JavaScript (no localStorage / no matchMedia).
     */
    public static function serverInitialDark(): bool
    {
        return static::resolveIsDark(null, false);
    }

    /** @return array{showToggle: bool, defaultMode: string, locked: bool} */
    public static function clientConfig(): array
    {
        $showToggle = static::showToggle();

        return [
            'showToggle' => $showToggle,
            'defaultMode' => static::defaultMode(),
            'locked' => ! $showToggle,
        ];
    }

    private static function modeToDark(string $mode, bool $prefersDark): bool
    {
        return match ($mode) {
            'dark' => true,
            'light' => false,
            default => $prefersDark,
        };
    }
}
