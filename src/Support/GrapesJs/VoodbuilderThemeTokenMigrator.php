<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Normalizes hardcoded Tailwind colors to Voodbuilder theme tokens so components
 * adapt automatically to light/dark mode and backend palette overrides.
 */
final class VoodbuilderThemeTokenMigrator
{
    /**
     * @var list<string>
     */
    private const VARIANT_PREFIXES = [
        'hover:',
        'focus:',
        'active:',
        'group-hover:',
        'focus-within:',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_COLORS = [
        'indigo',
        'yellow',
        'red',
        'purple',
        'violet',
        'pink',
        'blue',
        'green',
    ];

    /**
     * Default Tailwind palette hex values (not theme-specific overrides).
     * Used to replace hardcoded block colors with CSS variables while preserving
     * deliberate custom picks from the style manager.
     *
     * @var list<string>
     */
    private const BRAND_LIGHT_SURFACE_HEX = [
        '#eef2ff', '#e0e7ff', '#c7d2fe', '#eff6ff', '#dbeafe', '#bfdbfe',
        '#fefce8', '#fef9c3', '#fef2f2', '#fee2e2', '#faf5ff', '#f3e8ff',
        '#fdf2f8', '#fce7f3', '#f0fdf4', '#dcfce7',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_MID_SURFACE_HEX = [
        '#a5b4fc', '#818cf8', '#6366f1', '#93c5fd', '#60a5fa', '#3b82f6',
        '#eab308', '#facc15', '#ef4444', '#f87171', '#a855f7', '#c084fc',
        '#ec4899', '#f472b6', '#22c55e', '#4ade80',
    ];

    /**
     * @var list<string>
     */
    private const BRAND_DARK_SURFACE_HEX = [
        '#4f46e5', '#4338ca', '#3730a3', '#312e81', '#2563eb', '#1d4ed8',
        '#1e40af', '#ca8a04', '#a16207', '#dc2626', '#b91c1c', '#9333ea',
        '#7e22ce', '#db2777', '#be185d', '#16a34a', '#15803d',
        '#111827', '#1f2937', '#0f172a', '#374151', '#172554',
    ];

    public static function migrateHtml(string $html): string
    {
        $html = self::migrateSectionElements($html);
        $html = self::ensureSectionBlockContainers($html);
        $html = self::stripConflictingInlineTextColors($html);
        $html = self::migrateInlineStyles($html);

        return self::migrateHtmlClassAttributes($html);
    }

    private static function migrateHtmlClassAttributes(string $html): string
    {
        if (! str_contains($html, 'data-voodbuilder-component')) {
            return preg_replace_callback(
                '/\bclass=(["\'])(.*?)\1/',
                static function (array $matches): string {
                    $quote = $matches[1];
                    $classes = self::migrateClassList($matches[2]);

                    return 'class='.$quote.$classes.$quote;
                },
                $html,
            ) ?? $html;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element instanceof \DOMElement || ! $element->hasAttribute('class')) {
                continue;
            }

            if (self::isInsideComponentInstance($element)) {
                continue;
            }

            $element->setAttribute('class', self::migrateClassList($element->getAttribute('class')));
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof \DOMElement) {
            return $html;
        }

        $migrated = '';

        foreach ($body->childNodes as $child) {
            $migrated .= $document->saveHTML($child);
        }

        return $migrated;
    }

    private static function isInsideComponentInstance(\DOMElement $element): bool
    {
        $current = $element;

        while ($current instanceof \DOMElement) {
            if ($current->hasAttribute('data-voodbuilder-component')) {
                return true;
            }

            $parent = $current->parentNode;

            if (! $parent instanceof \DOMElement) {
                break;
            }

            $current = $parent;
        }

        return false;
    }

    public static function migrateCss(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        $css = self::stripScopedThemeTokenOverrides($css);
        $css = self::stripLegacyPaletteColorVariables($css);
        $css = self::replaceLegacyPaletteColorReferences($css);
        $css = self::replaceFixedBackgroundColors($css);
        $css = self::replaceFixedBrandBackgroundColors($css);
        $css = self::stripLegacyButtonCss($css);

        $css = str_replace(
            [
                'background-color: #6366f1',
                'background-color: #4f46e5',
            ],
            [
                'background-color: var(--color-vp-brand-1)',
                'background-color: var(--color-vp-brand-2)',
            ],
            $css,
        );

        return preg_replace_callback(
            '/\bcolor:\s*([^;}{]+)/',
            static function (array $matches): string {
                $value = strtolower(trim($matches[1]));

                if (self::isFixedDarkTextColor($value)) {
                    return 'color: var(--color-vp-text-1)';
                }

                return $matches[0];
            },
            $css,
        ) ?? $css;
    }

    /**
     * Page-level Tailwind CSS: migrate legacy tokens, then keep palette var() references usable
     * when --color-blue-* declarations were stripped from the compiled bundle.
     */
    public static function migratePublishedPageCss(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        return self::ensurePaletteVarFallbacks(self::migrateCss($css));
    }

    /**
     * Component Tailwind CSS keeps palette utilities (bg-blue-200, etc.) on instance HTML.
     * Do not strip --color-blue-* declarations or bare var() references needed by those utilities.
     */
    public static function migrateComponentCss(string $css): string
    {
        if ($css === '') {
            return $css;
        }

        $css = preg_replace(
            '/\s*--color-vp-(?:brand-\d|text-\d|bg(?:-alt|-elv)?|divider|gray-soft)\s*:[^;]+;/i',
            '',
            $css,
        ) ?? $css;

        $css = self::replaceLegacyPaletteColorReferences($css);
        $css = self::ensurePaletteVarFallbacks($css);
        $css = self::stripLegacyButtonCss($css);

        return $css;
    }

    public static function ensurePaletteVarFallbacks(string $css): string
    {
        return preg_replace_callback(
            '/var\(--color-(blue|green|indigo|yellow|red|purple|violet|pink)-(\d+)\)(?!\s*,)/i',
            static function (array $matches): string {
                $fallback = self::tailwindPaletteFallback(
                    strtolower($matches[1]),
                    (int) $matches[2],
                );

                if ($fallback === null) {
                    return $matches[0];
                }

                return 'var(--color-'.$matches[1].'-'.$matches[2].', '.$fallback.')';
            },
            $css,
        ) ?? $css;
    }

    private static function tailwindPaletteFallback(string $palette, int $shade): ?string
    {
        $values = self::TAILWIND_PALETTE_FALLBACKS[$palette] ?? null;

        if ($values === null) {
            return null;
        }

        return $values[$shade] ?? null;
    }

    /**
     * Tailwind CSS v4 default oklch values for legacy palette utilities in pasted components.
     *
     * @var array<string, array<int, string>>
     */
    private const TAILWIND_PALETTE_FALLBACKS = [
        'blue' => [
            50 => 'oklch(97% 0.014 254.604)',
            100 => 'oklch(93.2% 0.032 255.585)',
            200 => 'oklch(88.2% 0.059 254.128)',
            300 => 'oklch(80.9% 0.105 251.813)',
            400 => 'oklch(70.7% 0.165 254.624)',
            500 => 'oklch(62.3% 0.214 259.815)',
            600 => 'oklch(54.6% 0.245 262.881)',
            700 => 'oklch(48.8% 0.243 264.376)',
            800 => 'oklch(42.4% 0.199 265.638)',
            900 => 'oklch(37.9% 0.146 265.522)',
            950 => 'oklch(28.2% 0.091 267.935)',
        ],
        'indigo' => [
            50 => 'oklch(96.2% 0.018 272.314)',
            100 => 'oklch(93% 0.034 272.788)',
            200 => 'oklch(87% 0.065 274.039)',
            300 => 'oklch(78.5% 0.115 274.713)',
            400 => 'oklch(67.3% 0.182 276.935)',
            500 => 'oklch(58.5% 0.233 277.117)',
            600 => 'oklch(51.1% 0.262 276.966)',
            700 => 'oklch(45.7% 0.24 277.023)',
            800 => 'oklch(39.8% 0.195 277.366)',
            900 => 'oklch(35.9% 0.144 278.697)',
            950 => 'oklch(25.7% 0.09 281.288)',
        ],
        'green' => [
            50 => 'oklch(98.2% 0.018 155.826)',
            100 => 'oklch(96.2% 0.044 156.743)',
            200 => 'oklch(92.5% 0.084 155.995)',
            300 => 'oklch(87.1% 0.15 154.449)',
            400 => 'oklch(79.2% 0.209 151.711)',
            500 => 'oklch(72.3% 0.219 149.579)',
            600 => 'oklch(62.7% 0.194 149.214)',
            700 => 'oklch(52.7% 0.154 150.069)',
            800 => 'oklch(44.8% 0.119 151.328)',
            900 => 'oklch(39.3% 0.095 152.535)',
            950 => 'oklch(26.6% 0.065 152.934)',
        ],
        'red' => [
            50 => 'oklch(97.1% 0.013 17.38)',
            100 => 'oklch(93.6% 0.032 17.717)',
            200 => 'oklch(88.5% 0.062 18.334)',
            300 => 'oklch(80.8% 0.114 19.571)',
            400 => 'oklch(70.4% 0.191 22.216)',
            500 => 'oklch(63.7% 0.237 25.331)',
            600 => 'oklch(57.7% 0.245 27.325)',
            700 => 'oklch(50.5% 0.213 27.518)',
            800 => 'oklch(44.4% 0.177 26.899)',
            900 => 'oklch(39.6% 0.141 25.723)',
            950 => 'oklch(25.8% 0.092 26.042)',
        ],
        'yellow' => [
            50 => 'oklch(98.7% 0.026 102.212)',
            100 => 'oklch(97.3% 0.071 103.193)',
            200 => 'oklch(94.5% 0.129 101.54)',
            300 => 'oklch(90.5% 0.182 98.111)',
            400 => 'oklch(85.2% 0.199 91.936)',
            500 => 'oklch(79.5% 0.184 86.047)',
            600 => 'oklch(68.1% 0.162 75.834)',
            700 => 'oklch(55.4% 0.135 66.442)',
            800 => 'oklch(47.6% 0.114 61.907)',
            900 => 'oklch(42.1% 0.095 57.708)',
            950 => 'oklch(28.6% 0.066 53.813)',
        ],
        'purple' => [
            50 => 'oklch(97.7% 0.014 308.299)',
            100 => 'oklch(94.6% 0.033 307.174)',
            200 => 'oklch(90.2% 0.063 306.703)',
            300 => 'oklch(82.7% 0.119 306.383)',
            400 => 'oklch(71.4% 0.203 305.504)',
            500 => 'oklch(62.7% 0.265 303.9)',
            600 => 'oklch(55.8% 0.288 302.321)',
            700 => 'oklch(49.6% 0.265 301.924)',
            800 => 'oklch(43.8% 0.218 303.724)',
            900 => 'oklch(38.1% 0.176 304.987)',
            950 => 'oklch(29.1% 0.149 302.717)',
        ],
        'pink' => [
            50 => 'oklch(97.1% 0.014 343.198)',
            100 => 'oklch(94.8% 0.028 342.258)',
            200 => 'oklch(89.9% 0.061 343.231)',
            300 => 'oklch(82.3% 0.12 346.018)',
            400 => 'oklch(71.8% 0.202 349.761)',
            500 => 'oklch(65.6% 0.241 354.308)',
            600 => 'oklch(59.2% 0.249 0.584)',
            700 => 'oklch(52.5% 0.223 3.958)',
            800 => 'oklch(45.9% 0.187 3.815)',
            900 => 'oklch(40.8% 0.153 2.432)',
            950 => 'oklch(28.4% 0.109 3.907)',
        ],
        'gray' => [
            50 => 'oklch(98.5% 0.002 247.839)',
            100 => 'oklch(96.7% 0.003 264.542)',
            200 => 'oklch(92.8% 0.006 264.531)',
            300 => 'oklch(87.2% 0.01 258.338)',
            400 => 'oklch(70.7% 0.022 261.325)',
            500 => 'oklch(55.1% 0.027 264.364)',
            600 => 'oklch(44.6% 0.03 256.802)',
            700 => 'oklch(37.3% 0.034 259.733)',
            800 => 'oklch(27.8% 0.033 256.848)',
            900 => 'oklch(21% 0.034 264.665)',
            950 => 'oklch(13% 0.028 261.692)',
        ],
        'slate' => [
            50 => 'oklch(98.4% 0.003 247.858)',
            100 => 'oklch(96.8% 0.007 247.896)',
            200 => 'oklch(92.9% 0.013 255.508)',
            300 => 'oklch(86.9% 0.022 252.894)',
            400 => 'oklch(70.4% 0.04 256.788)',
            500 => 'oklch(55.4% 0.046 257.417)',
            600 => 'oklch(44.6% 0.043 257.281)',
            700 => 'oklch(37.2% 0.044 257.287)',
            800 => 'oklch(27.9% 0.041 260.031)',
            900 => 'oklch(20.8% 0.042 265.755)',
            950 => 'oklch(12.9% 0.042 264.695)',
        ],
        'zinc' => [
            50 => 'oklch(98.5% 0 0)',
            100 => 'oklch(96.7% 0.001 286.375)',
            200 => 'oklch(92% 0.004 286.32)',
            300 => 'oklch(87.1% 0.006 286.286)',
            400 => 'oklch(70.5% 0.015 286.067)',
            500 => 'oklch(55.2% 0.016 285.938)',
            600 => 'oklch(44.2% 0.017 285.786)',
            700 => 'oklch(37% 0.013 285.805)',
            800 => 'oklch(27.4% 0.006 286.033)',
            900 => 'oklch(21% 0.006 285.885)',
            950 => 'oklch(14.1% 0.005 285.823)',
        ],
    ];

    /**
     * @param  array<string, mixed>  $project
     * @return array<string, mixed>
     */
    public static function migrateProject(array $project): array
    {
        if (isset($project['styles']) && is_array($project['styles'])) {
            $project['styles'] = array_values(array_filter(
                array_map(
                    static function (mixed $styleRule): mixed {
                        if (! is_array($styleRule)) {
                            return $styleRule;
                        }

                        if (isset($styleRule['style']) && is_array($styleRule['style'])) {
                            $styleRule['style'] = self::migrateStyleObject($styleRule['style']);
                        }

                        return $styleRule;
                    },
                    $project['styles'],
                ),
                static function (mixed $styleRule): bool {
                    if (! is_array($styleRule)) {
                        return true;
                    }

                    return ! self::styleRuleTargetsLegacyButton($styleRule);
                },
            ));
        }

        return self::migrateProjectNode($project);
    }

    public static function hasExplicitTextColorClass(string $classList): bool
    {
        $tokens = preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return false;
        }

        foreach ($tokens as $token) {
            if (preg_match('/^text-(xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl|8xl|9xl|left|center|right|justify|start|end)$/', $token) === 1) {
                continue;
            }

            if (str_starts_with($token, 'text-')) {
                return true;
            }
        }

        return false;
    }

