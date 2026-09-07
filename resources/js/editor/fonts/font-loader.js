/**
 * Lazy-load webfonts into the editor document + GrapesJS canvas iframe.
 *
 * Loaders return CSS text via Vite `?inline`. We inject that CSS and also
 * register faces with the FontFace API so the canvas does not keep showing
 * the theme default (Inter Variable / system-ui).
 */

import { findFontByStack, getFontById, getFontCatalog, getFontProviderLoader } from './catalog.js';
import { fontsourceLoaders } from './fontsource-loaders.js';

/** @type {Set<string>} */
const loaded = new Set();

/** @type {Map<string, string>} */
const loadedCssText = new Map();

/**
 * @param {object} editor
 * @returns {Document[]}
 */
function canvasDocuments(editor) {
    const docs = [];
    const frameDoc = editor?.Canvas?.getDocument?.();

    if (frameDoc) {
        docs.push(frameDoc);
    }

    return docs;
}

/**
 * @param {Document} doc
 * @param {string} fontId
 * @param {string} cssText
 */
function injectCssText(doc, fontId, cssText) {
    if (! doc?.head || ! cssText?.trim()) {
        return;
    }

    const id = `voodbuilder-font-css-${fontId}`;
    let style = doc.getElementById(id);

    if (! style) {
        style = doc.createElement('style');
        style.id = id;
        style.setAttribute('data-voodbuilder-font', fontId);
        doc.head.appendChild(style);
    }

    style.textContent = cssText;
}

/**
 * Parse @font-face blocks and register them on doc.fonts (more reliable than
 * stylesheet injection alone inside GrapesJS iframes).
 *
 * @param {Document} doc
 * @param {string} cssText
 * @returns {Promise<void>}
 */
