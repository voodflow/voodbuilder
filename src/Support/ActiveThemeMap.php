<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\HtmlString;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;

/**
 * Active Theme Map.
 */
final class ActiveThemeMap
{
    /**
     * @param  array{sub_theme?: string|null, content_channel_sub_themes?: array<string, string>|null}  $data
     * @return list<array{
     *     area_id: string,
     *     area_label: string,
     *     theme_id: string,
     *     theme_label: string,
     *     source: string,
     * }>
     */
    public static function assignments(array $data): array
    {
        $registry = app(SubThemeRegistry::class);
        $siteTheme = SubThemeResolver::resolveId((string) ($data['sub_theme'] ?? SubThemeResolver::SITE))
            ?? SubThemeResolver::SITE;
        $overrides = is_array($data['content_channel_sub_themes'] ?? null)
            ? $data['content_channel_sub_themes']
            : [];

        $rows = [
            [
                'area_id' => 'site_pages',
                'area_label' => (string) __('voodbuilder::settings.area_site_pages'),
                'theme_id' => $siteTheme,
                'theme_label' => $registry->label($siteTheme),
                'source' => 'site',
            ],
        ];

        foreach (app(ContentChannelRegistry::class)->all() as $channel) {
            if (! ThemeBindings::shouldShowChannelBinding($channel)) {
                continue;
            }

            $rows[] = self::assignmentForChannel($channel, $siteTheme, $overrides);
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array{
     *     area_id: string,
     *     area_label: string,
     *     theme_id: string,
     *     theme_label: string,
     *     source: string,
     * }
     */
    protected static function assignmentForChannel(
        PublicContentChannel $channel,
        string $siteTheme,
        array $overrides,
    ): array {
        $registry = app(SubThemeRegistry::class);
        $channelId = $channel->id();
        $override = $overrides[$channelId] ?? null;

        if (is_string($override) && filled($override)) {
            $themeId = SubThemeResolver::normalize($override);

            return [
                'area_id' => $channelId,
                'area_label' => $channel->label(),
                'theme_id' => $themeId,
                'theme_label' => $registry->label($themeId),
                'source' => 'override',
            ];
        }

        $packageDefault = ContentChannelThemes::configuredDefaultFor($channelId);

        if ($packageDefault !== null) {
            return [
                'area_id' => $channelId,
                'area_label' => $channel->label(),
                'theme_id' => $packageDefault,
                'theme_label' => $registry->label($packageDefault),
                'source' => 'package_default',
            ];
        }

        return [
            'area_id' => $channelId,
            'area_label' => $channel->label(),
            'theme_id' => $siteTheme,
            'theme_label' => $registry->label($siteTheme),
            'source' => 'site_fallback',
        ];
    }

    /**
     * @param  array{sub_theme?: string|null, content_channel_sub_themes?: array<string, string>|null}  $data
     * @return array<string, string>
     */
    public static function themesInUseOptions(array $data): array
    {
        $options = [];

        foreach (self::assignments($data) as $row) {
            $options[$row['theme_id']] = $row['theme_label'];
        }

        return $options;
    }

    /**
     * @param  array{sub_theme?: string|null, content_channel_sub_themes?: array<string, string>|null}  $data
     */
    public static function overviewHtml(array $data): HtmlString
    {
        $rows = self::assignments($data);

        if ($rows === []) {
            return new HtmlString('');
        }

        $items = collect($rows)
            ->map(function (array $row): string {
                $swatch = e(ThemePresenter::previewColor($row['theme_id']));
                $customBadge = ThemePalette::themeHasCustomColors($row['theme_id'])
                    ? '<span class="voodbuilder-area-map__badge">'.e(__('voodbuilder::settings.theme_custom_colors_badge')).'</span>'
                    : '';

                return '<li class="voodbuilder-area-map__item">'
                    .'<span class="voodbuilder-area-map__area">'.e($row['area_label']).'</span>'
                    .'<span class="voodbuilder-area-map__theme">'
                        .'<span class="voodbuilder-area-map__swatch" style="background:'.$swatch.'"></span>'
                        .'<span class="voodbuilder-area-map__theme-label">'.e($row['theme_label']).'</span>'
                        .$customBadge
                    .'</span>'
                    .'<span class="voodbuilder-area-map__source">'.e(self::sourceLabel($row['source'])).'</span>'
                    .'</li>';
            })
            ->implode('');

        $html = '<style>'
            .'.voodbuilder-area-map{list-style:none;margin:0 0 1rem;padding:0;display:flex;flex-direction:column;gap:.5rem}'
            .'.voodbuilder-area-map__item{display:grid;grid-template-columns:minmax(6rem,1fr) minmax(8rem,1.4fr) auto;gap:.75rem;align-items:center;padding:.5rem .75rem;border-radius:.5rem;background:rgb(248 250 252);border:1px solid rgb(226 232 240)}'
            .'.dark .voodbuilder-area-map__item{background:rgb(30 41 59 / .5);border-color:rgb(51 65 85)}'
            .'.voodbuilder-area-map__area{font-size:.8125rem;font-weight:600;color:rgb(51 65 85)}'
            .'.dark .voodbuilder-area-map__area{color:rgb(203 213 225)}'
            .'.voodbuilder-area-map__theme{display:inline-flex;align-items:center;gap:.5rem;min-width:0}'
            .'.voodbuilder-area-map__swatch{width:1rem;height:1rem;border-radius:9999px;flex-shrink:0;border:1px solid rgb(0 0 0 / .08)}'
            .'.voodbuilder-area-map__theme-label{font-size:.8125rem;font-weight:500;color:rgb(15 23 42);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
            .'.dark .voodbuilder-area-map__theme-label{color:rgb(248 250 252)}'
            .'.voodbuilder-area-map__badge{font-size:.6875rem;font-weight:500;color:rgb(100 116 139);white-space:nowrap}'
            .'.voodbuilder-area-map__source{font-size:.6875rem;color:rgb(100 116 139);text-align:end;white-space:nowrap}'
            .'.dark .voodbuilder-area-map__source{color:rgb(148 163 184)}'
            .'@media(max-width:640px){.voodbuilder-area-map__item{grid-template-columns:1fr;gap:.25rem}.voodbuilder-area-map__source{text-align:start}}'
            .'</style>'
            .'<ul class="voodbuilder-area-map">'.$items.'</ul>';

        return new HtmlString($html);
    }

    protected static function sourceLabel(string $source): string
    {
        return match ($source) {
            'site' => (string) __('voodbuilder::settings.theme_assignment_source_site'),
            'override' => (string) __('voodbuilder::settings.theme_assignment_source_override'),
            'package_default' => (string) __('voodbuilder::settings.theme_assignment_source_package'),
            'site_fallback' => (string) __('voodbuilder::settings.theme_assignment_source_inherits_site'),
            default => $source,
        };
    }
}
