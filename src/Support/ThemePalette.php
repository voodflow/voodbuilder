<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

final class ThemePalette
{
    private const HEADER_CHROME_CSS = <<<'CSS'
html[data-voodbuilder-sub-theme] header[role='banner'].bg-vp-bg,html[data-voodbuilder-sub-theme] header[role='banner'].bg-vp-bg-alt,html[data-voodbuilder-sub-theme] header[role='banner'] .bg-vp-bg,html[data-voodbuilder-sub-theme] header[role='banner'] .bg-vp-bg-alt{background-color:var(--vx-header-bg,var(--color-vp-bg))!important}
html[data-voodbuilder-sub-theme] header[role='banner']{border-bottom:1px solid color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-1)) 12%,transparent)}
html[data-voodbuilder-sub-theme] header[role='banner'] :is(.text-vp-text-1,.text-vp-text-3):not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)){color:var(--vx-header-text)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .text-vp-text-2:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)){color:var(--vx-header-muted)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] :is(a,button):not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)):not(.voodbuilder-header-icon-btn):is(:hover,:focus-visible){color:color-mix(in srgb,var(--vx-header-text) 88%,#fff)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .hover\:text-vp-brand-1:hover:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)){color:var(--color-vp-brand-1)!important}
html[data-voodbuilder-sub-theme] header[role='banner'] .hover\:text-vp-text-1:hover:not(:where([role='menu'],[role='menu'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)){color:var(--vx-header-text)!important}
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
[data-voodbuilder-chrome-shell] header[role='banner'].bg-vp-bg,[data-voodbuilder-chrome-shell] header[role='banner'].bg-vp-bg-alt,[data-voodbuilder-chrome-shell] header[role='banner'] .bg-vp-bg,[data-voodbuilder-chrome-shell] header[role='banner'] .bg-vp-bg-alt{background-color:var(--vx-header-bg,var(--color-vp-bg))!important}
[data-voodbuilder-chrome-shell] header[role='banner']{border-bottom:1px solid color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-1)) 12%,transparent)}
header[role='banner'].bg-vp-bg,header[role='banner'].bg-vp-bg-alt,header[role='banner'] .bg-vp-bg,header[role='banner'] .bg-vp-bg-alt{background-color:var(--vx-header-bg,var(--color-vp-bg))!important}
header[role='banner']{border-bottom:1px solid color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-1)) 12%,transparent)}
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

    /** @var list<string> */
    private const SUB_THEME_VARIABLE_PRIORITIES = [
        '--color-vp-brand-1',
        '--color-vp-brand-2',
        '--color-vp-brand-3',
        '--color-vp-bg',
        '--color-vp-bg-alt',
        '--color-vp-bg-elv',
        '--color-vp-text-1',
        '--color-vp-text-2',
        '--color-vp-text-3',
        '--color-vp-divider',
        '--color-vp-gray-soft',
    ];

    /** @var list<string> */
    private const SUB_THEME_SEMANTIC_VARIABLE_PRIORITIES = [
        '--width-vp-layout',
        '--width-vp-content',
        '--vx-header-bg',
        '--vx-header-text',
        '--vx-header-muted',
        '--vx-sidebar-bg',
        '--vx-surface',
        '--vx-text',
        '--vx-muted',
        '--vx-border',
        '--vx-accent',
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
            self::filterSubThemeVariables(self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: false)),
        );

        if ($lightBuiltin !== null) {
            $rules[] = $lightBuiltin;
        }

        $lightSemantic = self::variablesToCssRule(
            "html[data-voodbuilder-sub-theme='{$subThemeId}']:not(.dark)",
            self::withoutAdminOverriddenSemanticVariables(
                $subThemeId,
                false,
                self::filterSubThemeSemanticVariables(self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: false)),
            ),
        );

        if ($lightSemantic !== null) {
            $rules[] = $lightSemantic;
        }

        $darkBuiltin = self::variablesToCssRule(
            "html.dark[data-voodbuilder-sub-theme='{$subThemeId}']",
            self::filterSubThemeVariables(self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: true)),
        );

        if ($darkBuiltin !== null) {
            $rules[] = $darkBuiltin;
        }

        $darkSemantic = self::variablesToCssRule(
            "html.dark[data-voodbuilder-sub-theme='{$subThemeId}']",
            self::withoutAdminOverriddenSemanticVariables(
                $subThemeId,
                true,
                self::filterSubThemeSemanticVariables(self::parseSubThemeVariableBlock($subThemeCss, $subThemeId, dark: true)),
            ),
        );

        if ($darkSemantic !== null) {
            $rules[] = $darkSemantic;
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

    public static function criticalChromeShellCss(string $subThemeId): string
    {
        $scoped = str_replace(
            [
                "html[data-voodbuilder-sub-theme='{$subThemeId}']",
                "html.dark[data-voodbuilder-sub-theme='{$subThemeId}']",
                'html[data-voodbuilder-sub-theme]',
            ],
            [
                "[data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']",
                ".dark [data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']",
                '[data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme]',
            ],
            self::criticalDocumentCss($subThemeId),
        );

        $css = self::readSubThemeCss($subThemeId);
        $semanticRules = [];

        if ($css !== '') {
            $lightSemanticRule = self::variablesToCssRule(
                "[data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']:not(.dark)",
                self::withoutAdminOverriddenSemanticVariables(
                    $subThemeId,
                    false,
                    self::filterSubThemeSemanticVariables(
                        self::parseSubThemeVariableBlock($css, $subThemeId, dark: false),
                    ),
                ),
            );

            if ($lightSemanticRule !== null) {
                $semanticRules[] = $lightSemanticRule;
            }

            $darkSemanticRule = self::variablesToCssRule(
                ".dark [data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']",
                self::withoutAdminOverriddenSemanticVariables(
                    $subThemeId,
                    true,
                    self::filterSubThemeSemanticVariables(
                        self::parseSubThemeVariableBlock($css, $subThemeId, dark: true),
                    ),
                ),
            );

            if ($darkSemanticRule !== null) {
                $semanticRules[] = $darkSemanticRule;
            }
        }

        return $scoped.implode("\n", $semanticRules).self::CANVAS_HEADER_BACKGROUND_CSS.self::chromeShellAdminOverrideCss($subThemeId);
    }

    public static function chromeShellAdminOverrideCss(string $subThemeId): string
    {
        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $palette = $colors[$subThemeId] ?? null;

        if (! is_array($palette)) {
            return '';
        }

        $rules = [];
        // Higher specificity than page/layout-embedded canvas CSS (same chrome-shell
        // selector without the html[data-voodbuilder-sub-theme] ancestor), so admin
        // palette colors always win on the live frontend.
        $lightRule = self::buildRule(
            $subThemeId,
            $palette['light'],
            false,
            "html[data-voodbuilder-sub-theme='{$subThemeId}'] [data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']:not(.dark)",
        );

        if ($lightRule !== null) {
            $rules[] = $lightRule;
        }

        $darkRule = self::buildRule(
            $subThemeId,
            $palette['dark'],
            true,
            "html.dark[data-voodbuilder-sub-theme='{$subThemeId}'] [data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='{$subThemeId}']",
        );

        if ($darkRule !== null) {
            $rules[] = $darkRule;
        }

        // Also win against stale layout CSS that stamps tokens on html:not(.dark).
        $htmlLight = self::buildRule($subThemeId, $palette['light'], false);
        $htmlDark = self::buildRule($subThemeId, $palette['dark'], true);

        if ($htmlLight !== null) {
            $rules[] = $htmlLight;
        }

        if ($htmlDark !== null) {
            $rules[] = $htmlDark;
        }

        return implode("\n", $rules);
    }

    /**
     * Remove palette custom-properties and ThemePalette chrome rules baked into
     * saved GrapesJS CSS so live ThemePalette / admin overrides control the canvas.
     */
    public static function stripEmbeddedPaletteOverrides(string $css): string
    {
        if ($css === '') {
            return '';
        }

        $tokens = [
            '--vx-header-bg',
            '--vx-header-text',
            '--vx-header-muted',
            '--vx-menu-text',
            '--vx-menu-text-muted',
            '--vx-menu-text-subtle',
            '--color-vp-brand-1',
            '--color-vp-brand-2',
            '--color-vp-brand-3',
            '--color-vp-bg',
            '--color-vp-bg-alt',
            '--color-vp-bg-elv',
            '--color-vp-text-1',
            '--color-vp-text-2',
            '--color-vp-text-3',
        ];

        $pattern = '/(?:'.implode('|', array_map(static fn (string $token): string => preg_quote($token, '/'), $tokens)).')\s*:\s*[^;}{]+;?/i';
        $stripped = preg_replace($pattern, '', $css);
        $stripped = is_string($stripped) ? $stripped : $css;

        return self::stripThemeManagedChromeRules($stripped);
    }

    /**
     * Drop header/chrome ThemePalette rules that are re-injected on every render.
     */
    private static function stripThemeManagedChromeRules(string $css): string
    {
        if ($css === '' || ! str_contains($css, 'header[role=')) {
            return $css;
        }

        $filtered = preg_replace_callback(
            '/([^{}@]+)\{([^{}]*)\}/s',
            static function (array $matches): string {
                $selectors = trim($matches[1]);

                if ($selectors === '') {
                    return $matches[0];
                }

                foreach (array_map('trim', explode(',', $selectors)) as $selector) {
                    if ($selector === '' || ! str_contains($selector, 'header[role=')) {
                        continue;
                    }

                    if (
                        str_contains($selector, 'data-voodbuilder-sub-theme')
                        || str_contains($selector, 'data-voodbuilder-chrome-shell')
                        || preg_match('/^header\[role=[\'"]banner[\'"]/', $selector) === 1
                    ) {
                        return '';
                    }
                }

                return $matches[0];
            },
            $css,
        );

        $filtered = is_string($filtered) ? $filtered : $css;

        return trim(preg_replace("/\n{3,}/", "\n\n", $filtered) ?? $filtered);
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
            // Early rule (before data-voodbuilder-sub-theme is stamped on the iframe).
            $earlyLight = self::buildRule($subThemeId, $palette['light'], false, 'html:not(.dark)');

            if ($earlyLight !== null) {
                $rules[] = $earlyLight;
            }

            $earlyDark = self::buildRule($subThemeId, $palette['dark'], true, 'html.dark');

            if ($earlyDark !== null) {
                $rules[] = $earlyDark;
            }

            // Specific rule beats bundled theme.css (html[data-voodbuilder-sub-theme=…]).
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
            $short = substr($color, 1);

            // Attempt to find a canonical 6-digit color in package CSS whose
            // pairs start with the shorthand nibbles (e.g. '#35b' -> '#3451b2').
            try {
                $packagePath = VoodbuilderPaths::packagePath();
                $cssFiles = glob($packagePath.'/resources/css/*.css') ?: [];

                foreach ($cssFiles as $file) {
                    $contents = @file_get_contents($file);

                    if ($contents === false) {
                        continue;
                    }

                    if (preg_match_all('/#([0-9a-f]{6})/i', $contents, $m)) {
                        foreach ($m[1] as $hex) {
                            if (
                                $hex[0] === $short[0]
                                && $hex[2] === $short[1]
                                && $hex[4] === $short[2]
                            ) {
                                $color = '#'.strtolower($hex);
                                break 2;
                            }
                        }
                    }
                }
            } catch (\Throwable) {
                // ignore filesystem issues and fall back to simple expansion
            }

            if (strlen($color) === 4) {
                $canonical = self::canonicalColorForShortHex($short);

                if ($canonical !== null) {
                    $color = $canonical;
                } else {
                    $color = '#'.implode('', array_map(
                        static fn (string $char): string => $char.$char,
                        str_split($short),
                    ));
                }
            }
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
        // Bridge Tailblocks/Tailwind palette tokens → Voodbuilder brand so canvas
        // utilities (bg-indigo-500, …) match compile-css / published popup CSS.
        return $selector.'{'
            .'--vp-c-brand-1:var(--color-vp-brand-1);'
            .'--vp-c-brand-2:var(--color-vp-brand-2);'
            .'--vp-c-brand-3:var(--color-vp-brand-3);'
            .'--vp-c-brand-soft:color-mix(in srgb,var(--color-vp-brand-1) 14%,transparent);'
            .'--vp-c-bg:var(--color-vp-bg);'
            .'--vp-c-bg-alt:var(--color-vp-bg-alt);'
            .'--vp-c-bg-soft:var(--color-vp-bg-alt);'
            .'--vp-c-bg-elv:var(--color-vp-bg-elv);'
            .'--vp-c-text-1:var(--color-vp-text-1);'
            .'--vp-c-text-2:var(--color-vp-text-2);'
            .'--vp-c-text-3:var(--color-vp-text-3);'
            .'--color-indigo-400:var(--color-vp-brand-1);'
            .'--color-indigo-500:var(--color-vp-brand-3);'
            .'--color-indigo-600:var(--color-vp-brand-2);'
            .'--color-indigo-700:var(--color-vp-brand-2);'
            .'--color-indigo-200:color-mix(in srgb,var(--color-vp-brand-1) 28%,transparent);'
            .'--color-blue-500:var(--color-vp-brand-3);'
            .'--color-blue-600:var(--color-vp-brand-2);'
            .'--color-purple-500:var(--color-vp-brand-3);'
            .'--color-purple-600:var(--color-vp-brand-2);'
            .'--color-violet-500:var(--color-vp-brand-3);'
            .'--color-violet-600:var(--color-vp-brand-2)'
            .'}';
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

        $lightValues = self::parseSubThemeVariableBlock($css, $subThemeId, dark: false);
        $darkValues = self::parseSubThemeVariableBlock($css, $subThemeId, dark: true);

        $lightColorRule = self::variablesToCssRule(
            'html:not(.dark)',
            self::filterSubThemeVariables($lightValues),
        );

        if ($lightColorRule !== null) {
            $rules[] = $lightColorRule;
        }

        $lightSemanticRule = self::variablesToCssRule(
            'html:not(.dark)',
            self::withoutAdminOverriddenSemanticVariables(
                $subThemeId,
                false,
                self::filterSubThemeSemanticVariables($lightValues),
            ),
        );

        if ($lightSemanticRule !== null) {
            $rules[] = $lightSemanticRule;
        }

        $darkColorRule = self::variablesToCssRule(
            'html.dark',
            self::filterSubThemeVariables($darkValues),
        );

        if ($darkColorRule !== null) {
            $rules[] = $darkColorRule;
        }

        $darkSemanticRule = self::variablesToCssRule(
            'html.dark',
            self::withoutAdminOverriddenSemanticVariables(
                $subThemeId,
                true,
                self::filterSubThemeSemanticVariables($darkValues),
            ),
        );

        if ($darkSemanticRule !== null) {
            $rules[] = $darkSemanticRule;
        }

        return $rules;
    }

    private static function readSubThemeCss(string $subThemeId): string
    {
        $cssPath = self::resolveSubThemeCssFile($subThemeId);

        if ($cssPath === null || ! is_readable($cssPath)) {
            return '';
        }

        $contents = (string) file_get_contents($cssPath);

        return self::normalizeSubThemeCss($contents, $subThemeId);
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

    /**
     * @param array<string, string> $variables
     * @return array<string, string>
     */
    private static function filterSubThemeVariables(array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        $filtered = [];

        foreach (self::SUB_THEME_VARIABLE_PRIORITIES as $key) {
            if (isset($variables[$key])) {
                $filtered[$key] = $variables[$key];
            }
        }

        return $filtered;
    }

    private static function filterSubThemeSemanticVariables(array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        $filtered = [];

        foreach (self::SUB_THEME_SEMANTIC_VARIABLE_PRIORITIES as $key) {
            if (isset($variables[$key])) {
                $filtered[$key] = $variables[$key];
            }
        }

        return $filtered;
    }

    /**
     * Drop bundled semantic tokens that admin palette already overrides, so saved
     * GrapesJS / chrome-layout CSS cannot re-apply the theme default later in the cascade.
     *
     * @param  array<string, string>  $variables
     * @return array<string, string>
     */
    private static function withoutAdminOverriddenSemanticVariables(string $subThemeId, bool $dark, array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        foreach (self::adminOverriddenSemanticVariableNames($subThemeId, $dark) as $name) {
            unset($variables[$name]);
        }

        return $variables;
    }

    /**
     * @return list<string>
     */
    private static function adminOverriddenSemanticVariableNames(string $subThemeId, bool $dark): array
    {
        $colors = self::normalize(VoodbuilderSettings::get('sub_theme_colors', []));
        $mode = is_array($colors[$subThemeId] ?? null)
            ? ($colors[$subThemeId][$dark ? 'dark' : 'light'] ?? [])
            : [];

        if (! is_array($mode)) {
            return [];
        }

        $names = [];

        if (($mode['header_bg'] ?? null) !== null) {
            $names[] = '--vx-header-bg';
        }

        if (($mode['header_text'] ?? null) !== null) {
            $names[] = '--vx-header-text';
            $names[] = '--vx-header-muted';
        }

        return $names;
    }

    private static function normalizeSubThemeCss(string $css, string $subThemeId): string
    {
        // Remove nested comments and preserve selector blocks for the sub-theme.
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css) ?: $css;

        // Normalize selectors to a stable, parseable form.
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(["\n", "\r"], ' ', $css);

        // Ensure the target selector is matched in both "html[data-voodbuilder-sub-theme='id']" and
        // "html.dark[data-voodbuilder-sub-theme='id']" orders.
        $css = preg_replace(
            '/html\s*\.dark\s*\[data-voodbuilder-sub-theme=(["\"]).+?\1\]/',
            'html.dark[data-voodbuilder-sub-theme='.$subThemeId.']',
            $css,
        );

        return $css;
    }
}
