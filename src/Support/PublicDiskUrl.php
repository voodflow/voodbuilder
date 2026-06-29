<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Build browser-facing URLs for files on Laravel's "public" disk.
 *
 * Relative /storage/... paths work across Docker port mappings and reverse
 * proxies without depending on APP_URL matching the active request host.
 */
final class PublicDiskUrl
{
    public static function fromPath(string $path): string
    {
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
