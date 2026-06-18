<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class LandingBlockSupport
{
    /** @return array<string, string> */
    public static function backgroundToneOptions(): array
    {
        return [
            'brand' => __('vpress::landing.tones.brand'),
            'dark' => __('vpress::landing.tones.dark'),
            'light' => __('vpress::landing.tones.light'),
            'custom' => __('vpress::landing.tones.custom'),
        ];
    }

    /** @return array<string, string> */
    public static function backgroundStyleOptions(): array
    {
        return [
            'solid' => __('vpress::landing.background_styles.solid'),
            'image' => __('vpress::landing.background_styles.image'),
        ];
    }

    /** @return array<string, string> */
    public static function textAlignOptions(): array
    {
        return [
            'center' => __('vpress::landing.align.center'),
            'left' => __('vpress::landing.align.left'),
        ];
    }

    /** @return array<string, string> */
    public static function imagePositionOptions(): array
    {
        return [
            'left' => __('vpress::landing.image_position.left'),
            'right' => __('vpress::landing.image_position.right'),
        ];
    }

    /** @return array<string, string> */
    public static function buttonStyleOptions(): array
    {
        return [
            'solid' => __('vpress::landing.button_styles.solid'),
            'outline' => __('vpress::landing.button_styles.outline'),
            'ghost' => __('vpress::landing.button_styles.ghost'),
        ];
    }

    /** @return array<string, string> */
    public static function sectionWidthOptions(): array
    {
        return [
            'bleed' => __('vpress::landing.section_width.bleed'),
            'full' => __('vpress::landing.section_width.full'),
            'contained' => __('vpress::landing.section_width.contained'),
            'narrow' => __('vpress::landing.section_width.narrow'),
        ];
    }

    /** @return array<string, string> */
    public static function sectionPaddingOptions(): array
    {
        return [
            'default' => __('vpress::landing.section_padding.default'),
            'large' => __('vpress::landing.section_padding.large'),
            'none' => __('vpress::landing.section_padding.none'),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{class: string, style: string, overlay: bool}
     */
    public static function sectionAppearance(array $config, string $defaultTone = 'brand'): array
    {
        $style = (string) ($config['background_style'] ?? 'solid');
        $tone = (string) ($config['background_tone'] ?? $defaultTone);
        $imageUrl = LandingBlockContent::backgroundImageUrl($config);

        if ($style === 'image' && $imageUrl !== null) {
            $opacity = max(0, min(100, (int) ($config['overlay_opacity'] ?? 55))) / 100;

            return [
                'class' => 'relative overflow-hidden text-white',
                'style' => "background-image: linear-gradient(rgba(15,23,42,{$opacity}), rgba(15,23,42,{$opacity})), url('".e($imageUrl)."'); background-size: cover; background-position: center;",
                'overlay' => false,
            ];
        }

        if ($tone === 'custom') {
            $color = self::normalizeHexColor($config['background_color'] ?? null) ?? '#111827';
            $onDark = self::isDarkColor($color);

            return [
                'class' => $onDark ? 'text-white' : 'text-vp-text-1',
                'style' => 'background-color: '.e($color).';',
                'overlay' => false,
            ];
        }

        return [
            'class' => match ($tone) {
                'dark' => 'bg-vp-text-1 text-white',
                'light' => 'bg-vp-bg-alt text-vp-text-1 border border-vp-divider',
                default => 'bg-vp-brand-1 text-white',
            },
            'style' => '',
            'overlay' => false,
        ];
    }

    public static function sectionCornerClass(array $config, array $defaults = []): string
    {
        $width = (string) ($config['section_width'] ?? $defaults['section_width'] ?? 'contained');

        return $width === 'bleed' ? '' : 'rounded-2xl';
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     */
    public static function sectionShellClass(array $config, array $defaults = []): string
    {
        $width = (string) ($config['section_width'] ?? $defaults['section_width'] ?? 'contained');
        $padding = (string) ($config['section_padding'] ?? $defaults['section_padding'] ?? 'default');

        return trim(implode(' ', [
            'vp-landing-section',
            'vp-landing-section--'.$width,
            'vp-landing-section--padding-'.$padding,
        ]));
    }

    public static function innerWidthClass(array $config, string $default = 'contained'): string
    {
        $width = (string) ($config['content_width'] ?? $default);

        return match ($width) {
            'narrow' => 'max-w-3xl',
            'wide' => 'max-w-6xl',
            default => 'max-w-5xl',
        };
    }

    public static function textAlignClass(string $align): string
    {
        return $align === 'left' ? 'text-left items-start' : 'text-center items-center';
    }

    public static function primaryButtonClass(string $style, bool $onDarkBackground = true): string
    {
        return match ($style) {
            'outline' => $onDarkBackground
                ? 'border-2 border-white/90 bg-transparent text-white hover:bg-white/10'
                : 'border-2 border-vp-brand-1 bg-transparent text-vp-brand-1 hover:bg-vp-brand-1/10',
            'ghost' => $onDarkBackground
                ? 'bg-white/15 text-white hover:bg-white/25'
                : 'bg-vp-gray-soft text-vp-text-1 hover:bg-vp-border',
            default => $onDarkBackground
                ? 'bg-white text-vp-brand-1 hover:bg-white/90'
                : 'bg-vp-brand-1 text-white hover:opacity-90',
        };
    }

    public static function secondaryButtonClass(bool $onDarkBackground = true): string
    {
        return $onDarkBackground
            ? 'border border-white/40 bg-transparent text-white hover:bg-white/10'
            : 'border border-vp-border bg-transparent text-vp-text-1 hover:bg-vp-gray-soft';
    }

    public static function onDarkBackground(array $config): bool
    {
        if (($config['background_style'] ?? 'solid') === 'image') {
            return true;
        }

        $tone = (string) ($config['background_tone'] ?? 'brand');

        if ($tone === 'custom') {
            $color = self::normalizeHexColor($config['background_color'] ?? null) ?? '#111827';

            return self::isDarkColor($color);
        }

        return $tone !== 'light';
    }

    public static function isDarkColor(string $color): bool
    {
        $hex = self::normalizeHexColor($color);

        if ($hex === null) {
            return true;
        }

        $red = hexdec(substr($hex, 1, 2));
        $green = hexdec(substr($hex, 3, 2));
        $blue = hexdec(substr($hex, 5, 2));
        $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;

        return $luminance < 0.55;
    }

    public static function normalizeHexColor(mixed $color): ?string
    {
        if (! is_string($color) || $color === '') {
            return null;
        }

        $color = trim($color);

        if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $matches)) {
            return null;
        }

        if (strlen($matches[1]) === 3) {
            $color = '#'.implode('', array_map(
                fn (string $char): string => $char.$char,
                str_split($matches[1]),
            ));
        }

        return strtoupper($color);
    }
}
