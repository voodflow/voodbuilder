<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Popups',
    ],

    'model' => [
        'label' => 'Popup',
        'plural' => 'Popups',
    ],

    'sections' => [
        'details' => 'Details',
        'trigger' => 'When to show',
        'frequency' => 'How often',
        'targeting' => 'Who / where',
        'display' => 'Appearance',
    ],

    'fields' => [
        'name' => 'Name',
        'enabled' => 'Enabled',
        'priority' => 'Priority',
        'trigger_type' => 'Trigger',
        'delay_seconds' => 'Delay (seconds)',
        'scroll_percent' => 'Scroll depth (%)',
        'click_selector' => 'CSS selector',
        'frequency_mode' => 'Frequency',
        'frequency_days' => 'Days between views',
        'logged_in' => 'Audience',
        'page_path' => 'Page path contains',
        'page_path_help' => 'Leave empty for all pages. Example: /blog',
        'width' => 'Modal width',
        'overlay' => 'Dim background',
        'close_on_overlay' => 'Close on overlay click',
        'close_on_escape' => 'Close on Escape',
        'updated_at' => 'Updated',
    ],

    'triggers' => [
        'load' => 'On page load',
        'delay' => 'After delay',
        'scroll' => 'On scroll depth',
        'exit_intent' => 'On exit intent',
        'click' => 'On element click',
    ],

    'frequency' => [
        'always' => 'Every visit',
        'once' => 'Once ever',
        'session' => 'Once per session',
        'days' => 'Every N days',
    ],

    'targeting' => [
        'any' => 'Everyone',
        'logged_in' => 'Logged-in users only',
        'logged_out' => 'Guests only',
    ],

    'width' => [
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
        'xl' => 'Extra large',
    ],

    'actions' => [
        'open_visual_editor' => 'Open visual editor',
    ],

    'defaults' => [
        'body' => 'Edit this popup in the visual builder. Use elements and saved components like on a normal page.',
        'cta' => 'Close',
    ],

    'editor' => [
        'title' => 'Popups',
        'hint' => 'Create and manage site popups without leaving the page editor.',
        'create' => 'Add popup',
        'create_title' => 'Add popup',
        'edit_title' => 'Edit popup',
        'edit_rules' => 'Rules',
        'test' => 'Test popup',
        'open_editor' => 'Design',
        'delete' => 'Delete',
        'delete_confirm' => 'Delete this popup?',
        'delete_error' => 'Could not delete popup.',
        'save_error' => 'Could not save popup.',
        'load_error' => 'Could not load popups.',
        'empty' => 'No popups yet.',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'editing_badge' => 'Editing popup: {name}',
    ],

    'page_paths' => [
        'all_pages' => 'All pages',
        'custom' => 'Custom path…',
        'group_general' => 'General',
        'group_pages' => 'Site pages',
        'group_menus' => 'Menu links',
        'group_routes' => 'App routes',
    ],
];
