<?php

declare(strict_types=1);
use Voodflow\Vpress\Support\DefaultHomeContent;
use Voodflow\Vpress\Support\VpressPaths;

return [
    'site_title' => env('APP_NAME', 'Laravel'),

    'layouts' => [
        'app' => 'vpress::layouts.app',
        'doc' => 'vpress::layouts.doc',
        'home' => 'vpress::layouts.home',
        'page' => 'vpress::layouts.page',
    ],

    /*
    | Visual sub-themes (distinct from light/dark mode).
    | Assign a default in Admin → Site → Settings, or per page in Pages.
    | Register custom themes with Vpress::subTheme() or php artisan vpress:make-subtheme.
    */
    'sub_themes' => [
        'default' => [
            'label' => 'Documentation',
            'description' => 'VitePress-style layout for docs and marketing pages.',
        ],
        'blog' => [
            'label' => 'Blog',
            'description' => 'Ghost-inspired centered blog with serif headlines.',
            'layouts' => [
                'home' => 'vpress::sub-themes.blog.layouts.home',
                'page' => 'vpress::sub-themes.blog.layouts.page',
                'section_index' => 'vpress::sub-themes.blog.layouts.section-index',
                'article' => 'vpress::sub-themes.blog.layouts.article',
            ],
            'css' => 'sub-themes/blog.css',
        ],
        'news' => [
            'label' => 'News',
            'description' => 'Editorial news layout with bold headlines and wider columns.',
            'layouts' => [
                'home' => 'vpress::sub-themes.news.layouts.home',
                'page' => 'vpress::sub-themes.news.layouts.page',
                'section_index' => 'vpress::sub-themes.news.layouts.section-index',
                'article' => 'vpress::sub-themes.news.layouts.article',
            ],
            'css' => 'sub-themes/news.css',
        ],
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
        'vite' => VpressPaths::defaultViteEntries(),
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
        'vtuts_url' => env('VPRESS_VTUTS_URL', 'https://filamentphp.com/plugins/voodflow-vtuts'),
        'vdocs_url' => env('VPRESS_VDOCS_URL', 'https://filamentphp.com/plugins/voodflow-vdocs'),
        'voodflow_url' => env('VPRESS_VOODFLOW_URL', 'https://filamentphp.com/plugins/voodflow-voodflow'),
    ],

    'home' => [
        'route_enabled' => true,
        'fallback_view' => 'vpress::pages.welcome',
        'fallback_seo' => [
            'title' => null,
            'description' => null,
        ],
        'default_content_callback' => [DefaultHomeContent::class, 'content'],
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
    | External content systems (blog, news, shop, …) register here or via Vpress::contentChannel().
    | Each channel can define route patterns (menu highlight + sub-theme) and optional search.
    |
    | Example:
    | 'blog' => [
    |     'label' => 'Blog',
    |     'routes' => ['blog.*'],
    |     'sub_theme' => 'blog',
    |     'search' => \App\Models\BlogPost::class, // static vpressSearch($term, $limit) method
    | ],
    */
    'content_channels' => [
        //
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
