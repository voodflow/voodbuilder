<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

final class GrapesJsImportCompatibilityAnalyzer
{
    /**
     * @var array<string, string>
     */
    private const IMPORT_ADAPTATIONS = [
        'bg-linear-to-t' => 'bg-gradient-to-t',
        'bg-linear-to-tr' => 'bg-gradient-to-tr',
        'bg-linear-to-r' => 'bg-gradient-to-r',
        'bg-linear-to-br' => 'bg-gradient-to-br',
        'bg-linear-to-b' => 'bg-gradient-to-b',
        'bg-linear-to-bl' => 'bg-gradient-to-bl',
        'bg-linear-to-l' => 'bg-gradient-to-l',
        'bg-linear-to-tl' => 'bg-gradient-to-tl',
        'shadow-xs' => 'shadow-sm',
        'text-sm/6' => 'text-sm leading-6',
        'text-base/7' => 'text-base leading-7',
        'text-xl/8' => 'text-xl leading-8',
        'sm:text-xl/8' => 'sm:text-xl sm:leading-8',
    ];

    /**
     * Exact class tokens excluded from Tailwind JIT review (not package-owned prefixes).
     *
     * @var list<string>
     */
    private const IGNORED_CLASSES = [
        'dark',
        'relative',
        'body-font',
        'title-font',
        'group',
        'peer',
    ];

    /**
     * Package-owned class prefixes — never flagged as "to review".
     *
     * @var list<string>
     */
    private const INTERNAL_CLASS_PREFIXES = [
        'voodbuilder-',
        'vb-',
    ];

    /**
     * Optional Tailwind variant prefixes (responsive, state, container, …).
     */
    private const VARIANT_PREFIX = '(?:(?:sm|md|lg|xl|2xl|max-sm|max-md|max-lg|max-xl|max-2xl|hover|focus|focus-visible|focus-within|active|visited|disabled|group-hover|group-focus|peer-hover|peer-focus|dark|motion-safe|motion-reduce|portrait|landscape|vp|max-vp):)*';

    /**
     * @var list<string>
     */
    private const THEME_UTILITY_PATTERNS = [
        '/^(?:max-)?vp:/',
        '/^(?:hover:|focus:|focus-visible:|active:|group-hover:|group-focus:|peer-focus:)?(?:bg|text|border|ring|outline|from|to|via)-vp-/',
        '/^(?:hover:|focus:|focus-visible:|active:|group-hover:)?(?:bg|text|border)-(?:primary|foreground|layer|surface|plain|inverse|muted)(?:-|$)/',
        '/\[(?:var\(--(?:vp-|spacing-vp-|width-vp-|color-vp-)[^]]*)\]/',
        '/^(?:pointer-events-(?:auto|none)|shrink-0|grow|antialiased|sr-only|not-sr-only)$/',
        '/^gap-/',
        '/^(?:items|justify|self|place)-/',
    ];

    /**
     * Standard Tailwind utilities usually provided by the canvas/page theme even when
     * missing from component-scoped JIT CSS (avoids false "Low compatibility" on native saves).
     *
     * @var list<string>
     */
    private const STANDARD_CANVAS_UTILITY_PATTERNS = [
        '/^(?:flex|inline-flex|grid|inline-grid|block|inline-block|inline|hidden|contents|flow-root|table(?:-\w+)?)$/',
        '/^(?:flex|grid|place|items|justify|content|self)-/',
        '/^(?:grow|shrink)(?:-\d+|-0)?$/',
        '/^basis-/',
        '/^flex-(?:1|auto|initial|none|row|row-reverse|col|col-reverse|wrap|wrap-reverse|nowrap)$/',
        '/^(?:col|row)-(?:auto|span-\d+|start-\d+|end-\d+)$/',
        '/^grid-(?:cols|rows)-(?:\d+|none|subgrid)$/',
        '/^-?(?:m|p|mt|mr|mb|ml|mx|my|ms|me|pt|pr|pb|pl|px|py|ps|pe|gap|gap-x|gap-y|space-x|space-y)-/',
        '/^(?:w|h|min-w|min-h|max-w|max-h|size)-/',
        '/^(?:font|text|leading|tracking|indent|align|whitespace|break|truncate|line-clamp)/',
        '/^(?:uppercase|lowercase|capitalize|normal-case|italic|not-italic|underline|overline|line-through|no-underline)$/',
        '/^(?:border|rounded|shadow|ring|outline|divide)/',
        '/^(?:bg|from|via|to)-/',
        '/^(?:static|fixed|absolute|relative|sticky)$/',
        '/^(?:inset|top|right|bottom|left|z|start|end)-/',
        '/^(?:opacity|blur|brightness|contrast|grayscale|hue-rotate|invert|saturate|sepia|drop-shadow)/',
        '/^(?:transition|duration|ease|delay|animate|scale|rotate|translate|skew|origin|transform)/',
        '/^(?:overflow|object|cursor|select|pointer-events|resize|scroll|snap|touch|overscroll)/',
        '/^(?:appearance-none|sr-only|not-sr-only|antialiased|subpixel-antialiased|visible|invisible|collapse)$/',
        '/^[\w.-]+-\[[^\]]+\]$/',
    ];

