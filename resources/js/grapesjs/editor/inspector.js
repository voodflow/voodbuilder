/**
 * Inspector extensions wiring (toolbar, settings, bindings).
 */

import { registerBindingsUi } from '../bindings-ui.js';
import { registerCanvasBlockDrag } from '../canvas-block-drag.js';
import { registerCanvasComponentToolbar } from '../canvas-component-toolbar.js';
import { registerCanvasSiteChrome } from '../canvas-site-chrome.js';
import { registerConditionsPersistence, registerConditionsUi } from '../conditions-ui.js';
import { registerGlobalClassesUi } from '../global-classes-ui.js';
import { registerNewsletterFormSettings } from '../grapesjs-forms-blocks.js';
import { registerInspectorColorFix } from '../inspector-color-fix.js';
import { registerNavSettings } from '../chrome/blocks/nav/settings.js';
import { registerFooterSettings } from '../chrome/blocks/footer/settings.js';
import {
    promoteRoot,
    registerSettingsUi,
    findInspectableRoot,
    ensureRootInspectable,
} from '../blocks/settings/index.js';

/**
 * @param {object} editor
 */
export function registerChromeLayoutInspectorSelection(editor) {
    if (editor.__voodbuilderChromeLayoutInspectorSelectionRegistered) {
        return;
    }

    editor.__voodbuilderChromeLayoutInspectorSelectionRegistered = true;

    editor.on('component:selected', (component) => {
        if (! editor.__voodbuilderChromeLayoutMode || ! component) {
            return;
        }

        const root = findInspectableRoot(component, editor);

        if (root) {
            ensureRootInspectable(root);
        }

        promoteRoot(editor, component);
    });
}

/**
 * @param {object} editor
 * @param {object|null} shell
 * @param {object} options
 * @param {object} labels
 */
export function wireInspector(editor, shell, options, labels) {
    if (editor.__voodbuilderInspectorExtensionsRegistered) {
        return;
    }

    editor.__voodbuilderInspectorExtensionsRegistered = true;

    registerCanvasComponentToolbar(editor, {
        makeDynamic: labels.makeDynamic,
        clearDynamic: labels.clearDynamic,
        selectParent: labels.selectParent,
        drag: labels.drag,
        clone: labels.clone,
        delete: labels.delete,
        editBlockCode: labels.editBlockCode,
    });

    registerCanvasBlockDrag(editor);

    registerCanvasSiteChrome(editor);

    void registerBindingsUi(editor, {
        bindingsUrl: options.bindingsUrl,
        bindingsPreviewUrl: options.bindingsPreviewUrl,
        labels: options.bindingLabels ?? labels,
        dynamicMount: shell?.mounts?.dynamic ?? null,
    });

    registerConditionsUi(editor, {
        mount: shell?.mounts?.conditions ?? null,
        labels,
        conditionOptions: options.conditionOptions ?? [],
    });

    registerConditionsPersistence(editor);

    registerGlobalClassesUi(editor, {
        globalClassesUrl: options.globalClassesUrl,
        csrf: options.csrf,
        labels,
        mount: shell?.mounts?.globalClasses ?? null,
    });

    registerFooterSettings(editor);
    registerNavSettings(editor);
    registerSettingsUi(editor, shell?.mounts?.siteChromeSettings ?? null);
    registerNewsletterFormSettings(editor);
    registerInspectorColorFix(editor, shell?.mounts ?? {});
}

/** @deprecated */
export const registerInspectorExtensions = wireInspector;
