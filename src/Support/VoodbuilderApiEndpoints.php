<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Public API used by the package for version checks. Base URL is fixed;
 * customers do not configure it.
 */
final class VoodbuilderApiEndpoints
{
    public const BASE_URL = 'https://api.voodflow.com';

    public const PATH_LATEST_VERSION = '/v1/packages/voodbuilder/latest';

    public static function latestVersionUrl(): string
    {
        return self::latestVersionUrlFor('voodbuilder');
    }

    public static function latestVersionUrlFor(string $packageSlug): string
    {
        $slug = trim($packageSlug, '/');

        if ($slug === '') {
            $slug = 'voodbuilder';
        }

        return rtrim(self::BASE_URL, '/').'/v1/packages/'.$slug.'/latest';
    }
}
