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
            html = slotHtml;
        }
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        html = extractChromeLayoutHtml(editor);
    }

    const payload = {
        html,
        css: editor.getCss(),
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
