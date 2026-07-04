<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteNavConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        $legacyVariant = (string) ($config['variant'] ?? 'simple');

        $normalized = array_merge(self::defaults(), $config, [
            'variant' => 'simple',
        ]);

        foreach (['show_search', 'show_notifications', 'show_profile_menu'] as $flag) {
            if (array_key_exists($flag, $config)) {
                $normalized[$flag] = (bool) $config[$flag];
            }
        }

        if ($legacyVariant !== 'simple') {
            if ($legacyVariant === 'centered_links') {
                $normalized['main_nav_align'] = 'center';
            }

            if (str_contains($legacyVariant, 'with_search')) {
                $normalized['show_search'] = true;
            }
        }

        $normalized['main_nav_align'] = in_array($normalized['main_nav_align'], ['start', 'center'], true)
            ? $normalized['main_nav_align']
            : 'start';

        $normalized['sticky_nav'] = in_array($normalized['sticky_nav'], ['inherit', 'sticky', 'static'], true)
            ? $normalized['sticky_nav']
            : 'inherit';

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'variant' => 'simple',
            'main_nav_align' => 'start',
            'sticky_nav' => 'inherit',
            'show_search' => true,
            'show_notifications' => true,
            'show_profile_menu' => true,
        ];
    }
}