    private static function stripConflictingInlineTextColors(string $html): string
    {
        return preg_replace_callback(
            '/<([a-z][\w:.-]*)\b([^>]*?)>/i',
            static function (array $matches): string {
                $attrs = $matches[2];

                if (preg_match('/\bclass=(["\'])(.*?)\1/i', $attrs, $classMatch) !== 1) {
                    return $matches[0];
                }

                if (! self::hasExplicitTextColorClass($classMatch[2])) {
                    return $matches[0];
                }

                if (preg_match('/\bstyle=(["\'])(.*?)\1/i', $attrs, $styleMatch) !== 1) {
                    return $matches[0];
                }

                $newStyle = self::removeColorFromStyleDeclaration($styleMatch[2]);

                if ($newStyle === trim($styleMatch[2])) {
                    return $matches[0];
                }

                if ($newStyle === '') {
                    $attrs = preg_replace('/\s*\bstyle=(["\'])(.*?)\1/i', '', $attrs) ?? $attrs;
                } else {
                    $attrs = preg_replace(
                        '/\bstyle=(["\'])(.*?)\1/i',
                        'style='.$styleMatch[1].$newStyle.$styleMatch[1],
                        $attrs,
                        1,
                    ) ?? $attrs;
                }

                return '<'.$matches[1].$attrs.'>';
            },
            $html,
        ) ?? $html;
    }

