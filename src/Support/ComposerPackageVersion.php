<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Composer\InstalledVersions;
use ReflectionClass;

/**
 * Resolve an installed Composer package version (dist tag or path-repo composer.json).
 */
final class ComposerPackageVersion
{
    public static function isInstalled(string $composerName): bool
    {
        if (! class_exists(InstalledVersions::class)) {
            return false;
        }

        try {
            return InstalledVersions::isInstalled($composerName);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function current(string $composerName, ?string $fallbackClass = null): ?string
    {
        if (class_exists(InstalledVersions::class)) {
            try {
                if (InstalledVersions::isInstalled($composerName)) {
                    $pretty = InstalledVersions::getPrettyVersion($composerName);

                    if (is_string($pretty) && $pretty !== '') {
                        $normalized = VoodbuilderPackageVersion::normalize($pretty);

                        if ($normalized !== '0.0.0') {
                            return $normalized;
                        }

                        // Path / branch installs often report "dev-main" — prefer composer.json version.
                        $fromJson = self::versionFromInstallPath($composerName);

                        if ($fromJson !== null) {
                            return $fromJson;
                        }

                        return $pretty;
                    }
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        if (is_string($fallbackClass) && class_exists($fallbackClass)) {
            try {
                $ref = new ReflectionClass($fallbackClass);
                $file = $ref->getFileName();

                if (is_string($file) && $file !== '') {
                    $composerPath = dirname($file, 2) . '/composer.json';
                    $fromFile = self::versionFromComposerFile($composerPath);

                    if ($fromFile !== null) {
                        return $fromFile;
                    }
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return null;
    }

    private static function versionFromInstallPath(string $composerName): ?string
    {
        if (! class_exists(InstalledVersions::class)) {
            return null;
        }

        try {
            $path = InstalledVersions::getInstallPath($composerName);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($path) || $path === '') {
            return null;
        }

        return self::versionFromComposerFile(rtrim($path, '/\\') . '/composer.json');
    }

    private static function versionFromComposerFile(string $composerPath): ?string
    {
        if (! is_readable($composerPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($decoded)) {
            return null;
        }

        $version = trim((string) ($decoded['version'] ?? ''));

        if ($version === '') {
            return null;
        }

        $normalized = VoodbuilderPackageVersion::normalize($version);

        return $normalized !== '0.0.0' ? $normalized : $version;
    }
}
