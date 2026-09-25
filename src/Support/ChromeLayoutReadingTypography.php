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
 * Edited in the Layout visual builder Integration tab — not Filament admin.
 * Every font / type-scale value is an optional override of AppTypography
 * (site Settings): null or '' inherits, so Settings changes propagate to
 * companion pages without re-saving the layout.
 *
 * Size tokens match Tailwind font-size labels (xs…9xl) and resolve to
 * theme vars (`var(--text-sm)`), same scale as the Style Typography panel.
 * `size` / `sizeMd` / `sizeLg` follow the Style panel viewports
 * (Mobile = base, Tablet = md, Desktop = lg), mobile-first.
 */
final class ChromeLayoutReadingTypography
{
    /** Default body size — Tailwind `text-base` (1rem). */
    public const DEFAULT_SIZE = 'base';

    /** Inter Variable (bundled in theme.css / fonts.css). */
    public const DEFAULT_FONT = 'inter';

    /** @var list<string> */
    public const ELEMENTS = ['h1', 'h2', 'h3', 'h4', 'p'];

    /** @var list<string> */
    public const SCALE_PROPS = ['size', 'sizeMd', 'sizeLg', 'weight', 'leading'];

    /** @var list<string> */
    public const TAILWIND_SIZE_TOKENS = [
        'xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', '8xl', '9xl',
    ];

    /**
     * Article scale overrides — all null: inherit the site type scale.
     *
     * @return array<string, array{size: ?string, sizeMd: ?string, sizeLg: ?string, weight: ?string, leading: ?string}>
     */
    public static function defaultTypeScale(): array
    {
        $row = array_fill_keys(self::SCALE_PROPS, null);

        return array_fill_keys(self::ELEMENTS, $row);
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
            $asRem = rtrim(rtrim(sprintf('%.4f', $rem), '0'), '.') . 'rem';

            if (array_key_exists($asRem, $remMap)) {
                return $remMap[$asRem];
            }
        }

