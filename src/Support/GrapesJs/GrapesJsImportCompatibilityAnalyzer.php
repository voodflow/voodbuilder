<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

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
     * @var list<string>
     */
    private const IGNORED_CLASSES = [
        'dark',
        'voodbuilder-pasted-component',
        'voodbuilder-gjs-section',
        'voodbuilder-gjs-container',
        'voodbuilder-gjs-bound',
        'voodbuilder-gjs-component-instance',
        'body-font',
        'title-font',
        'group',
        'peer',
    ];

    /**
     * @return array{
     *     status: 'excellent'|'good'|'partial'|'poor',
     *     totals: array{classes: int, ready: int, adapted: int, review: int},
     *     adaptations: list<array{from: string, to: string}>,
     *     review: list<string>,
     *     ready_sample: list<string>,
     * }
     */
    public static function analyze(string $rawHtml, string $normalizedHtml, string $compiledCss): array
    {
        $rawClasses = GrapesJsImportedTailwindSupport::extractClassNames($rawHtml);
        $normalizedClasses = GrapesJsImportedTailwindSupport::extractClassNames($normalizedHtml);
        $adaptations = self::detectAdaptations($rawHtml, $rawClasses, $normalizedClasses);
        $adaptedFrom = array_column($adaptations, 'from');

        $ready = [];
        $review = [];

        foreach ($normalizedClasses as $class) {
            if (in_array($class, self::IGNORED_CLASSES, true) || in_array($class, $adaptedFrom, true)) {
                continue;
            }

            if (self::cssIncludesUtility($compiledCss, $class)) {
                $ready[] = $class;

                continue;
            }

            $review[] = $class;
        }

        $ready = array_values(array_unique($ready));
        $review = array_values(array_unique($review));
        sort($ready);
        sort($review);

        $totals = [
            'classes' => count($normalizedClasses),
            'ready' => count($ready),
            'adapted' => count($adaptations),
            'review' => count($review),
        ];

        return [
            'status' => self::resolveStatus($totals),
            'totals' => $totals,
            'adaptations' => $adaptations,
            'review' => $review,
            'ready_sample' => array_slice($ready, 0, 8),
        ];
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
     * @param  array{classes: int, ready: int, adapted: int, review: int}  $totals
     */
    private static function resolveStatus(array $totals): string
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

        if ($ratio >= 0.9) {
            return 'good';
        }

        if ($ratio >= 0.7) {
            return 'partial';
        }

        return 'poor';
    }
}
