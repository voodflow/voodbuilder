/**
 * Extensible webfont catalog for the editor.
 * Core ships Fontsource (~50). Plugins call registerFonts().
 */

import coreCatalog from '../../../fonts/core-catalog.json';

/** @typedef {{
 *   id: string,
 *   family: string,
 *   category: string,
 *   provider: string,
 *   package?: string|null,
 *   files?: string[],
 *   stack: string,
 *   weights?: number[],
 *   meta?: Record<string, unknown>,
 *   load?: (font: object) => Promise<void>,
 * }} FontEntry */

/** @type {Map<string, FontEntry>} */
const fonts = new Map();

/** @type {Map<string, (font: FontEntry) => Promise<void>>} */
const providerLoaders = new Map();

/**
 * Prefer single quotes around multi-word families so stacks survive HTML
 * style="..." attributes (nested double quotes break the attribute).
 *
 * @param {string} stack
 * @returns {string}
 */
export function cssSafeFontStack(stack) {
    const trimmed = String(stack ?? '')
        .trim()
        .replace(/\s*!important\s*$/i, '')
        .trim();

    if (! trimmed.includes('"')) {
        return trimmed;
    }

    return trimmed.replace(/"([^"]+)"/g, "'$1'");
}

function normalize(entry) {
    const id = String(entry?.id ?? '').trim();
    const family = String(entry?.family ?? '').trim();
    const stack = cssSafeFontStack(entry?.stack ?? '');

    if (! id || ! family || ! stack) {
        throw new Error('registerFonts entries require id, family, and stack.');
    }

    return {
        id,
        family,
        category: String(entry.category ?? 'sans-serif').trim() || 'sans-serif',
        provider: String(entry.provider ?? 'fontsource').trim() || 'fontsource',
        package: entry.package ? String(entry.package) : null,
        files: Array.isArray(entry.files) ? entry.files.map(String) : [],
        stack,
        weights: Array.isArray(entry.weights) ? entry.weights.map(Number) : [400],
        meta: entry.meta && typeof entry.meta === 'object' ? entry.meta : {},
        load: typeof entry.load === 'function' ? entry.load : undefined,
    };
}

/**
 * @param {FontEntry|FontEntry[]} entries
 */
export function registerFonts(entries) {
    const list = Array.isArray(entries) ? entries : [entries];

    for (const entry of list) {
        const font = normalize(entry);
        fonts.set(font.id, font);
    }
}

/**
 * @param {string} provider
 * @param {(font: FontEntry) => Promise<void>} loader
 */
export function registerFontProvider(provider, loader) {
    const key = String(provider ?? '').trim();

    if (! key || typeof loader !== 'function') {
        throw new Error('registerFontProvider requires provider id and loader fn.');
    }

    providerLoaders.set(key, loader);
}

export function getFontProviderLoader(provider) {
    return providerLoaders.get(String(provider ?? '').trim()) ?? null;
}

export function getFontCatalog() {
    return [...fonts.values()].sort((a, b) => a.family.localeCompare(b.family));
}

export function getFontById(id) {
    return fonts.get(String(id ?? '').trim()) ?? null;
}

export function findFontByStack(stack) {
    const needle = cssSafeFontStack(stack);
    const compact = needle.replace(/['"]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    const unquoted = needle.replace(/^["']|["']$/g, '');

    for (const font of fonts.values()) {
        if (font.stack === needle || font.family === needle || font.family === unquoted) {
            return font;
        }
    }

    for (const font of fonts.values()) {
        const fontCompact = cssSafeFontStack(font.stack)
            .replace(/['"]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

        if (
            needle === font.stack
            || compact === fontCompact
            || compact.startsWith(font.family.toLowerCase())
            || needle.startsWith(`${font.family},`)
            || needle.startsWith(`"${font.family}"`)
            || needle.startsWith(`'${font.family}'`)
        ) {
            return font;
        }
    }

    return null;
}

export function styleManagerFontOptions() {
    const system = [
        { id: 'Arial, Helvetica, sans-serif', label: 'Arial' },
        { id: 'Georgia, serif', label: 'Georgia' },
        { id: "'Courier New', Courier, monospace", label: 'Courier New' },
        { id: 'system-ui, sans-serif', label: 'System UI' },
    ];

    return [
        ...system,
        ...getFontCatalog().map((font) => ({
            id: font.stack,
            label: font.family,
        })),
    ];
}

export function bootCoreFontCatalog(serverFonts = null) {
    fonts.clear();

    const source = Array.isArray(serverFonts) && serverFonts.length > 0
        ? serverFonts
        : coreCatalog;

    registerFonts(source);
}
