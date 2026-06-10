<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Vpress',
        'menus' => 'Menus',
        'pages' => 'Pages',
        'settings' => 'Settings',
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
    ],

    'helpers' => [
        'menu_route' => 'Public GET routes registered in your app. The active state is set automatically.',
        'menu_route_match' => 'Optional. Used only for external URLs when you need custom highlight rules.',
        'sub_theme_site' => 'Default visual style for the public site. Individual pages can override this.',
        'sub_theme_page' => 'Override the site sub-theme for this page — useful for blog or news sections linked from the menu.',
        'excerpt' => 'Short summary for section listings, cards, and SEO.',
        'section_home' => 'Marks this page as the index for its section (lists sibling pages in the sidebar).',
    ],
];
