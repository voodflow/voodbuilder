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
        'landing' => 'vpress::layouts.landing',
        'page' => 'vpress::layouts.page',
    ],

    /*
    | Visual themes (distinct from light/dark mode).
    | Bundled themes live in packages/voodflow/vpress/resources/themes/{id}/.
    | App themes from `php artisan vpress:make-subtheme` live in resources/vpress/themes/{id}/.
    | See Voodflow\Vpress\Support\ThemeConvention.
    */
    'sub_themes' => [
        'default' => [
            'label' => 'Documentation',
            'description' => 'VitePress-style layout for docs and tutorials.',
            'type' => 'content',
            'capabilities' => ['doc'],
        ],
        'site' => [
            'label' => 'Site',
            'description' => 'Public site layout with optional dark header, landing pages, and card grids.',
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'layouts' => [
                'home' => 'vpress::themes.site.layouts.home',
                'landing' => 'vpress::themes.site.layouts.landing',
                'page' => 'vpress::themes.site.layouts.page',
            ],
            'css' => 'themes/site/theme.css',
        ],
    ],

    /*
    | Required visual capability per content channel (route package area).
    | Used to filter compatible themes in Admin → Settings → Appearance.
    */
    'content_channel_capabilities' => [
        'docs' => 'doc',
        'tutorials' => 'doc',
        'blog' => 'article',
        'news' => 'article',
        'events' => 'landing',
        'exhibitors' => 'landing',
        'pages' => 'landing',
    ],

    /*
    | Default visual theme per content channel (route package area).
    | Overridable in Admin → Settings → Appearance. Plugins do not ship themes.
    */
    'content_channel_defaults' => [
        'events' => 'site',
        'exhibitors' => 'site',
        'tutorials' => 'default',
        'docs' => 'default',
    ],

    /*
    | Legacy blog/news Site Page demos (section field). Disabled by default.
    | Real blog content should come from a dedicated package (e.g. vblog).
    */
    'demo_site_sections' => (bool) env('VPRESS_DEMO_SITE_SECTIONS', false),

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

    /*
    | Spatie permission for frontend GrapesJS editing without Filament admin access.
    | Seeded by VpressSeeder when spatie/laravel-permission is installed.
    */
    'permissions' => [
        'page_builder' => 'builder',
        'page_builder_roles' => ['editor', 'super_admin'],
    ],

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
        'vpress_url' => env('VPRESS_URL', 'https://filamentphp.com/plugins/voodflow-vpress'),
        'github_url' => env('VPRESS_GITHUB_URL', 'https://github.com/voodflow/vpress'),
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
    |     'search' => \App\Models\BlogPost::class, // static vpressSearch($term, $limit) method
    | ],
    | Default visual theme per channel: content_channel_defaults (not on the channel array).
    */
    'content_channels' => [
        //
    ],

    /*
    | GrapesJS visual page builder (vpress-pro).
    | Requires npm packages in the host app: grapesjs, grapesjs-blocks-basic.
    | Add VpressPaths::grapesJsViteEntry() to vite.config.js input, then npm run build.
    */
    'grapesjs' => [
        'enabled' => env('VPRESS_GRAPESJS_ENABLED', true),
        'vite' => VpressPaths::grapesJsViteEntry(),
        'canvas_styles' => VpressPaths::grapesJsCanvasStyleEntries(),
        'upload' => [
            'disk' => 'public',
            'directory' => 'vpress/grapesjs',
            'max_size' => 4096,
        ],
        'include_vpress_blocks' => true,
        'include_landing_blocks' => true,
        'tailblocks' => [
            'enabled' => env('VPRESS_TAILBLOCKS_ENABLED', true),
            'theme' => env('VPRESS_TAILBLOCKS_THEME', 'indigo'),
            'modes' => ['adaptive'],
        ],
        'payload' => [
            'max_html_bytes' => 500_000,
            'max_css_bytes' => 100_000,
            'max_project_bytes' => 2_000_000,
        ],
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
