<?php

declare(strict_types=1);
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Support\DefaultHomeContent;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

return [
    'site_title' => env('VOODBUILDER_SITE_TITLE', env('APP_NAME', 'VoodBuilder')),

    /*
    | Public marketing site linked from editor upsells (Components, Dynamics, Templates).
    */
    'marketing_url' => env('VOODBUILDER_MARKETING_URL', 'https://voodflow.com/voodbuilder'),

    /*
    | Admin Filament resource authorization (Pages, Menus, Chrome layouts).
    |
    | auto         — Shield / Spatie Permission present → named abilities
    |                (ViewAny:SitePage, …); otherwise any Filament panel user
    | permissions  — always named abilities (custom Gate / Shield)
    | panel        — always Filament panel access (ignore named abilities)
    */
    'authorization' => [
        'driver' => env('VOODBUILDER_AUTHORIZATION', 'auto'),
    ],

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

    /*
    | Licence edition drives capability resolution.
    |
    | Capabilities gate *authoring*. Rendering a published page does not consult them, so a
    | lapsed licence or an unreachable licensing endpoint cannot empty a live site — see
    | Support/Editor/DynamicDataCollectionsBridge::renderingEnabled(). The single exception
    | is author-written JavaScript (Licensing/AuthorScriptPolicy).
    |
    | There is no separate "enforce" switch. A key-pattern check used to live in
    | Support/License/VoodbuilderLicense, uncalled by anything and satisfied by any string
    | starting with `vb_`; it was removed rather than left to imply a second gate.
    */
    'license' => [
        'edition' => env('VOODBUILDER_EDITION', 'community'),
        'key' => env('VOODBUILDER_LICENSE_KEY', ''),
        /*
        | Driver: config (local edition matrix) | anystack (remote) | testing
        */
        'driver' => env('VOODBUILDER_LICENSE_DRIVER', 'config'),
        'cache' => env('VOODBUILDER_LICENSE_CACHE', true),
        'cache_ttl' => (int) env('VOODBUILDER_LICENSE_CACHE_TTL', 3600),
        'anystack' => [
            'endpoint' => env('VOODBUILDER_ANYSTACK_ENDPOINT', ''),
            'timeout' => (int) env('VOODBUILDER_ANYSTACK_TIMEOUT', 5),
            // Keep last successful entitlement for 7 days when remote is down.
            'grace_seconds' => (int) env('VOODBUILDER_ANYSTACK_GRACE_SECONDS', 604800),
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
    | Visual themes / skins (Theme Studio catalog).
    | Distinct from light/dark mode (Settings → Appearance) and from chrome Layouts
    | (Admin → Layouts: header/footer shell assigned per content channel).
    |
    | Bundled skins: packages/voodflow/voodbuilder/resources/themes/{id}/
    | App skins: resources/voodbuilder/themes/{id}/ (make-subtheme / Theme Studio import)
    | Companion-owned skins register via Voodbuilder::subTheme() (vdocs, vtuts, …).
    | See Voodflow\Voodbuilder\Support\ThemeConvention.
    */
    'sub_themes' => [
        // Fallback labels when companion packages are not installed. Full skins are
        // registered by vdocs (docs) and vtuts (tutorials) via Voodbuilder::subTheme().
        'docs' => [
            'label' => 'Documentation',
            'description' => 'Clean reading layout for documentation.',
            'type' => 'content',
            'capabilities' => ['doc'],
        ],
        'tutorials' => [
            'label' => 'Tutorials',
            'description' => 'Tutorial catalogue and lesson reading layout.',
            'type' => 'content',
            'capabilities' => ['doc'],
        ],
        'site' => [
            'label' => 'VoodBuilder base',
            'description' => 'Base theme for pages built with the page builder.',
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
        // Article themes (blog/news) belong to dedicated packages — register via Voodbuilder::subTheme().
    ],

    /*
    | Required SubThemeCapability per content channel id.
    | Theme Studio → Assignments filters theme pickers with this map.
    | Channels themselves are registered by companions (Voodbuilder::contentChannel)
    | or listed under content_channels below — not by this array alone.
    |
    | Site Pages ("Site pages" / Pagine del sito) use settings `sub_theme`, not a
    | channel row: the `pages` channel stays for routing/search/chrome only
    | (ThemeBindings::shouldShowChannelBinding hides it from Assignments).
    */
    'content_channel_capabilities' => [
        'pages' => 'landing',
        'docs' => 'doc',
        'tutorials' => 'doc',
        'events' => 'landing',
        'exhibitors' => 'landing',
        'partners' => 'landing',
        'sponsors' => 'landing',
        // Reserved until an article package registers those channels + skins:
        'blog' => 'article',
        'news' => 'article',
    ],

    /*
    | Default skin when Theme Studio has no per-channel override.
    | Overridable in Theme Studio → Assignments. Companions may own defaults
    | (docs → docs via vdocs, tutorials → tutorials via vtuts).
    |
    | Site Pages use `sub_theme` (not this map). `pages` is kept for chrome-layout
    | resolution when a layout is assigned to the pages channel.
    | blog/news omitted until a package owns those themes.
    */
    'content_channel_defaults' => [
        'pages' => 'site',
        'events' => 'site',
        'exhibitors' => 'site',
        'partners' => 'site',
        'sponsors' => 'site',
        'docs' => 'docs',
        'tutorials' => 'tutorials',
    ],

    /*
    | Legacy blog/news Site Page demos (section field). Disabled by default.
    | Real blog content should come from a dedicated package (e.g. vblog).
    */
    'demo_site_sections' => (bool) env('VOODBUILDER_DEMO_SITE_SECTIONS', false),

    /*
    | Install seeder defaults: settings, empty menus, permissions, starter templates.
    | Sample pages (home / privacy / cookie) are opt-in — fresh sites start empty.
    */
    'seed' => [
        'sample_pages' => (bool) env('VOODBUILDER_SEED_SAMPLE_PAGES', false),
    ],

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
    | Spatie permission for frontend Editor editing without Filament admin access.
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
        | When true, pages nested under a menu Group/Page get URLs like
        | /{prefix}/{parent-segment}/{slug} (e.g. /pages/products/a-voodflow).
        | Top-level menu pages stay /{prefix}/{slug}. Legacy flat URLs still resolve.
        */
        'menu_paths' => (bool) env('VOODBUILDER_PAGES_MENU_PATHS', false),
        /*
        | Visual Editor is always used for new pages (no Content builder select).
        | Kept for documentation / older hosts; SitePageForm::editorOnly() is hard-true.
        */
        'editor_only' => env('VOODBUILDER_PAGES_EDITOR_ONLY', true),
        'default_builder' => PageBuilder::normalize(env('VOODBUILDER_PAGES_DEFAULT_BUILDER', 'visual'))?->value ?? 'visual',
        /*
        | Per-page visual theme in Admin → Pages (Aspetto). Default area theme stays
        | in Theme Studio → Assignments (“Site pages”). When true, authors can override
        | the skin for a single page without changing the channel default.
        */
        'allow_sub_theme_override' => env('VOODBUILDER_PAGES_ALLOW_SUB_THEME_OVERRIDE', true),
    ],

    /*
    | Frontend page access (visibility + password gate).
    | Visibility maps to Spatie roles / Gate ability used by Cosmolab & vtuts.
    */
    'access' => [
        'login_route' => env('VOODBUILDER_ACCESS_LOGIN_ROUTE', 'login'),
        'register_route' => env('VOODBUILDER_ACCESS_REGISTER_ROUTE', 'register'),
        'subscribe_url' => env('VOODBUILDER_ACCESS_SUBSCRIBE_URL'),
        'subscriber_ability' => env('VOODBUILDER_ACCESS_SUBSCRIBER_ABILITY', 'access subscriber content'),
        /*
        | Minutes the password unlock stays in session (null = until Laravel session ends).
        | Laravel session lifetime defaults to SESSION_LIFETIME (120).
        */
        'password_unlock_minutes' => env('VOODBUILDER_PASSWORD_UNLOCK_MINUTES'),
    ],

    'popups' => [
        'enabled' => env('VOODBUILDER_POPUPS_ENABLED', true),
        // null = Site pages channel theme (Theme Studio → Site pages), same as chrome/page editors.
        // Override with VOODBUILDER_POPUPS_EDITOR_SUB_THEME when needed.
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
    | Each channel can define route patterns (menu highlight + sub-theme resolution) and optional search.
    | Companions (vdocs, vtuts, vevents, …) usually register in their service provider instead.
    |
    | Example:
    | 'blog' => [
    |     'label' => 'Blog',
    |     'routes' => ['blog.*'],
    |     'search' => \App\Models\BlogPost::class, // static voodbuilderSearch($term, $limit)
    | ],
    |
    | Default skin per channel: content_channel_defaults / Theme Studio Assignments
    | (not a key on the channel array). Chrome shell: Admin → Layouts.
    */
    'content_channels' => [
        //
    ],

    /*
    | Editor chrome layouts — header/footer shell around plugin content.
    | Assign layouts to content channels in Admin → Layouts (not in Theme Studio).
    | Theme Studio skins the page; Layouts choose the chrome shell.
    */
    'chrome_layouts' => [
        'enabled' => env('VOODBUILDER_CHROME_LAYOUTS_ENABLED', true),
        // Prefer null: unassigned layouts use the pages-channel / site-default theme.
        'editor_sub_theme' => env('VOODBUILDER_CHROME_LAYOUTS_EDITOR_SUB_THEME'),
        'shell_sub_theme' => env('VOODBUILDER_CHROME_LAYOUTS_SHELL_SUB_THEME'),
    ],

    /*
    | Visual page builder.
    | Requires host npm packages (see ConfigureNpmForVoodbuilder) and
    | VoodbuilderPaths::editorViteEntry() in vite.config.js, then npm run build.
    */
    'editor' => [
        'enabled' => env('VOODBUILDER_EDITOR_ENABLED', true),
        'vite' => VoodbuilderPaths::editorViteEntry(),
        'canvas_styles' => VoodbuilderPaths::editorCanvasStyleEntries(),
        'upload' => [
            'disk' => 'public',
            // Public files land under storage/app/public/voodbuilder (URL /storage/voodbuilder/…).
            'directory' => 'voodbuilder',
            // Soft ceiling; the image editor re-encodes to JPEG and retries at lower quality.
            'max_size' => (int) env('VOODBUILDER_EDITOR_UPLOAD_MAX_KB', 12288),
            // Self-hosted MP4/WebM for Video / hero backgrounds (KB).
            'video_max_size' => (int) env('VOODBUILDER_EDITOR_UPLOAD_VIDEO_MAX_KB', 51200),
        ],
        /*
        | In-canvas image editor (@jodit/image-editor, MIT). Requires the host app
        | npm dependency `@jodit/image-editor`. Disable with env or config.
        */
        'image_editor' => env('VOODBUILDER_EDITOR_IMAGE_EDITOR', true),
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
        | Block IDs to hide from the Editor sidebar (runtime rendering still works).
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
            // Character limits (Laravel `max:` on strings). Pages with Forms / SVGs /
            // Style Manager #id rules routinely exceed the old 100–500KB caps.
            'max_html_bytes' => 2_000_000,
            'max_css_bytes' => 1_000_000,
            'max_js_bytes' => 500_000,
            'max_project_bytes' => 5_000_000,
        ],
        'plugins' => [
            'forms' => env('VOODBUILDER_EDITOR_FORMS', false),
            'style_bg' => env('VOODBUILDER_EDITOR_STYLE_BG', true),
            'tabs' => env('VOODBUILDER_EDITOR_TABS', true),
            'custom_code' => env('VOODBUILDER_EDITOR_CUSTOM_CODE', false),
        ],
        'forms' => [
            'success_message' => 'Thank you. Your message has been received.',
            'newsletter_success_message' => 'Thank you for subscribing to our newsletter.',
        ],
        'newsletter_lists' => [
            'default' => 'Default newsletter',
        ],
        'revisions' => [
            'max_to_keep' => (int) env('VOODBUILDER_EDITOR_REVISIONS_MAX', 50),
            // Autosaves have their own budget so unattended writes cannot evict the
            // history an author curated. A handful is enough: they only ever describe
            // work that has not been saved yet.
            'max_autosaves_to_keep' => (int) env('VOODBUILDER_EDITOR_AUTOSAVES_MAX', 5),
        ],

        'autosave' => [
            'enabled' => (bool) env('VOODBUILDER_EDITOR_AUTOSAVE', true),
            // Server-side parking is the coarse net; the local draft below covers the
            // seconds in between without touching the database.
            'interval_ms' => (int) env('VOODBUILDER_EDITOR_AUTOSAVE_INTERVAL_MS', 120_000),
            'local_draft_interval_ms' => (int) env('VOODBUILDER_EDITOR_LOCAL_DRAFT_INTERVAL_MS', 5_000),
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
