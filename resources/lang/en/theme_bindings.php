<?php

declare(strict_types=1);

return [
    'section_title' => 'Where themes are used',
    'section_help' => 'Assign a visual theme to each public area. Per-page overrides remain available under Site → Pages.',
    'layout_column' => 'Layout',
    'site_pages' => 'Home & site pages',
    'site_pages_description' => 'Home, auth screens, and CMS pages from Site → Pages. Override one page under Pages → Publish.',
    'unregistered_fallback' => 'Installed packages that do not register a vpress area use the Home & site pages layout above — not the “Documentation” layout unless you set it here.',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">No package areas yet. When you install vdocs, vtuts, events, and similar packages, they appear in this list.</p>',
    'channels' => [
        'docs' => 'Documentation from the vdocs package.',
        'tutorials' => 'Tutorials from the vtuts package.',
        'blog' => 'Blog posts from the blog package.',
        'news' => 'News articles from the news package.',
        'events' => 'Events and listings from the events package.',
        'exhibitors' => 'Exhibitor profiles and directories.',
    ],
    'channel_generic' => 'Public pages for :label.',
];
