/**
 * Canonical component library categories (aligned with Elements block groups).
 */

const DEFAULT_CATEGORIES = [
    'General',
    'Hero',
    'Content',
    'Features',
    'Articles',
    'Gallery',
    'Stats',
    'Testimonials',
    'Team',
    'Steps',
    'Pricing',
    'CTA',
    'Contact',
    'Shop',
    'Header',
    'Footer',
    'Code',
];

/** @type {Record<string, string>} */
const CATEGORY_ALIASES = {
    sections: 'Content',
    section: 'Content',
    uncategorized: 'General',
    general: 'General',
    heroes: 'Hero',
    feature: 'Features',
    features: 'Features',
    article: 'Articles',
    articles: 'Articles',
    galleries: 'Gallery',
    gallery: 'Gallery',
    stat: 'Stats',
    stats: 'Stats',
    testimonial: 'Testimonials',
    testimonials: 'Testimonials',
    teams: 'Team',
    team: 'Team',
    step: 'Steps',
    steps: 'Steps',
    price: 'Pricing',
    pricing: 'Pricing',
    'call to action': 'CTA',
    contacts: 'Contact',
    contact: 'Contact',
    shops: 'Shop',
    shop: 'Shop',
    headers: 'Header',
    header: 'Header',
    footers: 'Footer',
    footer: 'Footer',
    codes: 'Code',
    code: 'Code',
};

/**
 * @param {string[] | undefined} categories
 * @returns {string[]}
 */
export function resolveComponentCategories(categories) {
    if (Array.isArray(categories) && categories.length > 0) {
        return categories.map((value) => String(value));
    }

    return [...DEFAULT_CATEGORIES];
}

/**
 * @param {string[] | undefined} categories
 * @param {string | null | undefined} value
 * @param {string | undefined} fallback
 * @returns {string}
 */
export function normalizeComponentCategory(value, categories, fallback) {
    const list = resolveComponentCategories(categories);
    const defaultFallback = fallback ?? list[0] ?? 'General';
    const raw = String(value ?? '').trim();

    if (! raw) {
        return defaultFallback;
    }

    const lookup = new Map(list.map((category) => [category.toLowerCase(), category]));
    const lower = raw.toLowerCase();

    if (lookup.has(lower)) {
        return lookup.get(lower);
    }

    const alias = CATEGORY_ALIASES[lower];

    if (alias && lookup.has(alias.toLowerCase())) {
        return lookup.get(alias.toLowerCase());
    }

    return defaultFallback;
}
