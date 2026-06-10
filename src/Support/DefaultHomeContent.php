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
                    'primary_label' => __('vpress::home.cta_primary'),
                    'primary_url' => (string) config('vpress.packages.vpress_url'),
                    'secondary_label' => __('vpress::home.cta_secondary'),
                    'secondary_url' => (string) config('vpress.packages.github_url'),
                ]),
                self::customBlock('features_grid', [
                    'title' => __('vpress::home.features_title'),
                    'features' => [
                        [
                            'icon' => '⚙️',
                            'title' => __('vpress::home.feature_1_title'),
                            'text' => __('vpress::home.feature_1_text'),
                        ],
                        [
                            'icon' => '📖',
                            'title' => __('vpress::home.feature_2_title'),
                            'text' => __('vpress::home.feature_2_text'),
                        ],
                        [
                            'icon' => '🎨',
                            'title' => __('vpress::home.feature_3_title'),
                            'text' => __('vpress::home.feature_3_text'),
                        ],
                        [
                            'icon' => '⚡',
                            'title' => __('vpress::home.feature_4_title'),
                            'text' => __('vpress::home.feature_4_text'),
                        ],
                        [
                            'icon' => '🧭',
                            'title' => __('vpress::home.feature_5_title'),
                            'text' => __('vpress::home.feature_5_text'),
                        ],
                        [
                            'icon' => '🔍',
                            'title' => __('vpress::home.feature_6_title'),
                            'text' => __('vpress::home.feature_6_text'),
                        ],
                    ],
                ]),
                self::customBlock('package_promos', [
                    'title' => __('vpress::home.extend_title'),
                    'packages' => [
                        [
                            'title' => __('vpress::home.extend_vdocs_title'),
                            'text' => __('vpress::home.extend_vdocs_text'),
                            'button_label' => __('vpress::home.extend_vdocs_cta'),
                            'button_url' => (string) config('vpress.packages.vdocs_url'),
                        ],
                        [
                            'title' => __('vpress::home.extend_vtuts_title'),
                            'text' => __('vpress::home.extend_vtuts_text'),
                            'button_label' => __('vpress::home.extend_vtuts_cta'),
                            'button_url' => (string) config('vpress.packages.vtuts_url'),
                        ],
                        [
                            'title' => __('vpress::home.extend_voodflow_title'),
                            'text' => __('vpress::home.extend_voodflow_text'),
                            'button_label' => __('vpress::home.extend_voodflow_cta'),
                            'button_url' => (string) config('vpress.packages.voodflow_url'),
                        ],
                    ],
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
