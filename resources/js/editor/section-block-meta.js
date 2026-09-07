/**
 * Descriptive sidebar labels and per-block wireframes for section blocks.
 */

import { isComponentBlock, isComponentCategoryId } from './component-block-utils.js';
import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';

export const CODE_BLOCK_CATEGORY = 'Code';

export const CATEGORY_ORDER = {
    Pinned: -200,
    Layout: -190,
    Basic: -180,
    Media: -170,
    Single: -160,
    Site: -110,
    Hero: -100,
    Content: -95,
    Code: -94,
    Features: -90,
    Articles: -85,
    Gallery: -80,
    Stats: -75,
    Testimonials: -70,
    Team: -65,
    Steps: -60,
    Pricing: -55,
    CTA: -50,
    Animated: -48,
    Contact: -45,
    Shop: -40,
    Tabs: -35,
    Header: -30,
    Footer: -25,
    Voodbuilder: -120,
    Dynamic: -115,
    Events: -114,
    Eventi: -114,
    Forms: 100,
};

/** @type {Record<string, string>} */
const CATEGORY_ALIASES = {
    forms: 'Forms',
    dynamic: 'Events',
    Dynamic: 'Events',
    'Vevents / Landing': 'Events',
    'vevents / landing': 'Events',
    Exhibitors: 'Events',
    Espositori: 'Events',
    Partners: 'Events',
    Partner: 'Events',
    Sponsors: 'Events',
    Sponsor: 'Events',
    'Sections · Hero': 'Hero',
    'Sections · Content': 'Content',
    'Sections · Features': 'Features',
    'Sections · Articles': 'Articles',
    'Sections · Gallery': 'Gallery',
    'Sections · Stats': 'Stats',
    'Sections · Testimonials': 'Testimonials',
    'Sections · Team': 'Team',
    'Sections · Steps': 'Steps',
    'Sections · Pricing': 'Pricing',
    'Sections · CTA': 'CTA',
    'Sections · Contact': 'Contact',
    'Sections · Shop': 'Shop',
    'Sections · Tabs': 'Tabs',
    'Sections · Header': 'Header',
    'Sections · Footer': 'Footer',
    Extra: 'Layout',
};

export const BASIC_BLOCK_LABELS = {
    image: 'Image',
    video: 'Video',
    'voodbuilder-heading': 'Heading',
    'voodbuilder-text': 'Basic text',
    'voodbuilder-rich-text': 'Rich text',
    'voodbuilder-text-link': 'Text link',
    'voodbuilder-button': 'Button',
    'voodbuilder-icon': 'Icon',
    'voodbuilder-divider': 'Divider',
    'voodbuilder-layout-section': 'Section',
    'voodbuilder-layout-container': 'Container',
    'voodbuilder-layout-block': 'Block',
    'voodbuilder-layout-div': 'Div',
};

