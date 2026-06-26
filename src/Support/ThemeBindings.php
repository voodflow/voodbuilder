<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Enums\SubThemeCapability;
use Voodflow\Vpress\Enums\SubThemeType;

final class ThemeBindings
{
    public static function sitePagesCapability(): SubThemeCapability
    {
        return SubThemeCapability::Landing;
    }

    public static function requiredCapabilityForChannel(PublicContentChannel $channel): SubThemeCapability
    {
        $configured = config("vpress.content_channel_capabilities.{$channel->id()}");

        if (is_string($configured)) {
            $capability = SubThemeCapability::tryFrom($configured);

            if ($capability !== null) {
                return $capability;
            }
        }

        return SubThemeCapability::Doc;
    }

    public static function requiredCapabilityForChannelId(string $channelId): SubThemeCapability
    {
        $configured = config("vpress.content_channel_capabilities.{$channelId}");

        if (is_string($configured)) {
            $capability = SubThemeCapability::tryFrom($configured);

            if ($capability !== null) {
                return $capability;
            }
        }

        return SubThemeCapability::Doc;
    }

    public static function themeSupportsCapability(string $themeId, SubThemeCapability $capability): bool
    {
        return app(SubThemeRegistry::class)->supportsCapability($themeId, $capability);
    }

    public static function isValidChannelBinding(string $channelId, string $themeId): bool
    {
        if (! app(SubThemeRegistry::class)->exists($themeId)) {
            return false;
        }

        return self::themeSupportsCapability(
            $themeId,
            self::requiredCapabilityForChannelId($channelId),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptionsForChannel(string $channelId, ?string $includeId = null): array
    {
        $registry = app(SubThemeRegistry::class);
        $capability = self::requiredCapabilityForChannelId($channelId);
        $options = $registry->optionsForCapability($capability, $includeId);

        if ($options !== []) {
            return $options;
        }

        $options = $registry->marketingOptions($includeId);

        if ($options !== []) {
            return $options;
        }

        return $registry->contentOptions($includeId);
    }

    public static function siteThemeLabel(): string
    {
        return app(SubThemeRegistry::class)->label(SubThemeResolver::siteDefault());
    }

    public static function effectiveThemeForChannelId(string $channelId): string
    {
        $override = ContentChannelThemes::overrideFor($channelId);

        if ($override !== null) {
            return $override;
        }

        $configured = ContentChannelThemes::configuredDefaultFor($channelId);

        if ($configured !== null) {
            return $configured;
        }

        return SubThemeResolver::siteDefault();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    public static function expandChannelThemesForForm(array $overrides): array
    {
        $expanded = [];

        foreach ($overrides as $channelId => $theme) {
            if (is_string($channelId) && is_string($theme) && filled($theme)) {
                $expanded[$channelId] = $theme;
            }
        }

        return $expanded;
    }

    public static function channelAreaDescription(PublicContentChannel $channel): string
    {
        $key = 'vpress::theme_bindings.channels.'.$channel->id();
        $translation = __($key);

        if ($translation !== $key) {
            return $translation;
        }

        return __('vpress::theme_bindings.channel_generic', [
            'label' => $channel->label(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function sitePagesSelectOptions(?string $includeId = null): array
    {
        return app(SubThemeRegistry::class)->optionsForCapability(
            self::sitePagesCapability(),
            $includeId,
        );
    }

    public static function channelRoutesSummary(PublicContentChannel $channel): string
    {
        $patterns = $channel->routePatterns();

        if ($patterns === []) {
            return '';
        }

        return implode(', ', $patterns);
    }

    public static function shouldShowChannelBinding(PublicContentChannel $channel): bool
    {
        // Site Pages use `sub_theme` above; the pages channel is for search/routing only.
        return $channel->id() !== 'pages';
    }
}
