/**
 * Tabler Icons catalog API for VoodBuilder.
 * Full set (~5k outline + filled) is lazy-loaded so the editor chunk stays light.
 *
 * @see https://tabler.io/icons
 */

/** @type {string} */
export const DEFAULT_TABLER_ICON = 'circle';

/** @type {'outline'|'filled'} */
export const DEFAULT_TABLER_ICON_STYLE = 'outline';

export const DEFAULT_TABLER_ICON_STROKE = '1.75';

/** Tiny fallback so canvas works before the full catalog chunk loads. */
export const TABLER_ICON_FALLBACK_PATHS = {
    circle: 'M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0',
    star: 'M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z',
    heart: 'M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572',
    home: 'M5 12l-2 0l9 -9l9 9l-2 0 M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7 M10 12h4v4h-4z',
    check: 'M5 12l5 5l10 -10',
    x: 'M18 6l-12 12 M6 6l12 12',
    plus: 'M12 5l0 14 M5 12l14 0',
    search: 'M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0 M21 21l-6 -6',
    settings: 'M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0',
};

/**
 * Official Tabler category labels (https://tabler.io/icons).
 * Used before / after full catalog load.
 */
export const TABLER_CATEGORY_LABELS = [
    'Animals', 'Arrows', 'Badges', 'Brand', 'Buildings', 'Charts', 'Communication',
    'Computers', 'Currencies', 'Database', 'Design', 'Development', 'Devices',
    'Document', 'E-commerce', 'Electrical', 'Extensions', 'Food', 'Games', 'Gender',
    'Gestures', 'Health', 'Laundry', 'Letters', 'Logic', 'Map', 'Math', 'Media',
    'Mood', 'Nature', 'Numbers', 'Photography', 'Shapes', 'Sport', 'Symbols',
    'System', 'Text', 'Vehicles', 'Version control', 'Weather', 'Zodiac',
];

/**
 * @typedef {{
 *   version: string,
 *   categories: string[],
 *   byCategory: Record<string, string[]>,
 *   tags: Record<string, string>,
 *   outline: Record<string, string>,
 *   filled: Record<string, string>,
 * }} TablerIconCatalog
 */

/** @type {TablerIconCatalog|null} */
let catalogCache = null;

/** @type {Promise<TablerIconCatalog>|null} */
let catalogPromise = null;

/**
 * @returns {Promise<TablerIconCatalog>}
 */
export async function ensureTablerCatalog() {
    if (catalogCache) {
        return catalogCache;
    }

    // Load as a static asset (?url + fetch) so Vite does not wrap ~2MB JSON in a JS chunk.
    catalogPromise ??= import('./generated/tabler-icons-full.json?url')
        .then((mod) => fetch(String(mod.default ?? mod)))
        .then((response) => {
            if (! response.ok) {
                throw new Error(`Failed to load Tabler catalog (${response.status})`);
            }

            return response.json();
        })
        .then((data) => {
            catalogCache = /** @type {TablerIconCatalog} */ (data);
            return catalogCache;
        })
        .catch((error) => {
            catalogPromise = null;
            throw error;
        });

    return catalogPromise;
}

/**
 * @returns {TablerIconCatalog|null}
 */
export function getTablerCatalogSync() {
    return catalogCache;
}

/**
 * @param {unknown} value
 * @returns {'outline'|'filled'}
 */
export function resolveTablerIconStyle(value) {
    return String(value ?? '').toLowerCase() === 'filled' ? 'filled' : 'outline';
}

/**
 * @param {unknown} value
 * @returns {string}
 */
export function resolveTablerIconStroke(value) {
    const n = Number(value);

    if (! Number.isFinite(n) || n <= 0) {
        return DEFAULT_TABLER_ICON_STROKE;
    }

    return String(Math.min(4, Math.max(0.5, n)));
}

/**
 * @param {string|null|undefined} name
 * @param {'outline'|'filled'} [style]
 * @returns {string|null}
 */
export function lookupTablerIconInner(name, style = 'outline') {
    const key = String(name ?? '').trim();

    if (! key) {
        return null;
    }

    const catalog = catalogCache;

    if (style === 'filled' && catalog?.filled?.[key]) {
        return catalog.filled[key];
    }

    if (catalog?.outline?.[key]) {
        return catalog.outline[key];
    }

    if (TABLER_ICON_FALLBACK_PATHS[key]) {
        return `<path d="${TABLER_ICON_FALLBACK_PATHS[key]}"/>`;
    }

    return null;
}

/**
 * @param {unknown} value
 * @returns {string}
 */
export function resolveTablerIconName(value) {
    const name = String(value ?? '').trim();

    if (! name) {
        return DEFAULT_TABLER_ICON;
    }

    if (catalogCache?.outline?.[name] || catalogCache?.filled?.[name] || TABLER_ICON_FALLBACK_PATHS[name]) {
        return name;
    }

    // Unknown until catalog loads — keep the stored name so apply can resolve later.
    if (! catalogCache) {
        return name;
    }

    return DEFAULT_TABLER_ICON;
}

