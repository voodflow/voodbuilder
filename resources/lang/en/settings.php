<?php

return [
    'logo_help' => 'Desktop header logo. Recommended: 80–120 px tall (or SVG), up to ~400 px wide. Displayed at ~40 px height in the header.',
    'logo_mobile' => 'Mobile logo (optional)',
    'logo_mobile_help' => 'Compact version for small screens (e.g. icon only). Recommended: 64–80 px. Falls back to the main logo when empty.',
    'primary_locale' => 'Primary site language',
    'primary_locale_help' => 'Default language for the site UI and listings. Uses clean URLs without a /en/ or /it/ prefix. Linked translations use their own slug (e.g. /tutorials/my-article and /tutorials/il-mio-articolo).',
    'default_ui_locale' => 'Default UI language',
    'default_ui_locale_help' => 'Applied to navigation, buttons, and other interface strings when the language switcher is hidden.',

    'tabs' => [
        'site' => 'Site',
        'appearance' => 'Appearance',
        'theme' => 'Theme',
        'seo' => 'SEO',
        'geo_ai' => 'GEO & AI',
        'analytics' => 'Analytics',
    ],

    'theme_scope_info' => '<div class="rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-100"><strong class="font-medium">Site-wide vs sub-theme</strong><p class="mt-2">Logo, SEO defaults, cookie consent, analytics, navigation, and language settings apply to <strong>every</strong> sub-theme. Sub-themes only change layout and visual style (typography, columns, accents). Each page can override the site default sub-theme.</p></div>',

    'theme_default_section' => 'Default sub-theme',
    'theme_colors_section' => 'Brand colors',
    'theme_colors_help' => 'Optional overrides per sub-theme. When disabled, the built-in palette from the theme stylesheet is used.',
    'theme_customize' => 'Customize brand colors',
    'theme_customize_help' => 'Override the built-in palette for this sub-theme only.',
    'theme_light_mode' => 'Light mode',
    'theme_dark_mode' => 'Dark mode',
    'theme_primary' => 'Primary color',
    'theme_primary_help' => 'Links, accents, and highlights.',
    'theme_secondary' => 'Secondary color',
    'theme_secondary_help' => 'Buttons and stronger accents. A mid-tone is derived automatically.',
    'theme_dark_primary_help' => 'Primary color when dark mode is active.',
    'theme_dark_secondary_help' => 'Secondary color when dark mode is active.',
];
