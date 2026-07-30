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
import { syncVideoComponentsForExport } from '../editor-video.js';
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
 * Keep Style Manager #id / private-class rules from getCss() without the full
 * Tailwind/theme bundle (that baked stale --vx-header-bg on the frontend).
 *
 * @param {string} css
 * @returns {string}
 */
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

    html = encodeJsonDataGjsAttributes(html);

    // Intentionally empty page content must persist (delete-all / remove last block).
    // Do NOT restore __voodbuilderLastSavedPageHtml here — that blocked deletes from
    // reaching the front while the editor looked cleared.
    editor.__voodbuilderLastSavedPageHtml = String(html);
    // Chrome shell/layout: keep Style Manager #id paints from getCss() (private
    // .c* classes are promoted to #id on save — keeping them caused clone bleed).
    const liveCss = stripAuthorIdRules(String(editor.__voodbuilderPageLiveCss ?? '').trim());
    const composerCss = String(editor.getCss?.() ?? '').trim();
    const styleManagerCss = extractGrapesComposerCss(composerCss);
    const preferComposerSubset = Boolean(
        editor.__voodbuilderChromeShellMode || editor.__voodbuilderChromeLayoutMode,
    );
    const css = preferComposerSubset
        ? [styleManagerCss, liveCss].filter((chunk, index, all) => chunk !== '' && all.indexOf(chunk) === index).join('\n\n')
        : [composerCss, liveCss].filter((chunk, index, all) => chunk !== '' && all.indexOf(chunk) === index).join('\n\n');

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
