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
import { CMD_EDIT_IMAGE, resolveEditableImageTarget } from './jodit-image-editor.js';
import {
    buildContentWidthToolbarButton,
    ensureCanvasContentWidthToolbarState,
    ensureContentWidthCommand,
} from './content-width-toolbar.js';
import { buildLayoutPickerToolbarButton } from './layout-blocks.js';
import { findRichTextHost, isRichTextComponent } from './text-elements.js';

export const CMD_MAKE_DYNAMIC = 'voodbuilder-make-dynamic';
export const CMD_CLEAR_DYNAMIC = 'voodbuilder-clear-dynamic';
export const CMD_COPY_COMPONENT_CLASSES = 'voodbuilder:copy-component-classes';
export const CMD_COPY_COMPONENT_CODE = 'voodbuilder:copy-component-code';
export { CMD_EDIT_IMAGE };

const TOOLBAR_FLAG = 'data-voodbuilder-toolbar';

function isRichTextCanvasTarget(component) {
    return Boolean(isRichTextComponent(component) || findRichTextHost(component));
}

function isInnerDropSlotComponent(component) {
    return Boolean(component?.getAttributes?.()?.['data-voodbuilder-inner-drop'])
        || component?.get?.('type') === 'voodbuilder-inner-drop-slot';
}

function isTopDropSpacerComponent(component) {
    return Boolean(component?.getAttributes?.()?.['data-voodbuilder-top-drop-spacer']);
}

function listRealSiblings(component) {
    const parent = component?.parent?.();
    const collection = parent?.components?.() ?? component?.collection;
    const models = collection?.models ?? (Array.isArray(collection) ? collection : [...(collection ?? [])]);

    return models.filter((sibling) => (
        sibling
        && ! isInnerDropSlotComponent(sibling)
        && ! isTopDropSpacerComponent(sibling)
    ));
}

/**
 * Reorder among real siblings (skip editor-only drop sentinels).
 *
 * @param {object} editor
 * @param {1|-1} direction
 */
