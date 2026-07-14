/**
 * Shared guards for chrome layout + page shell editors.
 */

import { lucideIcon } from './editor-icons.js';
import {
    CHROME_SHELL_PART_ATTR,
    CONTENT_SLOT_ATTR,
    isChromeDropZoneComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
    isChromeShellPartComponent,
    isPageContentSlotComponent,
    shouldBlockChromeLayerContextMenu,
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

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs['data-voodbuilder-page-content'];
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
        const isShellZone = component.parent?.() === wrapper && (
            isTopLevelChromeLayoutZone(component, wrapper)
            || isChromeShellPartComponent(component)
            || isPageContentSlotComponent(component)
            || isChromeDropZoneComponent(component)
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
