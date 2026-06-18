<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Illuminate\Support\Facades\Vite;
use Voodflow\Vpress\Support\VpressPaths;

final class GrapesJsCanvas
{
    /**
     * @return list<string>
     */
    public static function styleUrls(): array
    {
        $entries = config('vpress.grapesjs.canvas_styles', VpressPaths::grapesJsCanvasStyleEntries());

        return collect($entries)
            ->map(static function (string $entry): ?string {
                if (str_starts_with($entry, 'http://') || str_starts_with($entry, 'https://')) {
                    return $entry;
                }

                if (! class_exists(Vite::class) || ! Vite::isRunningHot() && ! self::hasBuiltAsset($entry)) {
                    return null;
                }

                try {
                    return Vite::asset($entry);
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();
    }

    protected static function hasBuiltAsset(string $entry): bool
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($manifest), true);

        return is_array($decoded) && array_key_exists($entry, $decoded);
    }
}
