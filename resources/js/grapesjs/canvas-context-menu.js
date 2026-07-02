/**
 * Right-click menu for components already placed on the canvas.
 */

import { openContextMenu } from './context-menu.js';

const COMPONENT_ATTR = 'data-voodbuilder-component';

function resolveComponentFromElement(editor, element) {
    const doc = editor.Canvas?.getDocument?.();

    if (! element || ! doc?.contains(element)) {
        return null;
    }

    let node = element;

    while (node && node !== doc.documentElement) {
        const view = node.__gjsv;

        if (view?.model) {
            const model = view.model;
            const wrapper = editor.getWrapper?.();

            if (wrapper && model !== wrapper) {
                return model;
            }
        }

        node = node.parentElement;
    }

    return null;
}

function duplicateComponent(component) {
    if (! component?.parent?.()) {
        return;
    }

    const parent = component.parent();
    const index = parent.components().indexOf(component);
    const clone = component.clone();
    parent.components().add(clone, { at: index + 1 });
    clone.emit?.('change:parent');
}

export function registerCanvasContextMenu(editor, options = {}) {
    const labels = options.labels ?? {};
    const catalog = () => editor.__voodbuilderComponentsCatalog ?? [];
    const libraryActions = () => editor.__voodbuilderComponentLibraryActions ?? {};

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
            editor.select(component);

            const componentId = component.getAttributes?.()?.[COMPONENT_ATTR]
                ?? target.closest(`[${COMPONENT_ATTR}]`)?.getAttribute(COMPONENT_ATTR);
            const catalogItem = componentId
                ? catalog().find((entry) => String(entry.id) === String(componentId))
                : null;

            const items = [];

            if (catalogItem) {
                items.push({
                    id: 'edit-code',
                    label: labels.componentsCanvasEdit ?? labels.componentsEdit ?? 'Edit code',
                    onSelect: async () => {
                        await libraryActions().edit?.(catalogItem);
                    },
                });
            }

            items.push(
                {
                    id: 'duplicate',
                    label: labels.canvasDuplicate ?? 'Duplicate',
                    onSelect: () => {
                        duplicateComponent(component);
                    },
                },
                {
                    id: 'delete',
                    label: labels.canvasDelete ?? labels.componentsCanvasDelete ?? 'Remove from canvas',
                    danger: true,
                    onSelect: () => {
                        component.remove();
                    },
                },
            );

            openContextMenu({
                x: event.clientX,
                y: event.clientY,
                items,
                context: component,
            });
        }, true);
    };

    editor.on('canvas:frame:load', bind);

    if (editor.Canvas?.getDocument?.()) {
        bind();
    }
}
