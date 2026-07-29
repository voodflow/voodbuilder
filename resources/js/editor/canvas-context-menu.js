/**
 * Right-click menu for components already placed on the canvas.
 */

import {
    openComponentContextMenu,
    resolveComponentFromElement,
} from './component-context-menu.js';

export function registerCanvasContextMenu(editor, options = {}) {
    if (editor.__voodbuilderCanvasContextMenuRegistered) {
        return;
    }

    editor.__voodbuilderCanvasContextMenuRegistered = true;

    const bind = () => {
        const doc = editor.Canvas?.getDocument?.();

        if (! doc || doc.documentElement.dataset.voodbuilderCanvasMenuBound === 'true') {
            return;
        }

        doc.documentElement.dataset.voodbuilderCanvasMenuBound = 'true';

        doc.addEventListener('contextmenu', (event) => {
            const target = event.target instanceof Element ? event.target : null;

            if (! target) {
                return;
            }

            const component = resolveComponentFromElement(editor, target);

            if (! component) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openComponentContextMenu(editor, component, event.clientX, event.clientY);
        }, true);
    };

    editor.on('canvas:frame:load', bind);

    if (editor.Canvas?.getDocument?.()) {
        bind();
    }
}
