<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Mobile-first viewports for responsive typography CSS variables.
 *
 * Same mapping as the Style panel strip: Mobile = base, Tablet = md:, Desktop = lg:.
 * Published pages switch values with @media; the editor canvas switches on the
 * Grapes device attribute because frame width and breakpoints can disagree.
 */
final class TypographyBreakpoints
{
    /** @var list<string> */
    public const KEYS = ['base', 'md', 'lg'];

    /** @var array<string, string> breakpoint => type-scale row size property */
    public const SIZE_PROPS = [
        'base' => 'size',
        'md' => 'sizeMd',
        'lg' => 'sizeLg',
    ];

    /** @var array<string, string> Tailwind v4 default breakpoints */
    public const MIN_WIDTHS = [
        'md' => '48rem',
        'lg' => '64rem',
    ];

    /** @var array<string, string> breakpoint => Grapes device id */
    public const EDITOR_DEVICES = [
        'base' => 'mobilePortrait',
        'md' => 'tablet',
        'lg' => 'desktop',
    ];

    /**
     * @param  array<string, array<string, string>>  $variablesByBreakpoint  breakpoint => (var => value); md/lg hold only changes
     */
    public static function css(array $variablesByBreakpoint, bool $editorCanvas = false): string
    {
        $blocks = [];
        $base = $variablesByBreakpoint['base'] ?? [];

        if ($base !== []) {
            $blocks[] = self::rule(':root, html', $base);
        }

        foreach (self::MIN_WIDTHS as $breakpoint => $minWidth) {
            $changes = $variablesByBreakpoint[$breakpoint] ?? [];

            if ($changes !== []) {
                $blocks[] = "@media (min-width: {$minWidth}) {\n" . self::rule(':root, html', $changes) . "\n}";
            }
        }

        if ($editorCanvas) {
            $cumulative = [];

            foreach (self::KEYS as $breakpoint) {
                $cumulative = [...$cumulative, ...($variablesByBreakpoint[$breakpoint] ?? [])];

                if ($cumulative !== []) {
                    $device = self::EDITOR_DEVICES[$breakpoint];
                    $blocks[] = self::rule("html[data-voodbuilder-editor-device='{$device}']", $cumulative);
                }
            }
        }

        return implode("\n", $blocks);
    }

    /**
     * First non-null size at or below the breakpoint (mobile-first cascade).
     *
     * @param  array<string, mixed>  $row
     */
    public static function cascadedSize(array $row, string $breakpoint): ?string
    {
        $index = array_search($breakpoint, self::KEYS, true);

        if ($index === false) {
            return null;
        }

        for ($i = $index; $i >= 0; $i--) {
            $value = $row[self::SIZE_PROPS[self::KEYS[$i]]] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $declarations
     */
    private static function rule(string $selector, array $declarations): string
    {
        $lines = [];

        foreach ($declarations as $name => $value) {
            $lines[] = "    {$name}: {$value};";
        }

        return $selector . " {\n" . implode("\n", $lines) . "\n}";
    }
}
