<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Vpress',
        'menus' => 'Menus',
        'pages' => 'Pages',
        'settings' => 'Settings',
        'content_channels' => 'Channel themes',
    ],

    'fields' => [
        'menu_route' => 'App route',
        'menu_route_match' => 'Active route pattern',
        'sub_theme' => 'Sub-theme',
        'sub_theme_inherit' => 'Site default',
        'excerpt' => 'Excerpt',
        'section' => 'Section',
        'section_none' => 'None',
        'section_home' => 'Section home',
        'sub_items' => 'Sub-items',
    ],

    'validation' => [
        'menu_max_depth' => 'Menus support at most :max levels (top-level item and sub-items). Move the item under a top-level entry instead.',
    ],

    'helpers' => [
        'menu_route' => 'Public GET routes registered in your app. The active state is set automatically. If the route needs parameters, fill in the fields shown below.',
        'menu_route_match' => 'Optional. Used only for external URLs when you need custom highlight rules.',
        'menu_sub_items' => 'Shown in a dropdown like Docs. Use "Dropdown group" for section titles without their own link.',
        'menu_tree' => 'Drag to reorder or drop an item onto another to create a submenu. Maximum 2 levels (top-level and sub-items). Use "Dropdown group" for labels without a link.',
        'sub_theme_site' => 'Default visual style for marketing Site Pages (home, landing, CMS). Content from installed packages (docs, tutorials, events) uses Channel themes instead.',
        'sub_theme_page' => 'Override the site default for this marketing page. Use GrapesJS or the rich editor to build the content.',
        'excerpt' => 'Short summary for section listings, cards, and SEO.',
        'section_home' => 'Marks this page as the index for its section (lists sibling pages in the sidebar).',
    ],
];
