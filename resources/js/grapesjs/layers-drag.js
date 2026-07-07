/**
 * Layer tree reorder — delegate to GrapesJS native sorter on move handles.
 */

import { resolveComponentFromLayerElement } from './component-context-menu.js';

function ensureSectionDraggable(component) {
    if (! component?.getAttributes?.()['data-voodbuilder-section-block']) {
        return;
    }

    if (component.get('draggable') === false) {
        component.set('draggable', true);
    }
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

    const handle = layerItem.querySelector('[data-toggle-move]');

    if (! handle || handle.offsetParent === null) {
        return;
    }

    handle.dispatchEvent(new MouseEvent('mousedown', {
        bubbles: true,
        cancelable: true,
        view: window,
        button: 0,
        clientX: event.clientX,
        clientY: event.clientY,
    }));
}

export function registerLayersDrag(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersDragRegistered) {
        return;
    }

    editor.__voodbuilderLayersDragRegistered = true;

    const syncSectionDraggable = () => {
        const wrapper = editor.getWrapper?.();

        wrapper?.find?.('section[data-voodbuilder-section-block]')?.forEach?.((section) => {
            ensureSectionDraggable(section);
        });
    };

    editor.on('load', syncSectionDraggable);
    editor.on('component:add', (component) => {
        ensureSectionDraggable(component);

        if (component?.getAttributes?.()['data-voodbuilder-section-block']) {
            ensureSectionDraggable(component);
        }
    });

    editor.on('voodbuilder:layers-panel:show', () => {
        syncSectionDraggable();

        window.requestAnimationFrame(() => {
            editor.Layers?.render?.();
        });
    });

    mount.addEventListener('mousedown', forwardRowDragToHandle);
}
