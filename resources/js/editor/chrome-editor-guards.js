/**
 * Shared guards for chrome layout + page shell editors.
 */

import { lucideIcon } from './editor-icons.js';
import { isGrapesComponent } from './tailwind-visual-style.js';
import {
    CONTENT_SLOT_ATTR,
    isChromeDropZoneComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
    isChromeShellPartComponent,
    isInsideChromeShellPartComponent,
    isPageContentSlotComponent,
    shouldBlockChromeLayerContextMenu,
} from './chrome-content-slot-utils.js';
import { isFooterBlock, isNavBlock } from './chrome/ids.js';
import {
    getLayoutChromeBlock,
    resolveLayoutChromeZone,
} from './blocks/settings/layout-chrome-registry.js';
import {
    createInspectorEmptyState,
    inspectorSelectElementMessage,
} from './inspector-empty-state.js';

export const SHELL_LAYER_LOCK_ATTR = 'data-voodbuilder-shell-locked';

export { createInspectorEmptyState, inspectorSelectElementMessage };

/**
 * @param {object|null|undefined} editor
 * @returns {string}
 */
export function chromeShellLayoutName(editor) {
    return String(
        editor?.__voodbuilderChromeShellName
        ?? editor?.__voodbuilderChromeLayoutName
        ?? '',
    ).trim();
}

/**
 * Page editor: selected nav/footer comes from a chrome layout — point authors there.
 *
 * @param {object|null|undefined} editor
 * @param {object|null|undefined} [labels]
 * @returns {string}
 */
