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
        'grapesjs_frontend' => 'Gli admin e gli utenti con permesso builder possono modificare la pagina pubblica. Usa il pulsante in basso a destra o aggiungi ?edit=1 all’URL.',
    ],

    'frontend' => [
        'toolbar_title' => 'Editor pagina GrapesJS',
        'exit_editor' => 'Esci dall\'editor',
        'save' => 'Salva',
        'saving' => 'Salvataggio…',
        'saved' => 'Salvato',
        'error' => 'Impossibile salvare la pagina. Riprova.',
        'assets_missing' => 'Gli asset frontend di GrapesJS non sono ancora stati compilati.',
    ],

    'grapesjs' => [
        'blocks' => [
            'site_header' => 'Header sito (menu Admin)',
            'site_footer' => 'Footer sito — menu a colonne',
            'site_footer_a' => 'Footer A — brand + colonne',
            'site_footer_b' => 'Footer B — colonne + brand',
            'site_footer_c' => 'Footer C — solo colonne',
            'site_footer_d' => 'Footer D — barra compatta',
            'site_footer_e' => 'Footer E — quattro colonne',
            'site_header_preview' => 'Menu reale da Admin → Menu',
            'site_footer_preview' => 'Modifica titoli, tagline e copyright nel canvas. Brand e link restano dinamici.',
            'site_footer_help' => 'Usa Admin → Menu → Footer colonna 1–4. Logo e nome brand dalle impostazioni sito.',
            'site_header_help' => 'Usa Admin → Menu → Navigazione principale e Extra header.',
            'site_footer_empty' => 'Nessuna voce in “:menu”. Aggiungi link in Admin → Menu.',
            'footer_menu_empty' => 'Nessun link in :menu',
            'footer_default_tagline' => 'Breve descrizione del brand.',
            'footer_col_1_title' => 'CATEGORIE',
            'footer_col_2_title' => 'RISORSE',
            'footer_col_3_title' => 'AZIENDA',
            'footer_col_4_title' => 'LEGALE',
        ],
    ],
];
