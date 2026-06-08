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
    ],

    'helpers' => [
        'menu_route' => 'Route GET pubbliche registrate nell\'app. Lo stato attivo viene impostato automaticamente.',
        'menu_route_match' => 'Opzionale. Usato solo per URL esterni quando serve una regola di evidenziazione personalizzata.',
    ],
];
