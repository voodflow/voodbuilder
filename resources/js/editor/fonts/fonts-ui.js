/**
 * Wire FontCatalog into GrapesJS Style Manager + live canvas preview.
 */

import {
    bootCoreFontCatalog,
    cssSafeFontStack,
    findFontByStack,
    getFontCatalog,
    styleManagerFontOptions,
} from './catalog.js';
import {
    applyFontSelectProperty,
    registerFontSelectType,
} from './font-select.js';
import {
    ensureFontLoaded,
    prefetchFontsFromCss,
    reassertComponentFontFamily,
    syncAllLoadedCanvasFonts,
} from './font-loader.js';

function applyFontFamilyOptions(editor) {
    try {
        applyFontSelectProperty(editor);

        const property = getFontFamilyProperty(editor);
        const options = styleManagerFontOptions();

        if (property?.set) {
            property.set('options', options);
        }
    } catch (error) {
        console.warn('Voodbuilder fonts: could not set Style Manager options.', error);
    }
}

/**
 * @param {string} family
 * @returns {string}
 */
function resolveCanonicalFontStack(family) {
    const safe = cssSafeFontStack(family);
    const font = findFontByStack(safe) ?? findFontByStack(family);

    return font?.stack ?? safe;
}

/**
 * Compare stacks ignoring quotes / !important / whitespace.
 *
 * @param {string} a
 * @param {string} b
 * @returns {boolean}
 */
