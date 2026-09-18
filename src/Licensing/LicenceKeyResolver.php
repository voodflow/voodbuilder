<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Resolves the licence key for remote entitlement checks.
 *
 * Prefers explicit config, then Composer HTTP Basic password from auth.json /
 * COMPOSER_AUTH (same secret used to download private packages).
 *
 * Does not depend on voodflow/voodflow — uses the in-package reader only so
 * VoodBuilder-only installs unlock correctly and dual installs cannot conflict.
 */
final class LicenceKeyResolver
{
    public static function resolve(): string
    {
        $fromConfig = trim((string) config('voodbuilder.license.key', ''));

        if ($fromConfig !== '') {
            return self::stripFingerprint($fromConfig);
        }

        $creds = ComposerAnystackCredentialsReader::read();

        if (is_array($creds) && isset($creds['key']) && is_string($creds['key']) && $creds['key'] !== '') {
            return self::stripFingerprint($creds['key']);
        }

        return '';
    }

    private static function stripFingerprint(string $value): string
    {
        $parts = explode(':', $value, 2);

        return trim($parts[0]);
    }
}
