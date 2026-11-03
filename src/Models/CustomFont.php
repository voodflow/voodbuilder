<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontStylesheet;
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Author-installed webfont (uploaded WOFF2/WOFF/TTF/OTF files).
 *
 * @property int $id
 * @property string $family
 * @property string $slug
 * @property string $category
 * @property string $stack
 * @property list<int>|null $weights
 * @property mixed $files
 * @property bool $is_active
 */
class CustomFont extends Model
{
    protected $table = 'voodbuilder_custom_fonts';

    protected $fillable = [
        'family',
        'slug',
        'category',
        'stack',
        'weights',
        'files',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weights' => 'array',
            'files' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (self $font): void {
            CustomFontStylesheet::sync($font);
            Voodbuilder::fonts()->reloadCustom();
        });

        static::deleted(static function (self $font): void {
            CustomFontStylesheet::delete($font);
            $font->deleteFiles();
            Voodbuilder::fonts()->reloadCustom();
        });
    }

    public function diskName(): string
    {
        return (string) config('voodbuilder.fonts.custom.disk', 'public');
    }

    public function directory(): string
    {
        $base = trim((string) config('voodbuilder.fonts.custom.directory', 'voodbuilder/fonts'), '/');

        return trim($base . '/' . $this->slug, '/');
    }

    public function stylesheetPath(): string
    {
        return $this->directory() . '/font.css';
    }

    /**
     * @return list<array{path: string, name: string, weight: int, style: string, format: string}>
     */
    public function faces(): array
    {
        $files = $this->files;

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $face): ?array {
                if (! is_array($face)) {
                    return null;
                }

                $path = trim((string) ($face['path'] ?? ''));

                if ($path === '') {
                    return null;
                }

                return [
                    'path' => $path,
                    'name' => trim((string) ($face['name'] ?? basename($path))),
                    'weight' => max(1, min(1000, (int) ($face['weight'] ?? 400))),
                    'style' => in_array(($face['style'] ?? 'normal'), ['normal', 'italic', 'oblique'], true)
                        ? (string) $face['style']
                        : 'normal',
                    'format' => CustomFontStylesheet::normalizeFormat((string) ($face['format'] ?? pathinfo($path, PATHINFO_EXTENSION))),
                ];
            },
            $files,
        )));
    }

    /**
     * @return array<string, array{weight: int, style: string, faces: list<array{path: string, name: string, weight: int, style: string, format: string}>}>
     */
    public function groupedFaces(): array
    {
        $groups = [];

        foreach ($this->faces() as $face) {
            $key = $face['weight'] . '|' . $face['style'];
            $groups[$key] ??= [
                'weight' => $face['weight'],
                'style' => $face['style'],
                'faces' => [],
            ];
            $groups[$key]['faces'][] = $face;
        }

        foreach ($groups as $key => $group) {
            usort(
                $group['faces'],
                static fn (array $a, array $b): int => CustomFontStylesheet::formatPriority($a['format'])
                    <=> CustomFontStylesheet::formatPriority($b['format']),
            );
            $groups[$key]['faces'] = $group['faces'];
        }

        uasort(
            $groups,
            static fn (array $a, array $b): int => [$a['weight'], $a['style']] <=> [$b['weight'], $b['style']],
        );

        return $groups;
    }

    public function fileUrl(string $path): string
    {
        $disk = Storage::disk($this->diskName());
        $url = $disk->url($path);

        // Local disks: return a root-relative URL so Docker port mappings,
        // reverse proxies, and APP_URL mismatches cannot break the font files.
        try {
            if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
                $relative = parse_url($url, PHP_URL_PATH);

                if (is_string($relative) && $relative !== '') {
                    return $relative;
                }
            }
        } catch (Throwable) {
            // Fall back to the disk URL below.
        }

        return $url;
    }

    /**
     * @return list<string>
     */
    public function fileUrls(): array
    {
        return array_values(array_unique(array_map(
            fn (array $face): string => $this->fileUrl($face['path']),
            $this->faces(),
        )));
    }

    /**
     * @return list<array{url: string, weight: int, style: string, format: string}>
     */
    public function facePayload(): array
    {
        return array_map(
            fn (array $face): array => [
                'url' => $this->fileUrl($face['path']),
                'weight' => $face['weight'],
                'style' => $face['style'],
                'format' => $face['format'],
            ],
            $this->faces(),
        );
    }

    public function stylesheetUrl(bool $ensure = true): ?string
    {
        return CustomFontStylesheet::urlFor($this, $ensure);
    }

    public function toFontDefinition(): FontDefinition
    {
        return FontDefinition::fromArray([
            'id' => $this->slug,
            'family' => $this->family,
            'category' => $this->category,
            'provider' => 'custom',
            'package' => null,
            'files' => $this->fileUrls(),
            'stack' => $this->stack,
            'weights' => $this->resolvedWeights(),
            'meta' => [
                'css_url' => $this->stylesheetUrl(false),
                'faces' => $this->facePayload(),
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    public function resolvedWeights(): array
    {
        $weights = is_array($this->weights) ? $this->weights : [];
        $weights = array_values(array_unique(array_map('intval', $weights)));

        if ($weights === []) {
            $weights = array_values(array_unique(array_map(
                static fn (array $face): int => $face['weight'],
                $this->faces(),
            )));
        }

        sort($weights);

        return $weights !== [] ? $weights : [400];
    }

    public static function stackFor(string $family, string $category, ?string $stack = null): string
    {
        $custom = FontDefinition::cssSafeStack((string) $stack);

        if ($custom !== '') {
            return $custom;
        }

        $family = trim($family) !== '' ? trim($family) : 'Custom Font';
        $quoted = "'" . str_replace("'", "\\'", $family) . "'";

        $fallback = match ($category) {
            'serif' => 'ui-serif, Georgia, serif',
            'monospace' => 'ui-monospace, SFMono-Regular, Menlo, monospace',
            'cursive', 'handwriting' => 'cursive',
            'fantasy', 'display' => 'ui-sans-serif, system-ui, sans-serif',
            default => 'ui-sans-serif, system-ui, sans-serif',
        };

        return FontDefinition::cssSafeStack($quoted . ', ' . $fallback);
    }

    public static function makeSlug(string $family): string
    {
        $slug = Str::slug($family);

        return $slug !== '' ? $slug : 'custom-font';
    }

    public function deleteFiles(): void
    {
        Storage::disk($this->diskName())->deleteDirectory($this->directory());
    }
}
