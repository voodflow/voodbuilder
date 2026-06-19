<?php

declare(strict_types=1);

return [
    'section_title' => 'Assegnazione temi',
    'section_help' => 'Scegli quale tema visivo applicare a ogni area pubblica. I canali sono registrati dai package installati (docs, tutorial, blog, eventi). Per ogni area sono elencati solo i temi compatibili.',
    'site_pages' => 'Pagine sito',
    'site_pages_help' => 'Tema predefinito per le Site Page marketing (home, landing, CMS). Ogni pagina può sovrascriverlo in Pagine.',
    'channel_help' => 'Richiede un tema :capability. Predefinito package: :default.',
    'no_package_default' => 'Predefinito sito',
    'no_channels' => '<p class="text-sm text-gray-600 dark:text-gray-400">Nessun content channel registrato. Installa un package come vdocs, vtuts o vevents.</p>',
    'inherit_default' => 'Predefinito package',
];
