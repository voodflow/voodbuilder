<?php

declare(strict_types=1);

return [
    'page_title' => 'Site sections',
    'intro' => '<p class="text-sm text-gray-600 dark:text-gray-400">Each section is registered by an installed package (events, exhibitors, tutorials, …). Choose which visual sub-theme applies when visitors browse those routes. Search and routes stay defined in code; only the look can be changed here.</p>',
    'empty' => '<p class="text-sm text-gray-600 dark:text-gray-400">No content channels are registered yet. Install a package such as vevents or vexhibitors, or register channels in <code>config/vpress.php</code>.</p>',
    'package_default' => 'Package default',
    'visual_theme' => 'Visual sub-theme',
    'override_help' => 'Leave on “Package default” to use the theme declared by the package. Pick another registered sub-theme to override it for this section only.',
    'no_default_theme' => 'Site default',
    'inherit_package' => 'Package default',
    'save' => 'Save sections',
    'saved' => 'Site section themes saved.',
];
