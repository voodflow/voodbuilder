<?php

declare(strict_types=1);

return [
    'builders' => [
        'rich_editor' => 'Rich editor (blocks)',
        'grapesjs' => 'Visual builder (GrapesJS)',
    ],

    'fields' => [
        'builder' => 'Content builder',
    ],

    'helpers' => [
        'builder' => 'Rich editor keeps TipTap blocks and docs-style content. GrapesJS is a drag-and-drop canvas inside the same vpress shell, menus, and sub-themes.',
        'grapesjs_assets' => 'Install GrapesJS in the host app: npm install -D grapesjs grapesjs-blocks-basic, then add the vpress GrapesJS Vite entry and run npm run build.',
    ],
];
