<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Layout resolution for integrated plugins and Voodbuilder shells.
 */
final class PluginLayout
{
    public static function appShell(): string
    {
        if (ChromeLayoutResolver::activeLayout() !== null) {
            return (string) config('voodbuilder.layouts.chrome_app', 'voodbuilder::layouts.chrome-app');
        }

        return (string) config('voodbuilder.layouts.app', 'voodbuilder::layouts.app');
    }

    public static function usesChromeShell(): bool
    {
        return ChromeLayoutResolver::activeLayout() !== null;
    }

    public static function resolve(string $key): string
    {
        $configured = config("voodbuilder.layouts.{$key}");

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return match ($key) {
            'doc' => 'voodbuilder::layouts.doc',
            'page' => 'voodbuilder::layouts.page',
            'full_width', 'home', 'landing' => 'voodbuilder::layouts.full-width',
            default => self::appShell(),
        };
    }
}