    private static ?string $themeReferenceCss = null;

    /**
     * @return array{
     *     status: 'excellent'|'good'|'partial'|'poor',
     *     totals: array{classes: int, ready: int, adapted: int, review: int, theme_ready: int},
     *     adaptations: list<array{from: string, to: string}>,
     *     review: list<string>,
     *     ready_sample: list<string>,
     *     context: array{chrome_block: bool, block_id: string|null},
     * }
     */
    public static function analyze(string $rawHtml, string $normalizedHtml, string $compiledCss, ?string $themeCss = null): array
    {
        $rawClasses = GrapesJsImportedTailwindSupport::extractClassNames($rawHtml);
        $normalizedClasses = GrapesJsImportedTailwindSupport::extractClassNames($normalizedHtml);
        $adaptations = self::detectAdaptations($rawHtml, $rawClasses, $normalizedClasses);
        $adaptedFrom = array_column($adaptations, 'from');
        $themeReferenceCss = $themeCss ?? self::themeReferenceCss();
        $context = self::detectChromeContext($normalizedHtml);

        $ready = [];
        $themeReady = [];
        $review = [];

        foreach ($normalizedClasses as $class) {
            if (
                in_array($class, self::IGNORED_CLASSES, true)
                || in_array($class, $adaptedFrom, true)
                || self::isInternalPackageClass($class)
            ) {
                continue;
            }

            if (self::cssIncludesUtility($compiledCss, $class)) {
                $ready[] = $class;

                continue;
            }

            if (self::isThemeCoveredUtility($class, $themeReferenceCss)) {
                $ready[] = $class;
                $themeReady[] = $class;

                continue;
            }

            $review[] = $class;
        }

        $ready = array_values(array_unique($ready));
        $themeReady = array_values(array_unique($themeReady));
        $review = array_values(array_unique($review));
        sort($ready);
        sort($themeReady);
        sort($review);

        $totals = [
            'classes' => count($normalizedClasses),
            'ready' => count($ready),
            'adapted' => count($adaptations),
            'review' => count($review),
            'theme_ready' => count($themeReady),
        ];

        return [
            'status' => self::resolveStatus($totals, $context),
            'totals' => $totals,
            'adaptations' => $adaptations,
            'review' => $review,
            'ready_sample' => array_slice($ready, 0, 8),
            'context' => $context,
        ];
    }

    public static function themeReferenceCss(): string
    {
        if (self::$themeReferenceCss !== null) {
            return self::$themeReferenceCss;
        }

        $paths = [
            VoodbuilderPaths::themeCssAbsolutePath(),
            VoodbuilderPaths::packagePath().'/resources/css/grapesjs/section-utilities.css',
            VoodbuilderPaths::packagePath().'/resources/css/mobile-nav.css',
        ];

        $chunks = [];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);

