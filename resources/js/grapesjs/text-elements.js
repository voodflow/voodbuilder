/**
 * Basic Text vs Rich Text component types + plain RTE behaviour.
 */

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
        if (editor.getSelected?.() === component) {
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
        // Do NOT extend `text`: GrapesJS text components reject/hoist block markup
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
 * Basic Text / plain text → no bar (use Style + Tailwind). RichText → Content panel.
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function shouldHideCanvasRteToolbar(component) {
    if (! component?.get) {
        return false;
    }

    if (isRichTextComponent(component) || findRichTextHost(component)) {
        return false;
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

        if (isRichTextComponent(current)) {
            return false;
        }

        current = current.parent?.();
    }

    const type = String(component.get('type') ?? '');

    // Generic GrapesJS text nodes: plain editing only.
    return type === 'text' || type === 'textnode';
}

/**
 * Hide GrapesJS canvas RTE toolbar for Basic Text / plain text.
 * Formatting belongs on Rich Text; Basic Text uses Tailwind + inline styles.
 *
 * @param {object} editor
 */
export function configurePlainTextRte(editor) {
    if (! editor || editor.__voodbuilderPlainTextRteConfigured) {
        return;
    }

    editor.__voodbuilderPlainTextRteConfigured = true;

    const shellRoot = () => editor.getContainer?.()?.closest?.('.voodbuilder-gjs-root')
        ?? document.querySelector('.voodbuilder-gjs-root');

    const hideToolbar = () => {
        const toolbar = editor.RichTextEditor?.getToolbarEl?.();

        if (toolbar) {
            toolbar.style.setProperty('display', 'none', 'important');
            toolbar.setAttribute('data-voodbuilder-rte-hidden', '');
            toolbar.setAttribute('aria-hidden', 'true');
        }

        shellRoot()?.classList.add('voodbuilder-gjs-rte-plain');
    };

    const showToolbar = () => {
        const toolbar = editor.RichTextEditor?.getToolbarEl?.();

        if (toolbar) {
            toolbar.style.removeProperty('display');
            toolbar.removeAttribute('data-voodbuilder-rte-hidden');
            toolbar.removeAttribute('aria-hidden');
        }

        shellRoot()?.classList.remove('voodbuilder-gjs-rte-plain');
    };

    const bindPlainPaste = (view, model) => {
        const el = view?.el ?? model?.getEl?.();

        if (! el || el.__vbPlainPasteBound) {
            return;
        }

        el.__vbPlainPasteBound = true;
        el.addEventListener('paste', (event) => {
            event.preventDefault();
            const text = event.clipboardData?.getData('text/plain') ?? '';

            const selection = window.getSelection?.();

            if (! selection) {
                return;
            }

            let range = selection.rangeCount ? selection.getRangeAt(0) : null;

            if (! range || ! el.contains(range.commonAncestorContainer)) {
                range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                selection.removeAllRanges?.();
                selection.addRange?.(range);
            }

            range.deleteContents();

            const node = document.createTextNode(text);
            range.insertNode(node);

            const caret = document.createRange();
            caret.setStartAfter(node);
            caret.collapse(true);

            selection.removeAllRanges?.();
            selection.addRange?.(caret);
        });
    };

    editor.on('rte:enable', (view) => {
        const model = view?.model ?? editor.getSelected?.() ?? null;

        if (! shouldHideCanvasRteToolbar(model)) {
            showToolbar();

            return;
        }

        hideToolbar();
        bindPlainPaste(view, model);
        // GrapesJS repositions/shows the toolbar after enable — re-assert hide.
        window.requestAnimationFrame(hideToolbar);
        window.setTimeout(hideToolbar, 0);
        window.setTimeout(hideToolbar, 50);
    });

    editor.on('rte:disable', () => {
        showToolbar();
    });

    editor.on('component:deselected', () => {
        showToolbar();
    });
}
