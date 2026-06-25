<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\VpressSettings;

final class ThemePalette
{
    private const HEADER_CHROME_CSS = <<<'CSS'
html[data-vpress-sub-theme] header[role='banner'] :is(.text-vp-text-1,.text-vp-text-3):not(:where([role='menu'],[role='menu'] *)){color:var(--vx-header-text)!important}
html[data-vpress-sub-theme] header[role='banner'] .text-vp-text-2:not(:where([role='menu'],[role='menu'] *)){color:var(--vx-header-muted)!important}
html[data-vpress-sub-theme] header[role='banner'] :is(a,button):not(:where([role='menu'],[role='menu'] *)):is(:hover,:focus-visible){color:color-mix(in srgb,var(--vx-header-text) 88%,#fff)!important}
html[data-vpress-sub-theme] header[role='banner'] .hover\:text-vp-brand-1:hover:not(:where([role='menu'],[role='menu'] *)){color:var(--color-vp-brand-1)!important}
html[data-vpress-sub-theme] header[role='banner'] .hover\:text-vp-text-1:hover:not(:where([role='menu'],[role='menu'] *)){color:var(--vx-header-text)!important}
html[data-vpress-sub-theme] header[role='banner'] [data-vpress-search] button{color:var(--vx-header-text,var(--color-vp-text-2))!important;background:color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-2)) 10%,transparent)!important}
html[data-vpress-sub-theme] header[role='banner'] [data-vpress-search] button:hover{color:var(--color-vp-brand-1)!important;background:color-mix(in srgb,var(--vx-header-text,var(--color-vp-text-1)) 16%,transparent)!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu']{color:var(--vx-menu-text,var(--color-vp-text-1))!important;background-color:var(--color-vp-bg-elv)!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-1,html[data-vpress-sub-theme] header[role='banner'] [role='menu'] .font-medium,html[data-vpress-sub-theme] header[role='banner'] [role='menu'] [role='menuitem']{color:var(--vx-menu-text,var(--color-vp-text-1))!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-2{color:var(--vx-menu-text-muted,var(--color-vp-text-2))!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu'] .text-vp-text-3{color:var(--vx-menu-text-subtle,var(--color-vp-text-3))!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu'] .text-vp-brand-1{color:var(--color-vp-brand-1)!important}
html[data-vpress-sub-theme] header[role='banner'] [role='menu'] [role='menuitem']:hover,html[data-vpress-sub-theme] header[role='banner'] [role='menu'] a:hover,html[data-vpress-sub-theme] header[role='banner'] [role='menu'] button[role='menuitem']:hover{color:var(--color-vp-brand-1)!important}
CSS;

    /**
     * vtuts/vdocs ship their own --vp-c-* tokens in an inline stylesheet loaded in <head>.
     * Re-map them on <html> after all bundles so admin palette overrides win.
     */
    private const TOKEN_BRIDGE_CSS = <<<'CSS'
html[data-vpress-sub-theme]{--vp-c-brand-1:var(--color-vp-brand-1);--vp-c-brand-2:var(--color-vp-brand-2);--vp-c-brand-3:var(--color-vp-brand-3);--vp-c-brand-soft:color-mix(in srgb,var(--color-vp-brand-1) 14%,transparent);--vp-c-bg:var(--color-vp-bg);--vp-c-bg-alt:var(--color-vp-bg-alt);--vp-c-bg-soft:var(--color-vp-bg-alt);--vp-c-bg-elv:var(--color-vp-bg-elv);--vp-c-text-1:var(--color-vp-text-1);--vp-c-text-2:var(--color-vp-text-2);--vp-c-text-3:var(--color-vp-text-3)}
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
        $colors = self::normalize(VpressSettings::get('sub_theme_colors', []));
        $rules = [self::HEADER_CHROME_CSS, self::TOKEN_BRIDGE_CSS];

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
     * GrapesJS canvas iframe: apply the active sub-theme palette without relying on
     * data-vpress-sub-theme being present before external stylesheets load.
     */
    public static function cssForCanvas(string $subThemeId): string
    {
        $colors = self::normalize(VpressSettings::get('sub_theme_colors', []));
        $palette = $colors[$subThemeId] ?? null;

        if ($palette === null) {
            return '';
        }

        $rules = [];

        $lightRule = self::buildRule($subThemeId, $palette['light'], false, 'html:not(.dark)');

        if ($lightRule !== null) {
            $rules[] = $lightRule;
        }

        $darkRule = self::buildRule($subThemeId, $palette['dark'], true, 'html.dark');

        if ($darkRule !== null) {
            $rules[] = $darkRule;
        }

        return implode("\n", $rules);
    }

    public static function headerChromeCss(): string
    {
        return self::HEADER_CHROME_CSS;
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
            ? "html.dark[data-vpress-sub-theme='{$subThemeId}']"
            : "html[data-vpress-sub-theme='{$subThemeId}']:not(.dark)";

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
}
