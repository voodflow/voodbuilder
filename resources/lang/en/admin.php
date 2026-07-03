<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'menus' => 'Menus',
        'pages' => 'Pages',
        'settings' => 'Settings',
        'content_channels' => 'Channel themes',
    ],

    'fields' => [
        'menu_route' => 'App route',
        'menu_route_match' => 'Active route pattern',
        'sub_theme' => 'Visual theme',
        'sub_theme_inherit' => 'Site default',
        'layout_auto' => 'Automatic',
        'layout_standard' => 'Standard page',
        'layout_full_width' => 'Full width (marketing)',
        'excerpt' => 'Excerpt',
        'section' => 'Section',
        'section_none' => 'None',
        'section_home' => 'Section home',
        'sub_items' => 'Sub-items',
        'language' => 'Language',
        'target_language' => 'Target language',
        'translations' => 'Translations',
    ],

    'actions' => [
        'translate_page' => 'Create translation',
    ],

    'translation' => [
        'tooltip' => 'Duplicate this page for another language',
        'modal_heading' => 'Create page translation',
        'modal_description' => 'A draft copy will be created with the same layout and content. Update the title, slug, and body for the target language.',
        'none_yet' => 'No linked translations yet.',
    ],

    'notifications' => [
        'translation_created' => 'Translation created',
        'translation_created_body' => 'Edit the :locale version and publish when ready.',
        'home_reassigned' => 'Other home pages updated',
        'home_reassigned_body' => ':count other page(s) are no longer marked as home.',
    ],

    'home_takeover' => [
        'heading' => 'Change site home page?',
        'description' => 'These pages are currently set as home and will be turned off: :pages. Translations of the same page in other languages can stay home.',
        'confirm' => 'Yes, use this page',
    ],

    'validation' => [
        'menu_max_depth' => 'Menus support at most :max levels (top-level item and sub-items). Move the item under a top-level entry instead.',
    ],

    'filters' => [
        'any' => 'Any',
        'yes' => 'Yes',
        'no' => 'No',
        'is_home' => 'Home page',
        'published' => 'Published',
        'builder' => 'Content builder',
        'layout' => 'Layout',
        'sub_theme' => 'Sub-theme',
        'sub_theme_inherit' => 'Site default (inherit)',
    ],

    'sections' => [
        'appearance' => 'Appearance (optional)',
    ],

    'menu_preview' => [
        'title' => 'Menu preview',
        'heading' => 'Live preview',
        'description' => 'See how this menu looks in the site header or footer. Save menu items to refresh the preview. In the page editor use the Site → Site header (menu) block.',
        'badge' => 'Preview: :menu',
        'iframe_title' => 'Navigation menu preview',
        'empty' => 'No menu items yet.',
        'standalone_help' => 'Preview of links for this menu placement.',
    ],

    'helpers' => [
        'menu_route' => 'Public GET routes registered in your app. The active state is set automatically. If the route needs parameters, fill in the fields shown below.',
        'menu_route_match' => 'Optional. Used only for external URLs when you need custom highlight rules.',
        'menu_sub_items' => 'Shown in a dropdown like Docs. Use "Dropdown group" for section titles without their own link.',
        'menu_tree' => 'Drag to reorder or drop an item onto another to create a submenu. Maximum 2 levels (top-level and sub-items). Use "Dropdown group" for labels without a link.',
        'sub_theme_site' => 'Default visual theme for Site Pages (home, landing, CMS). Configure per-area bindings below.',
        'sub_theme_page' => 'Leave “Site default” to use :theme from Settings → Theme. Change only when this single page needs a different look.',
        'excerpt' => 'Short summary for section listings, cards, and SEO.',
        'section_home' => 'Marks this page as the index for its section (lists sibling pages in the sidebar).',
        'home_page_locale' => 'One home per language. Linked translations (same page, other locales) can all be home. Turning on a new home disables other pages.',
        'home_page_layout' => 'Home pages are served at /. Choose “Automatic” unless you need a different layout.',
        'layout_auto_home' => 'Uses full width edge-to-edge (recommended for the site home).',
        'layout_auto_page' => 'Uses a standard contained page width.',
        'sub_theme_marketing_recommended' => 'Full-width pages work best with a marketing theme (Site or your custom theme).',
    ],
];
