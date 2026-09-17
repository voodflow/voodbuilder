<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Site Nav Config.
 */
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

        foreach (['show_search', 'show_notifications', 'show_profile_menu', 'show_logo', 'show_site_name', 'show_logo_desktop', 'show_logo_mobile', 'show_site_name_desktop', 'show_site_name_mobile'] as $flag) {
            if (array_key_exists($flag, $config)) {
                $normalized[$flag] = (bool) $config[$flag];
            }
        }

        if (array_key_exists('show_logo_desktop', $config) || array_key_exists('show_logo_mobile', $config)) {
            $normalized['show_logo'] = $normalized['show_logo_desktop'] || $normalized['show_logo_mobile'];
        } elseif (array_key_exists('show_logo', $config)) {
            $normalized['show_logo_desktop'] = $normalized['show_logo'];
            $normalized['show_logo_mobile'] = $normalized['show_logo'];
        }

        if (array_key_exists('show_site_name_desktop', $config) || array_key_exists('show_site_name_mobile', $config)) {
            $normalized['show_site_name'] = $normalized['show_site_name_desktop'] || $normalized['show_site_name_mobile'];
        } elseif (array_key_exists('show_site_name', $config)) {
            $normalized['show_site_name_desktop'] = $normalized['show_site_name'];
            $normalized['show_site_name_mobile'] = $normalized['show_site_name'];
        }

        $normalized = array_merge($normalized, ChromeBrandLogos::normalizeConfigKeys($config));

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
            'show_logo' => true,
            'show_site_name' => true,
            'show_logo_desktop' => true,
            'show_logo_mobile' => true,
            'show_site_name_desktop' => true,
            'show_site_name_mobile' => true,
            'logo_desktop_light' => null,
            'logo_desktop_dark' => null,
            'logo_mobile_light' => null,
            'logo_mobile_dark' => null,
            'logo_size' => ChromeBrandLogos::DEFAULT_SIZE,
            'logo_size_mobile' => ChromeBrandLogos::DEFAULT_SIZE,
            'logo_full_width' => false,
            'logo_shape' => ChromeBrandLogos::DEFAULT_SHAPE,
        ];
    }
}
