/**
 * Save/export payload assembly.
 */

import { detachTopDropSpacerForExport, restoreTopDropSpacerAfterExport } from '../canvas-block-drag.js';
import { syncBindingsForExport, syncRepeatBindingsForExport } from '../bindings-ui.js';
import {
    ensureComponentInstancesForExport,
    syncComponentInstancePaintForExport,
    syncComponentInstancesForExport,
} from '../components-ui.js';
import { syncConditionsForExport } from '../conditions-ui.js';
import { findPageContentSlotInEditor } from '../chrome-content-slot-utils.js';
import { extractChromeLayoutHtml } from '../editor-chrome-layout.js';
import { extractChromeShellPageHtml } from '../editor-chrome-shell.js';
import { syncVideoComponentsForExport } from '../editor-video.js';
import {
    pruneEmptyDynamicBlocks,
    syncVpressDynamicAttributes,
} from '../plugins/voodbuilder-grapesjs.js';
import { syncSiteHeaderConfig } from '../chrome/blocks/nav/config.js';
import { isNavBlock } from '../chrome/ids.js';
import {
    bakeSvgPaintForExport,
    pruneRedundantSpacingZerosForExport,
    purgeDesyncedBackgroundCssRules,
    restoreSvgPaintInspectorStyle,
    restoreSvgPaintInspectorStyles,
    safeFindComponents,
    syncPaintStylesForExport,
    syncSpacingStylesForExport,
} from '../tailwind-visual-style.js';

function normalizeVpressDynamicComponents(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        syncVpressDynamicAttributes(component);

        if (isNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
            syncSiteHeaderConfig(component);
        }
    });
}

/**
 * GrapesJS getHtml({ withProps: true }) serializes object props as
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
 */
export function buildPayload(editor) {
    const runExportStep = (label, step) => {
        try {
            step();
        } catch (error) {
            console.warn(`Voodbuilder buildPayload: ${label} failed`, error);
        }
    };

    runExportStep('normalizeVpressDynamicComponents', () => normalizeVpressDynamicComponents(editor));
    runExportStep('pruneEmptyDynamicBlocks', () => pruneEmptyDynamicBlocks(editor));
    runExportStep('syncBindingsForExport', () => syncBindingsForExport(editor));
    runExportStep('ensureComponentInstancesForExport', () => ensureComponentInstancesForExport(editor));
    runExportStep('purgeDesyncedBackgroundCssRules', () => purgeDesyncedBackgroundCssRules(editor));
    runExportStep('syncSpacingStylesForExport', () => syncSpacingStylesForExport(editor));
    runExportStep('syncPaintStylesForExport', () => syncPaintStylesForExport(editor));
    runExportStep('syncComponentInstancePaintForExport', () => syncComponentInstancePaintForExport(editor));
    runExportStep('bakeSvgPaintForExport', () => bakeSvgPaintForExport(editor));
    runExportStep('pruneRedundantSpacingZerosForExport', () => pruneRedundantSpacingZerosForExport(editor));
    runExportStep('syncComponentInstancesForExport', () => syncComponentInstancesForExport(editor));
    runExportStep('syncRepeatBindingsForExport', () => syncRepeatBindingsForExport(editor));
    runExportStep('syncConditionsForExport', () => syncConditionsForExport(editor));
    runExportStep('syncVideoComponentsForExport', () => syncVideoComponentsForExport(editor));
    runExportStep('detachTopDropSpacerForExport', () => detachTopDropSpacerForExport(editor));

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
                    html = '';
                } else {
                    console.error(
                        'VoodBuilder: chrome shell HTML extract was empty while the content slot still has children — keeping full canvas HTML for server strip.',
                        { childCount },
                    );
                }
            }
        }
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        html = extractChromeLayoutHtml(editor);
    }

    html = encodeJsonDataGjsAttributes(html);

    // Chrome shell: if slot serialization came back empty but the live canvas still
    // has page sections, refuse to publish a blank page (regression that wiped Home).
    if (
        editor.__voodbuilderChromeShellMode
        && String(html).trim() === ''
        && ! editor.__voodbuilderChromeLayoutMode
    ) {
        const previousHtml = String(editor.__voodbuilderLastSavedPageHtml ?? '').trim();

        if (previousHtml !== '') {
            console.error(
                'VoodBuilder: refusing to save empty chrome-shell page HTML; keeping previous page content.',
            );
            html = previousHtml;
        }
    }

    editor.__voodbuilderLastSavedPageHtml = String(html);
    // they bake stale --vx-header-bg (e.g. purple) over the admin palette on the frontend.
    const liveCss = String(editor.__voodbuilderPageLiveCss ?? '').trim();
    const composerCss = String(editor.getCss?.() ?? '').trim();
    const css = editor.__voodbuilderChromeShellMode
        ? liveCss
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
