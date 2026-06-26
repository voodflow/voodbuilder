<?php

declare(strict_types=1);

return [
    'section_title' => 'Tema del sito pubblico',
    'section_help' => 'Aspetto predefinito per home, pagine CMS e (se non sovrascrivi sotto) blog, eventi e aree simili.',
    'intro' => 'Scegli il tema visivo principale. Docs e tutorial di solito restano su Documentation; il resto eredita questa scelta salvo override in Avanzate.',
    'layout_column' => 'Tema',
    'site_pages' => 'Tema predefinito del sito',
    'site_pages_description' => 'Home, landing e Site → Pages. È il tema che vedono la maggior parte dei visitatori.',
    'advanced_section' => 'Avanzate: override per area',
    'advanced_section_help' => 'Modifica solo se un’area deve essere diversa — es. Documentation per i tutorial ma Site per il blog.',
    'use_site_default' => 'Come il tema predefinito del sito (:theme)',
    'unregistered_fallback' => 'Le aree senza override usano il tema predefinito del sito sopra.',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">Nessuna area da package ancora. Con vdocs, vtuts, eventi, ecc. compariranno sotto Override avanzate.</p>',
    'channels' => [
        'docs' => 'Documentazione dal package vdocs.',
        'tutorials' => 'Tutorial dal package vtuts.',
        'blog' => 'Articoli blog (Ink o simili).',
        'news' => 'Articoli news dal package news.',
        'events' => 'Eventi ed elenchi dal package eventi.',
        'exhibitors' => 'Profili espositori e directory.',
    ],
    'channel_generic' => 'Pagine pubbliche per :label.',
];
