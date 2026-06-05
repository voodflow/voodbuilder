<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

/**
 * The filament-cookie-consent package injects the public banner into every Filament panel.
 * Vpress shows the banner on the public frontend only (vpress::layouts.app).
 */
final class DisableFilamentCookieBanner
{
    public static function applyToComposerJson(string $composerPath): bool
    {
        if (! is_file($composerPath)) {
            return false;
        }

        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($composer)) {
            return false;
        }

        $dontDiscover = $composer['extra']['laravel']['dont-discover'] ?? [];

        if (! is_array($dontDiscover)) {
            $dontDiscover = [];
        }

        if (in_array('jeffersongoncalves/filament-cookie-consent', $dontDiscover, true)) {
            return false;
        }

        $dontDiscover[] = 'jeffersongoncalves/filament-cookie-consent';
        sort($dontDiscover);

        $composer['extra']['laravel']['dont-discover'] = array_values(array_unique($dontDiscover));

        File::put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );

        return true;
    }
}
