<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Layouts',
    ],

    'model' => [
        'label' => 'Layout',
        'plural' => 'Layouts',
    ],

    'sections' => [
        'details' => 'Details',
    ],

    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'enabled' => 'Enabled',
        'is_default' => 'Default layout',
        'is_default_help' => 'Used when no channel-specific layout matches the current request.',
        'channels' => 'Content channels',
        'channels_help' => 'Assign this chrome layout to docs, tutorials, blog, or other registered channels.',
    ],

    'actions' => [
        'open_visual_editor' => 'Open visual editor',
        'clone' => 'Clone layout',
    ],

    'clone' => [
        'modal_heading' => 'Clone layout',
        'modal_description' => 'Create a copy of this layout with a new name and slug. Header, footer, and content slot are duplicated.',
    ],

    'notifications' => [
        'cloned' => 'Layout cloned',
    ],

    'editor' => [
        'editing_badge' => 'Page content',
        'editing_hint' => 'Layout shell: {name} · header and footer are read-only here',
        'layout_editing_badge' => 'Editing layout: {name}',
        'layout_editing_hint' => 'Keep one content slot between header and footer',
        'page_content_placeholder' => 'Drag blocks here to build your page',
    ],

    'page_form' => [
        'shell' => 'Site chrome',
        'none' => 'No chrome layout assigned to the Pages channel. Nav and footer use the classic site shell.',
        'edit_shell' => 'Edit layout',
        'canvas_width_help' => 'Nav and footer come from Admin → Layouts. This only controls how wide the page content area is.',
    ],
];
