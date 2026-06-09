<?php

declare(strict_types=1);

return [
    'site_title' => env('APP_NAME', 'Laravel'),

    'layouts' => [
        'app' => 'vpress::layouts.app',
        'doc' => 'vpress::layouts.doc',
        'home' => 'vpress::layouts.home',
        'page' => 'vpress::layouts.page',
    ],

    /*
    | Fallback logo path (relative to the public disk) or absolute URL.
    | Prefer uploading the logo in Admin → Site → Settings.
    */
    'logo' => null,

    'logo_upload' => [
        'disk' => 'public',
        'directory' => 'vpress',
        'max_size' => 2048,
    ],

    'uploads' => [
        'disk' => 'public',
        'directory' => 'vpress',
        'max_size' => 2048,
        'social_max_size' => 4096,
    ],

    'notifications' => [
        'enabled' => true,
    ],

    'account' => [
        'enabled' => true,
        'route' => 'account',
        'avatar' => [
            'enabled' => true,
            'disk' => 'public',
            'directory' => 'avatars',
        ],
    ],

    'auth' => [
        'enabled' => true,
        'registration_enabled' => true,
        'redirect_after_login' => 'vpress.account',
        'registered_role' => 'registered',
    ],

    'footer' => [
        'enabled' => true,
    ],

    'admin_panel_id' => 'admin',

    'assets' => [
        'vite' => \Voodflow\Vpress\Support\VpressPaths::defaultViteEntries(),
    ],

    /*
    | Self-hosted fonts are bundled via Vite (see resources/css/fonts.css).
    | Install these npm devDependencies in the host app, then run npm run build:
    |
    |   npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono
    */
    'fonts' => [
        'sans' => 'Inter Variable',
        'mono' => 'JetBrains Mono',
    ],

    /*
    | Purchase / product pages for companion packages (used by the default home seeder).
    */
    'packages' => [
        'vtuts_url' => env('VPRESS_VTUTS_URL', 'https://github.com/voodflow/vtuts'),
        'vdocs_url' => env('VPRESS_VDOCS_URL', 'https://github.com/voodflow/vdocs'),
    ],

    'home' => [
        'route_enabled' => true,
        'fallback_view' => 'vpress::pages.welcome',
        'fallback_seo' => [
            'title' => null,
            'description' => null,
        ],
        'default_content_callback' => [\Voodflow\Vpress\Support\DefaultHomeContent::class, 'content'],
    ],

    'pages' => [
        'enabled' => true,
        'route_prefix' => 'pages',
    ],

    'search' => [
        'enabled' => true,
        'route' => 'search',
        'per_type' => 20,
    ],

    /*
    | Named routes offered when building navigation menu items (App route type).
    | Wildcard patterns exclude admin, Livewire, and other non-public endpoints.
    */
    'menus' => [
        'route_exclude_patterns' => [
            'filament.*',
            'livewire.*',
            'debugbar.*',
            'horizon.*',
            'telescope.*',
            'sanctum.*',
            'storage.*',
            'ignition.*',
            'vapor*',
            'cashier.*',
            'stripe.*',
            'password.*',
            'verification.*',
            'two-factor.*',
            'profile.*',
            'boost.*',
        ],
    ],
];
