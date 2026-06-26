<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;

final class ThemeMapPayload
{
    /**
     * @param  array<string, string|null>|null  $channelOverrides
     * @return array<string, mixed>
     */
    public static function build(?string $subTheme = null, ?array $channelOverrides = null): array
    {
        $subTheme = SubThemeResolver::resolveId((string) ($subTheme ?? VpressSettings::get('sub_theme')))
            ?? SubThemeResolver::SITE;

        $overrides = ThemeBindings::expandChannelThemesForForm(
            $channelOverrides ?? (is_array(VpressSettings::get('content_channel_sub_themes'))
                ? VpressSettings::get('content_channel_sub_themes')
                : []),
        );

        $data = [
            'sub_theme' => $subTheme,
            'content_channel_sub_themes' => $overrides,
        ];

        $themes = [];

        foreach (app(SubThemeRegistry::class)->ids() as $id) {
            $card = ThemePresenter::card($id);
            $themes[] = [
                'id' => $id,
                'label' => $card['label'],
                'preview' => $card['preview'],
                'surface' => $card['surface'],
                'strip' => $card['strip'],
            ];
        }

        $areas = [];
        $edges = [];

        foreach (ActiveThemeMap::assignments($data) as $row) {
            $areaId = $row['area_id'];
            $description = $areaId === 'site_pages'
                ? (string) __('vpress::theme_bindings.site_pages_description')
                : self::channelDescription($areaId);

            $allowedThemeIds = app(SubThemeRegistry::class)->ids();

            $areas[] = [
                'id' => $areaId,
                'label' => $row['area_label'],
                'description' => $description,
                'routes' => $areaId === 'site_pages' ? '' : self::channelRoutes($areaId),
                'theme_id' => $row['theme_id'],
                'source' => $row['source'],
                'allowed_theme_ids' => array_values($allowedThemeIds),
                'inherited_theme_id' => $areaId === 'site_pages'
                    ? $subTheme
                    : self::inheritedThemeForChannel($areaId, $subTheme),
            ];

            $edges[] = [
                'theme_id' => $row['theme_id'],
                'area_id' => $areaId,
                'inherited' => ! in_array($row['source'], ['site', 'override'], true),
            ];
        }

        return [
            'themes' => $themes,
            'areas' => $areas,
            'edges' => $edges,
            'sub_theme' => $subTheme,
            'channel_overrides' => $overrides,
            'i18n' => [
                'hint' => (string) __('vpress::settings.theme_map_hint'),
                'inherited' => (string) __('vpress::settings.theme_map_inherited'),
                'edit_theme' => (string) __('vpress::settings.theme_map_edit_theme'),
                'invalid_binding' => (string) __('vpress::settings.theme_map_invalid_binding'),
                'legend_explicit' => (string) __('vpress::settings.theme_map_legend_explicit'),
                'legend_inherited' => (string) __('vpress::settings.theme_map_legend_inherited'),
                'legend_controls' => (string) __('vpress::settings.theme_map_legend_controls'),
            ],
        ];
    }

    public static function inheritedThemeForChannel(string $channelId, string $siteTheme): string
    {
        $configured = ContentChannelThemes::configuredDefaultFor($channelId);

        if ($configured !== null) {
            return SubThemeResolver::normalize($configured);
        }

        return SubThemeResolver::normalize($siteTheme);
    }

    public static function normalizeOverride(string $channelId, string $themeId, string $siteTheme): ?string
    {
        $themeId = SubThemeResolver::normalize($themeId);
        $inherited = self::inheritedThemeForChannel($channelId, $siteTheme);

        return $themeId === $inherited ? null : $themeId;
    }

    protected static function channelDescription(string $channelId): string
    {
        $channel = app(ContentChannelRegistry::class)->get($channelId);

        if (! $channel instanceof PublicContentChannel) {
            return '';
        }

        return ThemeBindings::channelAreaDescription($channel);
    }

    protected static function channelRoutes(string $channelId): string
    {
        $channel = app(ContentChannelRegistry::class)->get($channelId);

        if (! $channel instanceof PublicContentChannel) {
            return '';
        }

        return ThemeBindings::channelRoutesSummary($channel);
    }
}
