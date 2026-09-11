<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Layout',
    ],

    'model' => [
        'label' => 'Layout',
        'plural' => 'Layout',
    ],

    'sections' => [
        'details' => 'Dettagli',
        'reading' => 'Tipografia di lettura',
        'reading_help' => 'Famiglia e dimensione del testo degli articoli docs/tutorial quando questo layout avvolge quei canali. L’anteprima live sotto usa le stesse variabili CSS consumate dai layout companion sul sito pubblico.',
    ],

    'preview' => [
        'heading' => 'Anteprima lettura',
        'hint' => 'Simula le pagine companion che estendono lo shell chrome di Voodbuilder.',
        'sample' => 'Campione generico',
        'sample_eyebrow' => 'Documentazione',
        'sidebar' => 'Indice',
        'nav_active' => 'Modello layout',
        'nav_item' => 'Per iniziare',
        'nav_item_alt' => 'Token tema',
        'toc' => 'In questa pagina',
        'toc_active' => 'Pattern consigliato',
        'toc_item' => 'Section',
    ],

    'fields' => [
        'name' => 'Nome',
        'slug' => 'Slug',
        'enabled' => 'Attivo',
        'is_default' => 'Layout predefinito',
        'is_default_help' => 'Fallback del sito: usato solo quando nessun altro layout è assegnato al canale della richiesta. Ha priorità inferiore rispetto agli assegnamenti per canale.',
        'channels' => 'Canali contenuto',
        'channels_help' => 'Assegna questo layout a uno o più canali. Documentation e Tutorials condividono il chrome se ne assegni solo uno. Search eredita il chrome di Documentation finché non assegni Search esplicitamente. Gli assegnamenti vincono sempre sul layout predefinito.',
        'content_width' => 'Larghezza contenuto',
        'content_width_help' => 'Full, standard (~80rem) o max personalizzata per il contenuto pagina.',
        'content_width_full' => 'Tutta larghezza',
        'content_width_standard' => 'Pagina standard',
        'content_width_custom' => 'Personalizzata',
        'content_max_width' => 'Larghezza max contenuto',
        'content_max_width_help' => 'Valore CSS, es. 72rem o 1200px.',
        'content_max_width_full_help' => 'Misura boxed opzionale per la toolbar elemento (Personalizzata). Il contenuto pagina resta a tutta larghezza.',
        'chrome_width' => 'Larghezza nav e footer',
        'chrome_width_help' => 'Nav/footer a tutta larghezza, oppure allineati al contenuto sopra.',
        'chrome_width_full' => 'Tutta larghezza',
        'chrome_width_content' => 'Come il contenuto',
        'reading_font' => 'Font articolo',
        'reading_font_help' => 'Applicato a docs e tutorial (`.vp-doc`). Inter Variable resta il default del tema sito.',
        'reading_font_size' => 'Dimensione base articolo',
        'reading_font_size_help' => 'Corpo del testo nella colonna di lettura. I titoli scalano di conseguenza.',
        'reading_font_size_sm' => 'Piccola (15px)',
        'reading_font_size_md' => 'Media (16px)',
        'reading_font_size_lg' => 'Grande (17px) — stile VitePress',
        'reading_font_size_xl' => 'Extra grande (18px)',
        'reading_preview_channel' => 'Campione anteprima',
        'reading_preview_channel_help' => 'I companion possono registrare un proprio campione con Voodbuilder::readingPreview(). La tipografia resta quella dei controlli sopra.',
    ],

    'actions' => [
        'open_visual_editor' => 'Apri editor visuale',
        'clone' => 'Clona layout',
    ],

    'clone' => [
        'modal_heading' => 'Clona layout',
        'modal_description' => 'Crea una copia di questo layout con un nuovo nome e slug. Header, footer e slot contenuto vengono duplicati.',
    ],

    'notifications' => [
        'cloned' => 'Layout clonato',
    ],

    'editor' => [
        'editing_badge' => 'Contenuto pagina',
        'editing_hint' => 'Layout: {name} · header e footer si modificano solo nel layout',
        'layout_editing_badge' => 'Modifica layout: {name}',
        'layout_editing_hint' => 'Mantieni un solo slot contenuto tra header e footer',
        'page_content_placeholder' => 'Trascina qui i blocchi per costruire la pagina',
        'layout_content_slot_placeholder' => 'Contenuto pagina — riempito automaticamente da ogni pagina.',
        'layout_nav_zone_placeholder' => 'Trascina qui i blocchi header',
        'layout_footer_zone_placeholder' => 'Trascina qui i blocchi footer',
    ],

    'page_form' => [
        'layout' => 'Layout',
        'layout_inherit_default' => 'Predefinito del sito',
        'layout_inherit_named' => 'Predefinito del canale (:name)',
        'layout_help' => 'Header e footer condivisi attorno al contenuto della pagina. Modifica nav e footer in Admin → Layout.',
        'shell' => 'Layout',
        'none' => 'Nessun layout chrome assegnato al canale Pages. Nav e footer usano il guscio classico.',
        'edit_shell' => 'Modifica layout',
        'canvas_width_help' => 'Nav e footer restano a tutta larghezza. Qui scegli se il contenuto pagina è edge-to-edge oppure contenuto (~80rem). Non ridimensiona l’area di editing.',
    ],
];
