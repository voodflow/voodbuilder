/**
 * Basic Text vs Rich Text component types + plain RTE behaviour.
 */

import { debugSwallowed } from './debug-swallowed.js';
import { resolveBlockLabel } from './section-block-meta.js';

export function isBasicTextComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-text') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};

    return attrs['data-voodbuilder-text'] != null;
}

export function isRichTextComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-rich-text') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const classes = component.getClasses?.() ?? [];

    return attrs['data-voodbuilder-rich-text'] != null
        || classes.includes('vb-rich-text');
}

/**
 * Walk up from an inner paragraph/span to the Rich Text host.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findRichTextHost(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isRichTextComponent(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Inner markup is edited in the Content panel — keep children non-selectable
 * so canvas clicks hit the host (toolbar + settings).
 *
 * @param {object} component
 */
export function lockRichTextChildren(component) {
    if (! component) {
        return;
    }

    const walk = (node) => {
        node.components?.().forEach((child) => {
            child.set({
                selectable: false,
                hoverable: false,
                highlightable: false,
                editable: false,
                draggable: false,
                droppable: false,
                copyable: false,
                removable: false,
            }, { silent: true });
            walk(child);
        });
    };

    walk(component);
}

/**
 * @param {object} editor
 * @param {object} component
 */
export function keepRichTextSelection(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const selectHost = () => {
        const selected = editor.getSelected?.();

        if (selected === component) {
            return;
        }

        // Promote inner markup (p/span/a) up to the Rich Text host — but never
        // steal selection when the author already clicked a different block.
        // Re-selecting across frames left canvas RTE stuck (caret at start →
        // "RTL" typing, other text nodes unselectable until Save).
        if (selected && findRichTextHost(selected) !== component) {
            return;
        }

        editor.select?.(component, { scroll: false });
    };

    selectHost();
    window.requestAnimationFrame(selectHost);
}

/**
 * @param {object} editor
 */
export function registerTextElementTypes(editor) {
    if (! editor?.DomComponents || editor.__voodbuilderTextTypesRegistered) {
        return;
    }

    editor.__voodbuilderTextTypesRegistered = true;

    const textBase = editor.DomComponents.getType('text');
    const textDefaults = textBase?.model?.prototype?.defaults ?? {};

    editor.DomComponents.addType('voodbuilder-text', {
        extend: 'text',
        isComponent: (el) => el?.hasAttribute?.('data-voodbuilder-text') === true,
        model: {
            defaults: {
                ...textDefaults,
                tagName: 'p',
                name: resolveBlockLabel('voodbuilder-text', 'Basic Text'),
                editable: true,
                droppable: false,
                attributes: {
                    'data-voodbuilder-text': '',
                },
            },
        },
    });

    editor.DomComponents.addType('voodbuilder-rich-text', {
        // Do NOT extend `text`: Editor text components reject/hoist block markup
        // (`<p>`, lists, …) which leaked RTE updates into the page slot before the footer.
        isComponent: (el) => (
            el?.hasAttribute?.('data-voodbuilder-rich-text') === true
            || el?.classList?.contains?.('vb-rich-text') === true
        ),
        model: {
            defaults: {
                tagName: 'div',
                name: resolveBlockLabel('voodbuilder-rich-text', 'Rich Text'),
                // Edit in Content panel (light RTE), not via canvas toolbar.
                editable: false,
                droppable: false,
                highlightable: true,
                selectable: true,
                hoverable: true,
                attributes: {
                    'data-voodbuilder-rich-text': '',
                },
                classes: ['vb-rich-text', 'space-y-3', 'text-base', 'leading-relaxed', 'text-vp-text-2'],
            },
            init() {
                const lock = () => lockRichTextChildren(this);
                this.on('change:components', lock);
                lock();
            },
        },
    });
}

