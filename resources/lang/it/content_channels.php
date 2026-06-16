<?php

declare(strict_types=1);

return [
    'page_title' => 'Sezioni sito',
    'intro' => '<p class="text-sm text-gray-600 dark:text-gray-400">Ogni sezione è registrata da un package installato (eventi, espositori, tutorial, …). Scegli quale sotto-tema visivo applicare quando i visitatori navigano quelle route. Ricerca e route restano definite nel codice; qui puoi cambiare solo l’aspetto.</p>',
    'empty' => '<p class="text-sm text-gray-600 dark:text-gray-400">Nessun content channel registrato. Installa un package come vevents o vexhibitors, oppure registra i channel in <code>config/vpress.php</code>.</p>',
    'package_default' => 'Predefinito del package',
    'visual_theme' => 'Sotto-tema visivo',
    'override_help' => 'Lascia su “Predefinito del package” per usare il tema dichiarato dal package. Scegli un altro sotto-tema registrato per sovrascriverlo solo in questa sezione.',
    'no_default_theme' => 'Predefinito sito',
    'inherit_package' => 'Predefinito del package',
    'save' => 'Salva sezioni',
    'saved' => 'Temi delle sezioni salvati.',
];
