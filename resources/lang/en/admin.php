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
    ],

    'helpers' => [
        'menu_route' => 'Public GET routes registered in your app. The active state is set automatically.',
        'menu_route_match' => 'Optional. Used only for external URLs when you need custom highlight rules.',
    ],
];
