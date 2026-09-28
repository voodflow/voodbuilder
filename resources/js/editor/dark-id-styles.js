/**
 * Dual-theme Style paints for elements: Grapes CssComposer cannot store real
 * `html.dark #id` companions (selectorsAdd becomes `#id, html.dark` and
 * overwrites light). Dark paints live in memory and are emitted on Save /
 * canvas preview as true descendant rules.
 */

import { debugSwallowed } from './debug-swallowed.js';
import { shouldOmitAuthorStyleValue } from './theme-tokens.js';

/** Canvas-only style tag mirroring persisted `html.dark #id` paints. */
export const DARK_ID_CANVAS_STYLE_ID = 'voodbuilder-dark-id-styles';

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {Record<string, Record<string, string>>}
 */
export function getDarkIdStylesCache(editor) {
    if (! editor) {
        return {};
    }

    if (! editor.__voodbuilderDarkIdStyles || typeof editor.__voodbuilderDarkIdStyles !== 'object') {
        editor.__voodbuilderDarkIdStyles = {};
    }

    return editor.__voodbuilderDarkIdStyles;
}

/**
 * @param {string} id
 * @returns {string}
 */
export function darkIdRuleSelector(id) {
    const safe = String(id ?? '').trim().replace(/[^\w-]/g, '');

    return safe === '' ? '' : `html.dark #${safe}`;
}

/**
 * `background-image: none` in the dark companion is an authored override
 * ("no photo in dark") — it must survive the generic cleared-value filter,
 * otherwise the light `#id` photo shows through under html.dark.
 *
 * @param {string} property
 * @param {string} value
 * @returns {boolean}
 */
export function isDarkExplicitNoneOverride(property, value) {
    return String(property ?? '').trim().toLowerCase() === 'background-image'
        && String(value ?? '').trim().toLowerCase() === 'none';
}

/**
 * @param {string} property
 * @param {string} value
 * @returns {boolean}
 */
function shouldOmitDarkValue(property, value) {
    if (isDarkExplicitNoneOverride(property, value)) {
        return false;
    }

    return value === '' || shouldOmitAuthorStyleValue(property, value);
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @returns {Record<string, string>}
 */
export function getDarkIdStyles(editor, id) {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '') {
        return {};
    }

    const cached = getDarkIdStylesCache(editor)[safe];

    return cached && typeof cached === 'object' ? { ...cached } : {};
}

/**
 * Merge styles into the dark companion for `#id` (does not touch light `#id`).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @param {Record<string, string>} styles
 * @returns {boolean}
 */
export function setDarkIdStyles(editor, id, styles) {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '' || ! styles || typeof styles !== 'object') {
        return false;
    }

    const cache = getDarkIdStylesCache(editor);
    const next = { ...(cache[safe] ?? {}) };

    for (const [property, value] of Object.entries(styles)) {
        const prop = String(property ?? '').trim();
        const raw = value == null ? '' : String(value).trim();

        if (prop === '') {
            continue;
        }

        if (shouldOmitDarkValue(prop, raw)) {
            delete next[prop];
            continue;
        }

        next[prop] = raw;
    }

    if (Object.keys(next).length === 0) {
        delete cache[safe];
    } else {
        cache[safe] = next;
    }

    try {
        syncDarkIdStylesCanvasPreview(editor);
    } catch (error) {
        debugSwallowed(error);
    }

    return true;
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @param {string} [property] When set, only drop that property.
 * @returns {boolean}
 */
export function clearDarkIdStyles(editor, id, property = '') {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '') {
        return false;
    }

    const cache = getDarkIdStylesCache(editor);
    const prop = String(property ?? '').trim();

    if (prop === '') {
        delete cache[safe];
    } else if (cache[safe] && typeof cache[safe] === 'object') {
        delete cache[safe][prop];

        if (Object.keys(cache[safe]).length === 0) {
            delete cache[safe];
        }
    }

    try {
        syncDarkIdStylesCanvasPreview(editor);
    } catch (error) {
        debugSwallowed(error);
    }

    return true;
}

