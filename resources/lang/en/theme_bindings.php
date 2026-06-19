<?php

declare(strict_types=1);

return [
    'section_title' => 'Theme bindings',
    'section_help' => 'Choose which visual theme applies to each public area. Channels are registered by installed packages (docs, tutorials, blog, events). Only compatible themes are listed for each area.',
    'site_pages' => 'Site pages',
    'site_pages_help' => 'Default theme for marketing Site Pages (home, landing, CMS). Individual pages can override this under Pages.',
    'channel_help' => 'Requires a :capability theme. Package default: :default.',
    'no_package_default' => 'Site default',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">No content channels are registered yet. Install a package such as vdocs, vtuts, or vevents.</p>',
    'inherit_default' => 'Package default',
];
