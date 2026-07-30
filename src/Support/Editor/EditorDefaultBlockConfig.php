<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Facades\Schema;
use Voodflow\Vevents\Models\Event;

/**
 * Editor Default Block Config.
 */
final class EditorDefaultBlockConfig
{
    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     * @return array<string, mixed>
     */
    public static function for(string $blockClass, ?int $eventId = null): array
    {
        $blockId = $blockClass::getId();
        $eventId ??= self::firstPublishedEventId();

        $defaults = self::defaults();

        $config = $defaults[$blockId] ?? [];

        if ($config === [] && str_starts_with($blockId, 'event_landing_')) {
            $landingId = 'landing_'.substr($blockId, strlen('event_landing_'));
            $config = $defaults[$landingId] ?? [];
        }

        if ($eventId > 0 && self::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        return $config;
    }

    public static function needsEventId(string $blockId): bool
    {
        return in_array($blockId, [
            'event_details',
            'event_exhibitors_grid',
            'event_program_summary',
            'event_landing_footer',
            'landing_footer',
            'vexhibitor_directory_cta',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function mergeEventId(array $config, string $blockId, ?int $eventId): array
    {
        if ($eventId > 0 && self::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        return $config;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'upcoming_events' => [
                'limit' => 6,
                'columns' => 3,
            ],
            'latest_vtuts' => [
                'limit' => 6,
                'columns' => 3,
            ],
            'event_details' => [
                'heading' => __('Event details'),
            ],
            'event_exhibitors_grid' => [
                'limit' => 12,
                'featured_only' => false,
            ],
            'event_program_summary' => [
                'title' => __('Program'),
            ],
            'event_stats_counters' => [
                'heading' => '',
                'items' => [
                    ['value' => '20,000+', 'label' => __('Visitors')],
                    ['value' => '400+', 'label' => __('Exhibitors')],
                    ['value' => '30+', 'label' => __('Countries')],
                ],
            ],
            'event_charts' => [
                'title' => __('Audience mix'),
                'segments' => [
                    __('Dealers') => '35',
                    __('Creators') => '25',
                    __('Musicians') => '40',
                ],
            ],
            'event_testimonials' => [
                'heading' => __('What exhibitors say'),
            ],
            'event_partners' => [
                'heading' => __('Partners'),
            ],
            'event_video' => [
                'heading' => __('Highlights'),
            ],
            'event_cta' => [
                'heading' => __('Join us'),
                'subheading' => __('Register for the next edition.'),
                'background_style' => 'brand',
                'text_align' => 'center',
                'button_label' => __('Get tickets'),
                'button_url' => '#',
                'button_style' => 'solid',
            ],
            'vexhibitor_directory_cta' => [
                'eyebrow' => __('Already exhibiting?'),
                'heading' => __('Browse the exhibitor directory'),
                'body' => __('Explore brands confirmed for the upcoming edition.'),
                'button_label' => __('View exhibitors'),
                'action_link_type' => 'exhibitor_listing',
            ],
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
                'section_width' => 'bleed',
                'section_padding' => 'large',
            ],
            'event_landing_hero' => [
                'eyebrow' => __('Featured event'),
                'heading' => __('Your event headline'),
                'subheading' => __('Supporting copy for the landing hero section.'),
                'background_style' => 'solid',
                'text_align' => 'center',
                'tall' => true,
                'primary_button_label' => __('Get tickets'),
                'primary_button_link_type' => 'url',
                'primary_button_link' => '#',
                'section_width' => 'bleed',
                'section_padding' => 'large',
            ],
            'landing_feature_grid' => [
                'heading' => __('Why attend'),
                'columns' => '3',
                'items' => [
                    ['title' => __('Networking'), 'description' => __('Meet industry professionals from around the world.')],
                    ['title' => __('Live demos'), 'description' => __('Experience products hands-on on the show floor.')],
                    ['title' => __('Education'), 'description' => __('Learn from talks, panels, and masterclasses.')],
                ],
                'section_width' => 'contained',
                'section_padding' => 'large',
            ],
            'landing_stats' => [
                'heading' => '',
                'items' => [
                    ['value' => '20,000+', 'label' => __('Visitors')],
                    ['value' => '400+', 'label' => __('Brands')],
                    ['value' => '30+', 'label' => __('Markets')],
                ],
                'section_width' => 'contained',
                'section_padding' => 'default',
            ],
            'landing_split' => [
                'eyebrow' => __('About'),
                'heading' => __('Split content section'),
                'body' => __('Use this block for storytelling with image and copy side by side.'),
                'image_position' => 'right',
                'section_width' => 'contained',
                'section_padding' => 'large',
            ],
            'landing_steps' => [
                'heading' => __('How it works'),
                'items' => [
                    ['title' => __('Step 1'), 'description' => __('Register online.')],
                    ['title' => __('Step 2'), 'description' => __('Plan your visit.')],
                    ['title' => __('Step 3'), 'description' => __('Join the show.')],
                ],
                'section_width' => 'contained',
                'section_padding' => 'default',
            ],
            'landing_text' => [
                'heading' => __('Section heading'),
                'body' => __('Supporting paragraph for a simple text band.'),
                'text_align' => 'center',
                'width' => 'wide',
                'section_width' => 'contained',
                'section_padding' => 'default',
            ],
            'landing_banner_cta' => [
                'heading' => __('Ready to join?'),
                'subheading' => __('Book your place today.'),
                'background_tone' => 'brand',
                'section_width' => 'bleed',
                'section_padding' => 'large',
            ],
            'landing_faq' => [
                'heading' => __('FAQ'),
                'items' => [
                    ['question' => __('When does the event start?'), 'answer' => __('Check the schedule page for dates and times.')],
                    ['question' => __('Where is the venue?'), 'answer' => __('Venue details are on the event page.')],
                ],
                'section_width' => 'contained',
                'section_padding' => 'default',
            ],
            'landing_video' => [
                'heading' => __('Watch the recap'),
                'section_width' => 'contained',
                'section_padding' => 'default',
            ],
            'landing_social_share' => [
                'heading' => __('Share this page'),
                'section_width' => 'contained',
                'section_padding' => 'default',
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
            'event_landing_footer' => [
                'heading' => __('Stay in touch'),
                'section_width' => 'contained',
            ],
        ];
    }

    public static function firstPublishedEventId(): ?int
    {
        if (! class_exists(Event::class)) {
            return null;
        }

        $table = (new Event)->getTable();

        if (! Schema::hasTable($table)) {
            return null;
        }

        $id = Event::query()->published()->orderBy('starts_at')->value('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
