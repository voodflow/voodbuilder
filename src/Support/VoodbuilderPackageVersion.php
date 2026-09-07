<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Voodbuilder Package Version.
 */
final class VoodbuilderPackageVersion
{
    public static function current(): string
    {
        $composerPath = dirname(__DIR__, 2).'/composer.json';

        if (! is_file($composerPath)) {
            return '0.0.0';
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($decoded)) {
            return '0.0.0';
        }

        $version = trim((string) ($decoded['version'] ?? '0.0.0'));

        return self::normalize($version);
    }

    public static function normalize(string $version): string
    {
        $version = ltrim(trim($version), 'vV');

        if ($version === '' || ! preg_match('/^\d+\.\d+\.\d+/', $version)) {
            return '0.0.0';
        }

        if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1];
        }

        return '0.0.0';
    }

    public static function isNewerThan(string $candidate, string $installed): bool
    {
        return version_compare(self::normalize($candidate), self::normalize($installed), '>');
    }
}
