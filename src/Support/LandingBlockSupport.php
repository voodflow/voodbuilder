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
        $imageUrl = filled($config['background_image_url'] ?? null)
            ? (string) $config['background_image_url']
            : null;

        if ($style === 'image' && $imageUrl !== null) {
            $opacity = max(0, min(100, (int) ($config['overlay_opacity'] ?? 55))) / 100;

            return [
                'class' => 'relative overflow-hidden text-white',
                'style' => "background-image: linear-gradient(rgba(15,23,42,{$opacity}), rgba(15,23,42,{$opacity})), url('".e($imageUrl)."'); background-size: cover; background-position: center;",
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
        return ($config['background_style'] ?? 'solid') === 'image'
            || ($config['background_tone'] ?? 'brand') !== 'light';
    }
}
