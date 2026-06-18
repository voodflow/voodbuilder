<?php

declare(strict_types=1);

return [
    'learn_more' => 'Scopri di più',
    'video_unavailable' => 'Video non disponibile. Controlla l\'URL YouTube nelle impostazioni del blocco.',

    'blocks' => [
        'hero' => 'Hero',
        'banner_cta' => 'Banner CTA',
        'split' => 'Immagine + testo',
        'feature_grid' => 'Griglia funzionalità',
        'stats' => 'Contatori statistiche',
        'logo_row' => 'Fila loghi',
        'steps' => 'Passi',
        'faq' => 'FAQ',
        'text' => 'Sezione testo',
        'video' => 'Video (YouTube)',
        'social_share' => 'Condividi sui social',
        'footer' => 'Footer landing',
        'contact_cta' => 'CTA contatti',
    ],

    'fields' => [
        'heading' => 'Titolo',
        'title' => 'Titolo',
        'description' => 'Descrizione',
        'columns' => 'Colonne',
        'eyebrow' => 'Occhiello',
        'subheading' => 'Sottotitolo',
        'intro' => 'Introduzione',
        'body' => 'Testo',
        'background_style' => 'Stile sfondo',
        'background_tone' => 'Colore sfondo',
        'background_color' => 'Colore personalizzato',
        'background_image' => 'Immagine di sfondo',
        'background_image_url' => 'URL immagine di sfondo',
        'overlay_opacity' => 'Opacità overlay (%)',
        'text_align' => 'Allineamento testo',
        'tall_hero' => 'Hero alto',
        'primary_button_label' => 'Etichetta pulsante primario',
        'primary_button_url' => 'URL pulsante primario',
        'secondary_button_label' => 'Etichetta pulsante secondario',
        'secondary_button_url' => 'URL pulsante secondario',
        'button_style' => 'Stile pulsante',
        'image_url' => 'URL immagine',
        'image' => 'Immagine',
        'image_position' => 'Posizione immagine',
        'features' => 'Funzionalità',
        'icon' => 'Icona / emoji',
        'link_label' => 'Etichetta link',
        'link_url' => 'URL link',
        'faq_items' => 'Domande',
        'question' => 'Domanda',
        'answer' => 'Risposta',
        'steps' => 'Passi',
        'stats' => 'Statistiche',
        'stat_value' => 'Valore',
        'stat_label' => 'Etichetta',
        'caption' => 'Didascalia',
        'video_url' => 'URL YouTube',
        'content_width' => 'Larghezza contenuto',
        'section_width' => 'Larghezza sezione',
        'section_padding' => 'Spaziatura sezione',
        'logos' => 'Loghi',
        'logo_name' => 'Nome (testo alt)',
        'logo_image' => 'Immagine logo',
        'logo_image_url' => 'URL logo',
        'logo_grayscale' => 'Loghi in scala di grigi fino al passaggio del mouse',
    ],

    'helpers' => [
        'icon' => 'Emoji o simbolo breve opzionale sopra il titolo.',
        'youtube_url' => 'Incolla un URL YouTube (watch, share o embed).',
        'section_width' => 'Bleed occupa tutta la larghezza; contained mantiene il contenuto centrato.',
        'background_color' => 'Usato quando il colore sfondo è impostato su Personalizzato.',
        'background_image' => 'Carica un\'immagine ampia per hero full-bleed.',
        'content_image' => 'Carica una foto o illustrazione per questa sezione.',
    ],

    'tones' => [
        'brand' => 'Colore brand',
        'dark' => 'Scuro',
        'light' => 'Chiaro',
        'custom' => 'Personalizzato',
    ],

    'background_styles' => [
        'solid' => 'Colore solido',
        'image' => 'Immagine di sfondo',
    ],

    'align' => [
        'center' => 'Centro',
        'left' => 'Sinistra',
    ],

    'image_position' => [
        'left' => 'Immagine a sinistra',
        'right' => 'Immagine a destra',
    ],

    'button_styles' => [
        'solid' => 'Pieno',
        'outline' => 'Contorno',
        'ghost' => 'Ghost',
    ],

    'content_width' => [
        'wide' => 'Ampio',
        'narrow' => 'Stretto',
    ],

    'section_width' => [
        'bleed' => 'Full bleed (bordo a bordo)',
        'full' => 'Larghezza piena con padding',
        'contained' => 'Contenuto (max larghezza layout)',
        'narrow' => 'Colonna stretta',
    ],

    'section_padding' => [
        'default' => 'Predefinita',
        'large' => 'Ampia',
        'none' => 'Nessuna',
    ],

    'social' => [
        'default_heading' => 'Condividi questo sito',
        'share_url' => 'URL da condividere',
        'share_url_help' => 'Lascia vuoto per usare l\'URL della pagina corrente.',
        'share_title' => 'Titolo condivisione',
        'share_title_help' => 'Usato per X, WhatsApp ed email. Predefinito: titolo del sito.',
        'networks_label' => 'Social network',
        'copied' => 'Copiato!',
        'networks' => [
            'facebook' => 'Facebook',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'copy_link' => 'Copia link',
        ],
    ],

    'footer' => [
        'logo' => 'Logo footer',
        'logo_help' => 'Carica un logo chiaro per footer landing su sfondo scuro.',
        'brand_name' => 'Nome brand',
        'brand_name_help' => 'Mostrato quando non è caricato un logo.',
        'organizer' => 'Organizzatore',
        'organizer_help' => 'Recupera i dati di contatto dall\'organizzatore selezionato. Sulle pagine evento, se vuoto, usa l\'organizzatore dell\'evento corrente.',
        'organizer_legal' => 'Riga legale / P.IVA',
        'menu_placement' => 'Menu footer',
        'menu_placement_help' => 'Gestisci le colonne in Admin → Menu con posizione "Colonne footer landing". Ogni gruppo di primo livello diventa una colonna.',
        'default_menu' => 'Colonne footer landing',
        'organizer_line_1' => 'Riga contatto 1',
        'organizer_line_2' => 'Riga contatto 2',
        'organizer_email' => 'Email contatto',
        'menu_columns' => 'Colonne menu',
        'menu_columns_help' => 'Fino a 4 colonne opzionali (es. Contatti, Info, Espositori, Visitatori).',
        'menu_links' => 'Link',
        'open_in_new_tab' => 'Apri in nuova scheda',
        'copyright_year' => 'Anno copyright',
        'copyright_brand' => 'Brand copyright',
        'copyright_claim' => 'Dichiarazione copyright',
        'copyright_highlight' => 'Evidenziato',
        'copyright_highlight_help' => 'Mostrato nel colore accent (es. nome partner).',
        'copyright_links' => 'Link inline nel copyright',
        'link_highlight' => 'Colore accent',
    ],

    'link' => [
        'type' => 'Tipo link',
    ],

    'contact' => [
        'intro' => 'Testo introduttivo',
        'highlight_phrase' => 'Frase evidenziata',
        'display_style' => 'Azione principale',
        'display_email' => 'Email grande',
        'display_button' => 'Pulsante',
        'email' => 'Indirizzo email',
        'link_type' => 'Tipo link',
        'link_url' => 'URL',
        'link_email' => 'Email',
        'link_target' => 'Destinazione link',
        'link_target_help' => 'URL completo o indirizzo email a seconda del tipo di link.',
    ],

    'layouts' => [
        'landing' => 'Landing (senza margini)',
        'landing_help' => 'Canvas a tutta larghezza per i blocchi landing. Nascondi il footer del sito se usi un blocco footer landing.',
        'hide_site_footer' => 'Nascondi footer del sito',
        'hide_site_footer_help' => 'Usa quando la pagina include un blocco footer landing.',
    ],
];
