<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Reading typography for doc/tutorial surfaces (chrome layout settings).
 */
final class ChromeLayoutReadingTypography
{
    public const SIZE_SM = 'sm';

    public const SIZE_MD = 'md';

    public const SIZE_LG = 'lg';

    public const SIZE_XL = 'xl';

    public const DEFAULT_SIZE = self::SIZE_LG;

    /** Default Inter Variable (bundled in theme.css / fonts.css). */
    public const DEFAULT_FONT = 'inter';

    /**
     * @return array<string, string>
     */
    public static function sizeOptions(): array
    {
        return [
            self::SIZE_SM => __('voodbuilder::chrome_layouts.fields.reading_font_size_sm'),
            self::SIZE_MD => __('voodbuilder::chrome_layouts.fields.reading_font_size_md'),
            self::SIZE_LG => __('voodbuilder::chrome_layouts.fields.reading_font_size_lg'),
            self::SIZE_XL => __('voodbuilder::chrome_layouts.fields.reading_font_size_xl'),
        ];
    }

    /**
     * @return array<string, string> font id => family label
     */
    public static function fontOptions(): array
    {
        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        $options = [
            self::DEFAULT_FONT => 'Inter Variable (default)',
        ];

        foreach ($catalog->all() as $font) {
            if ($font->id === self::DEFAULT_FONT || str_starts_with($font->id, 'inter')) {
                continue;
            }

            $options[$font->id] = $font->family;
        }

        return $options;
    }

    public static function normalizeSize(?string $size): string
    {
        $size = strtolower(trim((string) $size));

        return array_key_exists($size, self::sizeOptions())
            ? $size
            : self::DEFAULT_SIZE;
    }

    public static function normalizeFont(?string $fontId): string
    {
        $fontId = trim((string) $fontId);

        if ($fontId === '' || $fontId === self::DEFAULT_FONT) {
            return self::DEFAULT_FONT;
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();

        return $catalog->get($fontId) !== null
            ? $fontId
            : self::DEFAULT_FONT;
    }

    /**
     * @return array{font: string, size: string, stack: string, cssSize: string, stylesheetUrls: list<string>}
     */
    public static function resolve(?ChromeLayout $layout): array
    {
        $fontId = self::normalizeFont($layout?->reading_font);
        $size = self::normalizeSize($layout?->reading_font_size);

        return [
            'font' => $fontId,
            'size' => $size,
            'stack' => self::stackFor($fontId),
            'cssSize' => self::cssSizeFor($size),
            'stylesheetUrls' => $fontId === self::DEFAULT_FONT
                ? []
                : FontStylesheets::urlsFor([$fontId]),
        ];
    }

    public static function stackFor(string $fontId): string
    {
        if ($fontId === self::DEFAULT_FONT) {
            return "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";
        }

        $catalog = Voodbuilder::fonts();
        $catalog->bootCore();
        $font = $catalog->get($fontId);

        return $font instanceof FontDefinition
            ? $font->stack
            : "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";
    }

    public static function cssSizeFor(string $size): string
    {
        return match (self::normalizeSize($size)) {
            self::SIZE_SM => '15px',
            self::SIZE_MD => '16px',
            self::SIZE_XL => '18px',
            default => '17px',
        };
    }
}
