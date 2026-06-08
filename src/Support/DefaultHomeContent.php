<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\Route;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vtuts\Support\VtutUrls;

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

        $tutorialsUrl = Route::has('vtuts.index')
            ? (class_exists(VtutUrls::class)
                ? VtutUrls::index($locale)
                : route('vtuts.index'))
            : url('/vtuts');

        $content = [
            'type' => 'doc',
            'content' => [
                static::customBlock('hero', [
                    'name' => config('app.name', 'Cosmolab'),
                    'headline' => __('vpress::home.headline'),
                    'tagline' => __('vpress::home.tagline'),
                    'primary_label' => __('vpress::home.browse_vtuts'),
                    'primary_url' => $tutorialsUrl,
                    'secondary_label' => __('vpress::home.shop_kits'),
                    'secondary_url' => 'https://cosmolab.example/shop',
                ]),
                static::customBlock('features_grid', [
                    'title' => __('vpress::home.why_title'),
                    'features' => [
                        [
                            'icon' => '⚡',
                            'title' => __('vpress::home.feature_1_title'),
                            'text' => __('vpress::home.feature_1_text'),
                        ],
                        [
                            'icon' => '🎛️',
                            'title' => __('vpress::home.feature_2_title'),
                            'text' => __('vpress::home.feature_2_text'),
                        ],
                        [
                            'icon' => '🛠️',
                            'title' => __('vpress::home.feature_3_title'),
                            'text' => __('vpress::home.feature_3_text'),
                        ],
                    ],
                ]),
                static::customBlock('latest_vtuts', [
                    'limit' => 6,
                    'columns' => 3,
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
