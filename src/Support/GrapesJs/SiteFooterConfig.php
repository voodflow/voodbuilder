<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class SiteFooterConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        $normalized = array_merge(self::defaults(), $config);

        $normalized['columns'] = max(1, min(4, (int) ($normalized['columns'] ?? 4)));

        foreach (['show_newsletter', 'show_social', 'show_footer_menu', 'show_copyright'] as $flag) {
            if (array_key_exists($flag, $config)) {
                $normalized[$flag] = (bool) $config[$flag];
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'columns' => 4,
            'show_newsletter' => true,
            'show_social' => true,
            'show_footer_menu' => true,
            'show_copyright' => true,
        ];
    }
}
