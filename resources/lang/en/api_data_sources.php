<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'API Data Sources',
    ],

    'model' => [
        'label' => 'API Data Source',
        'plural' => 'API Data Sources',
    ],

    'sections' => [
        'details' => 'Details',
        'http' => 'HTTP',
        'static' => 'Static rows',
        'eloquent' => 'Model integration',
        'callback' => 'Callback',
    ],

    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'enabled' => 'Enabled',
        'driver' => 'Driver',
        'description' => 'Description',
        'cache_ttl_seconds' => 'Cache TTL (seconds)',
        'cache_ttl_help' => '0 disables caching. Cached responses are used for page bindings without query/value params.',
        'url' => 'URL',
        'method' => 'Method',
        'query_param' => 'Search query param',
        'value_param' => 'Exact value param',
        'response_path' => 'Response path (dot)',
        'value_path' => 'Value path',
        'label_path' => 'Label path',
        'min_query_length' => 'Min query length',
        'local_filter' => 'Local filter fallback',
        'expose_all_fields' => 'Expose all API fields',
        'headers' => 'Headers',
        'meta_paths' => 'Meta paths (optional aliases)',
        'model_integration_id' => 'Model integration',
        'value_column' => 'Value column',
        'label_column' => 'Label column',
        'search_columns' => 'Search columns',
        'meta_columns' => 'Meta columns (key → DB column)',
        'filters' => 'Fixed filters (column → value)',
        'callback' => 'Callback key',
        'rows' => 'Rows',
        'row_value' => 'Value',
        'row_label' => 'Label',
        'row_meta' => 'Meta',
    ],

    'helpers' => [
        'slug' => 'Bindings: {slug}.remote / {slug}.item / List repeat {slug}.list',
        'meta_paths' => 'Optional aliases only. With “Expose all API fields” on, List item already lists firstName, email, image, … from the JSON. Put fields here only to rename paths (key → response path).',
        'url' => 'Absolute https URL. Tokens: {{query}}, {{value}}, {{route.id}}, {{request.locale}}. Auth in Headers (Authorization: Bearer …).',
        'headers' => 'HTTP headers only (Authorization, Accept, …). Do not put JSON field names here.',
        'response_path' => 'Dot path to the list in the JSON. DummyJSON users → users (not data). Leave empty to auto-detect users/data/results/items.',
        'label_path' => 'Field used as the list label. DummyJSON: firstName (or leave blank to use firstName + lastName).',
        'expose_all_fields' => 'When on, every scalar field from each API row (plus one nested level, e.g. address.city) becomes a List item binding.',
        'eloquent' => 'Reuse a Model Integration allowlist — same security model as Dynamic Data integrations.',
        'search_columns' => 'Comma-separated columns for typeahead (e.g. name,slug).',
        'meta_columns' => 'Each key becomes a bindable field (like meta paths for HTTP).',
        'callback' => 'Key registered in config voodbuilder.api_data_sources.callbacks or Voodbuilder::registerApiDataSourceCallback().',
    ],

    'bindings' => [
        'package' => 'API Data Sources',
        'remote' => 'Remote',
        'list_item' => 'List item',
        'repeat_source' => 'List',
        'fields' => [
            'value' => 'Value',
            'label' => 'Label',
        ],
    ],

    'table' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'driver' => 'Driver',
        'enabled' => 'Enabled',
    ],
];