export function chromeShellManagedInspectorNotice(editor, labels = {}) {
    const name = chromeShellLayoutName(editor);
    const withName = labels.chromeShellManagedInspectorNotice
        ?? 'To edit this element, open the layout “{name}”.';
    const withoutName = labels.chromeShellManagedInspectorNoticeFallback
        ?? 'To edit this element, open its layout in the admin.';

    if (name === '') {
        return withoutName;
    }

    return withName.replaceAll('{name}', name);
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {boolean}
 */
export function isChromeShellManagedInspectorSelection(component, editor) {
    if (
        ! component
        || ! isChromeShellModeEditor(editor)
        || isChromeLayoutModeEditor(editor)
        || isPageContentSlotComponent(component)
    ) {
        return false;
    }

    return isChromeShellPartComponent(component)
        || isInsideChromeShellPartComponent(component);
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @param {object|null|undefined} [labels]
 * @returns {string|null} Notice for inspector empty state, or null when selection is normal.
 */
export function inspectorSelectionNotice(component, editor, labels = {}) {
    if (! component || component.isRemoved?.()) {
        return inspectorSelectElementMessage(labels);
    }

    // Empty header/footer zone (block removed) → courtesy empty state, not chrome notices
    // or leftover Style/Content controls for a ghost selection.
    if (isChromeLayoutModeEditor(editor)) {
        const zone = resolveLayoutChromeZone(component);

        if (
            (zone === 'nav' || zone === 'footer')
            && ! getLayoutChromeBlock(editor, zone)
        ) {
            return inspectorSelectElementMessage(labels);
        }
    }

    if (isChromeShellManagedInspectorSelection(component, editor)) {
        return chromeShellManagedInspectorNotice(editor, labels);
    }

    if (isChromeLayoutAdvancedInspectorLimited(component, editor)) {
        return chromeLayoutAdvancedInspectorNotice(component, labels);
    }

    return null;
}

export function isChromeLayoutNavBlock(component) {
    const blockId = String(component?.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

    return blockId === 'site_header' || blockId.startsWith('site_nav_') || isNavBlock(blockId);
}

export function isChromeLayoutFooterBlock(component) {
    const blockId = String(component?.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

    return isFooterBlock(blockId);
}

export function isChromeLayoutContentSlot(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs['data-voodbuilder-page-content'];
}

/**
 * Layout editor only: Style / Dynamic / Conditions stay locked on structural
 * chrome slots (header/footer drop-zone shells + page-content placeholder).
 * Nav/footer blocks and anything dropped into those zones stay editable for
 * classes and styles.
 */
export function isChromeLayoutAdvancedInspectorLimited(component, editor) {
    if (! component || ! isChromeLayoutModeEditor(editor)) {
        return false;
    }

    if (
        isChromeDropZoneComponent(component)
        || isChromeLayoutContentSlot(component)
    ) {
        return true;
    }

    return false;
}

export function chromeLayoutAdvancedInspectorNotice(component, labels = {}) {
    if (
        isChromeDropZoneComponent(component)
        || isChromeLayoutContentSlot(component)
    ) {
        return labels.chromeLayoutContentSlotInspectorNotice
            ?? 'Layout editor: this slot is filled by each page — use Style, Dynamic, and Conditions inside page content.';
    }

    return labels.chromeLayoutStructureInspectorNotice
        ?? 'Layout editor: use the Content tab for navbar/footer in the header and footer zones.';
}

export function isTopLevelChromeLayoutZone(component, wrapper) {
    if (! component || ! wrapper || component.parent?.() !== wrapper) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-chrome-drop-zone']) {
        return true;
    }

    return isChromeLayoutContentSlot(component)
        || isChromeShellPartComponent(component)
        || isPageContentSlotComponent(component);
}

export function isChromeEditorProtectedComponent(component, editor) {
    return shouldBlockChromeLayerContextMenu(component, editor);
}

export function canRemoveChromeEditorComponent(component, editor) {
    return ! shouldBlockChromeLayerContextMenu(component, editor);
}

export function canDuplicateChromeEditorComponent(component, editor) {
    return ! shouldBlockChromeLayerContextMenu(component, editor);
}

export function canRenameChromeEditorLayer(component, editor) {
    if (! shouldBlockChromeLayerContextMenu(component, editor)) {
        return true;
    }

    return false;
}

export function filterChromeContextMenuItems(editor, component, items) {
    if (shouldBlockChromeLayerContextMenu(component, editor)) {
        return [];
    }

    return items;
}

export function patchChromeZoneLayerIcons(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    wrapper.components().forEach((component) => {
        if (! isGrapesComponent(component)) {
            return;
        }

        const isShellZone = component.parent?.() === wrapper && (
            isTopLevelChromeLayoutZone(component, wrapper)
            || isChromeShellPartComponent(component)
            || isPageContentSlotComponent(component)
            || isChromeDropZoneComponent(component)
        );

        if (! isShellZone) {
            return;
        }

        const shouldShowLock = isChromeShellModeEditor(editor)
            || (isChromeLayoutModeEditor(editor) && isChromeLayoutContentSlot(component));

        const layerEl = component.viewLayer?.el;

        if (! layerEl) {
            return;
        }

        const moveEl = layerEl.querySelector('[data-toggle-move]');

        if (! moveEl) {
            return;
        }

        if (! shouldShowLock) {
            moveEl.style.display = '';
            moveEl.removeAttribute(SHELL_LAYER_LOCK_ATTR);
            moveEl.style.cursor = '';
            moveEl.style.pointerEvents = '';

            return;
        }

        // Idempotent: avoid rewriting layer DOM on every layer:render (reflow storms).
        if (moveEl.getAttribute(SHELL_LAYER_LOCK_ATTR) === '1' && moveEl.querySelector('svg')) {
            return;
        }

        moveEl.style.display = '';
        moveEl.setAttribute(SHELL_LAYER_LOCK_ATTR, '1');
        moveEl.innerHTML = lucideIcon('lock', 14);
        moveEl.style.cursor = 'default';
        moveEl.style.pointerEvents = 'none';
    });
}

export function registerChromeLayerIconPatch(editor) {
    if (editor.__voodbuilderChromeLayerIconPatchRegistered) {
        return;
    }

    editor.__voodbuilderChromeLayerIconPatchRegistered = true;

    let patchFrame = null;

    const patch = () => {
        if (! isChromeShellModeEditor(editor) && ! isChromeLayoutModeEditor(editor)) {
            return;
        }

        if (patchFrame != null) {
            return;
        }

        patchFrame = window.requestAnimationFrame(() => {
            patchFrame = null;
            patchChromeZoneLayerIcons(editor);
        });
    };

    editor.on('load', patch);
    editor.on('layer:render', patch);
    // Do NOT listen to component:update — it re-enters on every model tweak and
    // keeps scheduling rAF work while the tab is focused.
    editor.on('voodbuilder:layers-panel:show', patch);
    editor.on('voodbuilder:chrome-layout-ready', patch);
    editor.on('voodbuilder:dynamic-blocks-refreshed', patch);
}
