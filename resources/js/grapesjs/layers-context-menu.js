/**
 * Right-click menu on the layer tree (same actions as the canvas context menu).
 */

import {
    openComponentContextMenu,
    resolveComponentFromLayerElement,
} from './component-context-menu.js';
import { shouldBlockChromeLayerContextMenu } from './chrome-content-slot-utils.js';

export function registerLayersContextMenu(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersContextMenuRegistered) {
        return;
    }

    editor.__voodbuilderLayersContextMenuRegistered = true;

    mount.addEventListener('contextmenu', (event) => {
        const layerEl = event.target.closest?.('.gjs-layer, .gjs-layer-item, [data-toggle-select]');

        if (! layerEl) {
            return;
        }

        const component = resolveComponentFromLayerElement(layerEl, editor);

        if (! component) {
            return;
        }

        const wrapper = editor.getWrapper?.();

        if (! wrapper || component === wrapper) {
            return;
        }

        if (shouldBlockChromeLayerContextMenu(component, editor)) {
            event.preventDefault();
            event.stopPropagation();

            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openComponentContextMenu(editor, component, event.clientX, event.clientY);
    }, true);
}
