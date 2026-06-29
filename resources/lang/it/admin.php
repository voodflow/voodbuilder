<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'menus' => 'Menu',
        'pages' => 'Pagine',
        'settings' => 'Impostazioni',
        'content_channels' => 'Temi canali',
    ],

    'fields' => [
        'menu_route' => 'Route applicazione',
        'menu_route_match' => 'Pattern route attiva',
        'sub_theme' => 'Tema visivo',
        'sub_theme_inherit' => 'Predefinito sito',
        'layout_auto' => 'Automatico',
        'layout_standard' => 'Pagina standard',
        'layout_full_width' => 'Tutta larghezza (marketing)',
        'excerpt' => 'Estratto',
        'section' => 'Sezione',
        'section_none' => 'Nessuna',
        'section_home' => 'Home sezione',
        'sub_items' => 'Sotto-voci',
        'language' => 'Lingua',
        'target_language' => 'Lingua di destinazione',
        'translations' => 'Traduzioni',
    ],

    'actions' => [
        'translate_page' => 'Crea traduzione',
    ],

    'translation' => [
        'tooltip' => 'Duplica questa pagina in un’altra lingua',
        'modal_heading' => 'Crea traduzione pagina',
        'modal_description' => 'Verrà creata una bozza con lo stesso layout e contenuto. Aggiorna titolo, slug e testi per la lingua scelta.',
        'none_yet' => 'Nessuna traduzione collegata.',
    ],

    'notifications' => [
        'translation_created' => 'Traduzione creata',
        'translation_created_body' => 'Modifica la versione :locale e pubblica quando pronta.',
        'home_reassigned' => 'Altre home aggiornate',
        'home_reassigned_body' => ':count altra/e pagina/e non sono più contrassegnate come home.',
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

    'helpers' => [
        'menu_route' => 'Route GET pubbliche registrate nell\'app. Lo stato attivo viene impostato automaticamente. Se la route richiede parametri, compila i campi mostrati sotto.',
        'menu_route_match' => 'Opzionale. Usato solo per URL esterni quando serve una regola di evidenziazione personalizzata.',
        'menu_sub_items' => 'Mostrate in un menu a tendina come Docs. Usa "Gruppo dropdown" per titoli di sezione senza link proprio.',
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
