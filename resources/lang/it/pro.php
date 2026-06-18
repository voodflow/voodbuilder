<?php

declare(strict_types=1);

return [
    'builders' => [
        'rich_editor' => 'Editor rich content (blocchi)',
        'grapesjs' => 'Builder visuale (GrapesJS)',
    ],

    'fields' => [
        'builder' => 'Builder contenuti',
        'grapesjs_edit' => 'Modifica visuale',
    ],

    'actions' => [
        'open_visual_editor' => 'Apri editor visuale',
    ],

    'helpers' => [
        'builder' => 'L\'editor rich content mantiene i blocchi TipTap e i contenuti stile documentazione. GrapesJS apre un editor drag-and-drop sulla pagina pubblica quando sei loggato come admin.',
        'grapesjs_save_first' => 'Salva prima la pagina, poi aprila sul sito per modificarla visivamente.',
        'grapesjs_frontend' => 'Il contenuto si modifica sulla pagina pubblica da admin. Apri: ',
    ],

    'frontend' => [
        'toolbar_title' => 'Editor pagina GrapesJS',
        'save' => 'Salva',
        'saving' => 'Salvataggio…',
        'saved' => 'Salvato',
        'error' => 'Impossibile salvare la pagina. Riprova.',
    ],
];
