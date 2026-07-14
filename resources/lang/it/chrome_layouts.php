<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Layout',
    ],

    'model' => [
        'label' => 'Layout',
        'plural' => 'Layout',
    ],

    'sections' => [
        'details' => 'Dettagli',
    ],

    'fields' => [
        'name' => 'Nome',
        'slug' => 'Slug',
        'enabled' => 'Attivo',
        'is_default' => 'Layout predefinito',
        'is_default_help' => 'Usato quando nessun layout specifico per canale corrisponde alla richiesta corrente.',
        'channels' => 'Canali contenuto',
        'channels_help' => 'Assegna questo layout chrome a docs, tutorial, blog o altri canali registrati.',
    ],

    'actions' => [
        'open_visual_editor' => 'Apri editor visuale',
        'clone' => 'Clona layout',
    ],

    'clone' => [
        'modal_heading' => 'Clona layout',
        'modal_description' => 'Crea una copia di questo layout con un nuovo nome e slug. Header, footer e slot contenuto vengono duplicati.',
    ],

    'notifications' => [
        'cloned' => 'Layout clonato',
    ],

    'editor' => [
        'editing_badge' => 'Contenuto pagina',
        'editing_hint' => 'Layout: {name} · header e footer si modificano solo nel layout',
        'layout_editing_badge' => 'Modifica layout: {name}',
        'layout_editing_hint' => 'Mantieni un solo slot contenuto tra header e footer',
        'page_content_placeholder' => 'Trascina qui i blocchi per costruire la pagina',
    ],

    'page_form' => [
        'shell' => 'Guscio sito',
        'none' => 'Nessun layout chrome assegnato al canale Pages. Nav e footer usano il guscio classico.',
        'edit_shell' => 'Modifica layout',
        'canvas_width_help' => 'Nav e footer arrivano da Admin → Layout. Qui scegli solo quanto è larga l’area contenuto della pagina.',
    ],
];
