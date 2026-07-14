/**
 * Shared guards for chrome layout + page shell editors.
 */

import { lucideIcon } from './editor-icons.js';
import {
    CHROME_SHELL_PART_ATTR,
    CONTENT_SLOT_ATTR,
    isChromeLayoutModeEditor,
    isChromeShellEditorProtectedComponent,
    isChromeShellModeEditor,
    isChromeShellPartComponent,
    isPageContentSlotComponent,
    looksLikeSiteChromeStructure,
    PAGE_CONTENT_ATTR,
} from './chrome-content-slot-utils.js';
import { isSiteFooterBlock, isSiteNavBlock } from './plugins/voodbuilder-grapesjs.js';

export const SHELL_LAYER_LOCK_ATTR = 'data-voodbuilder-shell-locked';

export function isChromeLayoutNavBlock(component) {
    const blockId = String(component?.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

    return blockId === 'site_header' || blockId.startsWith('site_nav_') || isSiteNavBlock(blockId);
}

export function isChromeLayoutFooterBlock(component) {
    const blockId = String(component?.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

    return isSiteFooterBlock(blockId);
}

export function isChromeLayoutContentSlot(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs[PAGE_CONTENT_ATTR];
}

export function isTopLevelChromeLayoutZone(component, wrapper) {
    if (! component || ! wrapper || component.parent?.() !== wrapper) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-chrome-drop-zone']) {
        return true;
    }

    if (isChromeLayoutContentSlot(component)) {
        return true;
    }

    if (isChromeLayoutNavBlock(component)) {
        return true;
    }

    if (isChromeLayoutFooterBlock(component)) {
        return true;
    }

    return isChromeShellPartComponent(component) || isPageContentSlotComponent(component);
}

export function isChromeEditorProtectedComponent(component, editor) {
    if (! component || ! editor) {
        return false;
    }

    if (isChromeShellModeEditor(editor) && isChromeShellEditorProtectedComponent(component, editor)) {
        return true;
    }

    if (! isChromeLayoutModeEditor(editor)) {
        return false;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper || component === wrapper) {
        return false;
    }

    if (isChromeLayoutContentSlot(component)) {
        return true;
    }

    if (component.parent?.() === wrapper && (isChromeLayoutNavBlock(component) || isChromeLayoutFooterBlock(component))) {
        return true;
    }

    if (looksLikeSiteChromeStructure(component)) {
        const parent = component.parent?.();

        if (parent === wrapper || isChromeLayoutNavBlock(parent) || isChromeLayoutFooterBlock(parent)) {
            return true;
        }
    }

    return false;
}

export function canRemoveChromeEditorComponent(component, editor) {
    return ! isChromeEditorProtectedComponent(component, editor);
}

export function canDuplicateChromeEditorComponent(component, editor) {
    return ! isChromeEditorProtectedComponent(component, editor);
}

export function canRenameChromeEditorLayer(component, editor) {
    if (! isChromeEditorProtectedComponent(component, editor)) {
        return true;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper || component.parent?.() !== wrapper) {
        return true;
    }

    return false;
}

export function filterChromeContextMenuItems(editor, component, items) {
    if (! isChromeEditorProtectedComponent(component, editor)) {
        return items;
    }

    const wrapper = editor.getWrapper?.();
    const isTopLevelZone = wrapper && component.parent?.() === wrapper && (
        isTopLevelChromeLayoutZone(component, wrapper)
        || isChromeShellPartComponent(component)
        || isPageContentSlotComponent(component)
    );

    return items.filter((item) => {
        if (item.id === 'delete' && ! canRemoveChromeEditorComponent(component, editor)) {
            return false;
        }

        if (item.id === 'duplicate' && ! canDuplicateChromeEditorComponent(component, editor)) {
            return false;
        }

        if (item.id === 'save-catalog' && isTopLevelZone) {
            return false;
        }

        if (item.id === 'rename-layer' && ! canRenameChromeEditorLayer(component, editor)) {
            return false;
        }

        return true;
    });
}

export function patchChromeZoneLayerIcons(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    wrapper.components().forEach((component) => {
        const isShellZone = component.parent?.() === wrapper && (
            isTopLevelChromeLayoutZone(component, wrapper)
            || isChromeShellPartComponent(component)
            || isPageContentSlotComponent(component)
            || component.getAttributes?.()['data-voodbuilder-chrome-drop-zone']
        );

        if (! isShellZone) {
            return;
        }

        const layerEl = component.viewLayer?.el;

        if (! layerEl) {
            return;
        }

        const moveEl = layerEl.querySelector('[data-toggle-move]');

        if (! moveEl) {
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

    const patch = () => {
        if (! isChromeShellModeEditor(editor) && ! isChromeLayoutModeEditor(editor)) {
            return;
        }

        window.requestAnimationFrame(() => patchChromeZoneLayerIcons(editor));
    };

    editor.on('load', patch);
    editor.on('layer:render', patch);
    editor.on('component:add', patch);
    editor.on('component:remove', patch);
    editor.on('component:update', patch);
    editor.on('voodbuilder:layers-panel:show', patch);
}
