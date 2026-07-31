/**
 * Save/export payload assembly.
 */

import { detachTopDropSpacerForExport, restoreTopDropSpacerAfterExport } from '../canvas-block-drag.js';
import { detachInnerDropSlotsForExport } from '../inner-drop-slots.js';
import { syncBindingsForExport, syncRepeatBindingsForExport } from '../bindings-ui.js';
import { syncAnimatedCountersForExport } from '../editor-animated-blocks.js';
import {
    ensureComponentInstancesForExport,
    syncComponentInstancePaintForExport,
    syncComponentInstancesForExport,
} from '../components-ui.js';
import { syncConditionsForExport } from '../conditions-ui.js';
import { findPageContentSlotInEditor } from '../chrome-content-slot-utils.js';
import { extractChromeLayoutHtml } from '../editor-chrome-layout.js';
import { ensureCtaButtonsForExport } from '../editor-button-link.js';
import { ensureIconsForExport } from '../editor-utility-blocks.js';
import { ensureLayoutContainersForExport } from '../layout-blocks.js';
import { restoreContentWidthFromAttributes } from '../content-width-toolbar.js';
import { extractChromeShellPageHtml } from '../editor-chrome-shell.js';
import { applyVideoFacadesToExportedHtml, syncVideoComponentsForExport } from '../editor-video.js';
import {
    pruneEmptyDynamicBlocks,
    syncDynamicBlockAttributes,
} from '../plugins/voodbuilder-editor.js';
import { syncSiteHeaderConfig } from '../chrome/blocks/nav/config.js';
import { syncSiteFooterConfig } from '../chrome/blocks/footer/config.js';
import { isFooterBlock, isNavBlock } from '../chrome/ids.js';
import {
    bakeAuthorStylesToComposerForExport,
    bakeSvgPaintForExport,
    pruneRedundantSpacingZerosForExport,
    purgeDesyncedBackgroundCssRules,
    restoreSvgPaintInspectorStyle,
    restoreSvgPaintInspectorStyles,
    safeFindComponents,
    syncPaintStylesForExport,
    syncSpacingStylesForExport,
} from '../tailwind-visual-style.js';

/**
 * Keep Style Manager author paints from getCss() in chrome-shell mode.
 * After promotePrivateStyleClassesToIdRules + bake, paints live on #id rules.
 * Do NOT keep GrapesJS private classes (.c1234): clones share those class names
 * and re-persisting them makes sibling fonts converge again on reload.
 *
 * @param {string} selectors
 * @returns {boolean}
 */
function isAuthorStyleSelector(selectors) {
    const value = String(selectors ?? '').trim();

    return value !== '' && value.includes('#');
}

