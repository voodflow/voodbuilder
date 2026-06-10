<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Vpress',
        'menus' => 'Menu',
        'pages' => 'Pagine',
        'settings' => 'Impostazioni',
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
    ],

    'helpers' => [
        'menu_route' => 'Route GET pubbliche registrate nell\'app. Lo stato attivo viene impostato automaticamente.',
        'menu_route_match' => 'Opzionale. Usato solo per URL esterni quando serve una regola di evidenziazione personalizzata.',
        'sub_theme_site' => 'Stile visivo predefinito del sito pubblico. Le singole pagine possono sovrascriverlo.',
        'sub_theme_page' => 'Sovrascrive il sotto-tema del sito per questa pagina — utile per sezioni blog o news nel menu.',
        'excerpt' => 'Breve riassunto per elenchi di sezione, card e SEO.',
        'section_home' => 'Contrassegna questa pagina come indice della sezione (elenca le pagine correlate nella sidebar).',
    ],
];
