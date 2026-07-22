<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Page content width is owned by the chrome layout (not the page form).
 * The GrapesJS editing frame stays full-bleed; only page content is constrained.
 */
final class ChromeLayoutContentWidth
{
    public const MODE_FULL = 'full';

    public const MODE_STANDARD = 'standard';

    public const MODE_CUSTOM = 'custom';

    public const STANDARD_MAX_WIDTH = '80rem';

    public const CHROME_FULL = 'full';

    public const CHROME_CONTENT = 'content';

    /**
     * @return array{mode: string, maxWidth: string|null}
     */
    public static function resolve(?ChromeLayout $layout, ?SitePage $page = null): array
    {
        if ($layout instanceof ChromeLayout) {
            return self::fromLayout($layout);
        }

        // Legacy pages without a chrome layout still use the page layout field.
        if ($page instanceof SitePage && ! $page->usesFullWidthLayout()) {
            return [
                'mode' => self::MODE_STANDARD,
                'maxWidth' => self::STANDARD_MAX_WIDTH,
            ];
        }

        return [
            'mode' => self::MODE_FULL,
            'maxWidth' => null,
        ];
    }

    public static function resolveChromeWidth(?ChromeLayout $layout): string
    {
        if (! $layout instanceof ChromeLayout) {
            return self::CHROME_FULL;
        }

        return self::normalizeChromeWidth((string) ($layout->chrome_width ?? self::CHROME_FULL));
    }

    /**
     * @return array{mode: string, maxWidth: string|null}
     */
    public static function fromLayout(ChromeLayout $layout): array
    {
        $mode = self::normalizeMode((string) ($layout->content_width ?? self::MODE_FULL));
        $custom = self::normalizeMaxWidth($layout->content_max_width);

        return match ($mode) {
            self::MODE_STANDARD => [
                'mode' => self::MODE_STANDARD,
                'maxWidth' => self::STANDARD_MAX_WIDTH,
            ],
            self::MODE_CUSTOM => [
                'mode' => self::MODE_CUSTOM,
                'maxWidth' => $custom ?? self::STANDARD_MAX_WIDTH,
            ],
            default => [
                'mode' => self::MODE_FULL,
                'maxWidth' => null,
            ],
        };
    }

    public static function isFull(array $resolved): bool
    {
        return ($resolved['mode'] ?? self::MODE_FULL) === self::MODE_FULL;
    }

    /**
     * @param  array{mode?: string, maxWidth?: string|null}  $resolved
     */
    public static function cssMaxWidth(array $resolved): ?string
    {
        if (self::isFull($resolved)) {
            return null;
        }

        $max = $resolved['maxWidth'] ?? null;

        return is_string($max) && $max !== '' ? $max : self::STANDARD_MAX_WIDTH;
    }

    /**
     * @return array<string, string>
     */
    public static function modeOptions(): array
    {
        return [
            self::MODE_FULL => __('voodbuilder::chrome_layouts.fields.content_width_full'),
            self::MODE_STANDARD => __('voodbuilder::chrome_layouts.fields.content_width_standard'),
            self::MODE_CUSTOM => __('voodbuilder::chrome_layouts.fields.content_width_custom'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function chromeWidthOptions(): array
    {
        return [
            self::CHROME_FULL => __('voodbuilder::chrome_layouts.fields.chrome_width_full'),
            self::CHROME_CONTENT => __('voodbuilder::chrome_layouts.fields.chrome_width_content'),
        ];
    }

    public static function normalizeMode(string $mode): string
    {
        return match ($mode) {
            self::MODE_STANDARD, self::MODE_CUSTOM => $mode,
            default => self::MODE_FULL,
        };
    }

    public static function normalizeChromeWidth(string $mode): string
    {
        return $mode === self::CHROME_CONTENT ? self::CHROME_CONTENT : self::CHROME_FULL;
    }

    public static function normalizeMaxWidth(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Allow rem/px/em/% or unitless numbers (treated as rem).
        if (preg_match('/^\d+(\.\d+)?$/', $value) === 1) {
            return $value.'rem';
        }

        if (preg_match('/^\d+(\.\d+)?(rem|px|em|%)$/', $value) === 1) {
            return $value;
        }

        return null;
    }
}
