<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Central accessors for landing block config: media paths, section layout, appearance.
 */
final class LandingBlockContent
{
    public const FIELD_BACKGROUND_IMAGE = 'background_image';

    public const FIELD_IMAGE = 'image';

    public const FIELD_LOGO_IMAGE = 'image';

    /**
     * @param  array<string, mixed>  $config
     */
    public static function mediaUrl(array $config, string $field, ?string $legacyField = null): ?string
    {
        $url = LandingBlockMedia::publicUrl($config[$field] ?? null);

        if ($url !== null) {
            return $url;
        }

        $legacy = $legacyField ?? "{$field}_url";
        $value = $config[$legacy] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return LandingBlockMedia::publicUrl($value);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function backgroundImageUrl(array $config): ?string
    {
        return self::mediaUrl($config, self::FIELD_BACKGROUND_IMAGE, 'background_image_url');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function imageUrl(array $config, string $field = self::FIELD_IMAGE): ?string
    {
        return self::mediaUrl($config, $field);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     * @return array{
     *     shell: string,
     *     corners: string,
     *     appearance: array{class: string, style: string, overlay: bool},
     *     onDark: bool,
     *     align: string
     * }
     */
    public static function section(array $config, array $defaults = [], string $defaultTone = 'brand'): array
    {
        return [
            'shell' => LandingBlockSupport::sectionShellClass($config, $defaults),
            'corners' => LandingBlockSupport::sectionCornerClass($config, $defaults),
            'appearance' => LandingBlockSupport::sectionAppearance($config, $defaultTone),
            'onDark' => LandingBlockSupport::onDarkBackground($config),
            'align' => LandingBlockSupport::textAlignClass((string) ($config['text_align'] ?? 'center')),
        ];
    }
}