    private static function removeColorFromStyleDeclaration(string $style): string
    {
        $style = preg_replace('/\bcolor\s*:\s*[^;]+;?/i', '', $style) ?? $style;

        return trim($style, " \t\n\r\0\x0B;");
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function classListFromNode(array $node): ?string
    {
        if (isset($node['attributes']['class']) && is_string($node['attributes']['class'])) {
            return $node['attributes']['class'];
        }

        if (isset($node['classes']) && is_array($node['classes']) && is_string($node['classes'][0] ?? null)) {
            return implode(' ', $node['classes']);
        }

        return null;
    }

    public static function migrateClassList(string $classList): string
    {
        $tokens = preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false || $tokens === []) {
            return $classList;
        }

        $tokens = array_map(static fn (string $token): string => self::migrateToken($token), $tokens);
        $tokens = self::migrateLegacyButtonClassesArray($tokens);
        $tokens = self::normalizeBrandBackgroundClasses($tokens);
        $tokens = self::normalizeLegacyFlexColumnWidths($tokens);
        $tokens = self::migrateContainerClass($tokens);
        $tokens = self::syncContainerTailwindUtilities($tokens);
        $tokens = self::migrateLegacyOpacityUtilities($tokens);

        return implode(' ', $tokens);
    }

    /**
     * Tailwind v3 `border-opacity-*` / `bg-opacity-*` → v4 slash modifier on the color utility.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function migrateLegacyOpacityUtilities(array $tokens): array
    {
        $slots = [
            'border-opacity-' => 'border-',
            'divide-opacity-' => 'divide-',
            'bg-opacity-' => 'bg-',
            'text-opacity-' => 'text-',
        ];

        foreach ($tokens as $index => $token) {
            foreach ($slots as $opacityPrefix => $colorPrefix) {
                if (! str_starts_with($token, $opacityPrefix)) {
                    continue;
                }

                $opacity = substr($token, strlen($opacityPrefix));

                if ($opacity === '') {
                    continue;
                }

                $colorIndex = null;

                foreach ($tokens as $candidateIndex => $candidate) {
                    if ($candidateIndex === $index) {
                        continue;
                    }

                    if (
                        str_starts_with($candidate, $colorPrefix)
                        && ! str_contains($candidate, '/')
                    ) {
                        $colorIndex = $candidateIndex;
                    }
                }

                if ($colorIndex === null) {
                    continue;
                }

                $tokens[$colorIndex] = $tokens[$colorIndex].'/'.$opacity;
                unset($tokens[$index]);

                break;
            }
        }

        return array_values($tokens);
    }

    /**
     * Tailblocks catalog columns often use only `lg:w-1/3` (1024px+). Below that breakpoint
     * flex children have no width and collapse to content. Add `w-full md:w-*` when missing.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function normalizeLegacyFlexColumnWidths(array $tokens): array
    {
        $lgWidths = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => preg_match('/^lg:w-/', $token) === 1,
        ));

        if ($lgWidths === []) {
            return $tokens;
        }

        foreach ($tokens as $token) {
            if (preg_match('/^w-/', $token) === 1) {
                return $tokens;
            }

            if (preg_match('/^(?:sm|md):w-/', $token) === 1) {
                return $tokens;
            }
        }

        $additions = ['w-full'];

        foreach ($lgWidths as $lgWidth) {
            $fraction = substr($lgWidth, strlen('lg:w-'));
            $mdWidth = 'md:w-'.$fraction;

            if (! in_array($mdWidth, $tokens, true)) {
                $additions[] = $mdWidth;
            }
        }

        return array_values(array_unique(array_merge($additions, $tokens)));
    }

    /**
     * Tailwind `.container` uses viewport breakpoints that break inside the GrapesJS canvas
     * and in page JIT CSS (media queries emitted in wrong cascade order). Use a stable wrapper.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function migrateContainerClass(array $tokens): array
    {
        if (! in_array('container', $tokens, true)) {
            return $tokens;
        }

        $tokens = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => $token !== 'container',
        ));

        if (! in_array('voodbuilder-gjs-container', $tokens, true)) {
            array_unshift($tokens, 'voodbuilder-gjs-container');
        }

        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => $token !== 'mx-auto',
        ));
    }

    /**
     * @var list<string>
     */
    private const CONTAINER_TAILWIND_UTILITIES = [
        'mx-auto',
        'w-full',
        'max-w-[var(--width-vp-layout)]',
    ];

