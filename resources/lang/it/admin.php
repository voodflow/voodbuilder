<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Vpress',
        'menus' => 'Menu',
        'pages' => 'Pagine',
        'settings' => 'Impostazioni',
        'content_channels' => 'Temi canali',
    ],

    'fields' => [
        'menu_route' => 'Route applicazione',
        'menu_route_match' => 'Pattern route attiva',
        'sub_theme' => 'Sotto-tema',
        'sub_theme_inherit' => 'Predefinito sito',
        'excerpt' => 'Estratto',
        'section' => 'Sezione',
        'section_none' => 'Nessuna',
        'section_home' => 'Home sezione',
        'sub_items' => 'Sotto-voci',
    ],

    'validation' => [
        'menu_max_depth' => 'Il menu supporta al massimo :max livelli (voce principale e sotto-voci). Sposta la voce sotto una voce di primo livello.',
    ],

    'helpers' => [
        'menu_route' => 'Route GET pubbliche registrate nell\'app. Lo stato attivo viene impostato automaticamente. Se la route richiede parametri, compila i campi mostrati sotto.',
        'menu_route_match' => 'Opzionale. Usato solo per URL esterni quando serve una regola di evidenziazione personalizzata.',
        'menu_sub_items' => 'Mostrate in un menu a tendina come Docs. Usa "Gruppo dropdown" per titoli di sezione senza link proprio.',
        'menu_tree' => 'Trascina per riordinare o rilascia una voce su un’altra per creare un sottomenu. Massimo 2 livelli (principale + sotto-voci). Usa "Gruppo dropdown" per etichette senza link.',
        'sub_theme_site' => 'Tema visivo predefinito per le Site Page (home, landing, CMS). Configura le assegnazioni per area sotto.',
        'sub_theme_page' => 'Sovrascrive il layout globale (Settings → Layouts). Per ereditare Politecnico o altro tema globale, scegli “Predefinito sito”.',
        'excerpt' => 'Breve riassunto per elenchi di sezione, card e SEO.',
        'section_home' => 'Contrassegna questa pagina come indice della sezione (elenca le pagine correlate nella sidebar).',
    ],
];
