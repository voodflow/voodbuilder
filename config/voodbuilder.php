<?php

declare(strict_types=1);
use Voodflow\Voodbuilder\Support\DefaultHomeContent;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

return [
    'site_title' => env('VOODBUILDER_SITE_TITLE', env('APP_NAME', 'VoodBuilder')),

    /*
    | Internal module enablement (same package; physical splits come later).
    */
    'modules' => [
        'history' => [
            'enabled' => env('VOODBUILDER_MODULE_HISTORY', true),
        ],
        'conditions' => [
            'enabled' => env('VOODBUILDER_MODULE_CONDITIONS', true),
        ],
        'templates' => [
            'enabled' => env('VOODBUILDER_MODULE_TEMPLATES', true),
        ],
        'themes' => [
            'enabled' => env('VOODBUILDER_MODULE_THEMES', true),
        ],
        'menus' => [
            'enabled' => env('VOODBUILDER_MODULE_MENUS', true),
        ],
        'layouts' => [
            'enabled' => env('VOODBUILDER_MODULE_LAYOUTS', true),
        ],
        'pages' => [
            'enabled' => env('VOODBUILDER_MODULE_PAGES', true),
        ],
        'dynamic_data' => [
            'enabled' => env('VOODBUILDER_MODULE_DYNAMIC_DATA', true),
        ],
        'components' => [
            'enabled' => env('VOODBUILDER_MODULE_COMPONENTS', true),
        ],
        'popups' => [
            'enabled' => env('VOODBUILDER_MODULE_POPUPS', true),
        ],
    ],

    'layouts' => [
        'app' => 'voodbuilder::layouts.app',
        'chrome_app' => 'voodbuilder::layouts.chrome-app',
        'doc' => 'voodbuilder::layouts.doc',
        'full_width' => 'voodbuilder::layouts.full-width',
        'home' => 'voodbuilder::layouts.home',
        'landing' => 'voodbuilder::layouts.landing',
        'page' => 'voodbuilder::layouts.page',
    ],

    /*
    | Visual themes (distinct from light/dark mode).
    | Bundled themes live in packages/voodflow/voodbuilder/resources/themes/{id}/.
    | App themes from `php artisan voodbuilder:make-subtheme` live in resources/voodbuilder/themes/{id}/.
    | See Voodflow\Voodbuilder\Support\ThemeConvention.
    */
    'sub_themes' => [
        'docs' => [
            'label' => 'Documentation',
            'description' => 'Clean reading layout for documentation and tutorials.',
            'type' => 'content',
            'capabilities' => ['doc'],
        ],
        'site' => [
            'label' => 'Landing page',
            'description' => 'Marketing pages, home, and site sections with hero blocks.',
            'type' => 'marketing',
            'capabilities' => ['landing'],
            'css' => 'themes/site/theme.css',
            'layouts' => [
                'home' => 'voodbuilder::themes.site.layouts.home',
                'landing' => 'voodbuilder::themes.site.layouts.landing',
                'page' => 'voodbuilder::themes.site.layouts.page',
            ],
            'chrome' => [
                'hide_site_nav' => false,
                'hide_site_footer' => false,
            ],
        ],
        'blog' => [
            'label' => 'Blog',
            'description' => 'Ghost-inspired reading layout for blog archives and posts.',
            'type' => 'content',
            'capabilities' => ['article'],
            'css' => 'themes/blog/theme.css',
            'layouts' => [
                'home' => 'voodbuilder::themes.blog.layouts.home',
                'landing' => 'voodbuilder::themes.blog.layouts.landing',
                'page' => 'voodbuilder::themes.blog.layouts.page',
                'article' => 'voodbuilder::themes.blog.layouts.article',
                'section_index' => 'voodbuilder::themes.blog.layouts.section-index',
            ],
        ],
        'news' => [
            'label' => 'News',
            'description' => 'Editorial magazine layout with story grids and sidebars.',
            'type' => 'content',
            'capabilities' => ['article'],
            'css' => 'themes/news/theme.css',
            'layouts' => [
                'home' => 'voodbuilder::themes.news.layouts.home',
                'landing' => 'voodbuilder::themes.news.layouts.landing',
                'page' => 'voodbuilder::themes.news.layouts.page',
                'article' => 'voodbuilder::themes.news.layouts.article',
                'section_index' => 'voodbuilder::themes.news.layouts.section-index',
            ],
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
        'tutorials' => 'docs',
        'docs' => 'docs',
        'blog' => 'blog',
        'news' => 'news',
    ],

    /*
    | Legacy blog/news Site Page demos (section field). Disabled by default.
    | Real blog content should come from a dedicated package (e.g. vblog).
    */
    'demo_site_sections' => (bool) env('VOODBUILDER_DEMO_SITE_SECTIONS', false),

    /*
    | Fallback logo path (relative to the public disk) or absolute URL.
    | Prefer uploading the logo in Admin → Site → Settings.
    */
    'logo' => null,

    'logo_upload' => [
        'disk' => 'public',
        'directory' => 'voodbuilder',
        'max_size' => 2048,
    ],

    'uploads' => [
        'disk' => 'public',
        'directory' => 'voodbuilder',
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
        'redirect_after_login' => 'voodbuilder.account',
        'registered_role' => 'registered',
    ],

    'footer' => [
        'enabled' => true,
    ],

    'admin_panel_id' => 'admin',

    /*
    | Spatie permission for frontend GrapesJS editing without Filament admin access.
    | Seeded by VoodbuilderSeeder when spatie/laravel-permission is installed.
    */
    'permissions' => [
        'page_builder' => 'builder',
        'page_builder_roles' => ['editor', 'super_admin'],
    ],

    'assets' => [
        'vite' => VoodbuilderPaths::defaultViteEntries(),
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
        'voodbuilder_url' => env('VOODBUILDER_URL', 'https://filamentphp.com/plugins/voodflow-voodbuilder'),
        'github_url' => env('VOODBUILDER_GITHUB_URL', 'https://github.com/voodflow/voodbuilder'),
        'vtuts_url' => env('VOODBUILDER_VTUTS_URL', 'https://filamentphp.com/plugins/voodflow-vtuts'),
        'vdocs_url' => env('VOODBUILDER_VDOCS_URL', 'https://filamentphp.com/plugins/voodflow-vdocs'),
        'voodflow_url' => env('VOODBUILDER_VOODFLOW_URL', 'https://filamentphp.com/plugins/voodflow-voodflow'),
    ],

    'home' => [
        'route_enabled' => true,
        'fallback_view' => 'voodbuilder::pages.welcome',
        'fallback_seo' => [
            'title' => null,
            'description' => null,
        ],
        'default_content_callback' => [DefaultHomeContent::class, 'content'],
    ],

    'pages' => [
        'enabled' => true,
        'route_prefix' => 'pages',
        /*
        | When true, new pages use GrapesJS only (no Rich editor toggle in admin).
        | Existing rich-editor pages stay editable until converted manually.
        */
        'grapesjs_only' => env('VOODBUILDER_PAGES_GRAPESJS_ONLY', false),
        'default_builder' => env('VOODBUILDER_PAGES_DEFAULT_BUILDER', 'grapesjs'),
        /*
        | Per-page sub-theme override in Admin → Pages. Channel defaults live in Settings.
        */
        'allow_sub_theme_override' => env('VOODBUILDER_PAGES_ALLOW_SUB_THEME_OVERRIDE', true),
    ],

    'popups' => [
        'enabled' => env('VOODBUILDER_POPUPS_ENABLED', true),
        // null = use site default sub-theme (Settings → Appearance), same as page/layout editors.
        'editor_sub_theme' => env('VOODBUILDER_POPUPS_EDITOR_SUB_THEME'),
    ],

    'page_templates' => [
        'catalog_url' => env('VOODBUILDER_PAGE_TEMPLATE_CATALOG_URL'),
    ],

    'search' => [
        'enabled' => true,
        'route' => 'search',
        'per_type' => 20,
    ],

    /*
    | External content systems (blog, news, shop, …) register here or via Voodbuilder::contentChannel().
    | Each channel can define route patterns (menu highlight + sub-theme) and optional search.
    |
    | Example:
    | 'blog' => [
    |     'label' => 'Blog',
    |     'routes' => ['blog.*'],
    |     'search' => \App\Models\BlogPost::class, // static voodbuilderSearch($term, $limit) method
    | ],
    | Default visual theme per channel: content_channel_defaults (not on the channel array).
    */
    'content_channels' => [
        //
    ],

    /*
    | GrapesJS chrome layouts — header/footer shell around plugin content.
    */
    'chrome_layouts' => [
        'enabled' => env('VOODBUILDER_CHROME_LAYOUTS_ENABLED', true),
        // Prefer null: unassigned layouts use the pages-channel / site-default theme.
        'editor_sub_theme' => env('VOODBUILDER_CHROME_LAYOUTS_EDITOR_SUB_THEME'),
        'shell_sub_theme' => env('VOODBUILDER_CHROME_LAYOUTS_SHELL_SUB_THEME'),
    ],

    /*
    | GrapesJS visual page builder (voodbuilder-pro).
    | Requires npm packages in the host app: grapesjs, grapesjs-blocks-basic.
    | Add VoodbuilderPaths::grapesJsViteEntry() to vite.config.js input, then npm run build.
    */
    'grapesjs' => [
        'enabled' => env('VOODBUILDER_GRAPESJS_ENABLED', true),
        'vite' => VoodbuilderPaths::grapesJsViteEntry(),
        'canvas_styles' => VoodbuilderPaths::grapesJsCanvasStyleEntries(),
        'upload' => [
            'disk' => 'public',
            // Public files land under storage/app/public/voodbuilder (URL /storage/voodbuilder/…).
            'directory' => 'voodbuilder',
            // Soft ceiling; the image editor re-encodes to JPEG and retries at lower quality.
            'max_size' => (int) env('VOODBUILDER_GRAPESJS_UPLOAD_MAX_KB', 8192),
        ],
        /*
        | In-canvas image editor (@jodit/image-editor, MIT). Requires the host app
        | npm dependency `@jodit/image-editor`. Disable with env or config.
        */
        'image_editor' => env('VOODBUILDER_GRAPESJS_IMAGE_EDITOR', true),
        'include_voodbuilder_blocks' => false,
        'include_landing_blocks' => false,
        'site_blocks' => [
            'header_footer' => true,
            'hide_section_chrome' => true,
        ],
        'voodbuilder_footers' => [
            'enabled' => true,
        ],
        'sections' => [
            'enabled' => env('VOODBUILDER_SECTIONS_ENABLED', true),
            'modes' => ['adaptive'],
            'theme' => env('VOODBUILDER_SECTION_THEME', 'indigo'),
        ],
        /*
        | Block IDs to hide from the GrapesJS sidebar (runtime rendering still works).
        | Example: latest_vtuts — use Dynamic bindings in the inspector instead.
        | Redundant section layouts (item-count siblings) are also filtered in
        | SectionItemCountAnnotator::REDUNDANT_BLOCK_IDS.
        */
        'excluded_editor_blocks' => [
            'latest_vtuts',
        ],
        'builder' => [
            'brand' => env('VOODBUILDER_BUILDER_BRAND', 'VoodBuilder'),
        ],
        'model_integrations' => [
            'excluded_models' => [],
        ],
        'payload' => [
            'max_html_bytes' => 500_000,
            'max_css_bytes' => 100_000,
            'max_js_bytes' => 100_000,
            'max_project_bytes' => 2_000_000,
        ],
        'plugins' => [
            'forms' => env('VOODBUILDER_GRAPESJS_FORMS', true),
            'style_bg' => env('VOODBUILDER_GRAPESJS_STYLE_BG', true),
            'tabs' => env('VOODBUILDER_GRAPESJS_TABS', true),
            'custom_code' => env('VOODBUILDER_GRAPESJS_CUSTOM_CODE', false),
        ],
        'forms' => [
            'success_message' => 'Thank you. Your message has been received.',
            'newsletter_success_message' => 'Thank you for subscribing to our newsletter.',
        ],
        'newsletter_lists' => [
            'default' => 'Default newsletter',
        ],
        'revisions' => [
            'max_to_keep' => (int) env('VOODBUILDER_GRAPESJS_REVISIONS_MAX', 50),
        ],
        'component_categories' => [
            'General',
            'Hero',
            'Content',
            'Features',
            'Articles',
            'Gallery',
            'Stats',
            'Testimonials',
            'Team',
            'Steps',
            'Pricing',
            'CTA',
            'Contact',
            'Shop',
            'Header',
            'Footer',
            'Code',
        ],
    ],

    /*
    | Named routes offered when building navigation menu items (App route type).
    | Wildcard patterns exclude admin, Livewire, and other non-public endpoints.
    */
    /*
    | Host-app rich content blocks for the Filament page editor.
    | Plugins can also register blocks via Voodbuilder::richContentBlock() in their service provider.
    |
    | Example:
    | ['group' => 'Dynamic', 'class' => \App\Ink\LatestNewsBlock::class],
    */
    'rich_content_blocks' => [
        //
    ],

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
