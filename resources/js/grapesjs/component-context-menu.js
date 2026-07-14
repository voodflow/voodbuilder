/**
 * Shared context-menu actions for canvas and layer tree selections.
 */

import { openContextMenu } from './context-menu.js';
import { promptDialog } from './editor-dialog.js';
import { COMPONENT_ATTR } from './component-instance-type.js';
import {
    duplicateCanvasComponent,
} from './component-catalog-actions.js';
import {
    applyLayerDisplayName,
    resolveLayerDisplayName,
} from './layer-display-name.js';
import {
    canDuplicateChromeEditorComponent,
    canRemoveChromeEditorComponent,
    filterChromeContextMenuItems,
    isChromeEditorProtectedComponent,
} from './chrome-editor-guards.js';

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

export function resolveComponentFromLayerElement(layerEl, editor = null) {
    const layer = layerEl?.closest?.('.gjs-layer') ?? layerEl;

    if (! layer) {
        return null;
    }

    const viewModel = layer.__gjsv?.model;

    if (viewModel?.toHTML) {
        return viewModel;
    }

    const cashModel = layer.__cashData?.model;

    if (cashModel?.toHTML) {
        return cashModel;
    }

    if (! editor?.getWrapper) {
        return null;
    }

    let match = null;

    const walk = (component) => {
        if (match || ! component) {
            return;
        }

        if (component.viewLayer?.el === layer) {
            match = component;

            return;
        }

        component.components?.().forEach(walk);
    };

    walk(editor.getWrapper());

    return match;
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
        id: 'rename-layer',
        label: labels.layerRename ?? 'Rename layer',
        onSelect: async () => {
            const current = resolveLayerDisplayName(component, editor);
            const next = await promptDialog({
                title: labels.layerRename ?? 'Rename layer',
                message: labels.layerRenameHint ?? 'Changes the label in the layer tree only. Element ids are not modified.',
                labels,
                defaultValue: current,
                placeholder: labels.layerRenamePlaceholder ?? 'Layer name',
                confirmLabel: labels.dialogSave ?? 'Save',
            });

            if (! next?.trim()) {
                return;
            }

            applyLayerDisplayName(editor, component, next.trim());
            component.addAttributes({ 'data-voodbuilder-layer-label': 'custom' });
        },
    });

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

    if (canDuplicateChromeEditorComponent(component, editor)) {
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
    }

    if (canRemoveChromeEditorComponent(component, editor)) {
        items.push({
            id: 'delete',
            label: labels.canvasDelete ?? labels.componentsCanvasDelete ?? 'Remove from canvas',
            danger: true,
            onSelect: () => {
                component.remove();
            },
        });
    }

    return filterChromeContextMenuItems(editor, component, items);
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
