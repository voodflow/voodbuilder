<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Storage;

final class LandingBlockMedia
{
    public static function publicUrl(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = $path[0] ?? null;
        }

        if (! is_string($path) || blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = (string) config('voodbuilder.uploads.disk', 'public');

        return Storage::disk($disk)->url($path);
    }

    /** @return list<string> */
    public static function imageMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
    }

    public static function uploadDirectory(): string
    {
        return trim((string) config('voodbuilder.uploads.directory', 'voodbuilder'), '/').'/landing';
    }
}