async function registerFontFacesFromCss(doc, cssText) {
    const win = doc?.defaultView;

    if (! win?.FontFace || ! doc.fonts || ! cssText) {
        return;
    }

    const faceBlocks = cssText.match(/@font-face\s*\{[^}]+\}/gi) ?? [];

    await Promise.allSettled(faceBlocks.map(async (block) => {
        const familyRaw = block.match(/font-family\s*:\s*([^;]+);/i)?.[1]?.trim() ?? '';
        const family = familyRaw.replace(/^['"]|['"]$/g, '').trim();
        const source = block.match(/src\s*:\s*([^;]+);/i)?.[1]?.trim() ?? '';
        const weight = block.match(/font-weight\s*:\s*([^;]+);/i)?.[1]?.trim() ?? '400';
        const style = block.match(/font-style\s*:\s*([^;]+);/i)?.[1]?.trim() ?? 'normal';
        const display = block.match(/font-display\s*:\s*([^;]+);/i)?.[1]?.trim() ?? 'swap';

        if (! family || ! source) {
            return;
        }

        try {
            const face = new win.FontFace(family, source, {
                weight,
                style,
                display,
            });
            const loadedFace = await face.load();
            doc.fonts.add(loadedFace);
        } catch (error) {
            console.warn(`Voodbuilder fonts: FontFace failed for "${family}" (${weight}).`, error);
        }
    }));
}

/**
 * @param {Document} doc
 * @param {object} font
 * @returns {Promise<void>}
 */
async function warmFontFamily(doc, font) {
    if (! doc?.fonts?.load || ! font?.family) {
        return;
    }

    const family = font.family;
    const weights = Array.isArray(font.weights) && font.weights.length > 0
        ? font.weights
        : [400, 700];

    await Promise.allSettled(
        weights.map((weight) => doc.fonts.load(`${weight} 16px "${family}"`)),
    );
}

/**
 * @param {object} editor
 * @param {object} font
 * @param {string} cssText
 */
async function applyFontAssetsToDocuments(editor, font, cssText) {
    const docs = [document, ...canvasDocuments(editor)];

    for (const doc of docs) {
        injectCssText(doc, font.id, cssText);
        await registerFontFacesFromCss(doc, cssText);
        await warmFontFamily(doc, font);
    }
}

/**
 * Re-assert the stack on a component so theme `font-sans` (Inter) cannot keep
 * winning after a late font load. Prefer #id !important + model value that
 * matches Style Manager options (no !important in the model).
 *
 * @param {object} editor
 * @param {object|null|undefined} component
 * @param {object} font
 */
export function reassertComponentFontFamily(editor, component, font) {
    const target = component ?? editor?.getSelected?.();

    if (! target || ! font?.stack) {
        return;
    }

    const current = String(
        target.getStyle?.()?.['font-family']
        ?? target.getStyle?.()?.fontFamily
        ?? '',
    ).trim();

    const mentionsFamily = current.includes(font.family)
        || current.replace(/['"]/g, '').includes(font.family);

    if (! mentionsFamily && current !== '') {
        return;
    }

    if (typeof target.addStyle === 'function') {
        target.addStyle({ 'font-family': font.stack }, { inline: true });
    }

    const id = target.getId?.();

    if (id && editor?.Css?.setIdRule) {
        const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
        editor.Css.setIdRule(id, {
            ...existing,
            'font-family': `${font.stack} !important`,
        });
    }

    const el = target.getEl?.();

    if (el?.style) {
        el.style.setProperty('font-family', font.stack, 'important');
    }

    target.view?.render?.();
}

/**
 * @param {object} editor
 * @param {object} font
 */
function reassertSelectedFontFamily(editor, font) {
    reassertComponentFontFamily(editor, editor?.getSelected?.(), font);
}

/**
 * @param {object} editor
 * @param {object} font
 */
export async function syncCanvasFontStyles(editor, font) {
    const cssText = loadedCssText.get(font?.id) ?? '';

    if (! font?.id || cssText === '') {
        return;
    }

    for (const doc of canvasDocuments(editor)) {
        injectCssText(doc, font.id, cssText);
        await registerFontFacesFromCss(doc, cssText);
        await warmFontFamily(doc, font);
    }
}

/**
 * @param {object} editor
 */
export async function syncAllLoadedCanvasFonts(editor) {
    for (const fontId of loaded) {
        const font = getFontById(fontId) ?? { id: fontId };
        await syncCanvasFontStyles(editor, font);
    }
}

async function loadFontsource(font) {
    const loader = fontsourceLoaders[font.id];

    if (typeof loader !== 'function') {
        console.warn(`Voodbuilder fonts: no fontsource loader for "${font.id}".`);

        return '';
    }

    const result = await loader();

    return typeof result === 'string' ? result : '';
}

/**
 * @param {object} editor
 * @param {string|object} fontOrId
 * @param {{ reassert?: boolean }} [options]
 */
export async function ensureFontLoaded(editor, fontOrId, options = {}) {
    const font = typeof fontOrId === 'string'
        ? (getFontById(fontOrId) ?? findFontByStack(fontOrId))
        : fontOrId;
    const shouldReassert = options.reassert !== false;

    if (! font?.id) {
        return;
    }

    if (loaded.has(font.id)) {
        await syncCanvasFontStyles(editor, font);

        if (shouldReassert) {
            reassertSelectedFontFamily(editor, font);
        }

        return;
    }

    const providerLoader = getFontProviderLoader(font.provider);
    let cssText = '';

    if (typeof font.load === 'function') {
        const custom = await font.load(font);
        cssText = typeof custom === 'string' ? custom : String(custom?.cssText ?? '');
    } else if (typeof providerLoader === 'function') {
        const custom = await providerLoader(font);
        cssText = typeof custom === 'string' ? custom : String(custom?.cssText ?? '');
    } else if (font.provider === 'fontsource') {
        cssText = await loadFontsource(font);
    } else {
        console.warn(`Voodbuilder fonts: no loader for provider "${font.provider}" (${font.id}).`);

        return;
    }

    cssText = String(cssText ?? '').trim();

    if (cssText === '') {
        console.warn(`Voodbuilder fonts: CSS for "${font.id}" could not be loaded.`);

        return;
    }

    loadedCssText.set(font.id, cssText);
    loaded.add(font.id);

    await applyFontAssetsToDocuments(editor, font, cssText);

    if (shouldReassert) {
        reassertSelectedFontFamily(editor, font);
    }

    window.setTimeout(() => {
        void syncCanvasFontStyles(editor, font).then(() => {
            if (shouldReassert) {
                reassertSelectedFontFamily(editor, font);
            }
        });
    }, 80);
}

/**
 * @param {object} editor
 * @param {string} [css]
 */
export async function prefetchFontsFromCss(editor, css = '') {
    const catalog = getFontCatalog();
    const chunks = [String(css ?? ''), String(editor?.getCss?.() ?? '')];
    const wrapper = editor?.getWrapper?.();

    if (wrapper?.find) {
        try {
            wrapper.find('*').forEach((component) => {
                const family = component?.getStyle?.()?.['font-family']
                    ?? component?.getStyle?.()?.fontFamily;

                if (family) {
                    chunks.push(String(family));
                }
            });
        } catch {
            // ignore
        }
    }

    const hay = chunks.join('\n');

    await Promise.all(catalog
        .filter((font) => hay.includes(font.family) || hay.includes(font.stack) || hay.includes(font.id))
        .map((font) => ensureFontLoaded(editor, font, { reassert: false })));
}
