<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

final class ThemePreset
{
    public const SCHEMA = 'voodbuilder-theme-preset/1';

    /**
     * @param  array<string, string>  $channelThemes
     * @param  array<string, mixed>  $colors
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly ?string $description,
        public readonly string $sitePagesTheme,
        public readonly array $channelThemes,
        public readonly array $colors,
        public readonly bool $bundled = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'site_pages_theme' => $this->sitePagesTheme,
            'channel_themes' => $this->channelThemes,
            'colors' => $this->colors,
            'bundled' => $this->bundled,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, bool $bundled = false): self
    {
        $channelThemes = is_array($data['channel_themes'] ?? null) ? $data['channel_themes'] : [];
        $normalizedChannels = [];

        foreach ($channelThemes as $channelId => $themeId) {
            if (! is_string($channelId) || ! is_string($themeId) || ! filled($themeId)) {
                continue;
            }

            $normalizedChannels[$channelId] = $themeId;
        }

        return new self(
            id: (string) ($data['id'] ?? 'preset'),
            label: (string) ($data['label'] ?? 'Theme preset'),
            description: filled($data['description'] ?? null) ? (string) $data['description'] : null,
            sitePagesTheme: SubThemeResolver::normalize((string) ($data['site_pages_theme'] ?? SubThemeResolver::DEFAULT)),
            channelThemes: $normalizedChannels,
            colors: is_array($data['colors'] ?? null) ? $data['colors'] : [],
            bundled: $bundled || (bool) ($data['bundled'] ?? false),
        );
    }

    public function apply(): void
    {
        ThemePresetManager::apply($this);
    }
}