export function extractGrapesComposerCss(css) {
    const source = String(css ?? '').trim();

    if (source === '') {
        return '';
    }

    // Strip @media blocks (utilities / responsive bundles are regenerated server-side).
    let withoutMedia = source;
    let guard = 0;

    while (guard < 50) {
        const next = withoutMedia.replace(/@media[^{]*\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g, ' ');

        if (next === withoutMedia) {
            break;
        }

        withoutMedia = next;
        guard += 1;
    }

    const kept = [];

    for (const match of withoutMedia.matchAll(/([^{}@]+)\{([^{}]*)\}/g)) {
        const selectors = match[1].trim();
        const body = match[2].trim();

        if (selectors === '' || body === '') {
            continue;
        }

        if (! isAuthorStyleSelector(selectors)) {
            continue;
        }

        kept.push(`${selectors} {${body}}`);
    }

    return kept.join('\n');
}

/**
 * Remove author `#id { … }` rules from a CSS blob (e.g. live JIT sheet).
 * Keeps utilities; avoids stale font-family #id rules overriding the canvas
 * after Save when the live sheet is injected last in the iframe head.
 *
 * @param {string} css
 * @returns {string}
 */
export function stripAuthorIdRules(css) {
    const source = String(css ?? '');

    if (source === '' || ! source.includes('#')) {
        return source.trim();
    }

    return source
        .replace(/#[A-Za-z][\w-]*(?:\s*,\s*#[A-Za-z][\w-]*)*\s*\{[^{}]*\}/g, ' ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

/**
 * Safety net: emit `#id { … }` rules from component inline + CssComposer #id
 * styles. Grapes `getCss()` can omit unused/private rules; without this, Style
 * Manager paints vanish from builder_payload.css on chrome-shell save.
 *
 * @param {object} editor
 * @returns {string}
 */
export function collectAuthorIdCssFromComponents(editor) {
    const wrapper = editor?.getWrapper?.();
    const cssApi = editor?.Css;

    if (! wrapper?.onAll) {
        return '';
    }

    const rules = [];
    const seen = new Set();

    wrapper.onAll((component) => {
        const id = String(component?.getId?.() ?? '').trim();

        if (id === '' || seen.has(id)) {
            return;
        }

        seen.add(id);

        const fromId = { ...(cssApi?.getIdRule?.(id)?.getStyle?.() ?? {}) };
        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        // Combined getStyle() can include private-class paints still attached.
        const combined = { ...(component.getStyle?.() ?? {}) };
        const merged = { ...fromId, ...combined, ...inline };
        const decls = [];

        for (const [property, value] of Object.entries(merged)) {
            if (value == null || value === '') {
                continue;
            }

            const raw = String(value).trim();

            if (raw === '' || raw === 'undefined' || raw === 'null') {
                continue;
            }

            // Skip Grapes internal / empty clears.
            if (/^(none|transparent|initial|inherit|unset)$/i.test(raw.replace(/\s*!important\s*$/i, '').trim())
                && (property === 'background' || property === 'background-color' || property === 'background-image')) {
                continue;
            }

            decls.push(`${property}:${raw}`);
        }

        if (decls.length === 0) {
            return;
        }

        rules.push(`#${id} {${decls.join(';')}}`);
    });

    return rules.join('\n');
}

/**
 * Read Grapes CssComposer CSS, preferring keepUnusedStyles so #id paints that
 * are not referenced as classes still serialize.
 *
 * @param {object} editor
 * @returns {string}
 */
export function readComposerCssForPersist(editor) {
    if (typeof editor?.getCss !== 'function') {
        return '';
    }

    try {
        const withUnused = editor.getCss({ keepUnusedStyles: true });

        if (typeof withUnused === 'string' && withUnused.trim() !== '') {
            return withUnused.trim();
        }
    } catch {
        // Older Grapes builds may not accept options.
    }

    return String(editor.getCss() ?? '').trim();
}

/**
 * Merge CSS chunks, preferring the first declaration block per `#id` selector
 * (later duplicates from live/composer are skipped).
 *
 * @param {string[]} chunks
 * @returns {string}
 */
export function mergeAuthorCssChunks(chunks) {
    const seenIds = new Set();
    const out = [];

    for (const chunk of chunks) {
        const source = String(chunk ?? '').trim();

        if (source === '') {
            continue;
        }

        // Split into rules while keeping non-# utility blobs intact.
        if (! source.includes('#')) {
            if (! out.includes(source)) {
                out.push(source);
            }

            continue;
        }

        let cursor = 0;
        const re = /([^{}@]+)\{([^{}]*)\}/g;
        let match;

        while ((match = re.exec(source)) !== null) {
            const between = source.slice(cursor, match.index).trim();

            if (between !== '' && ! out.includes(between)) {
                out.push(between);
            }

            cursor = match.index + match[0].length;
            const selectors = match[1].trim();
            const body = match[2].trim();
            const rule = `${selectors} {${body}}`;

            if (selectors.includes('#')) {
                const ids = [...selectors.matchAll(/#([A-Za-z][\w-]*)/g)].map((m) => m[1]);
                const already = ids.some((id) => seenIds.has(id));

                if (already) {
                    continue;
                }

                ids.forEach((id) => seenIds.add(id));
            }

            out.push(rule);
        }

        const tail = source.slice(cursor).trim();

        if (tail !== '' && ! out.includes(tail)) {
            out.push(tail);
        }
    }

    return out.join('\n');
}

function normalizeDynamicBlockComponents(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        syncDynamicBlockAttributes(component);

        const blockId = component.getAttributes()['data-voodbuilder-block'];

        if (isNavBlock(blockId)) {
            syncSiteHeaderConfig(component);
        }

        if (isFooterBlock(blockId)) {
            syncSiteFooterConfig(component);
        }
    });
}

/**
 * Editor getHtml({ withProps: true }) serializes object props as
 * data-gjs-resizable="{"ratioDefault":1}" which is invalid HTML and leaks
 * junk onto the published page. Prefer single-quoted JSON attributes.
 *
 * @param {string} html
 * @returns {string}
 */
export function encodeJsonDataGjsAttributes(html) {
    if (typeof html !== 'string' || html === '' || ! html.includes('data-gjs-')) {
        return html;
    }

    return html.replace(
        /\s+data-gjs-([a-zA-Z0-9_-]+)="(\{[\s\S]*?\})"/g,
        (match, name, json) => {
            if (! json.includes('"')) {
                return match;
            }

            return ` data-gjs-${name}='${json.replace(/'/g, '&#39;')}'`;
        },
    );
}

/**
 * @param {object} editor
 * @param {{ mutate?: boolean }} [options]
 *   mutate=false → read-only snapshot for onUpdate (must not wipe CssComposer / styles).
 *   mutate=true (default) → save path: sync/bake/purge then serialize.
 */
export function buildPayload(editor, options = {}) {
    const mutate = options.mutate !== false;

    const runExportStep = (label, step) => {
        try {
            step();
        } catch (error) {
            console.warn(`Voodbuilder buildPayload: ${label} failed`, error);
        }
    };

    if (mutate) {
        runExportStep('normalizeDynamicBlockComponents', () => normalizeDynamicBlockComponents(editor));
        runExportStep('pruneEmptyDynamicBlocks', () => pruneEmptyDynamicBlocks(editor));
        runExportStep('syncBindingsForExport', () => syncBindingsForExport(editor));
        runExportStep('ensureComponentInstancesForExport', () => ensureComponentInstancesForExport(editor));
        runExportStep('purgeDesyncedBackgroundCssRules', () => purgeDesyncedBackgroundCssRules(editor));
        runExportStep('syncSpacingStylesForExport', () => syncSpacingStylesForExport(editor));
        runExportStep('syncPaintStylesForExport', () => syncPaintStylesForExport(editor));
        // Persist Style Manager paints into #id CssComposer rules (and keep inline).
        runExportStep('bakeAuthorStylesToComposerForExport', () => bakeAuthorStylesToComposerForExport(editor));
        runExportStep('syncComponentInstancePaintForExport', () => syncComponentInstancePaintForExport(editor));
        runExportStep('bakeSvgPaintForExport', () => bakeSvgPaintForExport(editor));
        runExportStep('pruneRedundantSpacingZerosForExport', () => pruneRedundantSpacingZerosForExport(editor));
        runExportStep('syncComponentInstancesForExport', () => syncComponentInstancesForExport(editor));
        runExportStep('syncRepeatBindingsForExport', () => syncRepeatBindingsForExport(editor));
        runExportStep('syncAnimatedCountersForExport', () => syncAnimatedCountersForExport(editor));
        runExportStep('syncConditionsForExport', () => syncConditionsForExport(editor));
        runExportStep('syncVideoComponentsForExport', () => syncVideoComponentsForExport(editor));
        runExportStep('detachTopDropSpacerForExport', () => detachTopDropSpacerForExport(editor));
        runExportStep('detachInnerDropSlotsForExport', () => detachInnerDropSlotsForExport(editor));
        runExportStep('ensureCtaButtonsForExport', () => ensureCtaButtonsForExport(editor));
        runExportStep('ensureIconsForExport', () => ensureIconsForExport(editor));
        runExportStep('ensureLayoutContainersForExport', () => ensureLayoutContainersForExport(editor));
        runExportStep('restoreContentWidthFromAttributes', () => restoreContentWidthFromAttributes(editor));
        // Final bake after other syncs may have touched styles.
        runExportStep('bakeAuthorStylesToComposerForExport:final', () => bakeAuthorStylesToComposerForExport(editor));
    }

    let html = editor.getHtml({
        cleanId: false,
        withProps: true,
        keepInlineStyle: true,
    });

    if (editor.__voodbuilderChromeShellMode) {
        const slotHtml = extractChromeShellPageHtml(editor);

        if (slotHtml !== null) {
            const trimmed = slotHtml.trim();

            // Never wipe page content when the slot still has Grapes children but
            // serialization returned an empty string (regression that blanked Home).
            if (trimmed !== '') {
                html = slotHtml;
            } else {
                const childCount = findPageContentSlotInEditor(editor)?.components?.()?.length ?? 0;

                if (childCount === 0) {
                    // Author cleared the page content slot — empty save is intentional.
                    html = '';
                } else {
                    console.error(
                        'VoodBuilder: chrome shell HTML extract was empty while the content slot still has children — keeping full canvas HTML for server strip.',
                        { childCount },
                    );
                    // Keep `html` from getHtml(); server stripSiteChromeFromPageHtml removes chrome.
                }
            }
        }
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        html = extractChromeLayoutHtml(editor);
    }

    // Click-to-play cover must survive export (GrapesJS still serializes embed iframes).
    html = applyVideoFacadesToExportedHtml(editor, html);

    html = encodeJsonDataGjsAttributes(html);

    // Intentionally empty page content must persist (delete-all / remove last block).
    // Do NOT restore __voodbuilderLastSavedPageHtml here — that blocked deletes from
    // reaching the front while the editor looked cleared.
    editor.__voodbuilderLastSavedPageHtml = String(html);
    // Chrome shell/layout: persist Style Manager paints from every reliable source:
    // 1) CssComposer #id rules (after bake / promote)
    // 2) component inline + #id walk (safety net if getCss omits unused rules)
    // 3) any #id still sitting in the live JIT sheet
    // Utilities come from the live sheet with #id stripped (strip only for that chunk).
    // Never strip #id from the *persisted* payload — that wiped fonts/colors on reload.
    const liveCssRaw = String(editor.__voodbuilderPageLiveCss ?? '').trim();
    const liveCssUtilities = stripAuthorIdRules(liveCssRaw);
    const liveCssAuthorIds = extractGrapesComposerCss(liveCssRaw);
    const composerCss = readComposerCssForPersist(editor);
    const styleManagerCss = extractGrapesComposerCss(composerCss);
    const componentAuthorCss = collectAuthorIdCssFromComponents(editor);
    const preferComposerSubset = Boolean(
        editor.__voodbuilderChromeShellMode || editor.__voodbuilderChromeLayoutMode,
    );
    const css = preferComposerSubset
        ? mergeAuthorCssChunks([styleManagerCss, componentAuthorCss, liveCssAuthorIds, liveCssUtilities])
        : mergeAuthorCssChunks([composerCss, componentAuthorCss, liveCssRaw]);

    const payload = {
        html,
        css,
        js: editor.getJs(),
    };

    restoreTopDropSpacerAfterExport(editor);

    restoreSvgPaintInspectorStyles(editor);

    const selected = editor.getSelected?.();

    if (selected && String(selected.get?.('tagName') ?? '').toLowerCase() === 'svg') {
        window.requestAnimationFrame(() => {
            restoreSvgPaintInspectorStyle(selected);
            editor.StyleManager?.select?.(selected);
        });
    }

    return payload;
}
