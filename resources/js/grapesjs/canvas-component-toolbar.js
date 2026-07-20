/**
 * GrapesJS canvas component toolbar (parent select, drag, clone, delete) + dynamic bindings.
 * @see https://grapesjs.com/docs/api/component.html#toolbar
 */

import {
    canEditBlockCode,
    CMD_EDIT_BLOCK_CODE,
    extractBlockCodeHtml,
} from './canvas-block-code-editor.js';
import { copyTextToClipboard } from './clipboard.js';
import { copySelectedComponentClasses } from './tailwind-class-suggestions.js';
import { lucideIcon } from './editor-icons.js';
import { shouldSuppressChromeSlotInspector } from './chrome-content-slot-utils.js';
import { isChromeEditorProtectedComponent, canDuplicateChromeEditorComponent } from './chrome-editor-guards.js';

export const CMD_MAKE_DYNAMIC = 'voodbuilder-make-dynamic';
export const CMD_CLEAR_DYNAMIC = 'voodbuilder-clear-dynamic';
export const CMD_COPY_COMPONENT_CLASSES = 'voodbuilder:copy-component-classes';
export const CMD_COPY_COMPONENT_CODE = 'voodbuilder:copy-component-code';

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
    if (shouldSuppressChromeSlotInspector(component, editor) || isChromeEditorProtectedComponent(component, editor)) {
        return [];
    }

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

    if (component.get('copyable') && canDuplicateChromeEditorComponent(component, editor)) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--duplicate',
                [TOOLBAR_FLAG]: 'duplicate',
                title: labels.clone ?? 'Duplicate',
                'aria-label': labels.clone ?? 'Duplicate',
            },
            label: lucideIcon('copy-plus', 16),
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

        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--copy-code',
                [TOOLBAR_FLAG]: 'copy-code',
                title: labels.copyComponentCode ?? 'Copy code',
                'aria-label': labels.copyComponentCode ?? 'Copy code',
            },
            label: lucideIcon('clipboard', 16),
            command: async (ed) => {
                await runCopyComponentCode(ed, labels);
            },
        });
    }

    toolbar.push({
        attributes: {
            class: 'voodbuilder-gjs-toolbar-item--copy-classes',
            [TOOLBAR_FLAG]: 'copy-classes',
            title: labels.copyComponentClasses ?? 'Copy classes',
            'aria-label': labels.copyComponentClasses ?? 'Copy classes',
        },
        label: lucideIcon('tags', 16),
        command: async (ed) => {
            await copySelectedComponentClasses(ed, labels);
        },
    });

    toolbar.push(...buildDynamicToolbarButtons(labels));

    if (component.get('removable') && ! isChromeEditorProtectedComponent(component, editor)) {
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

function clampCanvasToolbarPosition(editor) {
    window.requestAnimationFrame(() => {
        const toolbar = editor.Canvas?.getToolbarEl?.();

        if (! toolbar) {
            return;
        }

        const canvasView = editor.Canvas?.getCanvasView?.()?.el;

        if (! canvasView) {
            return;
        }

        const canvasRect = canvasView.getBoundingClientRect();
        const toolbarRect = toolbar.getBoundingClientRect();
        const padding = 6;

        if (toolbarRect.left < canvasRect.left + padding) {
            const shift = (canvasRect.left + padding) - toolbarRect.left;
            const currentLeft = Number.parseFloat(toolbar.style.left || '0') || 0;

            toolbar.style.left = `${currentLeft + shift}px`;
        }

        if (toolbarRect.top < canvasRect.top + padding) {
            const shift = (canvasRect.top + padding) - toolbarRect.top;
            const currentTop = Number.parseFloat(toolbar.style.top || '0') || 0;

            toolbar.style.top = `${currentTop + shift}px`;
        }
    });
}

function showToolbarToast(message) {
    const toast = document.getElementById('voodbuilder-gjs-classes-toast')
        ?? Object.assign(document.createElement('div'), {
            id: 'voodbuilder-gjs-classes-toast',
            className: 'voodbuilder-gjs-classes-toast',
        });

    if (! toast.parentElement) {
        document.body.appendChild(toast);
    }

    toast.innerHTML = `<div class="voodbuilder-gjs-classes-toast__title">${message}</div>`;
    toast.hidden = false;
    window.clearTimeout(toast._hideTimer);
    toast._hideTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 2000);
}

async function runCopyComponentCode(editor, labels = {}) {
    const selected = editor.getSelected();

    if (! selected) {
        return false;
    }

    const html = canEditBlockCode(selected, editor)
        ? extractBlockCodeHtml(editor, selected)
        : String(selected.toHTML?.() ?? '');

    const ok = await copyTextToClipboard(html.trim());

    showToolbarToast(
        ok
            ? (labels.copyComponentCodeSuccess ?? 'Code copied to clipboard!')
            : (labels.copyComponentCodeFailed ?? 'Could not copy code'),
    );

    return ok;
}

function ensureCopyComponentCommands(editor, labels = {}) {
    if (! editor?.Commands) {
        return;
    }

    const commands = editor.Commands;
    const hasCommand = (id) => (typeof commands.has === 'function'
        ? commands.has(id)
        : Boolean(commands.getAll?.()?.[id]));

    // Never use Commands.get() for existence checks — missing ids log a warning.
    if (! hasCommand(CMD_COPY_COMPONENT_CLASSES)) {
        commands.add(CMD_COPY_COMPONENT_CLASSES, {
            run: async (ed) => {
                await copySelectedComponentClasses(ed, labels);
            },
        });
    }

    if (! hasCommand(CMD_COPY_COMPONENT_CODE)) {
        commands.add(CMD_COPY_COMPONENT_CODE, {
            run: async (ed) => {
                await runCopyComponentCode(ed, labels);
            },
        });
    }
}

/**
 * GrapesJS plugin — register copy commands before project hydration.
 *
 * @param {object} editor
 * @param {{ labels?: object }} [opts]
 */
export function voodbuilderCopyCommandsPlugin(editor, opts = {}) {
    ensureCopyComponentCommands(editor, opts.labels ?? {});
}

export function ensureCanvasComponentToolbarButtons(editor, component, labels = {}) {
    if (! component) {
        return;
    }

    ensureCopyComponentCommands(editor, labels);
    component.set('toolbar', buildComponentToolbar(editor, component, labels));

    window.requestAnimationFrame(() => {
        syncBoundToolbarState(editor, component);
        clampCanvasToolbarPosition(editor);
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

        if (editor.__voodbuilderChromeLayoutMode || editor.__voodbuilderChromeShellMode) {
            wrapper.set({
                droppable: false,
                highlightable: false,
                selectable: false,
                hoverable: false,
            });

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
    // Always (re)register copy commands — early returns must not leave toolbar IDs unresolved.
    ensureCopyComponentCommands(editor, labels);

    if (editor.__voodbuilderCanvasToolbarRegistered) {
        return;
    }

    editor.__voodbuilderCanvasToolbarRegistered = true;

    registerCanvasDropAffordance(editor);

    editor.on('component:selected', (component) => {
        ensureCanvasComponentToolbarButtons(editor, component, labels);
    });

    editor.on('component:toggled', (component) => {
        if (editor.getSelected() === component) {
            clampCanvasToolbarPosition(editor);
        }
    });

    editor.on('load', () => {
        ensureCopyComponentCommands(editor, labels);

        const canvasView = editor.Canvas?.getCanvasView?.()?.el;

        if (! canvasView) {
            return;
        }

        canvasView.addEventListener('scroll', () => {
            clampCanvasToolbarPosition(editor);
        }, { passive: true });
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
