<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Voodflow\Voodbuilder\Models\CustomFont;

/**
 * Generate the @font-face stylesheet for an author-installed font.
 *
 * The CSS is written next to the uploaded files on the public disk, so published
 * pages only need a plain <link rel="stylesheet"> like any Fontsource bundle.
 */
final class CustomFontStylesheet
{
    /**
     * @var array<string, string> lower-case extension/format => CSS format() keyword
     */
    private const FORMATS = [
        'woff2' => 'woff2',
        'woff' => 'woff',
        'ttf' => 'truetype',
        'otf' => 'opentype',
        'truetype' => 'truetype',
        'opentype' => 'opentype',
    ];

    /**
     * @var array<string, int>
     */
    private const FORMAT_PRIORITY = [
        'woff2' => 0,
        'woff' => 1,
        'truetype' => 2,
        'opentype' => 3,
    ];

    public static function sync(CustomFont $font): ?string
    {
        $disk = Storage::disk($font->diskName());
        $css = self::css($font);

        if ($css === '') {
            $disk->delete($font->stylesheetPath());

            return null;
        }

        $disk->put($font->stylesheetPath(), $css);

        return self::urlFor($font, false);
    }

    public static function urlFor(CustomFont $font, bool $ensure = true): ?string
    {
        $disk = Storage::disk($font->diskName());

        if ($font->slug === '') {
            return null;
        }

        if (! $disk->exists($font->stylesheetPath())) {
            if (! $ensure || self::sync($font) === null) {
                return null;
            }
        }

        return $font->fileUrl($font->stylesheetPath());
    }

    public static function urlForSlug(string $slug, bool $ensure = true): ?string
    {
        $slug = trim($slug);

        if ($slug === '' || ! config('voodbuilder.fonts.custom.enabled', true)) {
            return null;
        }

        try {
            if (! Schema::hasTable((new CustomFont)->getTable())) {
                return null;
            }

            $font = CustomFont::query()->where('slug', $slug)->where('is_active', true)->first();

            return $font instanceof CustomFont ? self::urlFor($font, $ensure) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function delete(CustomFont $font): void
    {
        Storage::disk($font->diskName())->delete($font->stylesheetPath());
    }

    public static function css(CustomFont $font): string
    {
        $family = str_replace("'", "\\'", trim($font->family));

        if ($family === '') {
            return '';
        }

        $blocks = [];

        foreach ($font->groupedFaces() as $group) {
            $sources = [];

            foreach ($group['faces'] as $face) {
                $url = $font->fileUrl($face['path']);

                if ($url === '') {
                    continue;
                }

                $sources[] = "url('" . str_replace("'", "\\'", $url) . "') format('" . self::normalizeFormat($face['format']) . "')";
            }

            if ($sources === []) {
                continue;
            }

            $blocks[] = implode("\n", [
                '@font-face {',
                "    font-family: '{$family}';",
                '    font-style: ' . $group['style'] . ';',
                '    font-weight: ' . $group['weight'] . ';',
                '    font-display: swap;',
                '    src: ' . implode(",\n         ", $sources) . ';',
                '}',
            ]);
        }

        return implode("\n\n", $blocks);
    }

    public static function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));

        return self::FORMATS[$format] ?? 'woff2';
    }

    public static function formatPriority(string $format): int
    {
        return self::FORMAT_PRIORITY[self::normalizeFormat($format)] ?? 99;
    }
}