/** @type {Record<string, string>} */
export const SECTION_BLOCK_LABELS = {
    // Hero
    'vb-bg-image': 'Hero · background image',
    'vb-bg-video': 'Hero · background video',
    'vb-hero-1': 'Hero · split text left',
    'vb-hero-2': 'Hero · centered',
    'vb-hero-3': 'Hero · split image left',
    'vb-hero-4': 'Hero · split newsletter',
    'vb-hero-5': 'Hero · split image + newsletter',
    'vb-hero-6': 'Hero · centered newsletter',
    'vb-hero-aurora': 'Hero · aurora glow',
    'vb-hero-plasma': 'Hero · plasma glow',
    'vb-hero-cinematic': 'Hero · cinematic full-bleed',
    'vb-landing01-hero': 'Hero · gradient',
    'vb-landing02-hero': 'Hero · editorial',
    // Content
    'vb-content-1': 'Content · 4 link columns',
    'vb-content-2': 'Content · split image cards',
    'vb-content-3': 'Content · centered icon grid',
    'vb-content-4': 'Content · link columns',
    'vb-content-5': 'Content · headline CTA row',
    'vb-content-6': 'Content · profile split image',
    'vb-content-7': 'Content · featured story CTA',
    'vb-content-8': 'Content · media image cards',
    'vb-content-teaser-card': 'Content · teaser with card',
    'vb-content-spotlight-split': 'Content · spotlight split',
    'vb-landing01-solution': 'Content · solution split',
    'vb-landing02-impact': 'Content · impact split',
    'vb-landing02-trust': 'Content · trust logos',
    // Features
    'vb-feature-1': 'Features · icon grid',
    'vb-feature-2': 'Features · 2 cards',
    'vb-feature-3': 'Features · bordered grid',
    'vb-feature-4': 'Features · bordered cards',
    'vb-feature-5': 'Features · large icons',
    'vb-feature-6': 'Features · alternating icons',
    'vb-feature-7': 'Features · checklist',
    'vb-feature-8': 'Features · numbered rows',
    'vb-landing01-features': 'Features · solution cards',
    'vb-landing02-features': 'Features · 2×2 grid',
    'vb-landing02-toolkit': 'Features · toolkit pills',
    // Articles
    'vb-blog-1': 'Articles · card grid',
    'vb-blog-2': 'Articles · elevated cards',
    'vb-blog-3': 'Articles · split layout',
    'vb-blog-4': 'Articles · timeline',
    'vb-blog-5': 'Articles · dated list',
    'vb-articles-featured-stories': 'Articles · featured stories',
    'vb-articles-explore-cards': 'Articles · explore cards',
    'vb-landing02-articles': 'Articles · duo',
    // Gallery
    'vb-gallery-1': 'Gallery · full bleed',
    'vb-gallery-2': 'Gallery · 2×2 grid',
    'vb-gallery-3': 'Gallery · mosaic',
    'vb-gallery-mission-cards': 'Gallery · mission cards',
    'vb-slider-images': 'Gallery · image slider',
    'vb-slider-videos': 'Gallery · video slider',
    // Stats
    'vb-statistic-1': 'Stats · counters',
    'vb-statistic-2': 'Stats · with icons',
    'vb-statistic-3': 'Stats · bar chart',
    'vb-stats-count-up': 'Stats · count up',
    'vb-stats-metrics-band': 'Stats · metrics band',
    'vb-stats-cinematic-band': 'Stats · cinematic band',
    // Testimonials / Team / Steps / Pricing
    'vb-testimonial-1': 'Testimonials · single card',
    'vb-testimonial-2': 'Testimonials · avatar row',
    'vb-testimonial-3': 'Testimonials · carousel style',
    'vb-landing01-testimonials': 'Testimonials · masonry',
    'vb-team-1': 'Team · member list',
    'vb-team-2': 'Team · cards',
    'vb-team-3': 'Team · large photos',
    'vb-step-1': 'Steps · horizontal',
    'vb-step-2': 'Steps · numbered',
    'vb-step-3': 'Steps · timeline',
    'vb-pricing-1': 'Pricing · tiers',
    'vb-pricing-2': 'Pricing · comparison',
    // CTA / Contact / Shop
    'vb-cta-1': 'CTA · headline + button',
    'vb-cta-2': 'CTA · split + signup',
    'vb-cta-3': 'CTA · centered form',
    'vb-cta-4': 'CTA · app download',
    'vb-cta-glow-pulse': 'CTA · glow pulse',
    'vb-cta-split-shimmer': 'CTA · split shimmer',
    'vb-contact-1': 'Contact · map + form',
    'vb-contact-2': 'Contact · map split',
    'vb-contact-3': 'Contact · centered form',
    'vb-ecommerce-1': 'Shop · product grid',
    'vb-ecommerce-2': 'Shop · featured product',
    'vb-ecommerce-3': 'Shop · compact row',
    // Header / Footer (library chrome bands)
    'vb-header-1': 'Header · logo links CTA',
    'vb-header-2': 'Header · logo links solid CTA',
    'vb-header-3': 'Header · centered logo',
    'vb-header-4': 'Header · stacked brand',
    'vb-footer-1': 'Footer · brand + columns',
    'vb-footer-2': 'Footer · brand + newsletter',
    'vb-footer-3': 'Footer · link columns',
    'vb-footer-4': 'Footer · compact social',
    'vb-footer-5': 'Footer · dense columns',
    // Core animated / media / site
    'voodbuilder-animated-cta': 'CTA · animated',
    'voodbuilder-animated-counter': 'Stats · animated counter',
    'voodbuilder-animated-stats': 'Stats · animated',
    'voodbuilder-logo-scroll': 'Content · logo scroll',
    'voodbuilder-logo-grid': 'Content · logo grid',
    'voodbuilder-logo-split': 'Content · logo split',
    'landing_navbar': 'Header · landing navbar',
    'site_nav_simple': 'Header · site navbar',
    'site_footer_columns_simple': 'Footer · 4 columns',
    'site_footer_columns_newsletter': 'Footer · newsletter + columns',
    'site_footer_centered': 'Footer · centered',
    'site_footer_social': 'Footer · social bar',
    'event_landing_footer': 'Footer · event',
    'voodbuilder-code-block': 'Code · editable block',
    'voodbuilder-form': 'Forms · contact',
    'newsletter-form': 'Forms · newsletter',
    'voodbuilder-tabs-pills': 'Tabs · pills',
    'voodbuilder-tabs-underline': 'Tabs · underline',
    'voodbuilder-tabs-segmented': 'Tabs · segmented',
    'voodbuilder-icon': 'Icon',
    'voodbuilder-text-link': 'Text link',
    'voodbuilder-button': 'Button',
    'voodbuilder-reading-time': 'Reading time',
    'voodbuilder-reading-progress': 'Reading progress',
    'voodbuilder-social-share': 'Social sharing',
    'voodbuilder-image-gallery': 'Image gallery',
    'voodbuilder-audio': 'Audio',
    'voodbuilder-carousel': 'Carousel',
    'voodbuilder-slider': 'Slider',
    form: 'Form (legacy)',
    input: 'Text field',
    textarea: 'Text area',
    select: 'Dropdown',
    button: 'Form button',
    label: 'Field label',
    checkbox: 'Checkbox',
    radio: 'Radio option',
};

