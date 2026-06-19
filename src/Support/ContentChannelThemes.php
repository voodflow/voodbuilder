<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;

final class ContentChannelThemes
{
    public static function resolveForChannel(PublicContentChannel $channel): ?string
    {
        $override = self::overrideFor($channel->id());

        if ($override !== null) {
            return $override;
        }

        return self::configuredDefaultFor($channel->id());
    }

    public static function configuredDefaultFor(string $channelId): ?string
    {
        $default = config("vpress.content_channel_defaults.{$channelId}");

        if (! is_string($default) || ! filled($default)) {
            return null;
        }

        return SubThemeResolver::normalize($default);
    }

    public static function overrideFor(string $channelId): ?string
    {
        $overrides = VpressSettings::get('content_channel_sub_themes', []);

        if (! is_array($overrides)) {
            return null;
        }

        $theme = $overrides[$channelId] ?? null;

        if (! is_string($theme) || ! filled($theme)) {
            return null;
        }

        return SubThemeResolver::normalize($theme);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    public static function normalizeOverrides(array $overrides): array
    {
        $registry = app(SubThemeRegistry::class);
        $normalized = [];

        foreach ($overrides as $channelId => $theme) {
            if (! is_string($channelId) || ! is_string($theme) || ! filled($theme)) {
                continue;
            }

            if ($registry->exists($theme)) {
                $normalized[$channelId] = $theme;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            '' => __('vpress::content_channels.inherit_package'),
            ...app(SubThemeRegistry::class)->contentOptions(),
        ];
    }

    public static function marketingSelectOptions(?string $includeId = null): array
    {
        return app(SubThemeRegistry::class)->marketingOptions($includeId);
    }
}
