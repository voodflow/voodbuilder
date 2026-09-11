<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Reading typography for companion doc/tutorial surfaces (chrome layout).
 *
 * Edited in the Layout visual builder Reading tab — not Filament admin.
 * Size tokens match Tailwind font-size labels (xs…9xl) and resolve to
 * theme vars (`var(--text-sm)`), same scale as the Style Typography panel.
 */
final class ChromeLayoutReadingTypography
{
    /** Default body size — Tailwind `text-base` (1rem). */
    public const DEFAULT_SIZE = 'base';

    /** Default Inter Variable (bundled in theme.css / fonts.css). */
    public const DEFAULT_FONT = 'inter';

    /** @var list<string> */
    public const ELEMENTS = ['h1', 'h2', 'h3', 'h4', 'p'];

    /** @var list<string> */
    public const TAILWIND_SIZE_TOKENS = [
        'xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', '8xl', '9xl',
    ];

    /**
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    public static function defaultTypeScale(): array
    {
        return [
            'h1' => ['size' => '3xl', 'weight' => '600', 'leading' => '1.25'],
            'h2' => ['size' => '2xl', 'weight' => '600', 'leading' => '1.375'],
            'h3' => ['size' => 'xl', 'weight' => '600', 'leading' => '1.375'],
            'h4' => ['size' => 'lg', 'weight' => '600', 'leading' => '1.375'],
            'p' => ['size' => 'base', 'weight' => '400', 'leading' => '1.625'],
        ];
    }

    /**
     * Defaults tuned for companion sidebars / TOC (smaller than article scale).
     *
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    public static function defaultSidebarTypeScale(): array
    {
        return [
            'h1' => ['size' => 'base', 'weight' => '600', 'leading' => '1.375'],
            'h2' => ['size' => 'sm', 'weight' => '600', 'leading' => '1.375'],
            'h3' => ['size' => 'xs', 'weight' => '500', 'leading' => '1.5'],
            'h4' => ['size' => 'xs', 'weight' => '500', 'leading' => '1.5'],
            'p' => ['size' => 'sm', 'weight' => '400', 'leading' => '1.5'],
        ];
    }

    /**
     * Absolute rem fallbacks (legacy stored lengths → nearest Tailwind token).
     *
     * @return array<string, string>
     */
    public static function legacyRemToTokenMap(): array
    {
        return [
            '0.75rem' => 'xs',
            '0.8125rem' => 'xs',
            '0.875rem' => 'sm',
            '0.9375rem' => 'sm',
            '1em' => 'base',
            '1rem' => 'base',
            '1.0625rem' => 'base',
            '1.125rem' => 'lg',
            '1.25rem' => 'xl',
            '1.5rem' => '2xl',
            '1.75rem' => '3xl',
            '1.875rem' => '3xl',
            '2rem' => '3xl',
            '2.25rem' => '4xl',
            '2.5rem' => '4xl',
            '3rem' => '5xl',
            '3.75rem' => '6xl',
            '4.5rem' => '7xl',
            '6rem' => '8xl',
            '8rem' => '9xl',
        ];
    }

    /**
     * Pre-Tailwind body tokens (sm/md/lg/xl) → current scale.
     *
     * @return array<string, string>
     */
    public static function legacyBodyTokenMap(): array
    {
        return [
            'sm' => 'sm',
            'md' => 'base',
            'lg' => 'lg',
            'xl' => 'xl',
        ];
    }

    /**
     * @return array<string, string> token => UI label
     */
    public static function sizeOptions(): array
    {
        $options = [];

        foreach (self::TAILWIND_SIZE_TOKENS as $token) {
            $options[$token] = $token;
        }

        return $options;
    }

    /**
     * @return array<string, string> font id => family label
     */
    public static function fontOptions(): array
    {
        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        $options = [
            self::DEFAULT_FONT => 'Inter Variable (default)',
        ];

        foreach ($catalog->all() as $font) {
            if ($font->id === self::DEFAULT_FONT || str_starts_with($font->id, 'inter')) {
                continue;
            }

            $options[$font->id] = $font->family;
        }

        return $options;
    }

