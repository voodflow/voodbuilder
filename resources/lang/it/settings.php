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

    'theme_scope_info' => '<div class="rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-100"><strong class="font-medium">Marketing vs canali contenuti</strong><p class="mt-2">Le <strong>Site Page marketing</strong> (home, landing, CMS) usano un sotto-tema <em>marketing</em> — costruite con GrapesJS o il rich editor. I <strong>canali contenuti</strong> (docs, tutorial, eventi, futuro blog) usano sotto-temi <em>contenuti</em> configurati in Temi canali. Logo, SEO, cookie e analytics valgono ovunque.</p></div>',

    'theme_default_section' => 'Predefinito marketing',
    'theme_marketing_default_help' => 'Sotto-tema di fallback per le Site Page senza override per pagina.',
    'theme_colors_section' => 'Colori brand',
    'theme_colors_content' => 'Temi canali contenuti',
    'theme_colors_marketing' => 'Temi marketing',
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
