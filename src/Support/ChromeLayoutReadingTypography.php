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

    /**
     * Article elements (inherit the site scale) plus `h5` for companion group /
     * outline titles. Sidebar and TOC links follow the `p` (body) scale — there is
     * no separate Integration row for links.
     *
     * @var list<string>
     */
    public const ELEMENTS = ['h1', 'h2', 'h3', 'h4', 'h5', 'p'];

    /** @var list<string> */
    public const COLUMN_ELEMENTS = ['h5'];

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
     * VitePress-like defaults for `h5` (no site Settings equivalent).
     *
     * @return array<string, array{size: string, sizeMd: ?string, sizeLg: ?string, weight: string, leading: string}>
     */
    public static function columnDefaults(): array
    {
        return [
            'h5' => ['size' => 'xs', 'sizeMd' => null, 'sizeLg' => null, 'weight' => '700', 'leading' => '1.5'],
        ];
    }

    /**
     * CSS value used when a layout does not override `$prop` of `$element`.
     * Null for side-column elements: nothing is emitted and each stylesheet rule
     * keeps its own fallback (sidebar links 500, TOC links 400, …).
     */
    public static function inheritedCssValue(string $element, string $prop): ?string
    {
        if (in_array($element, self::COLUMN_ELEMENTS, true)) {
            return null;
        }

        return "var(--vp-app-{$element}-{$prop})";
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
            // Sidebar / TOC links use the body font; their titles (h5) the heading font.
            '--vp-font-family-sidebar' => 'var(--vp-font-family-doc)',
            '--vp-font-size-doc' => 'var(--vp-doc-p-size)',
            // Links follow P — keep aliases so companion CSS that still reads
            // --vp-doc-link-* stays in sync without a separate Integration row.
            '--vp-doc-link-weight' => 'var(--vp-doc-p-weight)',
            '--vp-doc-link-leading' => 'var(--vp-doc-p-leading)',
        ];

        foreach ($typeScale as $element => $row) {
            foreach (['weight', 'leading'] as $prop) {
                $value = $row[$prop] ?? self::inheritedCssValue($element, $prop);

                if ($value !== null) {
                    $cssVariables["--vp-doc-{$element}-{$prop}"] = $value;
                }
            }
        }

        $sizeVariables = self::sizeVariables($typeScale);
        $sizeVariables['base']['--vp-doc-link-size'] = 'var(--vp-doc-p-size)';
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
                'typeScale' => array_merge($appTypography['typeScale'], self::columnDefaults()),
            ],
            'stylesheetUrls' => FontStylesheets::urlsFor($fontIds),
            'cssVariables' => $cssVariables,
            'sizeVariables' => $sizeVariables,
            'css' => TypographyBreakpoints::css($sizeVariables) . "\n" . self::elementRulesCss(),
            'editorCss' => TypographyBreakpoints::css($sizeVariables, editorCanvas: true) . "\n" . self::elementRulesCss(),
        ];
    }

    /**
     * Unlayered element rules so Integration sizes beat AppTypography's `:where(h1)`
     * (also unlayered). Theme.css puts the same selectors in `@layer components`,
     * which loses to unlayered app rules and made layout H1 overrides invisible
     * on docs/tutorials.
     */
    public static function elementRulesCss(): string
    {
        return <<<'CSS'
.vp-doc-title,
.vp-doc h1 {
    font-size: var(--vp-doc-h1-size, 2rem);
    font-weight: var(--vp-doc-h1-weight, 600);
    line-height: var(--vp-doc-h1-leading, 1.25);
}
.vp-doc h2 {
    font-size: var(--vp-doc-h2-size, 1.5rem);
    font-weight: var(--vp-doc-h2-weight, 600);
    line-height: var(--vp-doc-h2-leading, 1.333);
}
.vp-doc h3 {
    font-size: var(--vp-doc-h3-size, 1.25rem);
    font-weight: var(--vp-doc-h3-weight, 600);
    line-height: var(--vp-doc-h3-leading, 1.4);
}
.vp-doc h4 {
    font-size: var(--vp-doc-h4-size, 1.125rem);
    font-weight: var(--vp-doc-h4-weight, 600);
    line-height: var(--vp-doc-h4-leading, 1.333);
}
.vp-doc p,
.vp-doc li {
    font-size: var(--vp-doc-p-size, 1em);
    font-weight: var(--vp-doc-p-weight, 400);
    line-height: var(--vp-doc-p-leading, 1.75);
}
.vp-reading-sidebar-group,
.vp-outline__title {
    font-size: var(--vp-doc-h5-size, 0.75rem);
    font-weight: var(--vp-doc-h5-weight, 700);
    line-height: var(--vp-doc-h5-leading, 1.5);
}
.vp-reading-sidebar-link,
.vp-outline__link {
    font-size: var(--vp-doc-p-size, 0.875rem);
    font-weight: var(--vp-doc-p-weight, 400);
    line-height: var(--vp-doc-p-leading, 1.5);
}
CSS;
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
                $value = $token === null ? self::inheritedCssValue($element, 'size') : self::cssSizeFor($token);

                if ($value !== null && $value !== $previous) {
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