function moveSelectedSibling(editor, direction) {
    const component = editor?.getSelected?.();
    const parent = component?.parent?.();

    if (! component || ! parent?.components) {
        return;
    }

    const siblings = listRealSiblings(component);
    const currentIndex = siblings.indexOf(component);

    if (currentIndex < 0) {
        return;
    }

    const nextIndex = currentIndex + direction;

    if (nextIndex < 0 || nextIndex >= siblings.length) {
        return;
    }

    const target = siblings[nextIndex];
    const collectionIndex = parent.components().indexOf(target);

    if (collectionIndex < 0) {
        return;
    }

    editor.__voodbuilderSetCssRebuildSuspended?.(true);

    try {
        component.move(parent, { at: direction < 0 ? collectionIndex : collectionIndex + 1 });
        editor.select(component);
        // Select of an already-selected model may not re-fire component:selected —
        // rebuild move-up / move-down for the new sibling index immediately.
        ensureCanvasComponentToolbarButtons(editor, component, editor.__voodbuilderLabels ?? {});
    } finally {
        // Keep suspend briefly so post-move selector/selection noise cannot schedule compile.
        window.setTimeout(() => {
            editor.__voodbuilderSetCssRebuildSuspended?.(false);
        }, 500);
    }
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

/**
 * True when the selected component has a clearable dynamic bind/href attr.
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function hasClearableDynamicBinding(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(
        String(attrs['data-voodbuilder-bind'] ?? '').trim()
        || String(attrs['data-voodbuilder-bind-href'] ?? '').trim(),
    );
}

function toolbarHasClearDynamicButton(component) {
    const toolbar = component?.get?.('toolbar');

    if (! Array.isArray(toolbar)) {
        return false;
    }

    return toolbar.some((item) => item?.attributes?.[TOOLBAR_FLAG] === 'clear-dynamic');
}

function buildDynamicToolbarButtons(labels = {}, { showMake = true, showClear = false } = {}) {
    const buttons = [];

    if (showMake) {
        buttons.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--dynamic',
                [TOOLBAR_FLAG]: 'dynamic',
                title: labels.makeDynamic ?? 'Make dynamic',
                'aria-label': labels.makeDynamic ?? 'Make dynamic',
            },
            label: lucideIcon('link-2', 16),
            command: CMD_MAKE_DYNAMIC,
        });
    }

    if (showClear) {
        buttons.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--clear-dynamic',
                [TOOLBAR_FLAG]: 'clear-dynamic',
                title: labels.clearDynamic ?? 'Clear dynamic binding',
                'aria-label': labels.clearDynamic ?? 'Clear dynamic binding',
            },
            label: lucideIcon('unlink-2', 16),
            command: CMD_CLEAR_DYNAMIC,
        });
    }

    return buttons;
}

function buildComponentToolbar(editor, component, labels = {}) {
    if (shouldSuppressChromeSlotInspector(component, editor) || isChromeEditorProtectedComponent(component, editor)) {
        return [];
    }

    const stylePrefix = editor.getConfig?.('stylePrefix') ?? 'gjs-';
    const toolbar = [];

    // Heal nested page-content clones that inherited draggable:false from an older
    // chrome-shell lock (top-level-only). Layout editing needs move on every block.
    if (component.get('draggable') === false && ! component.getAttributes?.()?.['data-voodbuilder-chrome-shell-locked']) {
        component.set('draggable', true, { silent: true });
    }

    if (component.get('copyable') === false && ! component.getAttributes?.()?.['data-voodbuilder-chrome-shell-locked']) {
        component.set({ copyable: true, removable: true }, { silent: true });
    }

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

    const siblings = listRealSiblings(component);
    const siblingIndex = siblings.indexOf(component);
    const siblingCount = siblings.length;
    const canReorder = siblingCount > 1
        && siblingIndex >= 0
        && component.get('draggable') !== false
        && ! isChromeEditorProtectedComponent(component, editor);

    if (canReorder && siblingIndex > 0) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--move-up',
                [TOOLBAR_FLAG]: 'move-up',
                title: labels.moveUp ?? 'Move up',
                'aria-label': labels.moveUp ?? 'Move up',
            },
            label: lucideIcon('arrow-up', 16),
            command: (ed) => moveSelectedSibling(ed, -1),
        });
    }

    if (canReorder && siblingIndex < siblingCount - 1) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--move-down',
                [TOOLBAR_FLAG]: 'move-down',
                title: labels.moveDown ?? 'Move down',
                'aria-label': labels.moveDown ?? 'Move down',
            },
            label: lucideIcon('arrow-down', 16),
            command: (ed) => moveSelectedSibling(ed, 1),
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

    const layoutButton = buildLayoutPickerToolbarButton(component, labels);

    if (layoutButton) {
        toolbar.push(layoutButton);
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

    if (editor.__voodbuilderImageEditorEnabled && resolveEditableImageTarget(component)) {
        toolbar.push({
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--edit-image',
                [TOOLBAR_FLAG]: 'edit-image',
                title: labels.editImage ?? 'Edit image',
                'aria-label': labels.editImage ?? 'Edit image',
            },
            label: lucideIcon('pencil', 16),
            command: CMD_EDIT_IMAGE,
        });
    }

    // Rich Text: edit content in the Content panel — skip code/dynamic chrome here.
    const richText = isRichTextCanvasTarget(component);

    if (! richText && canEditBlockCode(component, editor)) {
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

    // Content width: place near class/dynamic tools so it stays visible in crowded toolbars.
    const contentWidthButton = buildContentWidthToolbarButton(component, editor, labels);

    if (contentWidthButton) {
        toolbar.push(contentWidthButton);
    }

    if (! richText) {
        const dynamicEnabled = editor.__voodbuilderDynamicDataEnabled !== false;
        const hasBinding = hasClearableDynamicBinding(component);

        // Soft commercial gate: hide Make dynamic when the companion plugin is off,
        // but keep Clear so orphan bindings on saved pages can still be removed.
        if (dynamicEnabled || hasBinding) {
            toolbar.push(...buildDynamicToolbarButtons(labels, {
                showMake: dynamicEnabled,
                showClear: hasBinding,
            }));
        }
    }

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

    const bound = hasClearableDynamicBinding(component);
    const dynamicButton = toolbarEl.querySelector(`[${TOOLBAR_FLAG}="dynamic"]`);
    const clearButton = toolbarEl.querySelector(`[${TOOLBAR_FLAG}="clear-dynamic"]`);

    dynamicButton?.classList.toggle('is-bound', bound);
    // Belt-and-suspenders: hide if a stale clear button is still in the DOM.
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
    ensureContentWidthCommand(editor, labels);
    component.set('toolbar', buildComponentToolbar(editor, component, labels));

    const sync = () => {
        syncBoundToolbarState(editor, component);
        ensureCanvasContentWidthToolbarState(editor, component, labels);
        clampCanvasToolbarPosition(editor);
    };

    window.requestAnimationFrame(sync);
    // Second pass after GrapesJS positions the toolbar / canvas attrs settle on ?edit=1.
    window.setTimeout(sync, 0);
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
    ensureContentWidthCommand(editor, labels);

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
        if (editor.getSelected() !== component) {
            return;
        }

        const shouldShowClear = hasClearableDynamicBinding(component);

        // Rebuild when bind presence diverges from toolbar items (make/clear dynamic).
        // Avoids loops: after rebuild, presence matches and we only sync classes.
        if (shouldShowClear !== toolbarHasClearDynamicButton(component)) {
            ensureCanvasComponentToolbarButtons(editor, component, labels);

            return;
        }

        syncBoundToolbarState(editor, component);
    });

    editor.on('component:styleUpdate', (component) => {
        if (editor.getSelected() === component) {
            syncBoundToolbarState(editor, component);
        }
    });
}
