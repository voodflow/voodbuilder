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
        'content_width' => 'Content width',
        'content_width_help' => 'Full, standard (~80rem), or a custom max for page content.',
        'content_width_full' => 'Full width',
        'content_width_standard' => 'Standard page',
        'content_width_custom' => 'Custom',
        'content_max_width' => 'Max content width',
        'content_max_width_help' => 'CSS length, e.g. 72rem or 1200px.',
        'chrome_width' => 'Nav & footer width',
        'chrome_width_help' => 'Keep nav/footer edge-to-edge, or match the content width above.',
        'chrome_width_full' => 'Full width',
        'chrome_width_content' => 'Match content',
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
        'layout_content_slot_placeholder' => 'Page content — filled automatically by each page.',
        'layout_nav_zone_placeholder' => 'Drop header blocks here',
        'layout_footer_zone_placeholder' => 'Drop footer blocks here',
    ],

    'page_form' => [
        'layout' => 'Layout',
        'layout_inherit_default' => 'Site default',
        'layout_inherit_named' => 'Channel default (:name)',
        'layout_help' => 'Shared header and footer around the page content. Edit nav and footer in Admin → Layouts.',
        'shell' => 'Layout',
        'none' => 'No chrome layout assigned to the Pages channel. Nav and footer use the classic site shell.',
        'edit_shell' => 'Edit layout',
        'canvas_width_help' => 'Nav and footer stay full width. This only chooses whether page content is edge-to-edge or contained (~80rem). It does not resize the editing workspace.',
    ],
];
