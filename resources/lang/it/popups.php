<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Popup',
    ],

    'model' => [
        'label' => 'Popup',
        'plural' => 'Popup',
    ],

    'sections' => [
        'details' => 'Dettagli',
        'trigger' => 'Quando mostrare',
        'frequency' => 'Frequenza',
        'targeting' => 'Chi / dove',
        'display' => 'Aspetto',
    ],

    'fields' => [
        'name' => 'Nome',
        'enabled' => 'Attivo',
        'priority' => 'Priorità',
        'trigger_type' => 'Trigger',
        'delay_seconds' => 'Ritardo (secondi)',
        'scroll_percent' => 'Profondità scroll (%)',
        'click_selector' => 'Selettore CSS',
        'frequency_mode' => 'Frequenza',
        'frequency_days' => 'Giorni tra una visualizzazione e l\'altra',
        'logged_in' => 'Audience',
        'page_path' => 'Percorso pagina contiene',
        'page_path_help' => 'Lascia vuoto per tutte le pagine. Esempio: /blog',
        'width' => 'Larghezza modale',
        'overlay' => 'Sfondo scuro',
        'close_on_overlay' => 'Chiudi al click sullo sfondo',
        'close_on_escape' => 'Chiudi con Esc',
        'updated_at' => 'Aggiornato',
    ],

    'triggers' => [
        'load' => 'Al caricamento pagina',
        'delay' => 'Dopo un ritardo',
        'scroll' => 'A una certa profondità di scroll',
        'exit_intent' => 'All\'uscita dal sito',
        'click' => 'Al click su un elemento',
    ],

    'frequency' => [
        'always' => 'Ogni visita',
        'once' => 'Una sola volta',
        'session' => 'Una volta per sessione',
        'days' => 'Ogni N giorni',
    ],

    'targeting' => [
        'any' => 'Tutti',
        'logged_in' => 'Solo utenti autenticati',
        'logged_out' => 'Solo ospiti',
    ],

    'width' => [
        'sm' => 'Piccola',
        'md' => 'Media',
        'lg' => 'Grande',
        'xl' => 'Molto grande',
    ],

    'actions' => [
        'open_visual_editor' => 'Apri editor visuale',
    ],

    'defaults' => [
        'body' => 'Modifica questo popup nell\'editor visuale. Puoi usare elementi e componenti salvati come su una pagina normale.',
        'cta' => 'Chiudi',
    ],

    'editor' => [
        'title' => 'Popup',
        'hint' => 'Crea e gestisci i popup del sito senza uscire dall\'editor pagina.',
        'create' => 'Aggiungi popup',
        'create_title' => 'Aggiungi popup',
        'edit_title' => 'Modifica popup',
        'edit_rules' => 'Regole',
        'test' => 'Test popup',
        'open_editor' => 'Design',
        'delete' => 'Elimina',
        'delete_confirm' => 'Eliminare questo popup?',
        'delete_error' => 'Impossibile eliminare il popup.',
        'save_error' => 'Impossibile salvare il popup.',
        'load_error' => 'Impossibile caricare i popup.',
        'empty' => 'Nessun popup ancora.',
        'enabled' => 'Attivo',
        'disabled' => 'Disattivo',
        'editing_badge' => 'Modifica popup: {name}',
    ],

    'page_paths' => [
        'all_pages' => 'Tutte le pagine',
        'custom' => 'Percorso personalizzato…',
        'group_general' => 'Generale',
        'group_pages' => 'Pagine del sito',
        'group_menus' => 'Voci di menu',
        'group_routes' => 'Route app',
    ],
];
