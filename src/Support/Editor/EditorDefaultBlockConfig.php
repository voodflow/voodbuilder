<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Facades\Schema;
use Voodflow\Vevents\Models\Event;
use Voodflow\Vevents\Support\EventRichContentContext;

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
        if (class_exists(EventRichContentContext::class)) {
            return in_array($blockId, EventRichContentContext::BLOCKS_NEEDING_EVENT_ID, true);
        }

        return in_array($blockId, [
            'event_details',
            'event_exhibitors_grid',
            'event_program_summary',
            'event_stats_counters',
            'event_cta_exhibitors',
            'event_cta_visitors',
            'event_cta_combined',
            'event_cta_partners',
            'event_cta_sponsors',
            'event_landing_footer',
            'landing_footer',
            'vexhibitor_directory_cta',
            'vexhibitor_grid',
            'vexhibitor_list',
            'vpartner_grid',
            'vpartner_list',
            'vsponsor_grid',
            'vsponsor_list',
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
                'show_days' => true,
                'show_map' => true,
                'show_program_link' => true,
            ],
            'event_exhibitors_grid' => [
                'heading' => __('Exhibitors'),
                'limit' => 12,
                'featured_only' => false,
                'columns' => 4,
            ],
            'event_program_summary' => [
                'title' => __('Program'),
            ],
            'event_stats_counters' => [
                'heading' => '',
                'columns' => 4,
                'items' => [
                    ['value' => '20,000+', 'label' => __('Visitors')],
                    ['value' => '400+', 'label' => __('Exhibitors')],
                    ['value' => '30+', 'label' => __('Countries')],
                ],
            ],
            'event_cta_exhibitors' => [
                'heading' => __('Become an exhibitor'),
                'subheading' => __('Join brands on the show floor.'),
                'button_label' => __('Exhibitor info'),
                'background_style' => 'solid',
                'background_tone' => 'brand',
                'text_align' => 'center',
                'button_style' => 'solid',
            ],
            'event_cta_visitors' => [
                'heading' => __('Visit the show'),
                'subheading' => __('Plan your visit and get tickets.'),
                'button_label' => __('Get tickets'),
                'visitors_url' => '#',
                'background_style' => 'solid',
                'background_tone' => 'brand',
                'text_align' => 'center',
                'button_style' => 'solid',
            ],
            'event_cta_combined' => [
                'heading' => __('Join the event'),
                'subheading' => __('Exhibit or attend — choose your path.'),
                'exhibitors_button_label' => __('Exhibitors'),
                'visitors_button_label' => __('Visitors'),
                'visitors_url' => '#',
                'background_style' => 'solid',
                'background_tone' => 'brand',
                'text_align' => 'center',
            ],
            'event_cta_partners' => [
                'heading' => __('Partner with us'),
                'subheading' => __('Grow with the community.'),
                'button_label' => __('Become a partner'),
                'background_style' => 'solid',
                'background_tone' => 'brand',
                'text_align' => 'center',
                'button_style' => 'solid',
            ],
            'event_cta_sponsors' => [
                'heading' => __('Sponsor the event'),
                'subheading' => __('Put your brand in front of the audience.'),
                'button_label' => __('Sponsorship options'),
                'background_style' => 'solid',
                'background_tone' => 'brand',
                'text_align' => 'center',
                'button_style' => 'solid',
            ],
            'vexhibitor_grid' => [
                'heading' => __('Exhibitors'),
                'columns' => 4,
                'limit' => 12,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'vexhibitor_list' => [
                'heading' => __('Exhibitors'),
                'columns' => 1,
                'limit' => 24,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'vpartner_grid' => [
                'heading' => __('Partners'),
                'columns' => 4,
                'limit' => 12,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'vpartner_list' => [
                'heading' => __('Partners'),
                'columns' => 1,
                'limit' => 24,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'vsponsor_grid' => [
                'heading' => __('Sponsors'),
                'columns' => 4,
                'limit' => 12,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'vsponsor_list' => [
                'heading' => __('Sponsors'),
                'columns' => 1,
                'limit' => 24,
                'featured_only' => false,
                'show_filters' => false,
            ],
            'event_landing_hero_video' => [
                'eyebrow' => __('Featured event'),
                'heading' => __('Your event headline'),
                'subheading' => __('Supporting copy for the video hero.'),
                'background_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'overlay_opacity' => 55,
                'text_align' => 'center',
                'tall' => true,
                'primary_button_label' => __('Get tickets'),
                'primary_button_link_type' => 'url',
                'primary_button_link' => '#',
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
                'items' => [
                    [
                        'quote' => __('The show floor was packed with the right audience — we booked meetings for months.'),
                        'author' => 'Alex Rivera',
                        'role' => __('Brand manager'),
                    ],
                    [
                        'quote' => __('Clear layout, strong program, and a community that actually buys.'),
                        'author' => 'Sam Chen',
                        'role' => __('Founder'),
                    ],
                    [
                        'quote' => __('Best ROI event of our year. Logistics and promotion were on point.'),
                        'author' => 'Jordan Lee',
                        'role' => __('Head of Sales'),
                    ],
                ],
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
            'event_landing_footer' => [
                'heading' => __('Stay in touch'),
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
