/**
 * Layer tree reorder — drag from the whole row, not only the tiny move handle.
 */

import { resolveComponentFromLayerElement } from './component-context-menu.js';

function isInteractiveLayerTarget(target) {
    return Boolean(
        target?.closest?.('[data-toggle-visible], [data-toggle-open], [data-name], [data-toggle-move]'),
    );
}

function canSortLayer(component, editor) {
    if (! component?.get?.('draggable')) {
        return false;
    }

    const config = editor?.Layers?.getConfig?.() ?? editor?.LayerManager?.getConfig?.() ?? {};

    return config.sortable !== false;
}

function triggerLayerMove(component, clientX, clientY) {
    const viewLayer = component?.viewLayer;
    const layerEl = viewLayer?.el;
    const handle = layerEl?.querySelector?.('[data-toggle-move]');

    if (! handle || handle.style.display === 'none') {
        return false;
    }

    handle.dispatchEvent(new MouseEvent('mousedown', {
        bubbles: true,
        cancelable: true,
        view: window,
        button: 0,
        clientX,
        clientY,
    }));

    return true;
}

export function registerLayersDrag(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersDragRegistered) {
        return;
    }

    editor.__voodbuilderLayersDragRegistered = true;

    editor.on('voodbuilder:layers-panel:show', () => {
        if (editor.__voodbuilderLayersSorterReady) {
            return;
        }

        editor.__voodbuilderLayersSorterReady = true;

        window.requestAnimationFrame(() => {
            editor.Layers?.render?.();
        });
    });

    mount.addEventListener('mousedown', (event) => {
        if (event.button !== 0 || isInteractiveLayerTarget(event.target)) {
            return;
        }

        const layerItem = event.target.closest?.('.gjs-layer-item[data-toggle-select]');

        if (! layerItem) {
            return;
        }

        const component = resolveComponentFromLayerElement(layerItem);

        if (! component || ! canSortLayer(component, editor)) {
            return;
        }

        if (triggerLayerMove(component, event.clientX, event.clientY)) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
}
