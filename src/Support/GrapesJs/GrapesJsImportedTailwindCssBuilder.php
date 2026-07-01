<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsImportedTailwindCssBuilder
{
    private const string SCOPE = '.voodbuilder-pasted-component';

    /**
     * @var array<string, int>
     */
    private const BREAKPOINTS = [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ];

    /**
     * @var array<string, string>
     */
    private const COLORS = [
        'gray-900' => '#111827',
        'gray-700' => '#374151',
        'gray-600' => '#4b5563',
        'gray-500' => '#6b7280',
        'gray-50' => '#f9fafb',
        'indigo-600' => '#4f46e5',
        'indigo-500' => '#6366f1',
        'white' => '#ffffff',
    ];

    public static function build(string $html): string
    {
        $classes = GrapesJsImportedTailwindSupport::extractClassNames($html);

        if ($classes === []) {
            return self::baseStyles();
        }

        $rules = [];

        foreach ($classes as $class) {
            $rule = self::ruleForClass($class);

            if ($rule !== null) {
                $rules[$class] = $rule;
            }
        }

        return trim(self::baseStyles()."\n".implode("\n", $rules));
    }

    public static function baseStyles(): string
    {
        return implode("\n", [
            self::SCOPE.' dialog:not([open]) { display: none; }',
            self::SCOPE.' { position: relative; }',
            self::SCOPE.':has(> header.absolute, header.absolute) { min-height: 42rem; }',
            self::SCOPE.' > header.absolute { position: absolute; left: 0; right: 0; top: 0; z-index: 50; }',
        ]);
    }

    protected static function ruleForClass(string $class): ?string
    {
        if (preg_match('/^(?:(sm|md|lg|xl|2xl):)?(?:(hover|focus-visible):)?(.+)$/', $class, $matches) !== 1) {
            return null;
        }

        $breakpoint = ($matches[1] ?? '') !== '' ? $matches[1] : null;
        $variant = ($matches[2] ?? '') !== '' ? $matches[2] : null;
        $utility = $matches[3];
        $declaration = self::declarationForUtility($utility, $variant);

        if ($declaration === null) {
            return null;
        }

        $selector = self::scopedSelector($class, $variant);

        $rule = $selector.' { '.$declaration.' }';

        if ($breakpoint === null) {
            return $rule;
        }

        $minWidth = self::BREAKPOINTS[$breakpoint] ?? null;

        if ($minWidth === null) {
            return null;
        }

        return '@media (min-width: '.$minWidth.'px) { '.$rule.' }';
    }

    protected static function declarationForUtility(string $utility, ?string $variant = null): ?string
    {
        if ($variant === 'hover') {
            if (str_starts_with($utility, 'bg-') && isset(self::COLORS[substr($utility, 3)])) {
                return 'background-color: '.self::COLORS[substr($utility, 3)].';';
            }

            if (str_starts_with($utility, 'ring-')) {
                return self::ringDeclaration($utility);
            }

            return null;
        }

        if ($variant === 'focus-visible') {
            return self::focusOutlineDeclaration($utility);
        }

        return match (true) {
            $utility === 'hidden' => 'display: none;',
            $utility === 'flex' => 'display: flex;',
            $utility === 'inline-flex' => 'display: inline-flex;',
            $utility === 'absolute' => 'position: absolute;',
            $utility === 'relative' => 'position: relative;',
            $utility === 'isolate' => 'isolation: isolate;',
            $utility === 'inset-x-0' => 'left: 0; right: 0;',
            $utility === 'top-0' => 'top: 0;',
            $utility === 'z-50' => 'z-index: 50;',
            $utility === 'items-center' => 'align-items: center;',
            $utility === 'justify-between' => 'justify-content: space-between;',
            $utility === 'justify-center' => 'justify-content: center;',
            $utility === 'justify-end' => 'justify-content: flex-end;',
            $utility === 'text-center' => 'text-align: center;',
            $utility === 'mx-auto' => 'margin-left: auto; margin-right: auto;',
            $utility === 'size-6' => 'width: 1.5rem; height: 1.5rem;',
            $utility === 'text-balance' => 'text-wrap: balance;',
            $utility === 'text-pretty' => 'text-wrap: pretty;',
            $utility === 'shadow-sm' => 'box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);',
            $utility === 'blur-3xl' => 'filter: blur(64px);',
            $utility === 'transform-gpu' => 'transform: translateZ(0);',
            $utility === 'rotate-30' => 'rotate: 30deg;',
            $utility === 'bg-white' => 'background-color: #fff;',
            $utility === 'bg-gradient-to-tr' => 'background-image: linear-gradient(to top right, var(--vb-tw-gradient-stops, #ff80b5, #9089fc));',
            str_starts_with($utility, 'bg-') && isset(self::COLORS[substr($utility, 3)]) => 'background-color: '.self::COLORS[substr($utility, 3)].';',
            str_starts_with($utility, 'text-') && isset(self::COLORS[substr($utility, 5)]) => 'color: '.self::COLORS[substr($utility, 5)].';',
            str_starts_with($utility, 'from-[#') && str_ends_with($utility, ']') => self::gradientFromDeclaration($utility),
            str_starts_with($utility, 'to-[#') && str_ends_with($utility, ']') => self::gradientToDeclaration($utility),
            str_starts_with($utility, 'ring-') => self::ringDeclaration($utility),
            str_starts_with($utility, 'aspect-') => self::aspectDeclaration($utility),
            str_starts_with($utility, 'w-') => self::widthDeclaration($utility),
            str_starts_with($utility, 'left-[') => self::positionDeclaration('left', $utility),
            str_starts_with($utility, 'top-[') => self::positionDeclaration('top', $utility),
            default => null,
        };
    }

    protected static function gradientFromDeclaration(string $utility): string
    {
        $color = self::extractBracketValue($utility);

        return '--vb-tw-gradient-from: '.$color.'; --vb-tw-gradient-stops: var(--vb-tw-gradient-from), var(--vb-tw-gradient-to, transparent);';
    }

    protected static function gradientToDeclaration(string $utility): string
    {
        $color = self::extractBracketValue($utility);

        return '--vb-tw-gradient-to: '.$color.';';
    }

    protected static function ringDeclaration(string $utility): ?string
    {
        if (preg_match('/^ring-(\d+)$/', $utility, $matches) === 1) {
            return 'box-shadow: 0 0 0 '.$matches[1].'px rgb(17 24 39 / 0.1);';
        }

        if (preg_match('/^ring-gray-900\/(\d+)$/', $utility, $matches) === 1) {
            $alpha = ((int) $matches[1]) / 100;

            return 'box-shadow: 0 0 0 1px rgb(17 24 39 / '.$alpha.');';
        }

        return null;
    }

    protected static function focusOutlineDeclaration(string $utility): ?string
    {
        return match ($utility) {
            'outline-2' => 'outline-width: 2px;',
            'outline-offset-2' => 'outline-offset: 2px;',
            'outline-indigo-600' => 'outline-color: #4f46e5;',
            default => null,
        };
    }

    protected static function aspectDeclaration(string $utility): ?string
    {
        if (preg_match('/^aspect-(\d+)\/(\d+)$/', $utility, $matches) !== 1) {
            return null;
        }

        return 'aspect-ratio: '.$matches[1].' / '.$matches[2].';';
    }

    protected static function widthDeclaration(string $utility): ?string
    {
        if (preg_match('/^w-([\d.]+)$/', $utility, $matches) !== 1) {
            return null;
        }

        return 'width: '.self::spacingToRem((float) $matches[1]).';';
    }

    protected static function positionDeclaration(string $property, string $utility): ?string
    {
        $value = self::extractBracketValue($utility);

        return $value !== null ? $property.': '.$value.';' : null;
    }

    protected static function spacingToRem(float $value): string
    {
        return rtrim(rtrim(number_format($value * 0.25, 4, '.', ''), '0'), '.').'rem';
    }

    protected static function extractBracketValue(string $utility): ?string
    {
        if (preg_match('/\[([^\]]+)\]/', $utility, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    protected static function scopedSelector(string $class, ?string $variant = null): string
    {
        $escaped = self::escapeClassSelector($class);

        if ($variant === 'hover') {
            return self::SCOPE.' .'.$escaped.':hover';
        }

        if ($variant === 'focus-visible') {
            return self::SCOPE.' .'.$escaped.':focus-visible';
        }

        return self::SCOPE.' .'.$escaped;
    }

    protected static function escapeClassSelector(string $class): string
    {
        return preg_replace('/([^a-zA-Z0-9_-])/', '\\\\$1', $class) ?? $class;
    }
}
