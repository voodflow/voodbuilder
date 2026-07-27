<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'label' => 'Model integrations',
    ],

    'bindings' => [
        'package' => 'Model integrations',
        'auth' => 'User profile',
        'latest' => 'Latest record',
        'list_item' => 'List item',
        'repeat_source' => 'Repeat list',
        'relation_group' => 'Relation · :name',
        'filter_by' => 'Filter by :name',
        'filter_any' => 'Any',
    ],

    'sections' => [
        'details' => 'Integration details',
        'fields' => 'Available fields',
    ],

    'fields' => [
        'name' => 'Name',
        'model_class' => 'Model class',
        'model_alias' => 'Binding alias',
        'essential_fields' => 'Essential fields',
        'relations' => 'Relations',
        'relation_name' => 'Relation name',
        'relation_alias' => 'Relation alias',
        'relation_fields' => 'Relation fields',
        'nested_relations' => 'Nested relations',
        'expand_relation' => 'Relation',
        'fields_to_load' => 'Fields to load',
        'updated_at' => 'Updated',
    ],

    'helpers' => [
        'model_alias' => 'Used in Dynamic bindings (e.g. tutorial.latest.title). Defaults to the camelCase model name.',
        'relation_alias' => 'Optional alias for this relation in advanced bindings.',
        'nested_relations' => 'Load nested relations when resolving list items.',
        'fields_optional' => 'Leave empty to load all fields on the relation.',
    ],

    'actions' => [
        'add_relation' => 'Add relation',
        'add_nested_relation' => 'Add nested relation',
    ],

    'labels' => [
        'reverse' => 'reverse relation',
    ],
];
