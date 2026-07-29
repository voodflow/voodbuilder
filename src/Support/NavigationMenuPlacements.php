<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\NavigationMenu;

/**
 * Admin-facing menu placements for the site chrome and Editor footer blocks.
 *
 * Site model:
 * - Header: {@see main} (center) + optional {@see header_extra} (right)
 * - Footer blocks: up to 4 column menus ({@see footer_col_1}…{@see footer_col_4})
 * - Footer inline row: {@see footer} (centered / social variants)
 * - Social icons: {@see social}
 */
final class NavigationMenuPlacements
{
    public const GROUP_HEADER = 'header';

    public const GROUP_FOOTER_COLUMNS = 'footer_columns';

    public const GROUP_FOOTER = 'footer';

    /** @var list<string> */
    private const DEPRECATED_SLUGS = [
        'landing_nav',
        'landing_footer',
        'landing_footer_col_1',
        'landing_footer_col_2',
        'landing_footer_col_3',
        'landing_footer_col_4',
    ];

    /**
     * @return array<string, array{group: string, label: string}>
     */
    public static function catalog(): array
    {
        return [
            'main' => [
                'group' => self::GROUP_HEADER,
                'label' => __('voodbuilder::admin.menu_placements.main'),
            ],
            'header_extra' => [
                'group' => self::GROUP_HEADER,
                'label' => __('voodbuilder::admin.menu_placements.header_extra'),
            ],
            'social' => [
                'group' => self::GROUP_FOOTER,
                'label' => __('voodbuilder::admin.menu_placements.social'),
            ],
            'footer' => [
                'group' => self::GROUP_FOOTER,
                'label' => __('voodbuilder::admin.menu_placements.footer_inline'),
            ],
            ...collect(SiteFooterColumnPlacements::placementLabels())
                ->map(fn (string $label, string $slug): array => [
                    'group' => self::GROUP_FOOTER_COLUMNS,
                    'label' => $label,
                ])
                ->all(),
            'landing_nav' => [
                'group' => self::GROUP_FOOTER,
                'label' => __('Landing navbar links'),
            ],
            ...collect(LandingMenuPlacements::footerColumnPlacementLabels())
                ->map(fn (string $label, string $slug): array => [
                    'group' => self::GROUP_FOOTER,
                    'label' => $label,
                ])
                ->all(),
            'landing_footer' => [
                'group' => self::GROUP_FOOTER,
                'label' => __('Landing footer columns (legacy groups)'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function formOptions(?NavigationMenu $record = null): array
    {
        $grouped = [];

        foreach (self::visibleCatalog($record) as $slug => $meta) {
            $groupLabel = self::groupLabel($meta['group']);
            $grouped[$groupLabel][$slug] = $meta['label'];
        }

        return $grouped;
    }

    /** @return array<string, string> */
    public static function flatOptions(?NavigationMenu $record = null): array
    {
        $flat = [];

        foreach (self::visibleCatalog($record) as $slug => $meta) {
            $flat[$slug] = $meta['label'];
        }

        return $flat;
    }

    public static function isDeprecated(string $slug): bool
    {
        return in_array($slug, self::DEPRECATED_SLUGS, true)
            || str_starts_with($slug, 'landing_footer_col_');
    }

    public static function label(string $slug): string
    {
        return self::catalog()[$slug]['label']
            ?? NavigationMenuResolver::placementLabel($slug);
    }

    /**
     * @return array<string, array{group: string, label: string}>
     */
    protected static function visibleCatalog(?NavigationMenu $record): array
    {
        $includeDeprecated = $record !== null && self::isDeprecated($record->slug);

        return collect(self::catalog())
            ->filter(fn (array $meta, string $slug): bool => self::shouldShow($slug, $includeDeprecated))
            ->all();
    }

    protected static function shouldShow(string $slug, bool $includeDeprecated): bool
    {
        if (self::isDeprecated($slug)) {
            return $includeDeprecated;
        }

        return true;
    }

    protected static function groupLabel(string $group): string
    {
        return match ($group) {
            self::GROUP_HEADER => __('voodbuilder::admin.menu_placements.groups.header'),
            self::GROUP_FOOTER_COLUMNS => __('voodbuilder::admin.menu_placements.groups.footer_columns'),
            self::GROUP_FOOTER => __('voodbuilder::admin.menu_placements.groups.footer'),
            default => $group,
        };
    }
}
