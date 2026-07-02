/**
 * Extend GrapesJS canvas component toolbar (arrow, move, copy, delete) via public APIs only.
 * @see https://grapesjs.com/docs/api/component.html#toolbar
 */

import { lucideIcon } from './editor-icons.js';

export const CMD_MAKE_DYNAMIC = 'voodbuilder-make-dynamic';
export const CMD_CLEAR_DYNAMIC = 'voodbuilder-clear-dynamic';

const TOOLBAR_FLAG = 'data-voodbuilder-toolbar';

function toolbarHasVoodbuilderButtons(toolbar) {
    return (toolbar ?? []).some((button) => {
        const flag = button?.attributes?.[TOOLBAR_FLAG];

        return flag === 'dynamic' || flag === 'clear-dynamic';
    });
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
            label: lucideIcon('link', 15),
            command: CMD_MAKE_DYNAMIC,
        },
        {
            attributes: {
                class: 'voodbuilder-gjs-toolbar-item--clear-dynamic',
                [TOOLBAR_FLAG]: 'clear-dynamic',
                title: labels.clearDynamic ?? 'Clear dynamic binding',
                'aria-label': labels.clearDynamic ?? 'Clear dynamic binding',
            },
            label: lucideIcon('unlink', 15),
            command: CMD_CLEAR_DYNAMIC,
        },
    ];
}

function ensureDefaultToolbar(component) {
    const toolbar = component.get('toolbar');

    if ((toolbar == null || toolbar.length === 0) && typeof component.initToolbar === 'function') {
        component.initToolbar();
    }
}

function insertBeforeDelete(toolbar, buttons) {
    const next = [...toolbar];
    const deleteIndex = next.findIndex((button) => button.command === 'tlb-delete');
    const insertAt = deleteIndex >= 0 ? deleteIndex : next.length;

    next.splice(insertAt, 0, ...buttons);

    return next;
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

    ensureDefaultToolbar(component);

    const toolbar = component.get('toolbar') ?? [];

    if (! toolbarHasVoodbuilderButtons(toolbar)) {
        component.set('toolbar', insertBeforeDelete(toolbar, buildDynamicToolbarButtons(labels)));
    }

    window.requestAnimationFrame(() => {
        syncBoundToolbarState(editor, component);
    });
}

export function registerCanvasComponentToolbar(editor, labels = {}) {
    if (editor.__voodbuilderCanvasToolbarRegistered) {
        return;
    }

    editor.__voodbuilderCanvasToolbarRegistered = true;

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
