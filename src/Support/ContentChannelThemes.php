<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Content Channel Themes.
 */
final class ContentChannelThemes
{
    public static function resolveForChannel(PublicContentChannel $channel): ?string
    {
        $override = self::overrideFor($channel->id());

        if ($override !== null) {
            return $override;
        }

        $peerTheme = self::peerLandingThemeFor($channel->id());

        if ($peerTheme !== null) {
            return $peerTheme;
        }

        return self::configuredDefaultFor($channel->id());
    }

    /**
     * Events and exhibitors often share the same visual theme (e.g. Soundmit).
     * When one landing channel has an explicit override, the other can inherit it.
     */
    public static function peerLandingThemeFor(string $channelId): ?string
    {
        $peerId = match ($channelId) {
            'events' => 'exhibitors',
            'exhibitors' => 'events',
            default => null,
        };

        if ($peerId === null) {
            return null;
        }

        $peerTheme = self::overrideFor($peerId);

        if ($peerTheme === null) {
            return null;
        }

        if (! ThemeBindings::isValidChannelBinding($channelId, $peerTheme)) {
            return null;
        }

        return $peerTheme;
    }

    public static function configuredDefaultFor(string $channelId): ?string
    {
        $default = config("voodbuilder.content_channel_defaults.{$channelId}");

        if (! is_string($default) || ! filled($default)) {
            $default = self::packageChannelDefaults()[$channelId] ?? null;
        }

        if (! is_string($default) || ! filled($default)) {
            return null;
        }

        $normalized = SubThemeResolver::normalize($default);

        if (! ThemeBindings::isValidChannelBinding($channelId, $normalized)) {
            return null;
        }

        return $normalized;
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

        $path = VoodbuilderPaths::packagePath().'/config/voodbuilder.php';

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
        $overrides = VoodbuilderSettings::get('content_channel_sub_themes', []);

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