/**
 * Canvas RTE formatting toolbar is for headings only (and legacy).
 * Basic Text / plain text → no bar (use Style + Tailwind).
 * Rich Text → Content panel light RTE (never the canvas Grapes toolbar).
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function shouldHideCanvasRteToolbar(component) {
    if (! component?.get) {
        return false;
    }

    if (isRichTextComponent(component) || findRichTextHost(component)) {
        return true;
    }

    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isBasicTextComponent(current)) {
            return true;
        }

        const type = String(current.get?.('type') ?? '');

        if (type === 'voodbuilder-heading' || type === 'heading') {
            return false;
        }

        current = current.parent?.();
    }

    const type = String(component.get('type') ?? '');

    // Generic Editor text nodes: plain editing only.
    return type === 'text' || type === 'textnode';
}

/**
 * Exit canvas Grapes RTE and scrub leftover contenteditable nodes.
 *
 * Clicks inside an active contenteditable often never change Grapes selection, so
 * `component:selected` never runs and other texts look unselectable until Save
 * (blur / remount). Call this on pointer-down outside the editing node.
 *
 * @param {object} editor
 * @param {Element|null} [keepEl] leave this node editable (optional)
 */
export function forceReleaseCanvasRte(editor, keepEl = null) {
    if (! editor || editor.__voodbuilderReleasingCanvasRte) {
        return;
    }

    editor.__voodbuilderReleasingCanvasRte = true;

    try {
        const editing = editor.getEditing?.();

        if (editing) {
            try {
                editing.view?.disableEditing?.();
            } catch (error) {
                debugSwallowed(error);
            }
        }

        try {
            editor.RichTextEditor?.disable?.();
        } catch (error) {
            debugSwallowed(error);
        }

        const doc = editor.Canvas?.getDocument?.();

        if (! doc?.querySelectorAll) {
            return;
        }

        doc.querySelectorAll('[contenteditable="true"]').forEach((node) => {
            if (
                keepEl
                && (
                    node === keepEl
                    || (typeof keepEl.contains === 'function' && keepEl.contains(node))
                    || (typeof node.contains === 'function' && node.contains(keepEl))
                )
            ) {
                return;
            }

            node.removeAttribute('contenteditable');

            if (node.getAttribute?.('dir') === 'ltr') {
                node.removeAttribute('dir');
            }
        });
    } finally {
        editor.__voodbuilderReleasingCanvasRte = false;
    }
}

/**
 * Hide Editor canvas RTE toolbar for Basic Text / plain text.
 * Formatting belongs on Rich Text; Basic Text uses Tailwind + inline styles.
 *
 * @param {object} editor
 */
