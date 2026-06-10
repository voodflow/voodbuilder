<?php

return [
    'logo_help' => 'Logo header desktop. Consigliato: altezza 80–120 px (o SVG), larghezza fino a ~400 px. In header viene mostrato a ~40 px di altezza.',
    'logo_mobile' => 'Logo mobile (opzionale)',
    'logo_mobile_help' => 'Versione compatta per schermi piccoli (es. solo icona). Consigliato: 64–80 px. Se assente, si usa il logo principale.',
    'primary_locale' => 'Lingua primaria del sito',
    'primary_locale_help' => 'Lingua predefinita per interfaccia ed elenchi. URL senza prefisso /en/ o /it/. Le traduzioni collegate usano slug propri (es. /tutorials/my-article e /tutorials/il-mio-articolo).',
    'default_ui_locale' => 'Lingua predefinita dell’interfaccia',
    'default_ui_locale_help' => 'Usata per menu, pulsanti e altre stringhe dell’interfaccia quando lo switcher lingua è nascosto.',

    'tabs' => [
        'site' => 'Sito',
        'appearance' => 'Aspetto',
        'theme' => 'Tema',
        'seo' => 'SEO',
        'geo_ai' => 'GEO & AI',
        'analytics' => 'Analytics',
    ],

    'theme_scope_info' => '<div class="rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-100"><strong class="font-medium">Globale vs sotto-tema</strong><p class="mt-2">Logo, SEO predefiniti, cookie bar, analytics, navigazione e lingua si applicano a <strong>tutti</strong> i sotto-temi. I sotto-temi cambiano solo layout e stile visivo (tipografia, colonne, accenti). Ogni pagina può sovrascrivere il sotto-tema predefinito del sito.</p></div>',

    'theme_default_section' => 'Sotto-tema predefinito',
    'theme_colors_section' => 'Colori brand',
    'theme_colors_help' => 'Override opzionali per ogni sotto-tema. Se disattivato, si usa la palette integrata nel foglio di stile del tema.',
    'theme_customize' => 'Personalizza colori brand',
    'theme_customize_help' => 'Sovrascrive la palette integrata solo per questo sotto-tema.',
    'theme_light_mode' => 'Modalità chiara',
    'theme_dark_mode' => 'Modalità scura',
    'theme_primary' => 'Colore primario',
    'theme_primary_help' => 'Link, accenti ed evidenziazioni.',
    'theme_secondary' => 'Colore secondario',
    'theme_secondary_help' => 'Pulsanti e accenti più marcati. Il tono intermedio viene calcolato automaticamente.',
    'theme_dark_primary_help' => 'Colore primario con modalità scura attiva.',
    'theme_dark_secondary_help' => 'Colore secondario con modalità scura attiva.',
];
