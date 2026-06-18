<?php

declare(strict_types=1);

return [
    'builders' => [
        'rich_editor' => 'Editor rich content (blocchi)',
        'grapesjs' => 'Builder visuale (GrapesJS)',
    ],

    'fields' => [
        'builder' => 'Builder contenuti',
    ],

    'helpers' => [
        'builder' => 'L\'editor rich content mantiene i blocchi TipTap e i contenuti stile documentazione. GrapesJS è un canvas drag-and-drop nello stesso shell vpress, con menu e sotto-temi.',
        'grapesjs_assets' => 'Installa GrapesJS nell\'app host: npm install -D grapesjs grapesjs-blocks-basic, aggiungi la voce Vite GrapesJS di vpress ed esegui npm run build.',
    ],
];
