<?php

declare(strict_types=1);

return [
    'builders' => [
        'rich_editor' => 'Rich editor (blocks)',
        'grapesjs' => 'Visual builder (GrapesJS)',
    ],

    'fields' => [
        'builder' => 'Content builder',
        'grapesjs_edit' => 'Visual editing',
    ],

    'actions' => [
        'open_visual_editor' => 'Open visual editor',
    ],

    'helpers' => [
        'builder' => 'Rich editor keeps TipTap blocks and docs-style content. GrapesJS opens a drag-and-drop editor on the public page when you are logged in as admin.',
        'grapesjs_save_first' => 'Save the page first, then open it on the site to edit visually.',
        'grapesjs_frontend' => 'Admins and users with the builder permission can edit on the public page. Use the button at the bottom-right or add ?edit=1 to the URL.',
    ],

    'frontend' => [
        'toolbar_title' => 'GrapesJS page editor',
        'exit_editor' => 'Exit editor',
        'save' => 'Save',
        'saving' => 'Saving…',
        'saved' => 'Saved',
        'error' => 'Could not save the page. Try again.',
    ],
];
