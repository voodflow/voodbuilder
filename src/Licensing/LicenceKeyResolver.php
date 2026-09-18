<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Resolves the licence key for remote entitlement checks.
 *
 * Prefers explicit config, then Composer HTTP Basic password when available.
 */
final class LicenceKeyResolver
{
    public static function resolve(): string
    {
        $fromConfig = trim((string) config('voodbuilder.license.key', ''));

        if ($fromConfig !== '') {
            return self::stripFingerprint($fromConfig);
        }

        if (class_exists(\Voodflow\Voodflow\Support\ComposerAnystackCredentialsReader::class)) {
            $creds = \Voodflow\Voodflow\Support\ComposerAnystackCredentialsReader::read();

            if (is_array($creds) && isset($creds['key']) && is_string($creds['key']) && $creds['key'] !== '') {
                return $creds['key'];
            }
        }

        return '';
    }

    private static function stripFingerprint(string $value): string
    {
        $parts = explode(':', $value, 2);

        return trim($parts[0]);
    }
}