    public static function normalizeSize(?string $size): string
    {
        $size = trim((string) $size);

        if ($size === '') {
            return self::DEFAULT_SIZE;
        }

        $lower = strtolower($size);

        if (str_starts_with($lower, 'text-')) {
            $lower = substr($lower, 5);
        }

        if (in_array($lower, self::TAILWIND_SIZE_TOKENS, true)) {
            return $lower;
        }

        $legacyBody = self::legacyBodyTokenMap();

        if (array_key_exists($lower, $legacyBody) && $lower === 'md') {
            return $legacyBody[$lower];
        }

        $remMap = self::legacyRemToTokenMap();

        if (array_key_exists($lower, $remMap)) {
            return $remMap[$lower];
        }

        // Bare px → nearest rem token via common conversions.
        if (preg_match('/^(\d*\.?\d+)px$/i', $lower, $matches) === 1) {
            $rem = ((float) $matches[1]) / 16;
            $asRem = rtrim(rtrim(sprintf('%.4f', $rem), '0'), '.').'rem';

            if (array_key_exists($asRem, $remMap)) {
                return $remMap[$asRem];
            }
        }

        return self::DEFAULT_SIZE;
    }

    public static function normalizeFont(?string $fontId): string
    {
        $fontId = trim((string) $fontId);

        if ($fontId === '' || $fontId === self::DEFAULT_FONT) {
            return self::DEFAULT_FONT;
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        return $catalog->get($fontId) !== null
            ? $fontId
            : self::DEFAULT_FONT;
    }

    /**
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    public static function normalizeTypeScale(mixed $scale): array
    {
        return self::normalizeTypeScaleAgainst($scale, self::defaultTypeScale());
    }

    /**
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    public static function normalizeSidebarTypeScale(mixed $scale): array
    {
        return self::normalizeTypeScaleAgainst($scale, self::defaultSidebarTypeScale());
    }

    /**
     * @param  array<string, array{size: string, weight: string, leading: string}>  $defaults
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    private static function normalizeTypeScaleAgainst(mixed $scale, array $defaults): array
    {
        if (! is_array($scale)) {
            return $defaults;
        }

        $normalized = [];

        foreach (self::ELEMENTS as $element) {
            $row = is_array($scale[$element] ?? null) ? $scale[$element] : [];
            $sizeRaw = $row['size'] ?? $defaults[$element]['size'];

            $normalized[$element] = [
                'size' => self::normalizeSize(is_string($sizeRaw) || is_numeric($sizeRaw) ? (string) $sizeRaw : null),
                'weight' => self::sanitizeCssToken($row['weight'] ?? null, $defaults[$element]['weight']),
                'leading' => self::sanitizeCssToken($row['leading'] ?? null, $defaults[$element]['leading']),
            ];
        }

        return $normalized;
    }

    /**
     * @return array{
     *     font: string,
     *     sidebarFont: string,
     *     size: string,
     *     stack: string,
     *     sidebarStack: string,
     *     cssSize: string,
     *     sidebarCssSize: string,
     *     typeScale: array<string, array{size: string, weight: string, leading: string}>,
     *     sidebarTypeScale: array<string, array{size: string, weight: string, leading: string}>,
     *     stylesheetUrls: list<string>,
     *     cssVariables: array<string, string>
     * }
     */
    public static function resolve(?ChromeLayout $layout): array
    {
        $fontId = self::normalizeFont($layout?->reading_font);
        $sidebarFontId = self::normalizeFont($layout?->reading_sidebar_font ?: $fontId);
        $size = self::normalizeSize($layout?->reading_font_size);
        $typeScale = self::normalizeTypeScale($layout?->reading_type_scale);
        $sidebarTypeScale = self::normalizeSidebarTypeScale($layout?->reading_sidebar_type_scale);
        $stack = self::stackFor($fontId);
        $sidebarStack = self::stackFor($sidebarFontId);
        $cssSize = self::cssSizeFor($size);
        $sidebarCssSize = self::cssSizeFor($sidebarTypeScale['p']['size']);

        $fontIds = array_values(array_unique(array_filter([
            $fontId === self::DEFAULT_FONT ? null : $fontId,
            $sidebarFontId === self::DEFAULT_FONT ? null : $sidebarFontId,
        ])));

        $cssVariables = [
            '--vp-font-family-doc' => $stack,
            '--vp-font-family-sidebar' => $sidebarStack,
            '--vp-font-size-doc' => $cssSize,
            '--vp-font-size-sidebar' => $sidebarCssSize,
        ];

        foreach ($typeScale as $element => $props) {
            $cssVariables["--vp-doc-{$element}-size"] = self::cssSizeFor($props['size']);
            $cssVariables["--vp-doc-{$element}-weight"] = $props['weight'];
            $cssVariables["--vp-doc-{$element}-leading"] = $props['leading'];
        }

        foreach ($sidebarTypeScale as $element => $props) {
            $cssVariables["--vp-sidebar-{$element}-size"] = self::cssSizeFor($props['size']);
            $cssVariables["--vp-sidebar-{$element}-weight"] = $props['weight'];
            $cssVariables["--vp-sidebar-{$element}-leading"] = $props['leading'];
        }

        return [
            'font' => $fontId,
            'sidebarFont' => $sidebarFontId,
            'size' => $size,
            'stack' => $stack,
            'sidebarStack' => $sidebarStack,
            'cssSize' => $cssSize,
            'sidebarCssSize' => $sidebarCssSize,
            'typeScale' => $typeScale,
            'sidebarTypeScale' => $sidebarTypeScale,
            'stylesheetUrls' => FontStylesheets::urlsFor($fontIds),
            'cssVariables' => $cssVariables,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     reading_font: string,
     *     reading_font_size: string,
     *     reading_sidebar_font: string,
     *     reading_type_scale: array<string, array{size: string, weight: string, leading: string}>,
     *     reading_sidebar_type_scale: array<string, array{size: string, weight: string, leading: string}>
     * }
     */
    public static function normalizeSavePayload(array $input): array
    {
        return [
            'reading_font' => self::normalizeFont(isset($input['font']) ? (string) $input['font'] : null),
            'reading_font_size' => self::normalizeSize(isset($input['size']) ? (string) $input['size'] : null),
            'reading_sidebar_font' => self::normalizeFont(isset($input['sidebarFont']) ? (string) $input['sidebarFont'] : null),
            'reading_type_scale' => self::normalizeTypeScale($input['typeScale'] ?? null),
            'reading_sidebar_type_scale' => self::normalizeSidebarTypeScale($input['sidebarTypeScale'] ?? null),
        ];
    }

    public static function stackFor(string $fontId): string
    {
        if ($fontId === self::DEFAULT_FONT) {
            return "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();
        $font = $catalog->get($fontId);

        return $font instanceof FontDefinition
            ? $font->stack
            : "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";
    }

    /**
     * Resolve a stored size token to a CSS value (Tailwind theme var).
     */
    public static function cssSizeFor(string $size): string
    {
        $token = self::normalizeSize($size);

        return 'var(--text-'.$token.')';
    }

    /**
     * @return array<string, string>
     */
    public static function cssVariablesStyle(array $cssVariables): string
    {
        $parts = [];

        foreach ($cssVariables as $name => $value) {
            $parts[] = $name.': '.$value;
        }

        return implode('; ', $parts);
    }

    private static function sanitizeCssToken(mixed $value, string $fallback): string
    {
        $token = trim((string) $value);

        if ($token === '' || ! preg_match('/^[A-Za-z0-9.\-%]+$/', $token)) {
            return $fallback;
        }

        return $token;
    }
}
