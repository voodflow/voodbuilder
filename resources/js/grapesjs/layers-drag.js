/**
 * Layer tree reorder — enable drag handles on page blocks and delegate to GrapesJS sorter.
 */

import { resolveComponentFromLayerElement } from './component-context-menu.js';
import {
    isChromeLayoutContentSlot,
    isChromeLayoutFooterBlock,
    isChromeLayoutNavBlock,
} from './chrome-editor-guards.js';

const SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const CHROME_DROP_ZONE_ATTR = 'data-voodbuilder-chrome-drop-zone';

function isSiteChromeBlock(component) {
    const attrs = component.getAttributes?.() ?? {};
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');

    if (attrs['data-voodbuilder-page-content'] || attrs['data-voodbuilder-content-slot']) {
        return true;
    }

    if (attrs['data-voodbuilder-chrome-shell-part']) {
        return true;
    }

    if (attrs[CHROME_DROP_ZONE_ATTR]) {
        return true;
    }

    if (isChromeLayoutContentSlot(component) || isChromeLayoutNavBlock(component) || isChromeLayoutFooterBlock(component)) {
        return true;
    }

    return Boolean(attrs['data-voodbuilder-gjs-site-header'])
        || attrs['data-voodbuilder-chrome-shell-locked']
        || blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header';
}

function isProtectedSlot(component) {
    const attrs = component.getAttributes?.() ?? {};

    return Boolean(attrs['data-voodbuilder-menu'] || attrs['data-voodbuilder-brand']);
}

function shouldEnableLayerReorder(component, editor) {
    if (! component?.get) {
        return false;
    }

    if (component.get('layerable') === false) {
        return false;
    }

    if (isProtectedSlot(component) || isSiteChromeBlock(component)) {
        return false;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper || component === wrapper) {
        return false;
    }

    if (component.getAttributes?.()?.[SPACER_ATTR]) {
        return false;
    }

    const parent = component.parent?.();
    const isWrapperChild = parent === wrapper || parent?.get?.('type') === 'wrapper';

    if (isWrapperChild) {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-section-block']) {
        return true;
    }

    return attrs['data-voodbuilder-block'] && component.get('type') === 'voodbuilder-dynamic';
}

function ensureLayerDraggable(component, editor) {
    if (! shouldEnableLayerReorder(component, editor)) {
        return;
    }

    if (component.get('draggable') === false) {
        component.set('draggable', true);
    }
}

function syncAllLayerDraggable(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const walk = (component) => {
        if (! component) {
            return;
        }

        ensureLayerDraggable(component, editor);
        component.components?.().forEach(walk);
    };

    walk(wrapper);
}

function layerSortEnabled(editor) {
    const config = editor?.Layers?.getConfig?.() ?? editor?.LayerManager?.getConfig?.() ?? {};

    return config.sortable !== false;
}

function canStartLayerSort(component, editor) {
    return Boolean(
        component?.get?.('draggable')
        && component.get('layerable') !== false
        && layerSortEnabled(editor),
    );
}

function startLayerSort(component, event) {
    const viewLayer = component?.viewLayer;

    if (! viewLayer || typeof viewLayer.startSort !== 'function') {
        return false;
    }

    viewLayer.startSort(event);

    return true;
}

function isRowDragTarget(target) {
    return Boolean(
        target?.closest?.('.gjs-layer-item[data-toggle-select]')
        && ! target?.closest?.('[data-toggle-visible], [data-toggle-open], [data-name]'),
    );
}

function handleLayerMouseDown(editor, event) {
    if (event.button !== 0) {
        return;
    }

    if (event.target?.closest?.('[data-toggle-move]')) {
        return;
    }

    const layerItem = event.target?.closest?.('.gjs-layer-item');

    if (! layerItem || ! isRowDragTarget(event.target)) {
        return;
    }

    const component = resolveComponentFromLayerElement(layerItem, editor);

    if (! component) {
        return;
    }

    ensureLayerDraggable(component, editor);

    if (! canStartLayerSort(component, editor)) {
        return;
    }

    if (startLayerSort(component, event)) {
        event.preventDefault();
        event.stopPropagation();
    }
}

export function registerLayersDrag(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersDragRegistered) {
        return;
    }

    editor.__voodbuilderLayersDragRegistered = true;

    const refreshLayers = () => {
        syncAllLayerDraggable(editor);

        window.requestAnimationFrame(() => {
            editor.Layers?.render?.();
        });
    };

    editor.on('load', refreshLayers);
    editor.on('component:add', (component) => {
        ensureLayerDraggable(component, editor);

        let parent = component.parent?.();

        while (parent) {
            ensureLayerDraggable(parent, editor);
            parent = parent.parent?.();
        }
    });

    editor.on('voodbuilder:layers-panel:show', refreshLayers);

    mount.addEventListener('mousedown', (event) => {
        handleLayerMouseDown(editor, event);
    });
}