            if (is_string($contents) && $contents !== '') {
                $chunks[] = $contents;
            }
        }

        self::$themeReferenceCss = implode("\n", $chunks);

        return self::$themeReferenceCss;
    }

    /**
     * @return array{chrome_block: bool, block_id: string|null}
     */
    public static function detectChromeContext(string $html): array
    {
        if (preg_match('/\bdata-voodbuilder-block=(["\'])([^"\']+)\1/i', $html, $matches) !== 1) {
            return [
                'chrome_block' => false,
                'block_id' => null,
            ];
        }

        $blockId = trim($matches[2]);
        $chromeBlock = str_starts_with($blockId, 'site_nav_') || str_starts_with($blockId, 'site_footer_');

        return [
            'chrome_block' => $chromeBlock,
            'block_id' => $blockId !== '' ? $blockId : null,
        ];
    }

    public static function isThemeCoveredUtility(string $class, string $themeCss): bool
    {
        if ($class === '') {
            return false;
        }

        if ($themeCss !== '' && self::cssIncludesUtility($themeCss, $class)) {
            return true;
        }

        foreach (self::THEME_UTILITY_PATTERNS as $pattern) {
            if (preg_match($pattern, $class) === 1) {
                return true;
            }
        }

        return self::isStandardCanvasUtility($class);
    }

    /**
     * Common Tailwind utilities that the canvas theme / page CSS already provide.
     */
    public static function isStandardCanvasUtility(string $class): bool
    {
        if ($class === '') {
            return false;
        }

        $base = preg_replace('/^'.self::VARIANT_PREFIX.'/', '', $class) ?? $class;

        if ($base === '') {
            return false;
        }

        foreach (self::STANDARD_CANVAS_UTILITY_PATTERNS as $pattern) {
            if (preg_match($pattern, $base) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Voodbuilder / GrapesJS chrome classes (vb-*, voodbuilder-*) are styled outside block JIT.
     */
    public static function isInternalPackageClass(string $class): bool
    {
        if ($class === '') {
            return false;
        }

        $candidates = [$class];

        if (str_contains($class, ':')) {
            $candidates[] = substr($class, (int) strrpos($class, ':') + 1);
        }

        foreach ($candidates as $candidate) {
            foreach (self::INTERNAL_CLASS_PREFIXES as $prefix) {
                if (str_starts_with($candidate, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $rawClasses
     * @param  list<string>  $normalizedClasses
     * @return list<array{from: string, to: string}>
     */
    private static function detectAdaptations(string $rawHtml, array $rawClasses, array $normalizedClasses): array
    {
        $adaptations = [];
        $rawSet = array_fill_keys($rawClasses, true);
        $normalizedSet = array_fill_keys($normalizedClasses, true);

        foreach (self::IMPORT_ADAPTATIONS as $from => $to) {
            if (! self::rawHtmlUsesToken($rawHtml, $rawClasses, $rawSet, $from)) {
                continue;
            }

            $targets = preg_split('/\s+/', trim($to)) ?: [];

            if ($targets !== [] && ! array_any($targets, static fn (string $target): bool => isset($normalizedSet[$target]))) {
                continue;
            }

            $adaptations[] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        foreach ($rawClasses as $class) {
            if (! self::isLegacyBrandUtility($class)) {
                continue;
            }

            $migrated = VoodbuilderThemeTokenMigrator::migrateClassList($class);
            $migratedTokens = preg_split('/\s+/', trim($migrated)) ?: [];

            if ($migrated === $class || $migratedTokens === []) {
                continue;
            }

            $adaptations[] = [
                'from' => $class,
                'to' => $migrated,
            ];
        }

        $unique = [];

        foreach ($adaptations as $adaptation) {
            $key = $adaptation['from'].'|'.$adaptation['to'];
            $unique[$key] = $adaptation;
        }

        return array_values($unique);
    }

    /**
     * @param  array<string, bool>  $rawSet
     */
    private static function rawHtmlUsesToken(string $rawHtml, array $rawClasses, array $rawSet, string $token): bool
    {
        if (isset($rawSet[$token])) {
            return true;
        }

        return preg_match('/\b'.preg_quote($token, '/').'\b/', $rawHtml) === 1;
    }

    private static function isLegacyBrandUtility(string $class): bool
    {
        return (bool) preg_match(
            '/\b(?:hover:|focus:|focus-visible:|active:|group-hover:)?(?:bg|text|border|ring|outline|from|to|via)-(?:indigo|yellow|red|purple|violet|pink|blue|green)-\d+/i',
            $class,
        );
    }

    public static function cssIncludesUtility(string $css, string $class): bool
    {
        if ($css === '' || $class === '') {
            return false;
        }

        $escaped = self::escapeClassSelector($class);
        $needles = [
            '.'.$escaped.'{',
            '.'.$escaped.' ',
            '.'.$escaped.',',
            '.'.$escaped.':',
            '.'.$escaped.'\n',
            '.'.$escaped.'\r',
            '.'.$escaped.'\t',
        ];

        foreach ($needles as $needle) {
            if (str_contains($css, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function escapeClassSelector(string $class): string
    {
        return preg_replace('/([^a-zA-Z0-9_-])/', '\\\\$1', $class) ?? $class;
    }

    /**
     * @param  array{classes: int, ready: int, adapted: int, review: int, theme_ready: int}  $totals
     * @param  array{chrome_block: bool, block_id: string|null}  $context
     */
    private static function resolveStatus(array $totals, array $context): string
    {
        if ($totals['classes'] === 0) {
            return 'poor';
        }

        if ($totals['review'] === 0) {
            return 'excellent';
        }

        if ($totals['review'] <= 2 && $totals['review'] <= (int) floor($totals['classes'] * 0.05)) {
            return 'good';
        }

        $supported = $totals['ready'] + $totals['adapted'];
        $ratio = $supported / max(1, $totals['classes']);

        $status = match (true) {
            $ratio >= 0.9 => 'good',
            $ratio >= 0.7 => 'partial',
            default => 'poor',
        };

        if ($context['chrome_block'] && in_array($status, ['poor', 'partial'], true)) {
            $themeBackedRatio = ($totals['theme_ready'] + $totals['adapted']) / max(1, $totals['classes']);

            if ($themeBackedRatio >= 0.6 || $totals['theme_ready'] >= 8) {
                return 'good';
            }
        }

        // Native canvas sections often rely on page theme CSS; treat heavy theme coverage as healthy.
        if (in_array($status, ['poor', 'partial'], true)) {
            $themeBackedRatio = ($totals['theme_ready'] + $totals['adapted']) / max(1, $totals['classes']);

            if ($themeBackedRatio >= 0.75 || ($totals['theme_ready'] >= 12 && $totals['review'] <= 4)) {
                return 'good';
            }

            if ($themeBackedRatio >= 0.5 && $totals['review'] <= 8) {
                return 'partial';
            }
        }

        return $status;
    }
}