/**
 * @param {string} selector
 * @param {Record<string, string>} styles
 * @returns {string}
 */
export function darkIdStylesToCssRule(selector, styles) {
    const sel = String(selector ?? '').trim();

    if (sel === '' || ! styles || typeof styles !== 'object') {
        return '';
    }

    const decls = [];

    for (const [property, value] of Object.entries(styles)) {
        const prop = String(property ?? '').trim();
        const raw = value == null ? '' : String(value).trim();

        if (prop === '' || shouldOmitDarkValue(prop, raw)) {
            continue;
        }

        decls.push(`${prop}:${raw}`);
    }

    return decls.length === 0 ? '' : `${sel} {${decls.join(';')}}`;
}

/**
 * Emit all cached dark companion rules for Save / author CSS.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {string}
 */
export function collectDarkIdStylesCssForPersist(editor) {
    if (! editor) {
        return '';
    }

    const cache = getDarkIdStylesCache(editor);
    const rules = [];

    for (const [id, styles] of Object.entries(cache)) {
        const selector = darkIdRuleSelector(id);
        const rule = darkIdStylesToCssRule(selector, styles);

        if (rule !== '') {
            rules.push(rule);
        }
    }

    return rules.join('\n');
}

/**
 * Inject/remove the canvas preview style tag for dark companions.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function syncDarkIdStylesCanvasPreview(editor) {
    if (! editor) {
        return false;
    }

    let doc = null;

    try {
        doc = editor.Canvas?.getDocument?.() ?? null;
    } catch (error) {
        debugSwallowed(error);
    }

    if (! doc?.head) {
        return false;
    }

    const css = collectDarkIdStylesCssForPersist(editor);
    let tag = doc.getElementById(DARK_ID_CANVAS_STYLE_ID);

    if (css === '') {
        tag?.remove?.();

        return true;
    }

    if (! tag) {
        tag = doc.createElement('style');
        tag.id = DARK_ID_CANVAS_STYLE_ID;
        doc.head.appendChild(tag);
    }

    tag.textContent = css;

    return true;
}

/**
 * Seed the memory cache from saved author CSS (`html.dark #id { … }`).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} css
 * @returns {number} rules imported
 */
export function hydrateDarkIdStylesFromCss(editor, css) {
    if (! editor) {
        return 0;
    }

    const sheet = String(css ?? '');

    if (! sheet.includes('html.dark')) {
        return 0;
    }

    const cache = getDarkIdStylesCache(editor);
    const ruleRe = /html\.dark\s+#([A-Za-z][\w-]*)\s*\{([^}]*)\}/g;
    let match;
    let imported = 0;

    while ((match = ruleRe.exec(sheet)) !== null) {
        const id = match[1];
        const body = match[2] ?? '';

        // Full page-wallpaper companions belong to page-surface cache — never
        // import size/position leftovers into element dark-id styles (that
        // re-emitted orphan html.dark #oldWrapper rules on Save).
        if (
            /background-attachment\s*:\s*fixed/i.test(body)
            || (
                /background-image\s*:[^;]*url\s*\(/i.test(body)
                && /background-attachment\s*:\s*fixed/i.test(body)
            )
        ) {
            continue;
        }

        const styles = {};

        for (const part of body.split(';')) {
            const trimmed = part.trim();

            if (trimmed === '' || ! trimmed.includes(':')) {
                continue;
            }

            const colon = trimmed.indexOf(':');
            const prop = trimmed.slice(0, colon).trim().toLowerCase();
            const value = trimmed.slice(colon + 1).trim();

            if (prop === '' || shouldOmitDarkValue(prop, value)) {
                continue;
            }

            styles[prop] = value;
        }

        if (Object.keys(styles).length === 0) {
            continue;
        }

        cache[id] = {
            ...(cache[id] ?? {}),
            ...styles,
        };
        imported += 1;
    }

    if (imported > 0) {
        try {
            syncDarkIdStylesCanvasPreview(editor);
        } catch (error) {
            debugSwallowed(error);
        }
    }

    return imported;
}