/**
 * @param {string} name
 * @param {{
 *   style?: 'outline'|'filled',
 *   stroke?: string|number,
 *   sizeClass?: string,
 *   className?: string,
 *   color?: string|null,
 * }} [options]
 * @returns {string}
 */
export function tablerIconSvg(name, options = {}) {
    const style = resolveTablerIconStyle(options.style);
    const stroke = resolveTablerIconStroke(options.stroke);
    const sizeClass = options.sizeClass ?? 'w-full h-full';
    const className = options.className ?? 'vb-icon__glyph';
    const color = String(options.color ?? '').trim();
    const classAttr = [className, sizeClass].filter(Boolean).join(' ');
    let safeName = resolveTablerIconName(name);
    let inner = lookupTablerIconInner(safeName, style);

    if (! inner && style === 'filled') {
        inner = lookupTablerIconInner(safeName, 'outline');
    }

    if (! inner) {
        safeName = DEFAULT_TABLER_ICON;
        inner = lookupTablerIconInner(safeName, 'outline')
            ?? `<path d="${TABLER_ICON_FALLBACK_PATHS[DEFAULT_TABLER_ICON]}"/>`;
    }

    const colorAttr = color ? ` style="color:${escapeAttr(color)}"` : '';

    if (style === 'filled' && catalogCache?.filled?.[safeName]) {
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true" class="${classAttr}" data-vb-icon-glyph="${safeName}" data-vb-icon-style="filled"${colorAttr}>${inner}</svg>`;
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${escapeAttr(stroke)}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="${classAttr}" data-vb-icon-glyph="${safeName}" data-vb-icon-style="outline"${colorAttr}>${inner}</svg>`;
}

/**
 * @param {string} name
 * @returns {string}
 */
export function findCategoryForIcon(name) {
    const safe = String(name ?? '').trim();
    const catalog = catalogCache;

    if (! catalog) {
        return 'all';
    }

    for (const [category, icons] of Object.entries(catalog.byCategory)) {
        if (icons.includes(safe)) {
            return category;
        }
    }

    return 'all';
}

/**
 * @returns {{ id: string, label: string, icons: string[] }[]}
 */
export function listTablerIconCategories() {
    const catalog = catalogCache;

    if (! catalog) {
        return [{ id: 'all', label: 'All', icons: Object.keys(TABLER_ICON_FALLBACK_PATHS) }];
    }

    return [
        { id: 'all', label: 'All', icons: Object.keys(catalog.outline) },
        ...catalog.categories.map((category) => ({
            id: category,
            label: category,
            icons: catalog.byCategory[category] ?? [],
        })),
    ];
}

/**
 * @deprecated Use listTablerIconCategories
 */
export function listTablerIconSets() {
    return listTablerIconCategories();
}

/**
 * @deprecated Use findCategoryForIcon
 */
export function findSetIdForIcon(name) {
    return findCategoryForIcon(name);
}

/**
 * @returns {string[]}
 */
export function listTablerIconNames() {
    if (catalogCache) {
        return Object.keys(catalogCache.outline);
    }

    return Object.keys(TABLER_ICON_FALLBACK_PATHS);
}

/**
 * @param {{
 *   category?: string,
 *   query?: string,
 *   style?: 'outline'|'filled'|'all',
 *   limit?: number,
 *   offset?: number,
 * }} [filters]
 * @returns {{ names: string[], total: number }}
 */
export function queryTablerIcons(filters = {}) {
    const catalog = catalogCache;
    const category = String(filters.category ?? 'all');
    const query = String(filters.query ?? '').trim().toLowerCase();
    const style = String(filters.style ?? 'all');
    const limit = Math.max(1, Number(filters.limit) || 80);
    const offset = Math.max(0, Number(filters.offset) || 0);

    let names = category === 'all' || ! catalog
        ? (catalog ? Object.keys(catalog.outline) : Object.keys(TABLER_ICON_FALLBACK_PATHS))
        : [...(catalog.byCategory[category] ?? [])];

    if (style === 'filled' && catalog) {
        names = names.filter((name) => Boolean(catalog.filled[name]));
    } else if (style === 'outline' && catalog) {
        names = names.filter((name) => Boolean(catalog.outline[name]));
    }

    if (query) {
        names = names.filter((name) => {
            if (name.includes(query) || name.replace(/-/g, ' ').includes(query)) {
                return true;
            }

            const tagBlob = catalog?.tags?.[name] ?? '';

            return tagBlob.toLowerCase().includes(query);
        });
    }

    return {
        names: names.slice(offset, offset + limit),
        total: names.length,
    };
}

function escapeAttr(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

/** @deprecated kept for older imports */
export const TABLER_ICON_PATHS = TABLER_ICON_FALLBACK_PATHS;

/** @deprecated */
export const TABLER_ICON_SETS = {
    general: { label: 'General', icons: Object.keys(TABLER_ICON_FALLBACK_PATHS) },
};
