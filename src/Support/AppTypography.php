<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Application-level brand typography (site-wide defaults).
 *
 * Distinct from ChromeLayoutReadingTypography (companion .vp-doc surfaces).
 * Editor element styles always win over these defaults.
 */
final class AppTypography
{
    public const DEFAULT_BODY_FONT = 'lato';

    public const DEFAULT_HEADING_FONT = 'montserrat';

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
            'h1' => ['size' => '3xl', 'weight' => '700', 'leading' => '1.25'],
            'h2' => ['size' => '2xl', 'weight' => '700', 'leading' => '1.3'],
            'h3' => ['size' => 'xl', 'weight' => '600', 'leading' => '1.35'],
            'h4' => ['size' => 'lg', 'weight' => '600', 'leading' => '1.4'],
            'p' => ['size' => 'base', 'weight' => '400', 'leading' => '1.625'],
        ];
    }

    /**
     * @return array<string, string> font id => family label
     */
    public static function fontOptions(): array
    {
        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        $options = [];

        foreach ($catalog->all() as $font) {
            $options[$font->id] = $font->family;
        }

        return $options;
    }

    public static function normalizeFont(?string $fontId, string $fallback): string
    {
        $fontId = trim((string) $fontId);

        if ($fontId === '') {
            return $fallback;
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        return $catalog->get($fontId) !== null ? $fontId : $fallback;
    }

    public static function normalizeSize(?string $size, string $fallback = 'base'): string
    {
        $size = trim((string) $size);

        if ($size === '') {
            return $fallback;
        }

        $lower = strtolower($size);

        if (str_starts_with($lower, 'text-')) {
            $lower = substr($lower, 5);
        }

        return in_array($lower, self::TAILWIND_SIZE_TOKENS, true) ? $lower : $fallback;
    }

    /**
     * @return array<string, array{size: string, weight: string, leading: string}>
     */
    public static function normalizeTypeScale(mixed $scale): array
    {
        $defaults = self::defaultTypeScale();

        if (! is_array($scale)) {
            return $defaults;
        }

        $normalized = [];

        foreach (self::ELEMENTS as $element) {
            $row = is_array($scale[$element] ?? null) ? $scale[$element] : [];
            $sizeRaw = $row['size'] ?? $defaults[$element]['size'];

            $normalized[$element] = [
                'size' => self::normalizeSize(
                    is_string($sizeRaw) || is_numeric($sizeRaw) ? (string) $sizeRaw : null,
                    $defaults[$element]['size'],
                ),
                'weight' => self::sanitizeCssToken($row['weight'] ?? null, $defaults[$element]['weight']),
                'leading' => self::sanitizeCssToken($row['leading'] ?? null, $defaults[$element]['leading']),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     typography_body_font: string,
     *     typography_heading_font: string,
     *     typography_type_scale: array<string, array{size: string, weight: string, leading: string}>
     * }
     */
    public static function normalizeSavePayload(array $input): array
    {
        return [
            'typography_body_font' => self::normalizeFont(
                isset($input['typography_body_font']) ? (string) $input['typography_body_font'] : null,
                self::DEFAULT_BODY_FONT,
            ),
            'typography_heading_font' => self::normalizeFont(
                isset($input['typography_heading_font']) ? (string) $input['typography_heading_font'] : null,
                self::DEFAULT_HEADING_FONT,
            ),
            'typography_type_scale' => self::normalizeTypeScale($input['typography_type_scale'] ?? null),
        ];
    }

    /**
     * @return array{
     *     bodyFont: string,
     *     headingFont: string,
     *     bodyStack: string,
     *     headingStack: string,
     *     typeScale: array<string, array{size: string, weight: string, leading: string}>,
     *     stylesheetUrls: list<string>,
     *     cssVariables: array<string, string>,
     *     canvasCss: string
     * }
     */
    public static function resolve(?array $settings = null): array
    {
        $data = $settings ?? VoodbuilderSettings::data();
        $bodyFontId = self::normalizeFont(
            isset($data['typography_body_font']) ? (string) $data['typography_body_font'] : null,
            self::DEFAULT_BODY_FONT,
        );
        $headingFontId = self::normalizeFont(
            isset($data['typography_heading_font']) ? (string) $data['typography_heading_font'] : null,
            self::DEFAULT_HEADING_FONT,
        );
        $typeScale = self::normalizeTypeScale($data['typography_type_scale'] ?? null);
        $bodyStack = self::stackFor($bodyFontId, self::DEFAULT_BODY_FONT);
        $headingStack = self::stackFor($headingFontId, self::DEFAULT_HEADING_FONT);

        $cssVariables = [
            '--font-sans' => $bodyStack,
            '--font-heading' => $headingStack,
            '--vp-font-family-body' => $bodyStack,
            '--vp-font-family-heading' => $headingStack,
        ];

        foreach ($typeScale as $element => $props) {
            $cssVariables["--vp-app-{$element}-size"] = self::cssSizeFor($props['size']);
            $cssVariables["--vp-app-{$element}-weight"] = $props['weight'];
            $cssVariables["--vp-app-{$element}-leading"] = $props['leading'];
        }

        return [
            'bodyFont' => $bodyFontId,
            'headingFont' => $headingFontId,
            'bodyStack' => $bodyStack,
            'headingStack' => $headingStack,
            'typeScale' => $typeScale,
            'stylesheetUrls' => FontStylesheets::urlsFor(array_values(array_unique([$bodyFontId, $headingFontId]))),
            'cssVariables' => $cssVariables,
            'canvasCss' => self::canvasCss($cssVariables, $bodyStack, $headingStack, $typeScale),
        ];
    }

    public static function stackFor(string $fontId, string $fallbackId): string
    {
        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();
        $font = $catalog->get($fontId) ?? $catalog->get($fallbackId);

        return $font instanceof FontDefinition
            ? $font->stack
            : "'Lato', ui-sans-serif, system-ui, sans-serif";
    }

    public static function cssSizeFor(string $size): string
    {
        return 'var(--text-' . self::normalizeSize($size) . ')';
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

    /**
     * Low-specificity base rules for canvas + published pages.
     * Author/editor element styles (utilities, #id rules) win.
     *
     * @param  array<string, string>  $cssVariables
     * @param  array<string, array{size: string, weight: string, leading: string}>  $typeScale
     */
    public static function canvasCss(
        array $cssVariables,
        string $bodyStack,
        string $headingStack,
        array $typeScale,
    ): string {
        $rootDecls = self::cssVariablesStyle($cssVariables);
        $h1Size = self::cssSizeFor($typeScale['h1']['size']);
        $h2Size = self::cssSizeFor($typeScale['h2']['size']);
        $h3Size = self::cssSizeFor($typeScale['h3']['size']);
        $h4Size = self::cssSizeFor($typeScale['h4']['size']);
        $pSize = self::cssSizeFor($typeScale['p']['size']);
        $h1Weight = $typeScale['h1']['weight'];
        $h2Weight = $typeScale['h2']['weight'];
        $h3Weight = $typeScale['h3']['weight'];
        $h4Weight = $typeScale['h4']['weight'];
        $pWeight = $typeScale['p']['weight'];
        $h1Leading = $typeScale['h1']['leading'];
        $h2Leading = $typeScale['h2']['leading'];
        $h3Leading = $typeScale['h3']['leading'];
        $h4Leading = $typeScale['h4']['leading'];
        $pLeading = $typeScale['p']['leading'];

        return <<<CSS
:root, html {
    {$rootDecls};
}
body {
    font-family: var(--vp-font-family-body, {$bodyStack});
    font-size: var(--vp-app-p-size, {$pSize});
    font-weight: var(--vp-app-p-weight, {$pWeight});
    line-height: var(--vp-app-p-leading, {$pLeading});
}
:where(h1, h2, h3, h4, h5, h6) {
    font-family: var(--vp-font-family-heading, {$headingStack});
}
:where(h1) {
    font-size: var(--vp-app-h1-size, {$h1Size});
    font-weight: var(--vp-app-h1-weight, {$h1Weight});
    line-height: var(--vp-app-h1-leading, {$h1Leading});
}
:where(h2) {
    font-size: var(--vp-app-h2-size, {$h2Size});
    font-weight: var(--vp-app-h2-weight, {$h2Weight});
    line-height: var(--vp-app-h2-leading, {$h2Leading});
}
:where(h3) {
    font-size: var(--vp-app-h3-size, {$h3Size});
    font-weight: var(--vp-app-h3-weight, {$h3Weight});
    line-height: var(--vp-app-h3-leading, {$h3Leading});
}
:where(h4) {
    font-size: var(--vp-app-h4-size, {$h4Size});
    font-weight: var(--vp-app-h4-weight, {$h4Weight});
    line-height: var(--vp-app-h4-leading, {$h4Leading});
}
:where(.title-font) {
    font-family: var(--vp-font-family-heading, {$headingStack});
}
:where(.body-font) {
    font-family: var(--vp-font-family-body, {$bodyStack});
}
CSS;
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
