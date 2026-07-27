<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Voodbuilder',
        'label' => 'Integrazioni modello',
    ],

    'bindings' => [
        'package' => 'Integrazioni modello',
        'auth' => 'Profilo utente',
        'latest' => 'Ultimo record',
        'list_item' => 'Elemento lista',
        'repeat_source' => 'Lista ripetuta',
        'relation_group' => 'Relazione · :name',
        'filter_by' => 'Filtra per :name',
        'filter_any' => 'Qualsiasi',
    ],

    'sections' => [
        'details' => 'Dettagli integrazione',
        'fields' => 'Campi disponibili',
    ],

    'fields' => [
        'name' => 'Nome',
        'model_class' => 'Classe modello',
        'model_alias' => 'Alias binding',
        'essential_fields' => 'Campi essenziali',
        'relations' => 'Relazioni',
        'relation_name' => 'Nome relazione',
        'relation_alias' => 'Alias relazione',
        'relation_fields' => 'Campi relazione',
        'nested_relations' => 'Relazioni annidate',
        'expand_relation' => 'Relazione',
        'fields_to_load' => 'Campi da caricare',
        'updated_at' => 'Aggiornato',
    ],

    'helpers' => [
        'model_alias' => 'Usato nei binding Dynamic (es. tutorial.latest.title). Default: nome modello in camelCase.',
        'relation_alias' => 'Alias opzionale per relazioni avanzate.',
        'nested_relations' => 'Carica relazioni annidate per gli elementi delle liste.',
        'fields_optional' => 'Lascia vuoto per caricare tutti i campi della relazione.',
    ],

    'actions' => [
        'add_relation' => 'Aggiungi relazione',
        'add_nested_relation' => 'Aggiungi relazione annidata',
    ],

    'labels' => [
        'reverse' => 'relazione inversa',
    ],
];
