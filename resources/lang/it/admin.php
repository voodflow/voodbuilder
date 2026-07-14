<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'menus' => 'Menu',
        'pages' => 'Pagine',
        'settings' => 'Impostazioni',
        'content_channels' => 'Temi canali',
        'footer_column_placement' => 'Footer colonna :number',
        'link_display' => 'Visualizzazione link',
        'link_display_help' => 'Vale per tutti i link di questo menu.',
        'link_display_icon_only' => 'Solo icona',
        'link_display_icon_text' => 'Icona + testo',
        'link_display_text_only' => 'Solo testo',
    ],

    'menu_placements' => [
        'helper' => 'I menu header compaiono nella navigazione del sito. Le colonne footer (1–4) alimentano i footer a colonne. Link in riga e icone social servono ai blocchi footer centered e social.',
        'main' => 'Navigazione principale',
        'header_extra' => 'Link header destra',
        'social' => 'Icone social',
        'footer_inline' => 'Link footer in riga',
        'footer_column' => 'Colonna footer :number',
        'groups' => [
            'header' => 'Header',
            'footer_columns' => 'Colonne footer',
            'footer' => 'Link e social footer',
        ],
    ],

    'fields' => [
        'menu_icon' => 'Icona Tabler',
        'menu_route' => 'Route applicazione',
        'menu_route_match' => 'Pattern route attiva',
        'sub_theme' => 'Tema visivo',
        'sub_theme_inherit' => 'Predefinito sito',
        'layout_auto' => 'Automatico',
        'canvas_width' => 'Larghezza contenuto',
        'layout_standard' => 'Pagina standard',
        'layout_full_width' => 'Tutta larghezza (marketing)',
        'excerpt' => 'Estratto',
        'section' => 'Sezione',
        'section_none' => 'Nessuna',
        'section_home' => 'Home sezione',
        'sub_items' => 'Sotto-voci',
        'language' => 'Lingua',
        'lang' => 'Traduzioni',
        'target_language' => 'Lingua di destinazione',
        'translations' => 'Traduzioni',
        'translations_to_delete' => 'Traduzioni da eliminare',
        'menu_clone_name' => 'Nome menu',
    ],

    'actions' => [
        'translate_page' => 'Crea traduzione',
        'translate_menu' => 'Traduci in…',
        'clone_menu' => 'Clona menu',
        'clone_menu_placement' => 'Copia in placement…',
        'delete_translations' => 'Elimina traduzioni',
        'actions' => 'Azioni',
        'more' => 'Azioni',
    ],

    'translation' => [
        'tooltip' => 'Duplica questa pagina in un’altra lingua',
        'modal_heading' => 'Crea traduzione pagina',
        'modal_description' => 'Verrà creata una bozza con lo stesso layout e contenuto. Aggiorna titolo, slug e testi per la lingua scelta.',
        'none_yet' => 'Nessuna traduzione collegata.',
        'delete_translations_heading' => 'Elimina traduzioni',
        'delete_translations_modal_description' => 'Seleziona quali traduzioni rimuovere. La riga canonica del gruppo viene sempre mantenuta.',
        'canonical_kept_notice' => 'Originale mantenuto: :locale — :title',
    ],

    'menu_translation' => [
        'tooltip' => 'Duplica questo menu in un’altra lingua',
        'modal_heading' => 'Crea traduzione menu',
        'modal_description' => 'Verrà creata una copia di tutte le voci per la lingua scelta. I link alle pagine vengono mappati alle traduzioni collegate quando disponibili.',
        'none_yet' => 'Nessuna traduzione collegata.',
    ],

    'menu_clone' => [
        'tooltip' => 'Duplica questo menu in un altro placement',
        'modal_heading' => 'Clona menu',
        'modal_description' => 'Crea una copia indipendente con le stesse voci. Scegli un placement non ancora usato per questa lingua.',
    ],

    'notifications' => [
        'translation_created' => 'Traduzione creata',
        'translation_created_body' => 'Modifica la versione :locale e pubblica quando pronta.',
        'menu_translation_created' => 'Traduzione menu creata',
        'menu_translation_created_body' => 'Modifica il menu :locale e aggiorna etichette e link.',
        'menu_cloned' => 'Menu clonato',
        'home_reassigned' => 'Altre home aggiornate',
        'home_reassigned_body' => ':count altra/e pagina/e non sono più contrassegnate come home.',
        'translations_delete_none_selected' => 'Seleziona almeno una traduzione da eliminare.',
        'translations_deleted' => 'Traduzioni eliminate',
        'translations_deleted_body' => ':count traduzione/i rimossa/e: :list',
    ],

    'home_takeover' => [
        'heading' => 'Cambiare la home del sito?',
        'description' => 'Queste pagine sono attualmente home e verranno disattivate: :pages. Le traduzioni della stessa pagina in altre lingue possono restare home.',
        'confirm' => 'Sì, usa questa pagina',
    ],

    'validation' => [
        'menu_max_depth' => 'Il menu supporta al massimo :max livelli (voce principale e sotto-voci). Sposta la voce sotto una voce di primo livello.',
    ],

    'filters' => [
        'any' => 'Qualsiasi',
        'yes' => 'Sì',
        'no' => 'No',
        'is_home' => 'Home page',
        'published' => 'Pubblicata',
        'builder' => 'Content builder',
        'layout' => 'Layout',
        'sub_theme' => 'Sotto-tema',
        'sub_theme_inherit' => 'Predefinito sito (ereditato)',
    ],

    'sections' => [
        'appearance' => 'Aspetto (opzionale)',
    ],

    'menu_preview' => [
        'title' => 'Anteprima menu',
        'heading' => 'Anteprima live',
        'description' => 'Vedi come questo menu appare nell’header o nel footer del sito. Salva le voci per aggiornare l’anteprima. Nell’editor pagine usa il blocco Site → Header sito (menu Admin).',
        'badge' => 'Anteprima: :menu',
        'iframe_title' => 'Anteprima menu di navigazione',
        'empty' => 'Nessuna voce di menu.',
        'standalone_help' => 'Anteprima dei link per questo placement.',
    ],

    'helpers' => [
        'menu_route' => 'Route GET pubbliche registrate nell\'app. Lo stato attivo viene impostato automaticamente. Se la route richiede parametri, compila i campi mostrati sotto.',
        'menu_route_match' => 'Opzionale. Usato solo per URL esterni quando serve una regola di evidenziazione personalizzata.',
        'menu_sub_items' => 'Mostrate in un menu a tendina come Docs. Usa "Gruppo dropdown" per titoli di sezione senza link proprio.',
        'menu_icon' => 'Nome icona Tabler (es. brand-facebook). Compatibile con daljo25/filament-tabler-icons.',
        'menu_grapes_pages_only' => 'Sono elencate solo le pagine GrapesJS. Usa “Apri editor visuale” per modificare la pagina selezionata sul sito.',
        'menu_tree' => 'Trascina per riordinare o rilascia una voce su un’altra per creare un sottomenu. Massimo 2 livelli (principale + sotto-voci). Usa "Gruppo dropdown" per etichette senza link.',
        'sub_theme_site' => 'Tema visivo predefinito per le Site Page (home, landing, CMS). Configura le assegnazioni per area sotto.',
        'sub_theme_page' => 'Lascia “Predefinito sito” per usare :theme da Settings → Theme. Cambia solo se questa pagina deve avere un aspetto diverso.',
        'excerpt' => 'Breve riassunto per elenchi di sezione, card e SEO.',
        'section_home' => 'Contrassegna questa pagina come indice della sezione (elenca le pagine correlate nella sidebar).',
        'home_page_locale' => 'Una home per lingua. Le traduzioni collegate (stessa pagina, altre lingue) possono essere tutte home. Attivando una nuova home, le altre pagine vengono disattivate.',
        'home_page_layout' => 'Le home sono servite su /. Scegli “Automatico” salvo esigenze diverse.',
        'layout_auto_home' => 'Usa tutta la larghezza edge-to-edge (consigliato per la home del sito).',
        'layout_auto_page' => 'Usa la larghezza contenuta della pagina standard.',
        'sub_theme_marketing_recommended' => 'Le pagine a tutta larghezza funzionano meglio con un tema marketing (Site o il tuo tema custom).',
    ],
];
