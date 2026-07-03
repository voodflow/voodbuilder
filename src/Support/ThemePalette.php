<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

final class ThemePalette
{
    private const HEADER_CHROME_CSS = <<<'CSS'
html[data-voodbuilder-sub-theme] header[role='banner'] :is(.text-vp-text-1,.text-vp-text-3):not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *)){color:var(--vx-header-text)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .text-vp-text-2:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *)){color:var(--vx-header-muted)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] :is(a,button):not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *)):not(.voodbuilder-header-icon-btn):is(:hover,:focus-visible){color:color-mix(in srgb,var(--vx-header-text) 88%,#fff)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .hover\:text-vp-brand-1:hover:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *)){color:var(--color-vp-brand-1)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .hover\:text-vp-text-1:hover:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *)){color:var(--vx-header-text)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .voodbuilder-header-icon-btn{color:var(--vx-header-text,var(--color-vp-text-2))!important;background:color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-2)) 10%,transparent)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .voodbuilder-header-icon-btn:is(:hover,:focus-visible){color:var(--color-vp-brand-1)!important;background:color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-1)) 16%,transparent)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu']{color:var(--vx-menu-text,var(--color-vp-text-1))!important;background-color:var(--color-vp-bg-elv)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-1,html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] .font-medium,html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] [role='menuitem']{color:var(--vx-menu-text,var(--color-vp-text-1))!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-2{color:var(--vx-menu-text-muted,var(--color-vp-text-2))!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-3{color:var(--vx-menu-text-subtle,var(--color-vp-text-3))!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] .text-vp-brand-1{color:var(--color-vp-brand-1)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] [role='menuitem']:hover,html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] a:hover,html[data-voodbuilder-sub-theme] header[role='banner'] [role='menu'] button[role='menuitem']:hover{color:var(--color-vp-brand-1)!important}
CSS;

    private const CANVAS_HEADER_BACKGROUND_CSS = <<<'CSS'
html header[role='banner'] .bg-vp-bg,html header[role='banner'] .bg-vp-bg-alt{background-color:var(--vx-header-bg)!important}
html header[role='banner']{border-bottom:1px solid color-mix(in srgb,var(--vx-header-text) 12%,transparent)}
CSS;

    /** @var list<string> */
    private const COLOR_KEYS = [
        'primary',
        'secondary',
        'header_bg',
        'header_text',
        'body_bg',
        'text',
    ];

    /**
     * @return array<string, array{custom: bool, light: array<string, ?string>, dark: array<string, ?string>}>
     */
    public static function normalize(array $colors): array
    {
        $normalized = [];

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            $existing = is_array($colors[$id] ?? null) ? $colors[$id] : [];
            $light = self::normalizeMode(is_array($existing['light'] ?? null) ? $existing['light'] : []);
            $dark = self::normalizeMode(is_array($existing['dark'] ?? null) ? $existing['dark'] : []);

            $normalized[$id] = [
                'custom' => (bool) ($existing['custom'] ?? false)
                    || self::modeHasOverrides($light)
                    || self::modeHasOverrides($dark),
                'light' => $light,
                'dark' => $dark,
            ];
        }

        return $normalized;
    }

    public static function css(): string
    {
        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $rules = [self::HEADER_CHROME_CSS, self::tokenBridgeCss('html[data-voodbuilder-sub-theme]')];

        foreach ($colors as $subThemeId => $palette) {
            $lightRule = self::buildRule((string) $subThemeId, $palette['light'], false);

            if ($lightRule !== null) {
                $rules[] = $lightRule;
            }

            $darkRule = self::buildRule((string) $subThemeId, $palette['dark'], true);

            if ($darkRule !== null) {
                $rules[] = $darkRule;
            }
        }

        if ($rules === []) {
            return '';
        }

        return implode("\n", array_filter($rules));
    }

    /**
     * Inline palette for the active sub-theme in <head> to avoid a flash of default/bundled colors.
     */
    public static function criticalDocumentCss(string $subThemeId): string
    {
        $rules = [
            self::HEADER_CHROME_CSS,
            self::tokenBridgeCss("html[data-voodbuilder-sub-theme='{$subThemeId}']"),
        ];

        $subThemeCss = self::readSubThemeCss($subThemeId);

        $lightBuiltin = self::variablesToCssRule(
            "html[data-voodbuilder-sub-theme='{$subThemeId}']:not(.dark)",
            self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: false),
        );

        if ($lightBuiltin !== null) {
            $rules[] = $lightBuiltin;
        }

        $darkBuiltin = self::variablesToCssRule(
            "html.dark[data-voodbuilder-sub-theme='{$subThemeId}']",
            self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: true),
        );

        if ($darkBuiltin !== null) {
            $rules[] = $darkBuiltin;
        }

        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $palette = $colors[$subThemeId] ?? null;

        if (is_array($palette)) {
            $lightRule = self::buildRule($subThemeId, $palette['light'], false);

            if ($lightRule !== null) {
                $rules[] = $lightRule;
            }

            $darkRule = self::buildRule($subThemeId, $palette['dark'], true);

            if ($darkRule !== null) {
                $rules[] = $darkRule;
            }
        }

        return implode("\n", array_filter($rules));
    }

    /**
     * GrapesJS canvas iframe: inject built-in sub-theme tokens plus optional admin overrides.
     * Does not rely on data-voodbuilder-sub-theme being present before external stylesheets load.
     */
    public static function cssForCanvas(string $subThemeId): string
    {
        $rules = [
            self::tokenBridgeCss('html'),
            self::headerChromeCssForCanvas(),
            self::CANVAS_HEADER_BACKGROUND_CSS,
            ...self::builtinSubThemeRulesForCanvas($subThemeId),
        ];

        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $palette = $colors[$subThemeId] ?? null;

        if (is_array($palette)) {
            $lightRule = self::buildRule($subThemeId, $palette['light'], false, 'html:not(.dark)');

            if ($lightRule !== null) {
                $rules[] = $lightRule;
            }

            $darkRule = self::buildRule($subThemeId, $palette['dark'], true, 'html.dark');

            if ($darkRule !== null) {
                $rules[] = $darkRule;
            }
        }

        return implode("\n", array_filter($rules));
    }

    public static function headerChromeCss(): string
    {
        return self::HEADER_CHROME_CSS;
    }

    public static function headerChromeCssForCanvas(): string
    {
        return str_replace(
            'html[data-voodbuilder-sub-theme]',
            'html',
            self::HEADER_CHROME_CSS,
        );
    }

    public static function resetForTheme(string $themeId): void
    {
        if (! app(SubThemeRegistry::class)->exists($themeId)) {
            return;
        }

        $colors = VoodbuilderSettings::get('sub_theme_colors', []);

        if (! is_array($colors)) {
            $colors = [];
        }

        unset($colors[$themeId]);

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => self::normalize($colors),
        ]);
    }

    public static function themeHasCustomColors(string $themeId): bool
    {
        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $palette = $colors[$themeId] ?? null;

        return is_array($palette) && ($palette['custom'] ?? false);
    }

    /**
     * @return array{custom: bool, light: array<string, ?string>, dark: array<string, ?string>}|null
     */
    public static function paletteFromBundledCss(string $themeId): ?array
    {
        $cssPath = self::resolveSubThemeCssFile($themeId);

        if ($cssPath === null || ! is_readable($cssPath)) {
            return null;
        }

        $css = (string) file_get_contents($cssPath);
        $light = self::cssVariablesToPaletteMode(self::parseSubThemeVariableBlock($css, $themeId, dark: false));
        $dark = self::cssVariablesToPaletteMode(self::parseSubThemeVariableBlock($css, $themeId, dark: true));

        if (! self::modeHasOverrides($light) && ! self::modeHasOverrides($dark)) {
            return null;
        }

        return [
            'custom' => true,
            'light' => $light,
            'dark' => $dark,
        ];
    }

    public static function sanitizeColor(mixed $color): ?string
    {
        if (! is_string($color) || $color === '') {
            return null;
        }

        $color = strtolower(trim($color));

        if ($color[0] !== '#') {
            if (preg_match('/^([0-9a-f]{3}|[0-9a-f]{6})$/', $color) === 1) {
                $color = '#'.$color;
            }
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $color) !== 1) {
            return null;
        }

        if (strlen($color) === 4) {
            $color = '#'.implode('', array_map(
                static fn (string $char): string => $char.$char,
                str_split(substr($color, 1)),
            ));
        }

        return $color;
    }

    /**
     * @param  array<string, string>  $variables
     * @return array<string, ?string>
     */
    private static function cssVariablesToPaletteMode(array $variables): array
    {
        return self::normalizeMode([
            'primary' => $variables['--color-vp-brand-1'] ?? null,
            'secondary' => $variables['--color-vp-brand-2'] ?? null,
            'header_bg' => $variables['--vx-header-bg'] ?? null,
            'header_text' => $variables['--vx-header-text'] ?? null,
            'body_bg' => $variables['--color-vp-bg'] ?? null,
            'text' => $variables['--color-vp-text-1'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $mode
     * @return array<string, ?string>
     */
    private static function normalizeMode(array $mode): array
    {
        $normalized = [];

        foreach (self::COLOR_KEYS as $key) {
            $normalized[$key] = self::sanitizeColor($mode[$key] ?? null);
        }

        return $normalized;
    }

    /**
     * @param  array<string, ?string>  $palette
     */
    private static function modeHasOverrides(array $palette): bool
    {
        foreach (self::COLOR_KEYS as $key) {
            if (filled($palette[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, ?string>  $palette
     */
    private static function buildRule(string $subThemeId, array $palette, bool $dark, ?string $selector = null): ?string
    {
        if (! self::modeHasOverrides($palette)) {
            return null;
        }

        $primary = $palette['primary'] ?? null;
        $secondary = $palette['secondary'] ?? null;

        if ($primary === null && $secondary !== null) {
            $primary = $secondary;
        }

        if ($secondary === null && $primary !== null) {
            $secondary = $primary;
        }

        $properties = [];

        if ($primary !== null) {
            $properties['--color-vp-brand-1'] = $primary;
        }

        if ($secondary !== null) {
            $properties['--color-vp-brand-2'] = $secondary;
            $properties['--color-vp-brand-3'] = $secondary;
        } elseif ($primary !== null) {
            $properties['--color-vp-brand-2'] = $primary;
            $properties['--color-vp-brand-3'] = $primary;
        }

        if (($bodyBg = $palette['body_bg'] ?? null) !== null) {
            $properties['--color-vp-bg'] = $bodyBg;
            $properties['--color-vp-bg-alt'] = $dark
                ? "color-mix(in srgb, {$bodyBg} 88%, #ffffff)"
                : "color-mix(in srgb, {$bodyBg} 94%, #000000)";
            $properties['--color-vp-bg-elv'] = $bodyBg;
        }

        if (($text = $palette['text'] ?? null) !== null) {
            $mixBase = $bodyBg ?? ($dark ? '#1b1b1f' : '#ffffff');
            $properties['--color-vp-text-1'] = $text;
            $properties['--color-vp-text-2'] = "color-mix(in srgb, {$text} 72%, {$mixBase})";
            $properties['--color-vp-text-3'] = "color-mix(in srgb, {$text} 52%, {$mixBase})";

            foreach (self::defaultSurfaceTextTokens($dark) as $token => $value) {
                $properties[$token] = $value;
            }
        }

        if (($headerBg = $palette['header_bg'] ?? null) !== null) {
            $properties['--vx-header-bg'] = $headerBg;
        }

        if (($headerText = $palette['header_text'] ?? null) !== null) {
            $properties['--vx-header-text'] = $headerText;
            $properties['--vx-header-muted'] = "color-mix(in srgb, {$headerText} 72%, transparent)";
        }

        if ($properties === []) {
            return null;
        }

        $selector ??= $dark
            ? "html.dark[data-voodbuilder-sub-theme='{$subThemeId}']"
            : "html[data-voodbuilder-sub-theme='{$subThemeId}']:not(.dark)";

        $declarations = [];

        foreach ($properties as $property => $value) {
            $declarations[] = "{$property}:{$value}!important";
        }

        return "{$selector}{".implode(';', $declarations).'}';
    }

    /**
     * @return array<string, string>
     */
    private static function defaultSurfaceTextTokens(bool $dark): array
    {
        return $dark
            ? [
                '--vx-menu-text' => '#dfdfd6',
                '--vx-menu-text-muted' => '#98989f',
                '--vx-menu-text-subtle' => '#6a6a71',
            ]
            : [
                '--vx-menu-text' => '#3c3c43',
                '--vx-menu-text-muted' => '#67676c',
                '--vx-menu-text-subtle' => '#929295',
            ];
    }

    private static function tokenBridgeCss(string $selector): string
    {
        return $selector.'{--vp-c-brand-1:var(--color-vp-brand-1);--vp-c-brand-2:var(--color-vp-brand-2);--vp-c-brand-3:var(--color-vp-brand-3);--vp-c-brand-soft:color-mix(in srgb,var(--color-vp-brand-1) 14%,transparent);--vp-c-bg:var(--color-vp-bg);--vp-c-bg-alt:var(--color-vp-bg-alt);--vp-c-bg-soft:var(--color-vp-bg-alt);--vp-c-bg-elv:var(--color-vp-bg-elv);--vp-c-text-1:var(--color-vp-text-1);--vp-c-text-2:var(--color-vp-text-2);--vp-c-text-3:var(--color-vp-text-3)}';
    }

    /**
     * @return list<string>
     */
    private static function builtinSubThemeRulesForCanvas(string $subThemeId): array
    {
        $css = self::readSubThemeCss($subThemeId);

        if ($css === '') {
            return [];
        }

        $rules = [];

        $lightRule = self::variablesToCssRule(
            'html:not(.dark)',
            self::parseSubThemeVariableBlock($css, $subThemeId, dark: false),
        );

        if ($lightRule !== null) {
            $rules[] = $lightRule;
        }

        $darkRule = self::variablesToCssRule(
            'html.dark',
            self::parseSubThemeVariableBlock($css, $subThemeId, dark: true),
        );

        if ($darkRule !== null) {
            $rules[] = $darkRule;
        }

        return $rules;
    }

    private static function readSubThemeCss(string $subThemeId): string
    {
        $cssPath = self::resolveSubThemeCssFile($subThemeId);

        if ($cssPath === null || ! is_readable($cssPath)) {
            return '';
        }

        return (string) file_get_contents($cssPath);
    }

    private static function resolveSubThemeCssFile(string $subThemeId): ?string
    {
        $cssPath = app(SubThemeRegistry::class)->cssPath($subThemeId);

        if (! is_string($cssPath) || $cssPath === '') {
            return null;
        }

        if (str_starts_with($cssPath, 'themes/')) {
            return VoodbuilderPaths::packagePath().'/resources/'.$cssPath;
        }

        if (str_starts_with($cssPath, 'resources/')) {
            return base_path($cssPath);
        }

        $absolute = base_path($cssPath);

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * @return array<string, string>
     */
    private static function parseSubThemeVariableBlock(string $css, string $subThemeId, bool $dark): array
    {
        $quotedId = preg_quote($subThemeId, '/');
        $patterns = $dark
            ? [
                "/html\\.dark\\[data-voodbuilder-sub-theme=['\"]{$quotedId}['\"]\\]\\s*\\{([^}]+)\\}/s",
                "/html\\[data-voodbuilder-sub-theme=['\"]{$quotedId}['\"]\\]\\.dark\\s*\\{([^}]+)\\}/s",
            ]
            : [
                "/html\\[data-voodbuilder-sub-theme=['\"]{$quotedId}['\"]\\]\\s*\\{([^}]+)\\}/s",
            ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $css, $matches) !== 1) {
                continue;
            }

            $variables = [];

            if (preg_match_all('/--([\w-]+)\s*:\s*([^;]+);/', $matches[1], $declarations, PREG_SET_ORDER) !== false) {
                foreach ($declarations as $declaration) {
                    $variables['--'.$declaration[1]] = trim($declaration[2]);
                }
            }

            if ($variables !== []) {
                return $variables;
            }
        }

        return [];
    }

    /**
     * @param  array<string, string>  $variables
     */
    private static function variablesToCssRule(string $selector, array $variables): ?string
    {
        if ($variables === []) {
            return null;
        }

        $declarations = [];

        foreach ($variables as $property => $value) {
            $declarations[] = "{$property}:{$value}!important";
        }

        return "{$selector}{".implode(';', $declarations).'}';
    }
}
