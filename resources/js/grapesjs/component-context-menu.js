/**
 * Shared context-menu actions for canvas and layer tree selections.
 */

import { openContextMenu } from './context-menu.js';
import { COMPONENT_ATTR } from './component-instance-type.js';
import {
    duplicateCanvasComponent,
} from './component-catalog-actions.js';

export function resolveComponentFromElement(editor, element) {
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

export function resolveComponentFromLayerElement(layerEl) {
    const layer = layerEl?.closest?.('.gjs-layer') ?? layerEl;

    if (! layer) {
        return null;
    }

    const viewModel = layer.__gjsv?.model;
    const cashModel = layer.__cashData?.model;

    if (viewModel?.toHTML) {
        return viewModel;
    }

    if (cashModel?.toHTML) {
        return cashModel;
    }

    return null;
}

function hasSelectableParent(component, editor) {
    let parent = component.parent?.();
    const wrapper = editor.getWrapper?.();

    while (parent && parent !== wrapper) {
        if (parent.get('selectable')) {
            return true;
        }

        parent = parent.parent?.();
    }

    return false;
}

export function buildComponentContextMenuItems(editor, component, labels = {}) {
    const catalog = editor.__voodbuilderComponentsCatalog ?? [];
    const libraryActions = editor.__voodbuilderComponentLibraryActions ?? {};
    const componentId = component.getAttributes?.()?.[COMPONENT_ATTR];
    const catalogItem = componentId
        ? catalog.find((entry) => String(entry.id) === String(componentId))
        : null;

    const items = [];

    if (catalogItem) {
        items.push({
            id: 'edit-code',
            label: labels.componentsCanvasEdit ?? labels.componentsEdit ?? 'Edit code',
            onSelect: async () => {
                await libraryActions.edit?.(catalogItem);
            },
        });
        items.push({
            id: 'export',
            label: labels.componentsExportOne ?? 'Export component',
            onSelect: async () => {
                await libraryActions.export?.(catalogItem);
            },
        });
    }

    items.push({
        id: 'save-catalog',
        label: labels.componentsCanvasSave ?? labels.componentsSave ?? 'Save to catalog',
        onSelect: async () => {
            await libraryActions.saveFromCanvas?.(component);
        },
    });

    if (hasSelectableParent(component, editor)) {
        items.push({
            id: 'select-parent',
            label: labels.selectParent ?? 'Select parent',
            onSelect: () => {
                editor.runCommand('core:component-exit', { force: true });
            },
        });
    }

    items.push({
        id: 'duplicate',
        label: labels.canvasDuplicate ?? 'Duplicate',
        onSelect: () => {
            const clone = duplicateCanvasComponent(component);

            if (clone) {
                editor.select(clone);
            }
        },
    });

    items.push({
        id: 'delete',
        label: labels.canvasDelete ?? labels.componentsCanvasDelete ?? 'Remove from canvas',
        danger: true,
        onSelect: () => {
            component.remove();
        },
    });

    return items;
}

export function openComponentContextMenu(editor, component, x, y) {
    if (! component) {
        return;
    }

    editor.select(component);

    openContextMenu({
        x,
        y,
        items: buildComponentContextMenuItems(editor, component, editor.__voodbuilderLabels ?? {}),
        context: component,
    });
}
