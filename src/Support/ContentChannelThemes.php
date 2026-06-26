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
            $default = self::packageChannelDefaults()[$channelId] ?? null;
        }

        if (! is_string($default) || ! filled($default)) {
            return null;
        }

        return SubThemeResolver::normalize($default);
    }

    /**
     * Package defaults survive when the host app's published config replaces the
     * merged `content_channel_defaults` array (Laravel array_merge is not recursive).
     *
     * @return array<string, string>
     */
    public static function packageChannelDefaults(): array
    {
        static $defaults = null;

        if (is_array($defaults)) {
            return $defaults;
        }

        $path = VpressPaths::packagePath().'/config/vpress.php';

        if (! is_file($path)) {
            $defaults = [];

            return $defaults;
        }

        /** @var array<string, mixed> $config */
        $config = require $path;
        $raw = $config['content_channel_defaults'] ?? [];
        $defaults = [];

        if (is_array($raw)) {
            foreach ($raw as $channelId => $themeId) {
                if (is_string($channelId) && is_string($themeId) && filled($themeId)) {
                    $defaults[$channelId] = $themeId;
                }
            }
        }

        return $defaults;
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

            if (! $registry->exists($theme)) {
                continue;
            }

            if (! ThemeBindings::isValidChannelBinding($channelId, $theme)) {
                continue;
            }

            $normalized[$channelId] = $theme;
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(?string $channelId = null): array
    {
        if ($channelId !== null) {
            return ThemeBindings::selectOptionsForChannel($channelId);
        }

        return app(SubThemeRegistry::class)->options();
    }

    public static function marketingSelectOptions(?string $includeId = null): array
    {
        return ThemeBindings::sitePagesSelectOptions($includeId);
    }
}
