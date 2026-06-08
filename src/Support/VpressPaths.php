<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class VpressPaths
{
    public static function packagePath(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function themeCssAbsolutePath(): string
    {
        return self::packagePath().'/resources/css/theme.css';
    }

    public static function themeCssRelativePath(): string
    {
        return self::relativeToBasePath(self::themeCssAbsolutePath());
    }

    /**
     * @return list<string>
     */
    public static function defaultViteEntries(): array
    {
        return [
            self::themeCssRelativePath(),
            'resources/js/app.js',
        ];
    }

    public static function isVendorInstall(): bool
    {
        return str_contains(str_replace('\\', '/', self::packagePath()), '/vendor/voodflow/vpress');
    }

    public static function relativeToBasePath(string $absolutePath): string
    {
        $base = realpath(base_path()) ?: base_path();
        $target = realpath($absolutePath) ?: $absolutePath;

        $base = rtrim(str_replace('\\', '/', $base), '/');
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, $base.'/')) {
            return substr($target, strlen($base) + 1);
        }

        return $target;
    }
}