export function configurePlainTextRte(editor) {
    if (! editor || editor.__voodbuilderPlainTextRteConfigured) {
        return;
    }

    editor.__voodbuilderPlainTextRteConfigured = true;

    const shellRoot = () => editor.getContainer?.()?.closest?.('.voodbuilder-editor-root')
        ?? document.querySelector('.voodbuilder-editor-root');

    const hideToolbar = () => {
        const toolbar = editor.RichTextEditor?.getToolbarEl?.();

        if (toolbar) {
            toolbar.style.setProperty('display', 'none', 'important');
            toolbar.setAttribute('data-voodbuilder-rte-hidden', '');
            toolbar.setAttribute('aria-hidden', 'true');
        }

        shellRoot()?.classList.add('voodbuilder-editor-rte-plain');
    };

    const showToolbar = () => {
        const toolbar = editor.RichTextEditor?.getToolbarEl?.();

        if (toolbar) {
            toolbar.style.removeProperty('display');
            toolbar.removeAttribute('data-voodbuilder-rte-hidden');
            toolbar.removeAttribute('aria-hidden');
        }

        shellRoot()?.classList.remove('voodbuilder-editor-rte-plain');
    };

    const bindPlainPaste = (view, model) => {
        const el = view?.el ?? model?.getEl?.();

        if (! el || el.__vbPlainPasteBound) {
            return;
        }

        el.__vbPlainPasteBound = true;
        el.addEventListener('paste', (event) => {
            // Canvas RTE lives in the iframe — use that document/selection, not the parent window.
            // Parent getSelection() misses the caret and we fall back to "insert at end",
            // while the browser (or another listener) still pastes at the caret → duplicate tag.
            event.preventDefault();
            event.stopImmediatePropagation();

            const text = event.clipboardData?.getData('text/plain') ?? '';

            if (text === '') {
                return;
            }

            const doc = el.ownerDocument ?? document;
            const win = doc.defaultView ?? window;
            const selection = win.getSelection?.();

            if (! selection) {
                return;
            }

            let range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

            if (! range || ! el.contains(range.commonAncestorContainer)) {
                range = doc.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                selection.removeAllRanges?.();
                selection.addRange?.(range);
            }

            range.deleteContents();

            const node = doc.createTextNode(text);
            range.insertNode(node);

            const caret = doc.createRange();
            caret.setStartAfter(node);
            caret.collapse(true);

            selection.removeAllRanges?.();
            selection.addRange?.(caret);

            // Keep GrapesJS model in sync with the DOM edit.
            el.dispatchEvent(new InputEvent('input', {
                bubbles: true,
                cancelable: true,
                inputType: 'insertFromPaste',
                data: text,
            }));
        }, true);
    };

    const onCanvasPointerDown = (event) => {
        const editing = editor.getEditing?.();

        if (! editing) {
            return;
        }

        const editingEl = editing.getEl?.() ?? editing.view?.el ?? null;
        const target = event.target;

        if (! editingEl || ! target) {
            return;
        }

        // Stay in RTE when interacting inside the active node.
        if (editingEl === target || editingEl.contains?.(target)) {
            return;
        }

        forceReleaseCanvasRte(editor);
    };

    const bindCanvasRteRelease = () => {
        const doc = editor.Canvas?.getDocument?.();

        if (! doc || doc.__vbCanvasRteReleaseBound) {
            return;
        }

        doc.__vbCanvasRteReleaseBound = true;
        doc.addEventListener('mousedown', onCanvasPointerDown, true);
    };

    editor.on('rte:enable', (view) => {
        const model = view?.model ?? editor.getSelected?.() ?? null;
        const el = view?.el ?? model?.getEl?.();

        // Grapes/contenteditable can inherit a stale dir=rtl (or dir=auto that
        // flips after caret reset). Only write when needed — setAttribute always
        // can reset the caret to index 0 after a click inside the text.
        if (el?.getAttribute && el.getAttribute('dir') !== 'ltr') {
            el.setAttribute('dir', 'ltr');
        }

        if (! shouldHideCanvasRteToolbar(model)) {
            showToolbar();

            return;
        }

        hideToolbar();
        bindPlainPaste(view, model);
        // Editor repositions/shows the toolbar after enable — re-assert hide.
        window.requestAnimationFrame(hideToolbar);
        window.setTimeout(hideToolbar, 0);
        window.setTimeout(hideToolbar, 50);
    });

    editor.on('rte:disable', (view) => {
        showToolbar();

        const el = view?.el ?? view?.model?.getEl?.();

        if (el?.getAttribute?.('dir') === 'ltr') {
            el.removeAttribute('dir');
        }

        if (el?.getAttribute?.('contenteditable') === 'true') {
            el.removeAttribute('contenteditable');
        }
    });

    editor.on('component:deselected', () => {
        showToolbar();
        forceReleaseCanvasRte(editor);
    });

    // If selection moves while a text view still thinks it is editing, force
    // disable so contenteditable cannot trap clicks / caret on the previous node.
    editor.on('component:selected', (component) => {
        const editing = editor.getEditing?.();

        if (! editing || editing === component) {
            return;
        }

        forceReleaseCanvasRte(editor);
    });

    // Frame reloads replace the iframe document — rebind capture listener.
    editor.on('canvas:frame:load', bindCanvasRteRelease);
    bindCanvasRteRelease();
}
