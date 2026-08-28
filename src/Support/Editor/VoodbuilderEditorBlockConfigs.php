<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Default editor block config for blocks shipped by voodbuilder core.
 */
final class VoodbuilderEditorBlockConfigs
{
    public static function register(): void
    {
        foreach (self::definitions() as $blockId => $defaults) {
            Voodbuilder::editorBlockConfig($blockId, ['defaults' => $defaults]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function definitions(): array
    {
        return [
            'landing_hero' => [
                'eyebrow' => __('Featured event'),
                'heading' => __('Your headline here'),
                'subheading' => __('Supporting copy for the landing hero section.'),
                'background_style' => 'solid',
                'text_align' => 'center',
                'tall' => true,
                'primary_button_label' => __('Learn more'),
                'primary_button_link_type' => 'url',
                'primary_button_link' => '#',
            ],
            'landing_feature_grid' => [
                'heading' => __('Why attend'),
                'columns' => '3',
                'items' => [
                    ['title' => __('Networking'), 'description' => __('Meet industry professionals from around the world.')],
                    ['title' => __('Live demos'), 'description' => __('Experience products hands-on on the show floor.')],
                    ['title' => __('Education'), 'description' => __('Learn from talks, panels, and masterclasses.')],
                ],
            ],
            'landing_stats' => [
                'heading' => '',
                'items' => [
                    ['value' => '20,000+', 'label' => __('Visitors')],
                    ['value' => '400+', 'label' => __('Brands')],
                    ['value' => '30+', 'label' => __('Markets')],
                ],
            ],
            'landing_split' => [
                'eyebrow' => __('About'),
                'heading' => __('Split content section'),
                'body' => __('Use this block for storytelling with image and copy side by side.'),
                'image_position' => 'right',
            ],
            'landing_steps' => [
                'heading' => __('How it works'),
                'items' => [
                    ['title' => __('Step 1'), 'description' => __('Register online.')],
                    ['title' => __('Step 2'), 'description' => __('Plan your visit.')],
                    ['title' => __('Step 3'), 'description' => __('Join the show.')],
                ],
            ],
            'landing_text' => [
                'heading' => __('Section heading'),
                'body' => __('Supporting paragraph for a simple text band.'),
                'text_align' => 'center',
                'width' => 'wide',
            ],
            'landing_banner_cta' => [
                'heading' => __('Ready to join?'),
                'subheading' => __('Book your place today.'),
                'background_tone' => 'brand',
            ],
            'landing_faq' => [
                'heading' => __('FAQ'),
                'items' => [
                    ['question' => __('When does the event start?'), 'answer' => __('Check the schedule page for dates and times.')],
                    ['question' => __('Where is the venue?'), 'answer' => __('Venue details are on the event page.')],
                ],
            ],
            'landing_video' => [
                'heading' => __('Watch the recap'),
            ],
            'landing_social_share' => [
                'heading' => __('Share this page'),
            ],
            'landing_footer' => [
                'variant' => 'a',
                'brand_name' => __('VoodBuilder'),
                'brand_tagline' => __('Build beautiful pages with VoodBuilder'),
                'copyright_brand' => 'VoodBuilder',
                'copyright_year' => now()->year,
            ],
            'landing_navbar' => [
                'variant' => 'a',
                'brand_name' => __('VoodBuilder'),
                'cta_label' => __('Button'),
                'cta_url' => '#',
            ],
        ];
    }
}
