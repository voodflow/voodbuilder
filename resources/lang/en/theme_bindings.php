<?php

declare(strict_types=1);

return [
    'section_title' => 'Public site theme',
    'section_help' => 'One default look for home, CMS pages, and (unless overridden below) blog, events, and similar areas.',
    'intro' => 'Pick the main visual theme for your public site. Docs and tutorials usually keep the Documentation layout; everything else inherits this choice unless you open Advanced overrides.',
    'layout_column' => 'Theme',
    'site_pages' => 'Default site theme',
    'site_pages_description' => 'Home, landing pages, and Site → Pages. This is the theme most visitors see.',
    'advanced_section' => 'Advanced: per-area overrides',
    'advanced_section_help' => 'Only change these when one area must look different — e.g. Documentation for tutorials but Site for the blog.',
    'use_site_default' => 'Same as default site theme (:theme)',
    'unregistered_fallback' => 'Areas without an override use the default site theme above.',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">No package areas yet. When you install vdocs, vtuts, events, and similar packages, they appear under Advanced overrides.</p>',
    'channels' => [
        'docs' => 'Documentation from the vdocs package.',
        'tutorials' => 'Tutorials from the vtuts package.',
        'blog' => 'Blog posts (Ink or similar).',
        'news' => 'News articles from the news package.',
        'events' => 'Events and listings from the events package.',
        'exhibitors' => 'Exhibitor profiles and directories.',
    ],
    'channel_generic' => 'Public pages for :label.',
];
