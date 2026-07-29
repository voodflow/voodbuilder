/**
 * Inspector extensions wiring (toolbar, settings, bindings).
 */

import { registerBindingsUi } from '../bindings-ui.js';
import { registerCanvasBlockDrag } from '../canvas-block-drag.js';
import { registerCanvasComponentToolbar } from '../canvas-component-toolbar.js';
import { registerCanvasSiteChrome } from '../canvas-site-chrome.js';
import { registerConditionsPersistence, registerConditionsUi } from '../conditions-ui.js';
import { registerGlobalClassesUi } from '../global-classes-ui.js';
import { registerNewsletterFormSettings } from '../editor-forms-blocks.js';
import { registerInspectorColorFix } from '../inspector-color-fix.js';
import { registerNavSettings } from '../chrome/blocks/nav/settings.js';
import { registerFooterSettings } from '../chrome/blocks/footer/settings.js';
import { registerSectionItemCountSettings } from '../section-item-count.js';
import { registerLogoScrollSettings } from '../logo-scroll-settings.js';
import {
    promoteRoot,
    registerSettingsUi,
    findInspectableRoot,
    ensureRootInspectable,
    readBlockId,
    setActiveLayoutSettingsRoot,
    getLayoutChromeBlock,
    rebuildLayoutChromeBlockRegistry,
} from '../blocks/settings/index.js';
import { isChromeDropZoneComponent } from '../chrome-content-slot-utils.js';
import { ATTR } from '../core/attrs.js';

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

        if (root && readBlockId(root) !== '') {
            ensureRootInspectable(root);
            setActiveLayoutSettingsRoot(editor, root);
        } else if (isChromeDropZoneComponent(component)) {
            const zone = component.getAttributes?.()?.[ATTR.dropZone];
            const block = (zone === 'nav' || zone === 'footer')
                ? getLayoutChromeBlock(editor, zone)
                : null;

            if (block) {
                ensureRootInspectable(block);
                setActiveLayoutSettingsRoot(editor, block, zone);
            }
        }

        promoteRoot(editor, component);
    });

    editor.on('component:add', () => {
        if (editor.__voodbuilderChromeLayoutMode) {
            rebuildLayoutChromeBlockRegistry(editor);
        }
    });

    editor.on('component:remove', () => {
        if (editor.__voodbuilderChromeLayoutMode) {
            rebuildLayoutChromeBlockRegistry(editor);
        }
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

    // Soft commercial gate for canvas "Make dynamic" when Dynamic Data plugin is off.
    editor.__voodbuilderDynamicDataEnabled = Boolean(options.bindingsUrl);
    editor.__voodbuilderLabels = {
        ...(editor.__voodbuilderLabels ?? {}),
        ...(options.bindingLabels ?? labels),
    };

    registerCanvasComponentToolbar(editor, {
        makeDynamic: labels.makeDynamic,
        clearDynamic: labels.clearDynamic,
        selectParent: labels.selectParent,
        drag: labels.drag,
        moveUp: labels.moveUp,
        moveDown: labels.moveDown,
        clone: labels.clone,
        delete: labels.delete,
        editBlockCode: labels.editBlockCode,
        editImage: labels.editImage,
        copyComponentCode: labels.copyComponentCode,
        copyComponentClasses: labels.copyComponentClasses,
        copyComponentCodeSuccess: labels.copyComponentCodeSuccess,
        copyComponentCodeFailed: labels.copyComponentCodeFailed,
        classCopySuccess: labels.classCopySuccess,
        classCopyEmpty: labels.classCopyEmpty,
        classCopyFailed: labels.classCopyFailed,
        contentWidthTitle: labels.contentWidthTitle,
        contentWidthFull: labels.contentWidthFull,
        contentWidthNormal: labels.contentWidthNormal,
        contentWidthCustom: labels.contentWidthCustom,
        imageEditorTitle: labels.imageEditorTitle,
        imageEditorApply: labels.imageEditorApply,
        imageEditorLoading: labels.imageEditorLoading,
        imageEditorSaving: labels.imageEditorSaving,
        imageEditorLoadError: labels.imageEditorLoadError,
        imageEditorUploadError: labels.imageEditorUploadError,
        imageEditorUploadMissing: labels.imageEditorUploadMissing,
        dialogCancel: labels.dialogCancel,
        modalCancel: labels.modalCancel,
    });

    registerCanvasBlockDrag(editor);

    registerCanvasSiteChrome(editor);

    void registerBindingsUi(editor, {
        bindingsUrl: options.bindingsUrl,
        bindingsPreviewUrl: options.bindingsPreviewUrl,
        labels: options.bindingLabels ?? labels,
        dynamicMount: shell?.mounts?.dynamic ?? null,
        // Pro collections (List repeat) — false hides repeatSources / List repeat UI.
        dynamicDataCollections: options.dynamicDataCollections === true,
    });

    if (options.conditionsEnabled !== false) {
        registerConditionsUi(editor, {
            mount: shell?.mounts?.conditions ?? null,
            labels,
            conditionOptions: options.conditionOptions ?? [],
        });

        registerConditionsPersistence(editor);
    }

    registerGlobalClassesUi(editor, {
        globalClassesUrl: options.globalClassesUrl,
        csrf: options.csrf,
        labels,
        mount: shell?.mounts?.globalClasses ?? null,
    });

    registerFooterSettings(editor);
    registerNavSettings(editor);
    registerSectionItemCountSettings(editor);
    registerLogoScrollSettings(editor);
    editor.__voodbuilderEnsureChromeBlockSettings = (targetEditor = editor) => {
        registerFooterSettings(targetEditor);
        registerNavSettings(targetEditor);
        registerSectionItemCountSettings(targetEditor);
        registerLogoScrollSettings(targetEditor);
    };
    registerSettingsUi(editor, shell?.mounts?.siteChromeSettings ?? null);
    registerNewsletterFormSettings(editor);
    registerInspectorColorFix(editor, shell?.mounts ?? {});
}

/** @deprecated */
export const registerInspectorExtensions = wireInspector;
