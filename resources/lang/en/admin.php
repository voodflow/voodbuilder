<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'menus' => 'Menus',
        'pages' => 'Pages',
        'settings' => 'Settings',
        'content_channels' => 'Channel themes',
        'footer_column_placement' => 'Footer column :number',
        'link_display' => 'Link display',
        'link_display_help' => 'Applies to every link in this menu.',
        'link_display_icon_only' => 'Icon only',
        'link_display_icon_text' => 'Icon + text',
        'link_display_text_only' => 'Text only',
    ],

    'menu_placements' => [
        'helper' => 'Header menus appear in the site navigation. Footer column menus (1–4) power multi-column footers. Inline links and social icons are used by centered and social footer blocks.',
        'main' => 'Main navigation',
        'header_extra' => 'Header right links',
        'social' => 'Social icons',
        'footer_inline' => 'Inline footer links',
        'footer_column' => 'Footer column :number',
        'groups' => [
            'header' => 'Header',
            'footer_columns' => 'Footer columns',
            'footer' => 'Footer links & social',
        ],
    ],

    'fields' => [
        'menu_icon' => 'Tabler icon',
        'menu_route' => 'App route',
        'menu_route_match' => 'Active route pattern',
        'sub_theme' => 'Visual theme',
        'sub_theme_inherit' => 'Site default',
        'layout_auto' => 'Automatic',
        'canvas_width' => 'Content width',
        'layout_standard' => 'Standard page',
        'layout_full_width' => 'Full width (marketing)',
        'excerpt' => 'Excerpt',
        'section' => 'Section',
        'section_none' => 'None',
        'section_home' => 'Section home',
        'sub_items' => 'Sub-items',
        'language' => 'Language',
        'lang' => 'Translations',
        'target_language' => 'Target language',
        'translations' => 'Translations',
        'translations_to_delete' => 'Translations to delete',
        'menu_clone_name' => 'Menu name',
    ],

    'actions' => [
        'translate_page' => 'Create translation',
        'translate_menu' => 'Translate to…',
        'clone_menu' => 'Clone menu',
        'clone_menu_placement' => 'Copy to placement…',
        'delete_translations' => 'Delete translations',
        'actions' => 'Actions',
        'more' => 'Actions',
    ],

    'translation' => [
        'tooltip' => 'Duplicate this page for another language',
        'modal_heading' => 'Create page translation',
        'modal_description' => 'A draft copy will be created with the same layout and content. Update the title, slug, and body for the target language.',
        'none_yet' => 'No linked translations yet.',
        'delete_translations_heading' => 'Delete translations',
        'delete_translations_modal_description' => 'Select which translations to remove. The canonical row in the group is always kept.',
        'canonical_kept_notice' => 'Original kept: :locale — :title',
    ],

    'menu_translation' => [
        'tooltip' => 'Duplicate this menu for another language',
        'modal_heading' => 'Create menu translation',
        'modal_description' => 'A copy of all menu items will be created for the target language. Page links are mapped to linked page translations when available.',
        'none_yet' => 'No linked translations yet.',
    ],

    'menu_clone' => [
        'tooltip' => 'Duplicate this menu to another placement',
        'modal_heading' => 'Clone menu',
        'modal_description' => 'Creates an independent copy with the same items. Choose a new placement that is not already used for this language.',
    ],

    'notifications' => [
        'translation_created' => 'Translation created',
        'translation_created_body' => 'Edit the :locale version and publish when ready.',
        'menu_translation_created' => 'Menu translation created',
        'menu_translation_created_body' => 'Edit the :locale menu and adjust labels and links.',
        'menu_cloned' => 'Menu cloned',
        'home_reassigned' => 'Other home pages updated',
        'home_reassigned_body' => ':count other page(s) are no longer marked as home.',
        'translations_delete_none_selected' => 'Select at least one translation to delete.',
        'translations_deleted' => 'Translations deleted',
        'translations_deleted_body' => ':count translation(s) removed: :list',
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
        'menu_icon' => 'Tabler icon name (e.g. brand-facebook). Compatible with daljo25/filament-tabler-icons.',
        'menu_grapes_pages_only' => 'Only GrapesJS pages are listed. Use “Open visual editor” to edit the selected page on the site.',
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
