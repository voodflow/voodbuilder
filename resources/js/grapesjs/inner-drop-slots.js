/**
 * Temporary inner drop slots so GrapesJS can nest into containers that already
 * have children (otherwise the placer only shows thin "between" lines).
 */

import { safeFindComponents, walkComponentTree } from './tailwind-visual-style.js';

export const INNER_DROP_SLOT_ATTR = 'data-voodbuilder-inner-drop';
export const INNER_DROP_SLOT_TYPE = 'voodbuilder-inner-drop-slot';

const LAYOUT_CLASS_HINTS = [
    'flex',
    'inline-flex',
    'grid',
    'voodbuilder-gjs-container',
];

const LAYOUT_CLASS_PREFIXES = [
    'gap-',
    'space-y-',
    'space-x-',
];

const CONTAINER_TAGS = new Set([
    'div',
    'section',
    'article',
    'main',
    'aside',
    'nav',
    'header',
    'footer',
    'form',
    'ul',
    'ol',
]);

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentClasses(component) {
    const classes = component?.getClasses?.() ?? component?.get?.('classes') ?? [];

    if (Array.isArray(classes)) {
        return classes.map((item) => (typeof item === 'string' ? item : String(item?.id ?? item?.get?.('name') ?? '')));
    }

    return [];
}

function isDroppable(component) {
    const droppable = component?.get?.('droppable');

    if (droppable === false) {
        return false;
    }

    if (typeof droppable === 'function') {
        return true;
    }

    return droppable !== false;
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {boolean}
 */
export function isInnerDropLayoutContainer(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs[INNER_DROP_SLOT_ATTR] || attrs['data-voodbuilder-top-drop-spacer']) {
        return false;
    }

    if (attrs['data-voodbuilder-role'] === 'media' || attrs['data-voodbuilder-role'] === 'shade') {
        return false;
    }

    if (attrs['data-voodbuilder-chrome-shell-locked'] != null) {
        return false;
    }

    if (! CONTAINER_TAGS.has(componentTag(component))) {
        return false;
    }

    if (! isDroppable(component)) {
        return false;
    }

    if (attrs['data-voodbuilder-dropzone'] || attrs['data-voodbuilder-role'] === 'content') {
        return true;
    }

    const classes = componentClasses(component);

    if (classes.some((name) => LAYOUT_CLASS_HINTS.includes(name))) {
        return true;
    }

    if (classes.some((name) => LAYOUT_CLASS_PREFIXES.some((prefix) => name.startsWith(prefix)))) {
        return true;
    }

    return false;
}

function lastChildIsSlot(component) {
    const children = component.components?.();
    const last = children?.at?.(children.length - 1);

    return Boolean(last?.getAttributes?.()?.[INNER_DROP_SLOT_ATTR]);
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function clearInnerDropSlots(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    for (const slot of safeFindComponents(wrapper, `[${INNER_DROP_SLOT_ATTR}]`)) {
        slot.remove({ silent: true });
    }

    editor.__voodbuilderInnerDropSlotsActive = false;
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function mountInnerDropSlots(editor) {
    if (! editor || editor.__voodbuilderInnerDropSlotsMounting) {
        return;
    }

    if (editor.__voodbuilderSettingsChange || editor.__voodbuilderSuppressInnerSlots) {
        return;
    }

    editor.__voodbuilderInnerDropSlotsMounting = true;

    try {
        clearInnerDropSlots(editor);

        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        const hosts = [];

        walkComponentTree(wrapper, (component) => {
            if (! isInnerDropLayoutContainer(component)) {
                return;
            }

            // Empty containers already use GrapesJS "inside" placement.
            if ((component.components?.()?.length ?? 0) === 0) {
                return;
            }

            if (lastChildIsSlot(component)) {
                return;
            }

            hosts.push(component);
        });

        // Keep drag responsive on large pages.
        hosts.slice(0, 80).forEach((host) => {
            host.append({
                type: INNER_DROP_SLOT_TYPE,
            }, { silent: true });
        });

        editor.__voodbuilderInnerDropSlotsActive = hosts.length > 0;
    } finally {
        editor.__voodbuilderInnerDropSlotsMounting = false;
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerInnerDropSlotType(editor) {
    if (editor.__voodbuilderInnerDropSlotTypeRegistered) {
        return;
    }

    editor.__voodbuilderInnerDropSlotTypeRegistered = true;

    editor.DomComponents.addType(INNER_DROP_SLOT_TYPE, {
        isComponent: (element) => element?.hasAttribute?.(INNER_DROP_SLOT_ATTR) === true,
        model: {
            defaults: {
                type: INNER_DROP_SLOT_TYPE,
                tagName: 'div',
                name: 'Drop inside',
                draggable: false,
                // Not droppable: the placer uses before/after on this sentinel so
                // the new component lands as a sibling in the parent container.
                droppable: false,
                selectable: false,
                highlightable: true,
                hoverable: true,
                badgable: false,
                removable: false,
                copyable: false,
                layerable: false,
                stylable: false,
                attributes: {
                    [INNER_DROP_SLOT_ATTR]: '1',
                    class: 'voodbuilder-gjs-inner-drop-slot',
                    'aria-hidden': 'true',
                },
            },
        },
    });
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerInnerDropSlots(editor) {
    if (editor.__voodbuilderInnerDropSlotsRegistered) {
        return;
    }

    editor.__voodbuilderInnerDropSlotsRegistered = true;

    registerInnerDropSlotType(editor);

    const scheduleClear = () => {
        window.requestAnimationFrame(() => {
            clearInnerDropSlots(editor);
        });
    };

    // Mount synchronously so GrapesJS sorter dimensions include the slots.
    editor.on('block:drag:start', () => mountInnerDropSlots(editor));
    editor.on('sorter:drag:start', () => mountInnerDropSlots(editor));
    editor.on('block:drag:stop', scheduleClear);
    editor.on('sorter:drag:end', scheduleClear);
    editor.on('load', () => clearInnerDropSlots(editor));
    editor.on('destroy', () => clearInnerDropSlots(editor));
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function detachInnerDropSlotsForExport(editor) {
    clearInnerDropSlots(editor);
}