        return self::DEFAULT_SIZE;
    }

    /**
     * Optional size override: blank → null (inherit).
     */
    public static function normalizeOptionalSize(mixed $size): ?string
    {
        if (! is_string($size) && ! is_numeric($size)) {
            return null;
        }

        $size = trim((string) $size);

        return $size === '' ? null : self::normalizeSize($size);
    }

    /**
     * Font override: '' inherits the site font; unknown ids inherit too.
     */
    public static function normalizeFont(?string $fontId): string
    {
        $fontId = trim((string) $fontId);

        if ($fontId === '' || $fontId === self::DEFAULT_FONT) {
            return $fontId;
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        return $catalog->get($fontId) !== null ? $fontId : '';
    }

    /**
     * @return array<string, array{size: ?string, sizeMd: ?string, sizeLg: ?string, weight: ?string, leading: ?string}>
     */
    public static function normalizeTypeScale(mixed $scale): array
    {
        $normalized = self::defaultTypeScale();

        if (! is_array($scale)) {
            return $normalized;
        }

        foreach (self::ELEMENTS as $element) {
            $row = is_array($scale[$element] ?? null) ? $scale[$element] : [];

            $normalized[$element] = [
                'size' => self::normalizeOptionalSize($row['size'] ?? null),
                'sizeMd' => self::normalizeOptionalSize($row['sizeMd'] ?? null),
                'sizeLg' => self::normalizeOptionalSize($row['sizeLg'] ?? null),
                'weight' => self::sanitizeOptionalCssToken($row['weight'] ?? null),
                'leading' => self::sanitizeOptionalCssToken($row['leading'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * @return array{
     *     font: string,
     *     headingFont: string,
     *     typeScale: array<string, array{size: ?string, sizeMd: ?string, sizeLg: ?string, weight: ?string, leading: ?string}>,
     *     inherited: array{
     *         bodyFont: string,
     *         headingFont: string,
     *         bodyLabel: string,
     *         headingLabel: string,
     *         typeScale: array<string, array{size: string, sizeMd: ?string, sizeLg: ?string, weight: string, leading: string}>
     *     },
     *     stylesheetUrls: list<string>,
     *     cssVariables: array<string, string>,
     *     sizeVariables: array<string, array<string, string>>,
     *     css: string,
     *     editorCss: string
     * }
     */
    public static function resolve(?ChromeLayout $layout): array
    {
        $appTypography = AppTypography::resolve();
        $bodyFont = self::normalizeFont($layout?->reading_font);
        $headingFont = self::normalizeFont($layout?->reading_heading_font);
        $typeScale = self::normalizeTypeScale($layout?->reading_type_scale);

        $cssVariables = [
            '--vp-font-family-doc' => $bodyFont === ''
                ? 'var(--vp-font-family-body, var(--font-sans))'
                : self::stackFor($bodyFont),
            '--vp-font-family-doc-heading' => $headingFont === ''
                ? 'var(--vp-font-family-heading, var(--font-heading))'
                : self::stackFor($headingFont),
            // Sidebar / TOC use fixed UI sizes and only follow the reading body font.
            '--vp-font-family-sidebar' => 'var(--vp-font-family-doc)',
            '--vp-font-size-doc' => 'var(--vp-doc-p-size)',
        ];

        foreach ($typeScale as $element => $row) {
            $cssVariables["--vp-doc-{$element}-weight"] = $row['weight'] ?? "var(--vp-app-{$element}-weight)";
            $cssVariables["--vp-doc-{$element}-leading"] = $row['leading'] ?? "var(--vp-app-{$element}-leading)";
        }

        $sizeVariables = self::sizeVariables($typeScale);
        $fontIds = array_values(array_unique(array_filter(
            [$bodyFont, $headingFont],
            static fn (string $id): bool => $id !== '' && $id !== self::DEFAULT_FONT,
        )));

        return [
            'font' => $bodyFont,
            'headingFont' => $headingFont,
            'typeScale' => $typeScale,
            'inherited' => [
                'bodyFont' => $appTypography['bodyFont'],
                'headingFont' => $appTypography['headingFont'],
                'bodyLabel' => $appTypography['bodyLabel'],
                'headingLabel' => $appTypography['headingLabel'],
                'typeScale' => $appTypography['typeScale'],
            ],
            'stylesheetUrls' => FontStylesheets::urlsFor($fontIds),
            'cssVariables' => $cssVariables,
            'sizeVariables' => $sizeVariables,
            'css' => TypographyBreakpoints::css($sizeVariables),
            'editorCss' => TypographyBreakpoints::css($sizeVariables, editorCanvas: true),
        ];
    }

    /**
     * `--vp-doc-{el}-size` per viewport. Without an override at or below a
     * viewport the value points at the (itself responsive) site size var.
     *
     * @param  array<string, array{size: ?string, sizeMd: ?string, sizeLg: ?string, weight: ?string, leading: ?string}>  $typeScale
     * @return array<string, array<string, string>>
     */
    public static function sizeVariables(array $typeScale): array
    {
        $variables = ['base' => [], 'md' => [], 'lg' => []];

        foreach ($typeScale as $element => $row) {
            $previous = null;

            foreach (TypographyBreakpoints::KEYS as $breakpoint) {
                $token = TypographyBreakpoints::cascadedSize($row, $breakpoint);
                $value = $token === null ? "var(--vp-app-{$element}-size)" : self::cssSizeFor($token);

                if ($value !== $previous) {
                    $variables[$breakpoint]["--vp-doc-{$element}-size"] = $value;
                }

                $previous = $value;
            }
        }

        return $variables;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     reading_font: ?string,
     *     reading_heading_font: ?string,
     *     reading_type_scale: array<string, array{size: ?string, sizeMd: ?string, sizeLg: ?string, weight: ?string, leading: ?string}>|null
     * }
     */
    public static function normalizeSavePayload(array $input): array
    {
        $font = self::normalizeFont(isset($input['font']) ? (string) $input['font'] : null);
        $headingFont = self::normalizeFont(isset($input['headingFont']) ? (string) $input['headingFont'] : null);
        $typeScale = self::normalizeTypeScale($input['typeScale'] ?? null);

        return [
            'reading_font' => $font !== '' ? $font : null,
            'reading_heading_font' => $headingFont !== '' ? $headingFont : null,
            'reading_type_scale' => self::hasOverrides($typeScale) ? $typeScale : null,
        ];
    }

    /**
     * @param  array<string, array<string, ?string>>  $typeScale
     */
    public static function hasOverrides(array $typeScale): bool
    {
        foreach ($typeScale as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    return true;
                }
            }
        }

        return false;
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

        return 'var(--text-' . $token . ')';
    }

    /**
     * @param  array<string, string>  $cssVariables
     */
    public static function cssVariablesStyle(array $cssVariables): string
    {
        $parts = [];

        foreach ($cssVariables as $name => $value) {
            $parts[] = $name . ': ' . $value;
        }

        return implode('; ', $parts);
    }

    private static function sanitizeOptionalCssToken(mixed $value): ?string
    {
        $token = trim((string) (is_scalar($value) ? $value : ''));

        if ($token === '' || ! preg_match('/^[A-Za-z0-9.\-%]+$/', $token)) {
            return null;
        }

        return $token;
    }
}
