<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Vtuts\Support\Locales;

/**
 * Default Home Content.
 */
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
                    'name' => __('voodbuilder::home.brand'),
                    'headline' => __('voodbuilder::home.headline'),
                    'tagline' => __('voodbuilder::home.tagline'),
                    'primary_label' => __('voodbuilder::home.cta_primary'),
                    'primary_url' => (string) config('voodbuilder.packages.voodbuilder_url'),
                    'secondary_label' => __('voodbuilder::home.cta_secondary'),
                    'secondary_url' => (string) config('voodbuilder.packages.github_url'),
                ]),
                self::customBlock('features_grid', [
                    'title' => __('voodbuilder::home.features_title'),
                    'features' => [
                        [
                            'icon' => '⚙️',
                            'title' => __('voodbuilder::home.feature_1_title'),
                            'text' => __('voodbuilder::home.feature_1_text'),
                        ],
                        [
                            'icon' => '📖',
                            'title' => __('voodbuilder::home.feature_2_title'),
                            'text' => __('voodbuilder::home.feature_2_text'),
                        ],
                        [
                            'icon' => '🎨',
                            'title' => __('voodbuilder::home.feature_3_title'),
                            'text' => __('voodbuilder::home.feature_3_text'),
                        ],
                        [
                            'icon' => '⚡',
                            'title' => __('voodbuilder::home.feature_4_title'),
                            'text' => __('voodbuilder::home.feature_4_text'),
                        ],
                        [
                            'icon' => '🧭',
                            'title' => __('voodbuilder::home.feature_5_title'),
                            'text' => __('voodbuilder::home.feature_5_text'),
                        ],
                        [
                            'icon' => '🔍',
                            'title' => __('voodbuilder::home.feature_6_title'),
                            'text' => __('voodbuilder::home.feature_6_text'),
                        ],
                    ],
                ]),
                self::customBlock('package_promos', [
                    'title' => __('voodbuilder::home.extend_title'),
                    'packages' => [
                        [
                            'title' => __('voodbuilder::home.extend_vdocs_title'),
                            'text' => __('voodbuilder::home.extend_vdocs_text'),
                            'button_label' => __('voodbuilder::home.extend_vdocs_cta'),
                            'button_url' => (string) config('voodbuilder.packages.vdocs_url'),
                        ],
                        [
                            'title' => __('voodbuilder::home.extend_vtuts_title'),
                            'text' => __('voodbuilder::home.extend_vtuts_text'),
                            'button_label' => __('voodbuilder::home.extend_vtuts_cta'),
                            'button_url' => (string) config('voodbuilder.packages.vtuts_url'),
                        ],
                        [
                            'title' => __('voodbuilder::home.extend_voodflow_title'),
                            'text' => __('voodbuilder::home.extend_voodflow_text'),
                            'button_label' => __('voodbuilder::home.extend_voodflow_cta'),
                            'button_url' => (string) config('voodbuilder.packages.voodflow_url'),
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
