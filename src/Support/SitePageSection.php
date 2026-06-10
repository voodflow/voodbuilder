<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class SitePageSection
{
    public const BLOG = 'blog';

    public const NEWS = 'news';

    /** @return list<string> */
    public static function codes(): array
    {
        return [
            self::BLOG,
            self::NEWS,
        ];
    }

    public static function isValid(?string $section): bool
    {
        return is_string($section) && in_array($section, self::codes(), true);
    }

    public static function subThemeFor(?string $section): string
    {
        return match ($section) {
            self::BLOG => 'blog',
            self::NEWS => 'news',
            default => SubThemeResolver::DEFAULT,
        };
    }
}
