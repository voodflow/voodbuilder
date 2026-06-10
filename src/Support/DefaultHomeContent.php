<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vtuts\Support\Locales;

final class DefaultHomeContent
{
    /** @return array<string, mixed> */
    public static function content(?string $locale = null): array
    {
        $locale ??= class_exists(Locales::class)
            ? Locales::default()
            : app()->getLocale();

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $content = [
            'type' => 'doc',
            'content' => [
                self::customBlock('hero', [
                    'name' => __('vpress::home.brand'),
                    'headline' => __('vpress::home.headline'),
                    'tagline' => __('vpress::home.tagline'),
                    'primary_label' => __('vpress::home.cta_vtuts'),
                    'primary_url' => (string) config('vpress.packages.vtuts_url'),
                    'secondary_label' => __('vpress::home.cta_vdocs'),
                    'secondary_url' => (string) config('vpress.packages.vdocs_url'),
                ]),
                self::customBlock('features_grid', [
                    'title' => __('vpress::home.why_title'),
                    'features' => [
                        [
                            'icon' => '✨',
                            'title' => __('vpress::home.feature_1_title'),
                            'text' => __('vpress::home.feature_1_text'),
                        ],
                        [
                            'icon' => '⚙️',
                            'title' => __('vpress::home.feature_2_title'),
                            'text' => __('vpress::home.feature_2_text'),
                        ],
                        [
                            'icon' => '📦',
                            'title' => __('vpress::home.feature_3_title'),
                            'text' => __('vpress::home.feature_3_text'),
                        ],
                    ],
                ]),
                self::customBlock('product_promo', [
                    'title' => __('vpress::home.voodflow_title'),
                    'text' => __('vpress::home.voodflow_text'),
                    'button_label' => __('vpress::home.voodflow_cta'),
                    'button_url' => (string) config('vpress.packages.voodflow_url'),
                ]),
            ],
        ];

        app()->setLocale($previousLocale);

        return $content;
    }

    /** @param  array<string, mixed>  $config */
    private static function customBlock(string $id, array $config): array
    {
        return [
            'type' => 'customBlock',
            'attrs' => [
                'id' => $id,
                'config' => $config,
            ],
        ];
    }
}