    /**
     * Keep layout when `voodbuilder-gjs-container` is present: mirror its global CSS
     * as Tailwind utilities so per-block JIT compile also constrains width.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function syncContainerTailwindUtilities(array $tokens): array
    {
        if (! in_array('voodbuilder-gjs-container', $tokens, true)) {
            return $tokens;
        }

        foreach (self::CONTAINER_TAILWIND_UTILITIES as $utility) {
            if (! in_array($utility, $tokens, true)) {
                $tokens[] = $utility;
            }
        }

        return $tokens;
    }

    /**
     * Catalog section blocks always wrap content in a container div. Restore it when
     * editors remove `voodbuilder-gjs-container` from the code modal.
     */
    private static function ensureSectionBlockContainers(string $html): string
    {
        if (! str_contains($html, 'data-voodbuilder-section-block')) {
            return $html;
        }

        return preg_replace_callback(
            '/<section\b([^>]*\bdata-voodbuilder-section-block=(["\'])[^"\']+\2[^>]*)>(\s*)<div\b([^>]*)\bclass=(["\'])(.*?)\5([^>]*)>/i',
            static function (array $matches): string {
                $tokens = preg_split('/\s+/', trim($matches[6]), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                if (in_array('voodbuilder-gjs-container', $tokens, true) || in_array('container', $tokens, true)) {
                    return $matches[0];
                }

                $tokens = self::syncContainerTailwindUtilities(array_merge(['voodbuilder-gjs-container'], $tokens));
                $classes = implode(' ', $tokens);

                return '<section'.$matches[1].'>'.$matches[3].'<div'.$matches[4].'class='.$matches[5].$classes.$matches[5].$matches[7].'>';
            },
            $html,
        ) ?? $html;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public static function normalizeBrandBackgroundClasses(array $tokens): array
    {
        foreach (array_merge([''], self::VARIANT_PREFIXES) as $prefix) {
            $tokens = self::dedupeBrandBackgroundSlot($tokens, $prefix);
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private static function dedupeBrandBackgroundSlot(array $tokens, string $prefix): array
    {
        $pattern = '/^'.preg_quote($prefix, '/').'bg-vp-brand-\d+/';

        $matches = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => preg_match($pattern, $token) === 1,
        ));

        if (count($matches) <= 1) {
            return $tokens;
        }

        $keep = self::preferredBrandBackgroundClass($prefix, $matches);

        return array_values(array_filter(
            $tokens,
            static function (string $token) use ($pattern, $keep): bool {
                if (preg_match($pattern, $token) !== 1) {
                    return true;
                }

                return $token === $keep;
            },
        ));
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function preferredBrandBackgroundClass(string $prefix, array $candidates): string
    {
        $priorities = match ($prefix) {
            'hover:' => ['hover:bg-vp-brand-2', 'hover:bg-vp-brand-1', 'hover:bg-vp-brand-3'],
            'focus:' => ['focus:bg-vp-brand-2', 'focus:bg-vp-brand-1', 'focus:bg-vp-brand-3'],
            'active:' => ['active:bg-vp-brand-2', 'active:bg-vp-brand-1', 'active:bg-vp-brand-3'],
            'group-hover:' => ['group-hover:bg-vp-brand-2', 'group-hover:bg-vp-brand-1', 'group-hover:bg-vp-brand-3'],
            'focus-within:' => ['focus-within:bg-vp-brand-2', 'focus-within:bg-vp-brand-1', 'focus-within:bg-vp-brand-3'],
            default => ['bg-vp-brand-1', 'bg-vp-brand-2', 'bg-vp-brand-3'],
        };

        foreach ($priorities as $preferred) {
            if (in_array($preferred, $candidates, true)) {
                return $preferred;
            }
        }

        return $candidates[0];
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public static function migrateLegacyButtonClassesArray(array $tokens): array
    {
        if (! in_array('voodbuilder-gjs-btn-primary', $tokens, true)) {
            return $tokens;
        }

        $tokens = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => $token !== 'voodbuilder-gjs-btn-primary',
        ));

        foreach (['bg-vp-brand-1', 'text-white', 'hover:bg-vp-brand-2'] as $className) {
            if (! in_array($className, $tokens, true)) {
                $tokens[] = $className;
            }
        }

        return $tokens;
    }

    /**
     * @param  list<string>  $tokens
     */
    public static function migrateLegacyButtonClasses(array $tokens): string
    {
        return implode(' ', self::migrateLegacyButtonClassesArray($tokens));
    }

    public static function migrateToken(string $token): string
    {
        foreach (self::VARIANT_PREFIXES as $prefix) {
            if (str_starts_with($token, $prefix)) {
                return $prefix.self::migrateToken(substr($token, strlen($prefix)));
            }
        }

        return match ($token) {
            'bg-white' => 'bg-vp-bg-elv',
            'bg-gray-50', 'bg-gray-100' => 'bg-vp-bg-alt',
            'bg-gray-200', 'bg-gray-300' => 'bg-vp-gray-soft',
            'text-gray-900', 'text-gray-800', 'text-gray-700' => 'text-vp-text-1',
            'text-gray-600', 'text-gray-500' => 'text-vp-text-2',
            'text-gray-400', 'text-gray-300', 'text-gray-200' => 'text-vp-text-3',
            'border-gray-100', 'border-gray-200', 'border-gray-300' => 'border-vp-divider',
            'divide-gray-100', 'divide-gray-200', 'divide-gray-300' => 'divide-vp-divider',
            default => self::migrateBrandToken($token) ?? $token,
        };
    }

    private static function migrateBrandToken(string $token): ?string
    {
        // Keep explicit Tailwind palette utilities (bg-blue-200, text-indigo-500, …).
        return null;
    }

    private static function migrateSectionElements(string $html): string
    {
        return preg_replace_callback(
            '/<section\b([^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];

                if (preg_match('/\bclass=(["\'])(.*?)\1/', $attrs, $classMatch) === 1) {
                    $classes = self::ensureSectionClasses($classMatch[2]);
                    $attrs = preg_replace(
                        '/\bclass=(["\'])(.*?)\1/',
                        'class='.$classMatch[1].$classes.$classMatch[1],
                        $attrs,
                        1,
                    ) ?? $attrs;
                } else {
                    $attrs .= ' class="voodbuilder-gjs-section bg-vp-bg"';
                }

                return '<section'.$attrs.'>';
            },
            $html,
        ) ?? $html;
    }

    private static function ensureSectionClasses(string $classes): string
    {
        $tokens = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (! in_array('voodbuilder-gjs-section', $tokens, true)) {
            $tokens[] = 'voodbuilder-gjs-section';
        }

        return implode(' ', $tokens);
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function hasBackgroundClass(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (preg_match('/^bg-(?!opacity|blend|clip|origin|size|position|repeat|none|auto)/', $token) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function migrateInlineStyles(string $html): string
    {
        return preg_replace_callback(
            '/\bstyle=(["\'])(.*?)\1/i',
            static function (array $matches): string {
                return 'style='.$matches[1].self::migrateStyleDeclaration($matches[2]).$matches[1];
            },
            $html,
        ) ?? $html;
    }

    private static function migrateStyleDeclaration(string $style, ?string $classList = null): string
    {
        if ($classList !== null && self::hasExplicitTextColorClass($classList)) {
            $style = self::removeColorFromStyleDeclaration($style);
        }

        $style = self::replaceFixedBackgroundColors($style);

        $style = preg_replace_callback(
            '/\bcolor:\s*([^;]+)/i',
            static function (array $matches): string {
                $value = strtolower(trim($matches[1]));

                if (self::isFixedDarkTextColor($value)) {
                    return 'color: var(--color-vp-text-1)';
                }

                return $matches[0];
            },
            $style,
        ) ?? $style;

        return self::replaceFixedBrandBackgroundColors($style);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function migrateProjectNode(array $node): array
    {
        if (isset($node['component']) && is_string($node['component'])) {
            $node['component'] = self::migrateHtml($node['component']);
        }

        if (isset($node['styles']) && is_string($node['styles'])) {
            $node['styles'] = self::migrateCss($node['styles']);
        }

        if (isset($node['attributes']) && is_array($node['attributes'])) {
            $node['attributes'] = self::migrateComponentAttributes($node['attributes']);
        }

        if (isset($node['classes']) && is_array($node['classes'])) {
            $node['classes'] = self::migrateComponentClasses($node['classes']);
        }

        if (isset($node['style']) && is_array($node['style'])) {
            $node['style'] = self::migrateStyleObject($node['style'], self::classListFromNode($node));
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (in_array($key, ['attributes', 'classes', 'style'], true)) {
                continue;
            }

            if (self::isListArray($value)) {
                $node[$key] = array_map(
                    static fn (mixed $item): mixed => is_array($item) ? self::migrateProjectNode($item) : $item,
                    $value,
                );
            } else {
                $node[$key] = self::migrateProjectNode($value);
            }
        }

        return $node;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private static function migrateComponentAttributes(array $attrs): array
    {
        $classList = null;

        if (isset($attrs['class']) && is_string($attrs['class'])) {
            $classList = self::migrateClassList($attrs['class']);
            $attrs['class'] = $classList;
        }

        if (isset($attrs['style']) && is_string($attrs['style'])) {
            $attrs['style'] = self::migrateStyleDeclaration($attrs['style'], $classList);
        }

        return $attrs;
    }

    /**
     * @param  list<mixed>  $classes
     * @return list<mixed>
     */
    private static function migrateComponentClasses(array $classes): array
    {
        if ($classes === []) {
            return $classes;
        }

        if (is_string($classes[0] ?? null)) {
            $tokens = array_map(
                static fn (string $token): string => self::migrateToken($token),
                array_values(array_filter($classes, 'is_string')),
            );

            $tokens = self::migrateLegacyButtonClassesArray($tokens);
            $tokens = self::normalizeBrandBackgroundClasses($tokens);

            return $tokens;
        }

        return self::migrateComponentClassesArray($classes);
    }

    /**
     * @param  list<mixed>  $classes
     * @return list<mixed>
     */
    private static function migrateComponentClassesArray(array $classes): array
    {
        $names = array_map(
            static function (mixed $item): ?string {
                if (! is_array($item) || ! isset($item['name']) || ! is_string($item['name'])) {
                    return null;
                }

                return self::migrateToken($item['name']);
            },
            $classes,
        );

        $names = array_values(array_filter($names, 'is_string'));
        $names = self::migrateLegacyButtonClassesArray($names);
        $names = self::normalizeBrandBackgroundClasses($names);

        return $names;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private static function migrateStyleObject(array $style, ?string $classList = null): array
    {
        foreach ($style as $property => $value) {
            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (preg_match('/^background(?:-color)?$/i', (string) $property) === 1) {
                $replacement = self::brandBackgroundVariableForColor($normalized);

                if ($replacement !== null) {
                    $style[$property] = $replacement;
                }

                continue;
            }

            if ((string) $property === 'color') {
                if ($classList !== null && self::hasExplicitTextColorClass($classList)) {
                    unset($style[$property]);

                    continue;
                }

                if (self::isFixedDarkTextColor($normalized)) {
                    $style[$property] = 'var(--color-vp-text-1)';

                    continue;
                }

                if (self::isLibraryLightTextColor($normalized)) {
                    unset($style[$property]);
                }
            }
        }

        return $style;
    }

    private static function isLibraryLightTextColor(string $value): bool
    {
        if (in_array($value, ['white', '#fff', '#ffffff'], true)) {
            return true;
        }

        $expanded = self::expandHex($value);

        if ($expanded === null) {
            return false;
        }

        return in_array($expanded, [
            '#f3f4f6', '#e5e7eb', '#d1d5db', '#9ca3af',
            '#a5b4fc', '#818cf8', '#6366f1', '#c7d2fe', '#93c5fd',
        ], true);
    }

    private static function styleRuleTargetsLegacyButton(array $styleRule): bool
    {
        $selectors = $styleRule['selectors'] ?? [];

        if (! is_array($selectors)) {
            return false;
        }

        foreach ($selectors as $selector) {
            if (is_string($selector) && str_contains($selector, 'voodbuilder-gjs-btn-primary')) {
                return true;
            }
        }

        return false;
    }

    private static function brandBackgroundVariableForColor(string $value): ?string
    {
        $hex = self::normalizeColorToHex($value);

        if ($hex === null) {
            return null;
        }

        return self::brandBackgroundVariableForHex($hex);
    }

    private static function normalizeColorToHex(string $value): ?string
    {
        $value = strtolower(trim($value));

        $expanded = self::expandHex($value);

        if ($expanded !== null) {
            return $expanded;
        }

        if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $value, $matches) === 1) {
            return sprintf(
                '#%02x%02x%02x',
                min(255, (int) $matches[1]),
                min(255, (int) $matches[2]),
                min(255, (int) $matches[3]),
            );
        }

        return null;
    }

    private static function stripLegacyButtonCss(string $css): string
    {
        return preg_replace(
            '/\.voodbuilder-gjs-btn-primary(?::hover)?\s*\{[^}]*\}\s*/',
            '',
            $css,
        ) ?? $css;
    }

    /**
     * Pasted component CSS must not pin VitePress theme tokens; inherit from page/canvas theme.
     */
    private static function stripScopedThemeTokenOverrides(string $css): string
    {
        $css = preg_replace(
            '/\s*--color-vp-(?:brand-\d|text-\d|bg(?:-alt|-elv)?|divider|gray-soft)\s*:[^;]+;/i',
            '',
            $css,
        ) ?? $css;

        return self::stripLegacyPaletteColorVariables($css);
    }

    private static function stripLegacyPaletteColorVariables(string $css): string
    {
        return preg_replace(
            '/\s*--color-(?:indigo|yellow|red|purple|violet|pink|blue|green)-[a-z0-9-]+:\s*[^;]+;/i',
            '',
            $css,
        ) ?? $css;
    }

    private static function replaceLegacyPaletteColorReferences(string $css): string
    {
        $css = preg_replace(
            '/background-color:\s*var\(--color-(?:indigo|purple|violet)-\d+\)/i',
            'background-color: var(--color-vp-brand-3)',
            $css,
        ) ?? $css;

        return preg_replace(
            '/outline-color:\s*var\(--color-(?:indigo|purple|violet)-\d+\)/i',
            'outline-color: var(--color-vp-brand-2)',
            $css,
        ) ?? $css;
    }

    private static function replaceFixedBackgroundColors(string $value): string
    {
        $value = preg_replace(
            '/background(?:-color)?:\s*(?:#(?:fff(?:fff)?|fefefe|fafafa|f9fafb|f6f6f7|f4f5f7|f3f4f6|e5e7eb)|rgb\(\s*255\s*,\s*255\s*,\s*255\s*\)|white)\s*(;|$)/i',
            'background-color: var(--color-vp-bg-elv)$1',
            $value,
        ) ?? $value;

        return preg_replace(
            '/background(?:-color)?:\s*(?:#(?:f0f0f0|eeeeee|e5e7eb|edf2f7)|rgb\(\s*244\s*,\s*245\s*,\s*247\s*\))\s*(;|$)/i',
            'background-color: var(--color-vp-bg-alt)$1',
            $value,
        ) ?? $value;
    }

    private static function replaceFixedBrandBackgroundColors(string $value): string
    {
        return preg_replace_callback(
            '/background(?:-color)?:\s*(#[0-9a-f]{3,8})\b/i',
            static function (array $matches): string {
                $replacement = self::brandBackgroundVariableForHex(strtolower($matches[1]));

                if ($replacement === null) {
                    return $matches[0];
                }

                return 'background-color: '.$replacement;
            },
            $value,
        ) ?? $value;
    }

    private static function brandBackgroundVariableForHex(string $hex): ?string
    {
        $expanded = self::expandHex($hex);

        if ($expanded === null) {
            return null;
        }

        if (in_array($expanded, self::BRAND_LIGHT_SURFACE_HEX, true)) {
            return 'var(--color-vp-gray-soft)';
        }

        if (in_array($expanded, self::BRAND_DARK_SURFACE_HEX, true)) {
            return 'var(--color-vp-brand-3)';
        }

        if (in_array($expanded, self::BRAND_MID_SURFACE_HEX, true)) {
            return 'var(--color-vp-brand-1)';
        }

        return null;
    }

    private static function expandHex(string $hex): ?string
    {
        if (preg_match('/^#([0-9a-f]{3})$/', $hex, $matches) === 1) {
            $chars = str_split($matches[1]);

            return '#'.$chars[0].$chars[0].$chars[1].$chars[1].$chars[2].$chars[2];
        }

        if (preg_match('/^#([0-9a-f]{6})$/', $hex) === 1) {
            return $hex;
        }

        return null;
    }

    /**
     * @param  array<mixed>  $array
     */
    private static function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    private static function isFixedDarkTextColor(string $value): bool
    {
        return preg_match('/^#(?:111|1f2937|374151|3c3c43|111827|000(?:000)?)$/', $value) === 1
            || preg_match('/^rgb\(\s*(?:17|31|55|60)\s*,/', $value) === 1;
    }
}
