<?php

declare(strict_types=1);

return [
    'page_title' => 'Channel themes',
    'intro' => '<p class="text-sm text-gray-600 dark:text-gray-400">Each channel is registered by an installed package (docs, tutorials, events, blog, …). Choose which <strong>content</strong> sub-theme applies when visitors browse those routes. Marketing Site Pages are configured separately under Pages and Settings.</p>',
    'empty' => '<p class="text-sm text-gray-600 dark:text-gray-400">No content channels are registered yet. Install a package such as vdocs, vtuts, or vevents, or register channels in <code>config/voodbuilder.php</code>.</p>',
    'package_default' => 'Package default',
    'visual_theme' => 'Content sub-theme',
    'override_help' => 'Leave on “Package default” to use the theme declared in config. Pick another content sub-theme to override it for this channel only.',
    'no_default_theme' => 'Site default',
    'inherit_package' => 'Package default',
    'save' => 'Save channel themes',
    'saved' => 'Channel themes saved.',
];
