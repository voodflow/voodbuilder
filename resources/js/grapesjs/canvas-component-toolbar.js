/**
 * GrapesJS canvas component toolbar (parent select, drag, clone, delete) + dynamic bindings.
 * @see https://grapesjs.com/docs/api/component.html#toolbar
 */

import {
    canEditBlockCode,
    CMD_EDIT_BLOCK_CODE,
} from './canvas-block-code-editor.js';
import { lucideIcon } from './editor-icons.js';

export const CMD_MAKE_DYNAMIC = 'voodbuilder-make-dynamic';
export const CMD_CLEAR_DYNAMIC = 'voodbuilder-clear-dynamic';

const TOOLBAR_FLAG = 'data-voodbuilder-toolbar';

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

function buildDynamicToolbarButtons(labels = {}) {
    return [
        {
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--dynamic',
                [TOOLBAR_FLAG]: 'dynamic',
                title: labels.makeDynamic ?? 'Make dynamic',
                'aria-label': labels.makeDynamic ?? 'Make dynamic',
            },
            label: lucideIcon('link-2', 16),
            command: CMD_MAKE_DYNAMIC,
        },
        {
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--clear-dynamic',
                [TOOLBAR_FLAG]: 'clear-dynamic',
                title: labels.clearDynamic ?? 'Clear dynamic binding',
                'aria-label': labels.clearDynamic ?? 'Clear dynamic binding',
            },
            label: lucideIcon('unlink-2', 16),
            command: CMD_CLEAR_DYNAMIC,
        },
    ];
}

function buildComponentToolbar(editor, component, labels = {}) {
    const stylePrefix = editor.getConfig?.('stylePrefix') ?? 'gjs-';
    const toolbar = [];

    if (component.collection && hasSelectableParent(component, editor)) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--parent',
                [TOOLBAR_FLAG]: 'select-parent',
                title: labels.selectParent ?? 'Select parent',
                'aria-label': labels.selectParent ?? 'Select parent',
            },
            label: lucideIcon('chevrons-up', 16),
            command: (ed) => ed.runCommand('core:component-exit', { force: true }),
        });
    }

    if (component.get('draggable')) {
        toolbar.push({
            attributes: {
                class: `${stylePrefix}no-touch-actions`,
                draggable: true,
                title: labels.drag ?? 'Drag to move',
                'aria-label': labels.drag ?? 'Drag to move',
            },
            label: lucideIcon('move', 16),
            command: 'tlb-move',
        });
    }

    if (component.get('copyable')) {
        toolbar.push({
            attributes: {
                title: labels.clone ?? 'Duplicate',
                'aria-label': labels.clone ?? 'Duplicate',
            },
            label: lucideIcon('copy', 16),
            command: 'tlb-clone',
        });
    }

    if (canEditBlockCode(component, editor)) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--code',
                [TOOLBAR_FLAG]: 'edit-code',
                title: labels.editBlockCode ?? 'Edit code',
                'aria-label': labels.editBlockCode ?? 'Edit code',
            },
            label: lucideIcon('code', 16),
            command: CMD_EDIT_BLOCK_CODE,
        });
    }

    toolbar.push(...buildDynamicToolbarButtons(labels));

    if (component.get('removable')) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--danger',
                title: labels.delete ?? 'Delete',
                'aria-label': labels.delete ?? 'Delete',
            },
            label: lucideIcon('trash-2', 16),
            command: 'tlb-delete',
        });
    }

    return toolbar;
}

function syncBoundToolbarState(editor, component) {
    const toolbarEl = editor.Canvas?.getToolbarEl?.();

    if (! toolbarEl || ! component) {
        return;
    }

    const bound = Boolean(component.getAttributes?.()['data-voodbuilder-bind']);
    const dynamicButton = toolbarEl.querySelector(`[${TOOLBAR_FLAG}="dynamic"]`);
    const clearButton = toolbarEl.querySelector(`[${TOOLBAR_FLAG}="clear-dynamic"]`);

    dynamicButton?.classList.toggle('is-bound', bound);
    clearButton?.classList.toggle('is-hidden', ! bound);
}

export function ensureCanvasComponentToolbarButtons(editor, component, labels = {}) {
    if (! component) {
        return;
    }

    component.set('toolbar', buildComponentToolbar(editor, component, labels));

    window.requestAnimationFrame(() => {
        syncBoundToolbarState(editor, component);
    });
}

export function registerCanvasDropAffordance(editor) {
    if (editor.__voodbuilderCanvasDropAffordanceRegistered) {
        return;
    }

    editor.__voodbuilderCanvasDropAffordanceRegistered = true;

    const ensureWrapperDroppable = () => {
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        wrapper.set({
            droppable: true,
            highlightable: false,
            selectable: false,
            hoverable: false,
        });
    };

    editor.on('load', ensureWrapperDroppable);
    editor.on('component:add', () => {
        window.requestAnimationFrame(ensureWrapperDroppable);
    });
}

export function registerCanvasComponentToolbar(editor, labels = {}) {
    if (editor.__voodbuilderCanvasToolbarRegistered) {
        return;
    }

    editor.__voodbuilderCanvasToolbarRegistered = true;

    registerCanvasDropAffordance(editor);

    editor.on('component:selected', (component) => {
        ensureCanvasComponentToolbarButtons(editor, component, labels);
    });

    editor.on('component:update', (component) => {
        if (editor.getSelected() === component) {
            syncBoundToolbarState(editor, component);
        }
    });

    editor.on('component:styleUpdate', (component) => {
        if (editor.getSelected() === component) {
            syncBoundToolbarState(editor, component);
        }
    });
}
