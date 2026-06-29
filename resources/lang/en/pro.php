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
        'assets_missing' => 'GrapesJS frontend assets are not built yet.',
    ],

    'bindings' => [
        'make_dynamic' => 'Make dynamic',
        'clear_dynamic' => 'Clear dynamic binding',
        'modal_title' => 'Connect to live data',
        'modal_source' => 'Data source',
        'modal_field' => 'Field',
        'modal_apply' => 'Apply',
        'modal_cancel' => 'Cancel',
        'select_component' => 'Select an element on the canvas first.',
        'no_sources' => 'No dynamic data sources are registered yet.',
        'inspector_hint' => 'Connect elements to live data from Model integrations. Use list repeat on containers for article grids.',
        'current_binding' => 'Current binding',
        'repeat_source' => 'Repeat list',
        'repeat_limit' => 'Items',
        'repeat_sort' => 'Sort by',
        'repeat_sort_dir' => 'Direction',
        'repeat_sort_asc' => 'Ascending',
        'repeat_sort_desc' => 'Descending',
        'sort_id' => 'ID',
        'sort_created_at' => 'Created date',
        'sort_updated_at' => 'Updated date',
        'apply_repeat' => 'Apply list repeat',
        'clear_repeat' => 'Clear list repeat',
        'current_repeat' => 'Current repeat',
        'repeat_list' => 'List repeat',
        'repeat_container_hint' => 'Apply List repeat on this container. Then select the title, text or link inside the card and bind with “List item” — not “Latest record”.',
        'binding_needs_leaf' => 'Bind text and images on the inner element (h2, p, img, a), not on the grid container.',
        'repeat_container_no_bind' => 'List repeat containers cannot hold a field binding. Bind the fields inside the card template.',
        'repeat_list_not_field' => 'Repeat list is configured via the List repeat section below, not as a field binding.',
    ],

    'editor_ui' => [
        'panel_blocks' => 'Blocks',
        'panel_inspector' => 'Inspector',
        'block_search' => 'Search blocks…',
        'tab_content' => 'Content',
        'tab_style' => 'Style',
        'tab_dynamic' => 'Dynamic',
        'tab_layers' => 'Layers',
        'device_desktop' => 'Desktop',
        'device_tablet' => 'Tablet',
        'device_mobile' => 'Mobile',
        'undo' => 'Undo',
        'redo' => 'Redo',
        'outline' => 'Show outlines',
        'preview' => 'Preview',
    ],

    'grapesjs' => [
        'blocks' => [
            'site_header' => 'Site header (menu)',
            'site_footer' => 'Site footer — column menus',
            'site_footer_a' => 'Footer A — brand + columns',
            'site_footer_b' => 'Footer B — columns + brand',
            'site_footer_c' => 'Footer C — columns only',
            'site_footer_d' => 'Footer D — compact bar',
            'site_footer_e' => 'Footer E — four columns',
            'site_header_preview' => 'Live menu from Admin → Menus',
            'site_footer_preview' => 'Edit titles, tagline and copyright in the canvas. Brand and links stay dynamic.',
            'site_footer_help' => 'Uses Admin → Menus → Footer column 1–4. Logo and brand name from site settings.',
            'site_header_help' => 'Uses Admin → Menus → Main navigation and Header extras.',
            'site_footer_empty' => 'No items in “:menu”. Add links in Admin → Menus.',
            'footer_menu_empty' => 'No links in :menu yet',
            'footer_default_tagline' => 'Short description for your brand.',
            'footer_col_1_title' => 'CATEGORIES',
            'footer_col_2_title' => 'RESOURCES',
            'footer_col_3_title' => 'COMPANY',
            'footer_col_4_title' => 'LEGAL',
        ],
    ],
];
