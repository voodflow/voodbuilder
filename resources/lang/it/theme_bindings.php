<?php

declare(strict_types=1);

return [
    'section_title' => 'Layout',
    'section_help' => 'Scegli quale layout visivo usare per ogni area del sito. I package installati possono aggiungere righe in automatico. Tutto ciò che non ha una riga dedicata usa il layout Home e pagine sito.',
    'layout_column' => 'Layout',
    'site_pages' => 'Home e pagine sito',
    'site_pages_description' => 'Home, schermate di accesso e pagine CMS da Sito → Pagine. Per una singola pagina: Pagine → Pubblica.',
    'unregistered_fallback' => 'I package installati senza area vpress usano il layout Home e pagine sito qui sopra — non il layout “Documentation” a meno che non lo imposti qui.',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">Nessuna area da package per ora. Quando installi vdocs, vtuts, eventi e simili, compaiono in questo elenco.</p>',
    'channels' => [
        'docs' => 'Documentazione dal package vdocs.',
        'tutorials' => 'Tutorial dal package vtuts.',
        'blog' => 'Articoli blog dal package blog.',
        'news' => 'Articoli news dal package news.',
        'events' => 'Eventi e listing dal package eventi.',
        'exhibitors' => 'Profili e directory espositori.',
    ],
    'channel_generic' => 'Pagine pubbliche per :label.',
];