/** Legacy catalog ids → canonical ids (layer label + attr migration). */
export const SECTION_BLOCK_ID_ALIASES = {
    'vb-nasa-hero': 'vb-hero-cinematic',
    'vb-nasa-teaser': 'vb-content-teaser-card',
    'vb-nasa-spotlight': 'vb-content-spotlight-split',
    'vb-nasa-featured': 'vb-articles-featured-stories',
    'vb-nasa-explore': 'vb-articles-explore-cards',
    'vb-nasa-missions': 'vb-gallery-mission-cards',
    'vb-nasa-stats': 'vb-stats-cinematic-band',
};


/** @type {Record<string, () => string>} */
export const BLOCK_WIREFRAMES = {
    'voodbuilder-code-block': () => thumbWrap(previewSvg(
        '<rect x="8" y="10" width="32" height="22" rx="2.5" />'
        + '<path d="M12 16h10M12 20h18M12 24h14" />'
        + '<rect x="30" y="12" width="8" height="4" rx="1" fill="currentColor" opacity="0.15" />',
    )),
    'voodbuilder-form': () => thumbWrap(previewSvg(
        '<rect x="8" y="10" width="32" height="28" rx="2.5" />'
        + '<path d="M12 16h24M12 21h20M12 26h14" />'
        + '<rect x="12" y="31" width="12" height="4" rx="1" fill="currentColor" opacity="0.2" />',
    )),
    input: () => thumbWrap(previewSvg(
        '<rect x="8" y="20" width="32" height="8" rx="2" />'
        + '<path d="M10 24h4" />',
    )),
    textarea: () => thumbWrap(previewSvg(
        '<rect x="8" y="14" width="32" height="20" rx="2" />'
        + '<path d="M12 20h20M12 24h16M12 28h12" />',
    )),
    select: () => thumbWrap(previewSvg(
        '<rect x="8" y="20" width="32" height="8" rx="2" />'
        + '<path d="M34 22l-3 3-3-3" />',
    )),
    button: () => thumbWrap(previewSvg(
        '<rect x="12" y="20" width="24" height="8" rx="2" fill="currentColor" opacity="0.15" />'
        + '<path d="M18 24h12" />',
    )),
    checkbox: () => thumbWrap(previewSvg(
        '<rect x="10" y="20" width="8" height="8" rx="1.5" />'
        + '<path d="M12 24l2 2 4-4" />',
    )),
    radio: () => thumbWrap(previewSvg(
        '<circle cx="14" cy="24" r="4" />'
        + '<circle cx="14" cy="24" r="1.5" fill="currentColor" />',
    )),
    'vb-content-1': () => thumbWrap(previewSvg(
        '<path d="M12 12h24M14 18h20" />'
        + '<path d="M10 26v8M18 26v8M26 26v8M34 26v8" />'
        + '<path d="M10 30h8M18 30h8M26 30h8M34 30h8" />',
    )),
    'vb-content-8': () => thumbWrap(previewSvg(
        '<path d="M12 12h24M14 18h20" />'
        + '<rect x="8" y="24" width="10" height="12" rx="1.5" />'
        + '<rect x="19" y="24" width="10" height="12" rx="1.5" />'
        + '<rect x="30" y="24" width="10" height="12" rx="1.5" />',
    )),
    'vb-content-2': () => thumbWrap(previewSvg(
        '<path d="M8 12h16M8 18h14" />'
        + '<rect x="28" y="12" width="12" height="8" rx="1.5" />'
        + '<rect x="8" y="26" width="14" height="10" rx="1.5" />'
        + '<rect x="26" y="26" width="14" height="10" rx="1.5" />',
    )),
    'vb-content-3': () => thumbWrap(previewSvg(
        '<path d="M14 10h20M12 16h24" />'
        + '<rect x="8" y="24" width="10" height="10" rx="1.5" />'
        + '<rect x="19" y="24" width="10" height="10" rx="1.5" />'
        + '<rect x="30" y="24" width="10" height="10" rx="1.5" />',
    )),
    'vb-content-4': () => thumbWrap(previewSvg(
        '<path d="M8 14h16M8 20h12M8 26h10" />'
        + '<path d="M28 14h12M28 20h10M28 26h8" />'
        + '<path d="M8 32h32" />',
    )),
    'vb-content-5': () => thumbWrap(previewSvg(
        '<path d="M8 18h20M8 24h14" />'
        + '<rect x="30" y="20" width="10" height="5" rx="1.5" />',
    )),
    'vb-cta-1': () => thumbWrap(previewSvg(
        '<path d="M8 22h22M8 28h16" />'
        + '<rect x="32" y="24" width="8" height="5" rx="1.5" />',
    )),
    'vb-cta-2': () => thumbWrap(previewSvg(
        '<path d="M8 14h18M8 20h14" />'
        + '<rect x="28" y="12" width="12" height="18" rx="2" />'
        + '<path d="M30 18h8M30 22h8" />',
    )),
    'vb-blog-1': () => thumbWrap(previewSvg(
        '<rect x="6" y="14" width="10" height="18" rx="1.5" />'
        + '<rect x="19" y="14" width="10" height="18" rx="1.5" />'
        + '<rect x="32" y="14" width="10" height="18" rx="1.5" />',
    )),
    'vb-blog-4': () => thumbWrap(previewSvg(
        '<path d="M10 14h8M10 20h24M10 26h18" />'
        + '<path d="M34 14v16" />',
    )),
    site_nav_simple: () => thumbWrap(previewSvg(
        '<rect x="6" y="16" width="36" height="10" rx="2" />'
        + '<circle cx="12" cy="21" r="2" />'
        + '<path d="M18 21h8M28 21h5M35 21h5" />',
    )),
    site_footer_columns_simple: () => thumbWrap(previewSvg(
        '<rect x="6" y="18" width="36" height="14" rx="2" />'
        + '<circle cx="11" cy="25" r="2.5" />'
        + '<path d="M16 23h5M16 27h4M23 23h5M23 27h4M30 23h5M30 27h4M37 23h4M37 27h3" />',
    )),
    site_footer_columns_newsletter: () => thumbWrap(previewSvg(
        '<rect x="6" y="18" width="36" height="14" rx="2" />'
        + '<circle cx="10" cy="25" r="2" />'
        + '<path d="M15 23h4M15 27h3M21 23h4M21 27h3M27 23h4" />'
        + '<rect x="34" y="22" width="6" height="6" rx="1" />',
    )),
    site_footer_centered: () => thumbWrap(previewSvg(
        '<rect x="6" y="20" width="36" height="12" rx="2" />'
        + '<circle cx="24" cy="26" r="2.5" />'
        + '<path d="M14 30h20" />',
    )),
    site_footer_social: () => thumbWrap(previewSvg(
        '<rect x="6" y="20" width="36" height="12" rx="2" />'
        + '<circle cx="12" cy="26" r="2" />'
        + '<path d="M18 26h14" />'
        + '<circle cx="32" cy="26" r="1.5" />'
        + '<circle cx="37" cy="26" r="1.5" />',
    )),
};

