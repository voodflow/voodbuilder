/**
 * Shared context-menu actions for canvas and layer tree selections.
 */

import { canEditBlockCode, CMD_EDIT_BLOCK_CODE, extractBlockCodeHtml } from './canvas-block-code-editor.js';
import { openContextMenu } from './context-menu.js';
import { buildContextInsertSubmenu } from './context-insert-elements.js';
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
} from './chrome-editor-guards.js';
import { copyTextToClipboard } from './clipboard.js';
import { copySelectedComponentClasses } from './tailwind-class-suggestions.js';
import { canEntitlement } from './editor/entitlements.js';
import { findRichTextHost, isRichTextComponent } from './text-elements.js';

function isRichTextCanvasTarget(component) {
    return Boolean(isRichTextComponent(component) || findRichTextHost(component));
}

function canUseBlockCodeTools(editor) {
    if (! editor?.__voodbuilderCanvasBlockCodeRegistered) {
        return false;
    }

    const entitlements = editor.__voodbuilderEntitlements ?? {};

    return canEntitlement(entitlements, 'componentsLibrary')
        || canEntitlement(entitlements, 'componentsCodeImport');
}

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

function pushSeparator(items) {
    if (items.length === 0) {
        return;
    }

    const last = items[items.length - 1];

    if (last?.type === 'separator') {
        return;
    }

    items.push({ type: 'separator', id: `sep-${items.length}` });
}

export function buildComponentContextMenuItems(editor, component, labels = {}) {
    const catalog = editor.__voodbuilderComponentsCatalog ?? [];
    const libraryActions = editor.__voodbuilderComponentLibraryActions ?? {};
    const componentId = component.getAttributes?.()?.[COMPONENT_ATTR];
    const catalogItem = componentId
        ? catalog.find((entry) => String(entry.id) === String(componentId))
        : null;

    const items = [];

    if (canDuplicateChromeEditorComponent(component, editor)) {
        items.push({
            id: 'duplicate',
            label: labels.canvasDuplicate ?? 'Duplicate',
            onSelect: () => {
                const clone = duplicateCanvasComponent(component, editor);

                if (clone) {
                    editor.select(clone);
                }
            },
        });
    }

    if (canRemoveChromeEditorComponent(component, editor)) {
        items.push({
            id: 'delete',
            label: labels.canvasDelete ?? labels.componentsCanvasDelete ?? 'Delete',
            danger: true,
            onSelect: () => {
                component.remove();
            },
        });
    }

    const insertSubmenu = buildContextInsertSubmenu(editor, component, labels);

    if (insertSubmenu) {
        pushSeparator(items);
        items.push(insertSubmenu);
    }

    pushSeparator(items);

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
        },
    });

    items.push({
        id: 'copy-classes',
        label: labels.copyComponentClasses ?? 'Copy classes',
        onSelect: async () => {
            editor.select(component);
            await copySelectedComponentClasses(editor, labels);
        },
    });

    items.push({
        id: 'save-catalog',
        label: labels.componentsCanvasSave ?? labels.componentsSave ?? 'Save to catalog',
        onSelect: async () => {
            await libraryActions.saveFromCanvas?.(component);
        },
    });

    if (catalogItem || (! isRichTextCanvasTarget(component) && canUseBlockCodeTools(editor) && canEditBlockCode(component, editor))) {
        pushSeparator(items);
    }

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

    if (! isRichTextCanvasTarget(component) && canUseBlockCodeTools(editor) && canEditBlockCode(component, editor)) {
        items.push({
            id: 'edit-block-code',
            label: labels.editBlockCode ?? 'Edit code',
            onSelect: () => {
                editor.runCommand(CMD_EDIT_BLOCK_CODE);
            },
        });
        items.push({
            id: 'copy-code',
            label: labels.copyComponentCode ?? 'Copy code',
            onSelect: async () => {
                const html = extractBlockCodeHtml(editor, component);
                await copyTextToClipboard(String(html ?? '').trim());
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
