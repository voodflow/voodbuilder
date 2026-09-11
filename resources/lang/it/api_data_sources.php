<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Sorgenti dati API',
    ],

    'model' => [
        'label' => 'Sorgente dati API',
        'plural' => 'Sorgenti dati API',
    ],

    'sections' => [
        'details' => 'Dettagli',
        'http' => 'HTTP',
        'static' => 'Righe statiche',
        'eloquent' => 'Model integration',
        'callback' => 'Callback',
    ],

    'fields' => [
        'name' => 'Nome',
        'slug' => 'Slug',
        'enabled' => 'Attiva',
        'driver' => 'Driver',
        'description' => 'Descrizione',
        'cache_ttl_seconds' => 'Cache TTL (secondi)',
        'cache_ttl_help' => '0 disattiva la cache. Le risposte in cache valgono per i binding di pagina senza query/value.',
        'url' => 'URL',
        'method' => 'Metodo',
        'query_param' => 'Parametro di ricerca',
        'value_param' => 'Parametro valore esatto',
        'response_path' => 'Path risposta (dot)',
        'value_path' => 'Path value',
        'label_path' => 'Path label',
        'min_query_length' => 'Lunghezza minima query',
        'local_filter' => 'Filtro locale di fallback',
        'expose_all_fields' => 'Espandi tutti i campi API',
        'headers' => 'Header',
        'meta_paths' => 'Meta path (alias opzionali)',
        'model_integration_id' => 'Model integration',
        'value_column' => 'Colonna value',
        'label_column' => 'Colonna label',
        'search_columns' => 'Colonne di ricerca',
        'meta_columns' => 'Meta colonne (chiave → colonna DB)',
        'filters' => 'Filtri fissi (colonna → valore)',
        'callback' => 'Chiave callback',
        'rows' => 'Righe',
        'row_value' => 'Value',
        'row_label' => 'Label',
        'row_meta' => 'Meta',
    ],

    'helpers' => [
        'slug' => 'Binding: {slug}.remote / {slug}.item / List repeat {slug}.list',
        'meta_paths' => 'Solo alias opzionali. Con “Espandi tutti i campi API” attivo, List item mostra già firstName, email, image… dal JSON. Usa questa sezione solo per rinominare path (chiave → path risposta).',
        'url' => 'URL https assoluto. Token: {{query}}, {{value}}, {{route.id}}, {{request.locale}}. Auth negli Header.',
        'headers' => 'Solo header HTTP (Authorization, Accept, …). Non mettere qui i nomi dei campi JSON.',
        'response_path' => 'Path (dot) della lista nel JSON. DummyJSON users → users (non data). Vuoto = auto-detect users/data/results/items.',
        'label_path' => 'Campo usato come label della lista. DummyJSON: firstName (oppure lascia vuoto per firstName + lastName).',
        'expose_all_fields' => 'Se attivo, ogni campo scalare della riga API (e un livello nested, es. address.city) diventa un binding List item.',
        'eloquent' => 'Riusa un Model Integration (allowlist) — stesso modello di sicurezza delle integrazioni Dynamic Data.',
        'search_columns' => 'Colonne separate da virgola per typeahead (es. name,slug).',
        'meta_columns' => 'Ogni chiave diventa un campo bindabile (come i meta path HTTP).',
        'callback' => 'Chiave in config voodbuilder.api_data_sources.callbacks o Voodbuilder::registerApiDataSourceCallback().',
    ],

    'bindings' => [
        'package' => 'Sorgenti dati API',
        'remote' => 'Remoto',
        'list_item' => 'Elemento lista',
        'repeat_source' => 'Lista',
        'fields' => [
            'value' => 'Value',
            'label' => 'Label',
        ],
    ],

    'table' => [
        'name' => 'Nome',
        'slug' => 'Slug',
        'driver' => 'Driver',
        'enabled' => 'Attiva',
    ],
];
