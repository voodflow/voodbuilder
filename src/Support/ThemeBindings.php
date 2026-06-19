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
    public static function selectOptionsForChannel(string $channelId): array
    {
        return [
            '' => __('vpress::theme_bindings.inherit_default'),
            ...app(SubThemeRegistry::class)->optionsForCapability(
                self::requiredCapabilityForChannelId($channelId),
            ),
        ];
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
}