function stacksEqual(a, b) {
    const norm = (value) => cssSafeFontStack(value)
        .replace(/['"]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    return norm(a) === norm(b) && norm(a) !== '';
}

/**
 * @param {object} editor
 * @returns {object|null}
 */
function getFontFamilyProperty(editor) {
    const sm = editor?.StyleManager;

    return sm?.getProperty?.('typography', 'font-family')
        ?? sm?.getSector?.('typography')?.getProperty?.('font-family')
        ?? null;
}

/**
 * @param {object} editor
 * @param {object} component
 * @returns {string}
 */
function readComponentFontFamily(editor, component) {
    const fromInline = String(
        component?.getStyle?.({ inline: true })?.['font-family']
        ?? '',
    ).trim();

    if (fromInline !== '') {
        return fromInline;
    }

    const id = component?.getId?.();

    if (id && editor?.Css) {
        const fromId = String(
            editor.Css.getIdRule?.(id)?.getStyle?.()?.['font-family']
            ?? '',
        ).trim();

        if (fromId !== '') {
            return fromId;
        }
    }

    const fromModel = String(
        component?.getStyle?.()?.['font-family']
        ?? component?.getStyle?.()?.fontFamily
        ?? '',
    ).trim();

    return fromModel;
}

/**
 * Hydrate one component's inline styles from its #id rule so Style Manager
 * can show font-family / color / etc. after editor reload.
 *
 * @param {object} editor
 * @param {object} component
 */
function hydrateComponentFromIdRule(editor, component) {
    const id = component?.getId?.();
    const css = editor?.Css;

    if (! id || ! css?.getIdRule) {
        return;
    }

    const fromId = { ...(css.getIdRule(id)?.getStyle?.() ?? {}) };

    if (Object.keys(fromId).length === 0) {
        return;
    }

    const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
    const next = {};

    for (const [property, value] of Object.entries(fromId)) {
        if (value == null || value === '') {
            continue;
        }

        let cleaned = String(value).replace(/\s*!important\s*$/i, '').trim();

        if (property === 'font-family') {
            cleaned = resolveCanonicalFontStack(cleaned);
        }

        if (inline[property] != null && String(inline[property]).trim() !== '') {
            next[property] = property === 'font-family'
                ? resolveCanonicalFontStack(String(inline[property]))
                : inline[property];
        } else {
            next[property] = cleaned;
        }
    }

    if (Object.keys(next).length === 0) {
        return;
    }

    editor.__voodbuilderFontsApplying = true;

    try {
        component.addStyle?.(next, { inline: true });
    } finally {
        editor.__voodbuilderFontsApplying = false;
    }
}

/**
 * Update the Font family select UI without pushing styles onto Style Manager
 * targets (avoids painting the previously selected clone).
 *
 * @param {object} editor
 * @param {string} canonical
 */
function syncStyleManagerFontFamilyValue(editor, canonical) {
    if (! canonical) {
        return;
    }

    const property = getFontFamilyProperty(editor);

    if (! property) {
        return;
    }

    const stack = resolveCanonicalFontStack(canonical);

    try {
        if (typeof property.upValue === 'function') {
            property.upValue(stack, { noTarget: true });
        } else if (typeof property._up === 'function') {
            property._up({ value: stack }, { noTarget: true });
        } else {
            property.set?.('value', stack, { silent: true });
        }

        // Custom font-select view reads value via update(); force a refresh.
        property.view?.setValue?.(stack);
        property.view?.update?.({ value: stack, el: property.view?.el, createdEl: property.view?.createdEl });
    } catch {
        // ignore SM quirks
    }
}

/**
 * Apply font-family so theme `font-sans` loses, keeping SM option ids clean.
 *
 * @param {object} editor
 * @param {object} component
 * @param {string} family
 * @returns {string}
 */
function ensureCssSafeFontFamilyStyle(editor, component, family) {
    const canonical = resolveCanonicalFontStack(family);

    if (! component || canonical === '') {
        return canonical;
    }

    const current = String(
        component.getStyle?.({ inline: true })?.['font-family']
        ?? component.getStyle?.()?.['font-family']
        ?? component.getStyle?.()?.fontFamily
        ?? '',
    ).trim();

    if (! stacksEqual(current, canonical)) {
        if (typeof component.addStyle === 'function') {
            component.addStyle({ 'font-family': canonical }, { inline: true });
        } else if (typeof component.setStyle === 'function') {
            const style = { ...(component.getStyle?.() ?? {}) };
            style['font-family'] = canonical;
            component.setStyle(style);
        }
    }

    const id = component.getId?.();

    if (id && editor?.Css?.setIdRule) {
        const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
        const ruleFamily = cssSafeFontStack(existing['font-family'] ?? '');

        if (! stacksEqual(ruleFamily, canonical)) {
            editor.Css.setIdRule(id, {
                ...existing,
                'font-family': `${canonical} !important`,
            });
        }
    }

    return canonical;
}

/**
 * @param {object} editor
 * @param {object|null|undefined} component
 * @param {string} rawValue
 */
async function applyFont(editor, component, rawValue) {
    const target = typeof component?.getStyle === 'function'
        ? component
        : editor.getSelected?.();
    const raw = String(rawValue ?? '').trim();

    if (! target || raw === '') {
        return;
    }

    if (editor.__voodbuilderFontsApplying) {
        return;
    }

    editor.__voodbuilderFontsApplying = true;

    try {
        const stack = ensureCssSafeFontFamilyStyle(editor, target, raw);
        const font = findFontByStack(stack);

        if (! font) {
            syncStyleManagerFontFamilyValue(editor, stack);

            return;
        }

        await ensureFontLoaded(editor, font, { reassert: false });
        reassertComponentFontFamily(editor, target, font);
        syncStyleManagerFontFamilyValue(editor, font.stack);
    } finally {
        editor.__voodbuilderFontsApplying = false;
    }
}

function watchFontFamilyChanges(editor) {
    if (editor.__voodbuilderFontsWatchBound) {
        return;
    }

    editor.__voodbuilderFontsWatchBound = true;

    editor.on('style:change:font-family', (component, value) => {
        if (editor.__voodbuilderFontsApplying) {
            return;
        }

        if (typeof component?.getStyle === 'function') {
            void applyFont(editor, component, value ?? component.getStyle?.({ inline: true })?.['font-family']);

            return;
        }

        void applyFont(editor, editor.getSelected?.(), value ?? component);
    });

    editor.on('component:styleUpdate', (component, property) => {
        if (editor.__voodbuilderFontsApplying) {
            return;
        }

        if (property && property !== 'font-family') {
            return;
        }

        const raw = readComponentFontFamily(editor, component);

        if (! raw) {
            return;
        }

        const font = findFontByStack(resolveCanonicalFontStack(raw));

        if (font) {
            void ensureFontLoaded(editor, font, { reassert: false });
        }
    });

    editor.on('component:selected', (component) => {
        if (! component || editor.__voodbuilderFontsApplying) {
            return;
        }

        // Bring #id paints into the model so every SM field (font, color, …) shows.
        hydrateComponentFromIdRule(editor, component);

        const raw = readComponentFontFamily(editor, component);
        const stack = raw ? resolveCanonicalFontStack(raw) : '';

        if (! stack) {
            return;
        }

        // Soft-write canonical stack on this component only (fixes "-" after reload
        // without touching sibling clones via SM targets).
        editor.__voodbuilderFontsApplying = true;

        try {
            ensureCssSafeFontFamilyStyle(editor, component, stack);
            syncStyleManagerFontFamilyValue(editor, stack);
        } finally {
            editor.__voodbuilderFontsApplying = false;
        }

        const font = findFontByStack(stack);

        if (font) {
            void ensureFontLoaded(editor, font, { reassert: false });
        }
    });

    editor.on('canvas:frame:load', () => {
        void syncAllLoadedCanvasFonts(editor).then(() => prefetchFontsFromCss(editor));
    });
}

/**
 * @param {object} editor
 * @param {object} [options]
 */
export function registerFontsUi(editor, options = {}) {
    bootCoreFontCatalog(options.fonts ?? null);

    const searchPlaceholder = options.labels?.fontSearchPlaceholder
        ?? options.searchPlaceholder
        ?? 'Cerca font…';

    registerFontSelectType(editor, { searchPlaceholder });
    applyFontFamilyOptions(editor);
    watchFontFamilyChanges(editor);

    editor.__voodbuilderGetFontCatalog = getFontCatalog;
    editor.__voodbuilderEnsureFontLoaded = (font) => ensureFontLoaded(editor, font, { reassert: false });

    const initialCss = String(options.initialCss ?? editor.getCss?.() ?? '');

    window.requestAnimationFrame(() => {
        void prefetchFontsFromCss(editor, initialCss);
    });

    editor.on('load', () => {
        applyFontFamilyOptions(editor);
        void prefetchFontsFromCss(editor, editor.getCss?.() ?? '');
    });
}
