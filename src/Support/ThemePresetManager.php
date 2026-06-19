<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Models\VpressSettings;

final class ThemePresetManager
{
    /**
     * @return Collection<int, ThemePreset>
     */
    public static function all(): Collection
    {
        return static::bundled()->merge(static::custom());
    }

    /**
     * @return Collection<int, ThemePreset>
     */
    public static function bundled(): Collection
    {
        $directory = __DIR__.'/../../resources/theme-presets';

        if (! File::isDirectory($directory)) {
            return collect();
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(function ($file): ?ThemePreset {
                $data = json_decode(File::get($file->getPathname()), true);

                if (! is_array($data)) {
                    return null;
                }

                return ThemePreset::fromArray($data, bundled: true);
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, ThemePreset>
     */
    public static function custom(): Collection
    {
        $presets = VpressSettings::get('theme_presets', []);

        if (! is_array($presets)) {
            return collect();
        }

        return collect($presets)
            ->filter(fn ($preset): bool => is_array($preset))
            ->map(fn (array $preset): ThemePreset => ThemePreset::fromArray($preset))
            ->values();
    }

    public static function find(string $id): ?ThemePreset
    {
        return static::all()->first(fn (ThemePreset $preset): bool => $preset->id === $id);
    }

    public static function snapshotFromSettings(string $id, string $label, ?string $description = null): ThemePreset
    {
        $data = VpressSettings::data();

        return new ThemePreset(
            id: $id,
            label: $label,
            description: $description,
            sitePagesTheme: SubThemeResolver::normalize((string) ($data['sub_theme'] ?? SubThemeResolver::DEFAULT)),
            channelThemes: is_array($data['content_channel_sub_themes'] ?? null)
                ? $data['content_channel_sub_themes']
                : [],
            colors: is_array($data['sub_theme_colors'] ?? null) ? $data['sub_theme_colors'] : [],
        );
    }

    public static function apply(ThemePreset $preset): void
    {
        $channelThemes = ContentChannelThemes::normalizeOverrides($preset->channelThemes);

        VpressSettings::saveData([
            'sub_theme' => SubThemeResolver::normalize($preset->sitePagesTheme),
            'content_channel_sub_themes' => $channelThemes,
            'sub_theme_colors' => ThemePalette::normalize($preset->colors),
            'active_theme_preset_id' => $preset->id,
        ]);
    }

    public static function saveCustom(ThemePreset $preset): void
    {
        if ($preset->bundled) {
            return;
        }

        $presets = collect(VpressSettings::get('theme_presets', []))
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => $item)
            ->reject(fn (array $item): bool => ($item['id'] ?? null) === $preset->id)
            ->values()
            ->all();

        $presets[] = $preset->toArray();

        VpressSettings::saveData([
            'theme_presets' => $presets,
        ]);
    }

    public static function deleteCustom(string $id): bool
    {
        if (static::bundled()->contains(fn (ThemePreset $preset): bool => $preset->id === $id)) {
            return false;
        }

        $presets = collect(VpressSettings::get('theme_presets', []))
            ->filter(fn ($item): bool => is_array($item))
            ->reject(fn (array $item): bool => ($item['id'] ?? null) === $id)
            ->values()
            ->all();

        $payload = ['theme_presets' => $presets];

        if (VpressSettings::get('active_theme_preset_id') === $id) {
            $payload['active_theme_preset_id'] = null;
        }

        VpressSettings::saveData($payload);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function export(ThemePreset $preset): array
    {
        return $preset->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function import(array $data, bool $apply = false, bool $saveCustom = true): ThemePreset
    {
        $preset = ThemePreset::fromArray($data);

        if ($saveCustom && ! $preset->bundled) {
            static::saveCustom($preset);
        }

        if ($apply) {
            static::apply($preset);
        }

        return $preset;
    }

    public static function importFromFile(string $path, bool $apply = false, bool $saveCustom = true): ThemePreset
    {
        $contents = File::get($path);
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            throw new \InvalidArgumentException("Invalid theme preset file: {$path}");
        }

        return static::import($data, $apply, $saveCustom);
    }

    public static function exportToFile(ThemePreset $preset, string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(static::export($preset), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }
}
