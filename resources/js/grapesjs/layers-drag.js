/**
 * Layer tree reorder — drag from the move handle or the row background.
 */

import { resolveComponentFromLayerElement } from './component-context-menu.js';

function isRowDragTarget(target) {
    return Boolean(
        target?.closest?.('.gjs-layer-item[data-toggle-select]')
        && ! target?.closest?.('[data-toggle-visible], [data-toggle-open], [data-name]'),
    );
}

function canSortLayer(component, editor) {
    if (! component?.get?.('draggable')) {
        return false;
    }

    const config = editor?.Layers?.getConfig?.() ?? editor?.LayerManager?.getConfig?.() ?? {};

    return config.sortable !== false;
}

function startLayerSort(component, clientX, clientY) {
    const viewLayer = component?.viewLayer;

    if (! viewLayer || typeof viewLayer.startSort !== 'function') {
        return false;
    }

    const handle = viewLayer.el?.querySelector?.('[data-toggle-move]');

    if (! handle || handle.style.display === 'none') {
        return false;
    }

    viewLayer.startSort(new MouseEvent('mousedown', {
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
        if (event.button !== 0) {
            return;
        }

        const onHandle = event.target?.closest?.('[data-toggle-move]');
        const layerItem = event.target?.closest?.('.gjs-layer-item[data-toggle-select]');

        if (! layerItem) {
            return;
        }

        const component = resolveComponentFromLayerElement(layerItem);

        if (! component || ! canSortLayer(component, editor)) {
            return;
        }

        if (onHandle || isRowDragTarget(event.target)) {
            if (startLayerSort(component, event.clientX, event.clientY)) {
                event.preventDefault();
                event.stopPropagation();
            }
        }
    });
}
