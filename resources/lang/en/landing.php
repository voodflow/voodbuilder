<?php

declare(strict_types=1);

return [
    'learn_more' => 'Learn more',
    'video_unavailable' => 'Video unavailable. Check the YouTube URL in the block settings.',

    'blocks' => [
        'hero' => 'Hero',
        'banner_cta' => 'Banner CTA',
        'split' => 'Split image + text',
        'feature_grid' => 'Feature grid',
        'stats' => 'Stats counters',
        'logo_row' => 'Logo row',
        'steps' => 'Steps',
        'faq' => 'FAQ',
        'text' => 'Text section',
        'video' => 'Video (YouTube)',
        'social_share' => 'Social share',
        'footer' => 'Landing footer',
        'contact_cta' => 'Contact CTA',
    ],

    'fields' => [
        'heading' => 'Heading',
        'title' => 'Title',
        'description' => 'Description',
        'columns' => 'Columns',
        'eyebrow' => 'Eyebrow',
        'subheading' => 'Subheading',
        'intro' => 'Intro',
        'body' => 'Body',
        'background_style' => 'Background style',
        'background_tone' => 'Background color',
        'background_color' => 'Custom color',
        'background_image' => 'Background image',
        'background_image_url' => 'Background image URL',
        'overlay_opacity' => 'Overlay opacity (%)',
        'text_align' => 'Text alignment',
        'tall_hero' => 'Tall hero',
        'primary_button_label' => 'Primary button label',
        'primary_button_url' => 'Primary button URL',
        'secondary_button_label' => 'Secondary button label',
        'secondary_button_url' => 'Secondary button URL',
        'button_style' => 'Button style',
        'image_url' => 'Image URL',
        'image' => 'Image',
        'image_position' => 'Image position',
        'features' => 'Features',
        'icon' => 'Icon / emoji',
        'link_label' => 'Link label',
        'link_url' => 'Link URL',
        'faq_items' => 'Questions',
        'question' => 'Question',
        'answer' => 'Answer',
        'steps' => 'Steps',
        'stats' => 'Stats',
        'stat_value' => 'Value',
        'stat_label' => 'Label',
        'caption' => 'Caption',
        'video_url' => 'YouTube URL',
        'content_width' => 'Content width',
        'section_width' => 'Section width',
        'section_padding' => 'Section padding',
        'logos' => 'Logos',
        'logo_name' => 'Name (alt text)',
        'logo_image' => 'Logo image',
        'logo_image_url' => 'Logo image URL',
        'logo_grayscale' => 'Grayscale logos until hover',
    ],

    'helpers' => [
        'icon' => 'Optional emoji or short symbol shown above the title.',
        'youtube_url' => 'Paste a YouTube watch, share, or embed URL.',
        'section_width' => 'Bleed spans the full viewport width; contained keeps content centered.',
        'background_color' => 'Used when background color is set to Custom.',
        'background_image' => 'Upload a wide image for full-bleed hero sections.',
        'content_image' => 'Upload a photo or illustration for this section.',
    ],

    'tones' => [
        'brand' => 'Brand color',
        'dark' => 'Dark',
        'light' => 'Light',
        'custom' => 'Custom',
    ],

    'background_styles' => [
        'solid' => 'Solid color',
        'image' => 'Background image',
    ],

    'align' => [
        'center' => 'Center',
        'left' => 'Left',
    ],

    'image_position' => [
        'left' => 'Image left',
        'right' => 'Image right',
    ],

    'button_styles' => [
        'solid' => 'Solid',
        'outline' => 'Outline',
        'ghost' => 'Ghost',
    ],

    'content_width' => [
        'wide' => 'Wide',
        'narrow' => 'Narrow',
    ],

    'section_width' => [
        'bleed' => 'Full bleed (edge to edge)',
        'full' => 'Full width with padding',
        'contained' => 'Contained (layout max width)',
        'narrow' => 'Narrow column',
    ],

    'section_padding' => [
        'default' => 'Default',
        'large' => 'Large',
        'none' => 'None',
    ],

    'social' => [
        'default_heading' => 'Share this site',
        'share_url' => 'Share URL',
        'share_url_help' => 'Leave empty to use the current page URL.',
        'share_title' => 'Share title',
        'share_title_help' => 'Used for X, WhatsApp, and email. Defaults to the site title.',
        'networks_label' => 'Social networks',
        'copied' => 'Copied!',
        'networks' => [
            'facebook' => 'Facebook',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'copy_link' => 'Copy link',
        ],
    ],

    'footer' => [
        'logo' => 'Footer logo',
        'logo_help' => 'Upload a white or light logo for dark landing footers.',
        'brand_name' => 'Brand name',
        'brand_name_help' => 'Shown when no logo is uploaded.',
        'organizer' => 'Organizer',
        'organizer_help' => 'Pulls contact lines from the selected organizer. On event pages the current event organizer is used when empty.',
        'organizer_legal' => 'Legal / VAT line',
        'menu_placement' => 'Footer menu',
        'menu_placement_help' => 'Manage columns in Admin → Menus with placement “Landing footer columns”. Each top-level group becomes a column.',
        'default_menu' => 'Landing footer columns',
        'organizer_line_1' => 'Contact line 1',
        'organizer_line_2' => 'Contact line 2',
        'organizer_email' => 'Contact email',
        'menu_columns' => 'Menu columns',
        'menu_columns_help' => 'Up to 4 optional columns (e.g. Contacts, About, Exhibitors, Visitors).',
        'menu_links' => 'Links',
        'open_in_new_tab' => 'Open in new tab',
        'copyright_year' => 'Copyright year',
        'copyright_brand' => 'Copyright brand',
        'copyright_claim' => 'Copyright claim',
        'copyright_highlight' => 'Highlighted claim',
        'copyright_highlight_help' => 'Shown in brand accent color (e.g. partner name).',
        'copyright_links' => 'Inline copyright links',
        'link_highlight' => 'Accent color',
    ],

    'link' => [
        'type' => 'Link type',
    ],

    'contact' => [
        'intro' => 'Intro text',
        'highlight_phrase' => 'Highlighted phrase',
        'display_style' => 'Primary action',
        'display_email' => 'Large email link',
        'display_button' => 'Button',
        'email' => 'Email address',
        'link_type' => 'Link type',
        'link_url' => 'URL',
        'link_email' => 'Email',
        'link_target' => 'Link target',
        'link_target_help' => 'Full URL or email address depending on link type.',
    ],

    'layouts' => [
        'landing' => 'Landing (no margins)',
        'landing_help' => 'Full-width canvas for landing blocks. Hide the site footer when using a landing footer block.',
        'hide_site_footer' => 'Hide site footer',
        'hide_site_footer_help' => 'Use when the page includes a landing footer block.',
    ],
];
