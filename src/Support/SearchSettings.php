<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Site search knobs (Settings → Search, with config fallbacks).
 */
final class SearchSettings
{
    public static function perPage(): int
    {
        return self::clampInt(
            VoodbuilderSettings::get('search_per_page', config('voodbuilder.search.per_page', 10)),
            5,
            50,
            10,
        );
    }

    public static function perType(): int
    {
        return self::clampInt(
            VoodbuilderSettings::get('search_per_type', config('voodbuilder.search.per_type', 20)),
            5,
            100,
            20,
        );
    }

    public static function snippetLength(): int
    {
        return self::clampInt(
            VoodbuilderSettings::get('search_snippet_length', config('voodbuilder.search.snippet_length', 160)),
            80,
            300,
            160,
        );
    }

    public static function suggestLimit(): int
    {
        return self::clampInt(
            config('voodbuilder.search.suggest_limit', 8),
            3,
            20,
            8,
        );
    }

    protected static function clampInt(mixed $value, int $min, int $max, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