function blockCategoryLabel(category) {
    if (category == null) {
        return '';
    }

    if (typeof category === 'object') {
        return String(category.label ?? category.id ?? '');
    }

    return String(category);
}

export function normalizeCategoryLabel(category) {
    const label = blockCategoryLabel(category);

    if (label === '') {
        return label;
    }

    if (label in CATEGORY_ALIASES) {
        return CATEGORY_ALIASES[label];
    }

    const lower = label.toLowerCase();

    for (const [alias, canonical] of Object.entries(CATEGORY_ALIASES)) {
        if (alias.toLowerCase() === lower) {
            return canonical;
        }
    }

    for (const canonical of Object.keys(CATEGORY_ORDER)) {
        if (canonical.toLowerCase() === lower) {
            return canonical;
        }
    }

    if (label.startsWith('Sections · ')) {
        return label.replace('Sections · ', '');
    }

    return label;
}

export function unifyBlockCategories(editor) {
    const blockManager = editor.BlockManager;

    if (! blockManager) {
        return;
    }

    blockManager.remove('custom-code');

    blockManager.getAll().forEach((block) => {
        if (isComponentBlock(block)) {
            return;
        }

        const normalized = normalizeCategoryLabel(block.get('category'));

        if (normalized && normalized !== blockCategoryLabel(block.get('category'))) {
            block.set('category', normalized);
        }
    });

    const categories = blockManager.getCategories?.();

    if (! categories?.each) {
        return;
    }

    const blocksByCategory = new Map();

    blockManager.getAll().forEach((block) => {
        if (isComponentBlock(block)) {
            return;
        }

        const label = normalizeCategoryLabel(block.get('category'));

        if (! label) {
            return;
        }

        if (! blocksByCategory.has(label)) {
            blocksByCategory.set(label, []);
        }

        blocksByCategory.get(label).push(block);
    });

    const categoriesByLabel = new Map();

    categories.each((category) => {
        const categoryId = String(category.get('id') ?? '');

        if (isComponentCategoryId(categoryId)) {
            return;
        }

        const rawLabel = String(category.get('label') ?? categoryId);
        const label = normalizeCategoryLabel(rawLabel) || rawLabel;

        if (! categoriesByLabel.has(label)) {
            categoriesByLabel.set(label, []);
        }

        categoriesByLabel.get(label).push(category);
    });

    const categoriesToRemove = [];

    for (const [label, categoryGroup] of categoriesByLabel.entries()) {
        const blocks = blocksByCategory.get(label) ?? [];
        const [canonical, ...duplicates] = categoryGroup;

        if (! canonical) {
            continue;
        }

        canonical.set('label', label);
        canonical.set('id', label);
        canonical.set('order', resolveCategoryOrder(label));
        categoriesToRemove.push(...duplicates);

        if (blocks.length === 0) {
            categoriesToRemove.push(canonical);
        }
    }

    for (const category of categoriesToRemove) {
        categories.remove(category);
    }

    blockManager.getAll().forEach((block) => {
        if (isComponentBlock(block)) {
            return;
        }

        const label = normalizeCategoryLabel(block.get('category'));

        if (label) {
            block.set('category', label);
        }
    });
}

export function resolveCanonicalBlockId(blockId) {
    const id = String(blockId ?? '').trim();

    if (! id) {
        return '';
    }

    return SECTION_BLOCK_ID_ALIASES[id] ?? id;
}

export function resolveBlockLabel(blockId, fallback = '') {
    const id = String(blockId ?? '').trim();

    if (! id) {
        return fallback;
    }

    const canonical = resolveCanonicalBlockId(id);

    return SECTION_BLOCK_LABELS[canonical] ?? SECTION_BLOCK_LABELS[id] ?? fallback;
}

export function resolveBlockWireframe(blockId) {
    const catalogId = String(blockId ?? '').replace(/^voodbuilder-/, '');
    const render = BLOCK_WIREFRAMES[blockId] ?? BLOCK_WIREFRAMES[catalogId];

    return render ? render() : null;
}

export function resolveCategoryOrder(categoryLabel) {
    const normalized = normalizeCategoryLabel(categoryLabel);

    if (normalized in CATEGORY_ORDER) {
        return CATEGORY_ORDER[normalized];
    }

    return 0;
}
