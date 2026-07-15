/**
 * Simplify the Layers panel for chrome layout shells.
 * Top-level header/footer/page-content stay visible (locked in editor-chrome-shell.js).
 */

import { walkComponentTree } from './tailwind-visual-style.js';
import {
    isChromeDropZoneComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
    isPageContentSlotComponent,
} from './chrome-content-slot-utils.js';

const TOP_DROP_SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const PAGE_CONTENT_ATTR = 'data-voodbuilder-page-content';
const CONTENT_SLOT_ATTR = 'data-voodbuilder-content-slot';
const CHROME_SHELL_PART_ATTR = 'data-voodbuilder-chrome-shell-part';
const CHROME_DROP_ZONE_ATTR = 'data-voodbuilder-chrome-drop-zone';

function shouldHideFromLayers(component) {
    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-gjs-site-header']) {
        return true;
    }

    if (attrs[TOP_DROP_SPACER_ATTR]) {
        return true;
    }

    return false;
}

function isTopLevelShellNode(component, wrapper) {
    if (! component || ! wrapper || component.parent?.() !== wrapper) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return Boolean(
        attrs[CHROME_SHELL_PART_ATTR]
        || attrs[PAGE_CONTENT_ATTR]
        || attrs[CONTENT_SLOT_ATTR]
        || attrs[CHROME_DROP_ZONE_ATTR],
    );
}

function isInsideLayoutDropZone(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isChromeDropZoneComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isDirectPageContentChild(component) {
    const parent = component?.parent?.();

    return isPageContentSlotComponent(parent);
}

function isNestedPageContentDescendant(component) {
    if (! component || isDirectPageContentChild(component)) {
        return false;
    }

    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isPageContentSlotComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function applyLayersChromeFilter(component, wrapper, insideChromeShell = false) {
    if (! component || component.get?.('type') === 'wrapper') {
        return;
    }

    if (isNestedPageContentDescendant(component)) {
        component.set({
            layerable: false,
        }, { silent: true });
    }

    if (isTopLevelShellNode(component, wrapper)) {
        const isDropZone = isChromeDropZoneComponent(component);

        component.components?.().forEach((child) => {
            applyLayersChromeFilter(child, wrapper, isDropZone ? false : true);
        });

        return;
    }

    const inChromeShell = (insideChromeShell || shouldHideFromLayers(component))
        && ! isInsideLayoutDropZone(component);

    if (inChromeShell) {
        component.set({
            layerable: false,
            draggable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
        });
    }

    component.components?.().forEach((child) => {
        applyLayersChromeFilter(child, wrapper, inChromeShell);
    });
}

export function registerLayersChromeFilter(editor) {
    if (editor.__voodbuilderLayersChromeFilterRegistered) {
        return;
    }

    editor.__voodbuilderLayersChromeFilterRegistered = true;

    let layersRenderFrame = null;

    const scheduleLayersRender = () => {
        if (layersRenderFrame != null) {
            return;
        }

        layersRenderFrame = window.requestAnimationFrame(() => {
            layersRenderFrame = null;
            editor.Layers?.render?.();
        });
    };

    const shouldSync = () => isChromeShellModeEditor(editor) || isChromeLayoutModeEditor(editor);

    const syncAll = () => {
        if (! shouldSync()) {
            return;
        }

        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        walkComponentTree(wrapper, (component) => {
            applyLayersChromeFilter(component, wrapper);
        });

        scheduleLayersRender();
    };

    const syncSubtree = (component) => {
        if (! shouldSync()) {
            return;
        }

        const wrapper = editor.getWrapper?.();

        if (! component || ! wrapper) {
            return;
        }

        applyLayersChromeFilter(component, wrapper);
        scheduleLayersRender();
    };

    editor.on('load', syncAll);
    editor.on('component:add', syncSubtree);
    editor.on('component:remove', syncAll);
    editor.on('voodbuilder:site-chrome-updated', syncAll);
}
