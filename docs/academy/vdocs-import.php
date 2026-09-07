<?php

declare(strict_types=1);

/**
 * vdocs import metadata for the VoodBuilder Academy topic.
 *
 * Merge these keys into config/vdocs.php (or require this file from there), then:
 *
 *   php artisan vdocs:import-vitepress packages/voodflow/voodbuilder/docs/academy \
 *     --topic=voodbuilder --create-topic --locale=en --force
 */
return [
    'section_catalog' => [
        'voodbuilder' => [
            'en' => [
                'getting-started' => 'Getting Started',
                'builder' => 'Builder',
                'developer' => 'Developer',
            ],
        ],
    ],

    'sidebar_catalog' => [
        'voodbuilder' => [
            '/getting-started/' => [
                [
                    'text' => 'Getting Started',
                    'items' => [
                        ['text' => 'Overview', 'link' => '/getting-started/'],
                        ['text' => 'Installation', 'link' => '/getting-started/installation'],
                        ['text' => 'Interface tour', 'link' => '/getting-started/interface-tour'],
                        ['text' => 'Your first page', 'link' => '/getting-started/your-first-page'],
                        ['text' => 'Site settings', 'link' => '/getting-started/site-settings'],
                        ['text' => 'Translations', 'link' => '/getting-started/translations'],
                    ],
                ],
            ],
            '/builder/' => [
                [
                    'text' => 'Site structure',
                    'items' => [
                        ['text' => 'Overview', 'link' => '/builder/'],
                        ['text' => 'Pages & layouts', 'link' => '/builder/pages-and-layouts'],
                        ['text' => 'Chrome layouts', 'link' => '/builder/chrome-layouts'],
                        ['text' => 'Menus', 'link' => '/builder/menus'],
                    ],
                ],
                [
                    'text' => 'Visual editor',
                    'items' => [
                        ['text' => 'Opening the editor', 'link' => '/builder/opening-the-editor'],
                        ['text' => 'Layout model', 'link' => '/builder/layout-model'],
                        ['text' => 'Content width', 'link' => '/builder/content-width'],
                        ['text' => 'Library & blocks', 'link' => '/builder/library-and-blocks'],
                        ['text' => 'Canvas & inspector', 'link' => '/builder/canvas-and-inspector'],
                        ['text' => 'Media & images', 'link' => '/builder/media-and-images'],
                    ],
                ],
                [
                    'text' => 'Dynamic content',
                    'items' => [
                        ['text' => 'Model integrations', 'link' => '/builder/model-integrations'],
                        ['text' => 'Lists & collections', 'link' => '/builder/lists-and-collections'],
                        ['text' => 'Global text tags', 'link' => '/builder/global-text-tags'],
                        ['text' => 'Visibility conditions', 'link' => '/builder/visibility-conditions'],
                    ],
                ],
                [
                    'text' => 'Publish',
                    'items' => [
                        ['text' => 'Page templates', 'link' => '/builder/page-templates'],
                        ['text' => 'Publishing & preview', 'link' => '/builder/publishing-and-preview'],
                        ['text' => 'Core vs companions', 'link' => '/builder/core-vs-companions'],
                    ],
                ],
            ],
            '/developer/' => [
                [
                    'text' => 'Developer',
                    'items' => [
                        ['text' => 'Overview', 'link' => '/developer/'],
                        ['text' => 'Installation', 'link' => '/developer/installation'],
                        ['text' => 'Block authoring', 'link' => '/developer/block-authoring'],
                        ['text' => 'Tailwind & theme tokens', 'link' => '/developer/tailwind-and-theme-tokens'],
                        ['text' => 'Registering blocks', 'link' => '/developer/registering-blocks'],
                    ],
                ],
            ],
        ],
    ],
];
