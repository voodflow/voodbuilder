/**
 * Canvas iframe <head> order for live JIT vs Theme Studio palette.
 *
 * Live page/component CSS must sit *before* `#voodbuilder-canvas-theme-palette`
 * so `--color-vp-*` from the palette (and html.dark) keep winning. Appending
 * live CSS as lastChild used to push the palette up; newly dropped blocks then
 * painted with light token fallbacks until a manual dark↔light toggle re-ran
 * `applyCanvasDocumentTheme`.
 */

export const CANVAS_THEME_PALETTE_STYLE_ID = 'voodbuilder-canvas-theme-palette';
export const CANVAS_CHROME_LAYOUT_STYLE_ID = 'voodbuilder-canvas-chrome-layout-css';

/**
 * @param {Document} doc
 * @param {HTMLStyleElement} styleEl
 */
export function placeCanvasLiveStyle(doc, styleEl) {
    if (! doc?.head || ! styleEl) {
        return;
    }

    const palette = doc.getElementById(CANVAS_THEME_PALETTE_STYLE_ID);
    const chrome = doc.getElementById(CANVAS_CHROME_LAYOUT_STYLE_ID);
    const anchor = palette ?? chrome;

    if (anchor?.parentNode === doc.head) {
        if (styleEl.parentNode !== doc.head || styleEl.nextSibling !== anchor) {
            doc.head.insertBefore(styleEl, anchor);
        }

        return;
    }

    if (styleEl.parentNode !== doc.head || styleEl !== doc.head.lastElementChild) {
        doc.head.appendChild(styleEl);
    }
}
