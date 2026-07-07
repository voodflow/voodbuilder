/**
 * Layer tree reorder — delegate to GrapesJS native sorter on move handles.
 */

import { resolveComponentFromLayerElement } from './component-context-menu.js';
import { safeFindComponents } from './tailwind-visual-style.js';

function ensureLayerDraggable(component) {
    if (! component?.get) {
        return;
    }

    if (component.get('layerable') === false) {
        return;
    }

    if (component.get('draggable') === false) {
        const attrs = component.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-gjs-site-header'] || attrs['data-voodbuilder-block']) {
            const blockId = String(attrs['data-voodbuilder-block'] ?? '');

            if (blockId.startsWith('site_nav_') || blockId.startsWith('site_footer_')) {
                return;
            }
        }

        component.set('draggable', true);
    }
}

function isMoveHandleVisible(handle) {
    if (! handle) {
        return false;
    }

    return getComputedStyle(handle).display !== 'none';
}

function startSortFromLayerView(layerEl, event) {
    const view = layerEl?.__gjsv;

    if (! view || typeof view.startSort !== 'function') {
        return false;
    }

    view.startSort(event);

    return true;
}

function forwardRowDragToHandle(event) {
    if (event.button !== 0) {
        return;
    }

    if (event.target?.closest?.('[data-toggle-move]')) {
        return;
    }

    const layerItem = event.target?.closest?.('.gjs-layer-item');

    if (! layerItem) {
        return;
    }

    if (event.target?.closest?.('[data-toggle-visible], [data-toggle-open], [data-name]')) {
        return;
    }

    const layerEl = layerItem.closest('.gjs-layer');

    if (layerEl && startSortFromLayerView(layerEl, event)) {
        return;
    }

    const handle = layerItem.querySelector('[data-toggle-move]');

    if (! isMoveHandleVisible(handle)) {
        return;
    }

    event.preventDefault();

    handle.dispatchEvent(new MouseEvent('mousedown', {
        bubbles: true,
        cancelable: true,
        view: window,
        button: 0,
        buttons: 1,
        clientX: event.clientX,
        clientY: event.clientY,
        screenX: event.screenX,
        screenY: event.screenY,
    }));
}

export function registerLayersDrag(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersDragRegistered) {
        return;
    }

    editor.__voodbuilderLayersDragRegistered = true;

    const syncLayerDraggable = () => {
        const wrapper = editor.getWrapper?.();

        safeFindComponents(wrapper, '[data-voodbuilder-section-block]').forEach((section) => {
            ensureLayerDraggable(section);
        });

        const walk = (component) => {
            if (! component) {
                return;
            }

            ensureLayerDraggable(component);
            component.components?.().forEach(walk);
        };

        walk(wrapper);
    };

    editor.on('load', syncLayerDraggable);
    editor.on('component:add', (component) => {
        ensureLayerDraggable(component);
    });

    editor.on('voodbuilder:layers-panel:show', () => {
        syncLayerDraggable();

        window.requestAnimationFrame(() => {
            editor.Layers?.render?.();
        });
    });

    mount.addEventListener('mousedown', forwardRowDragToHandle);
}
