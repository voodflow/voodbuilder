/**
 * Temporary inner drop slots so Editor can nest into containers that already
 * have children (otherwise the placer only shows thin "between" lines).
 */

import { safeFindComponents, walkComponentTree } from './tailwind-visual-style.js';

export const INNER_DROP_SLOT_ATTR = 'data-voodbuilder-inner-drop';
export const INNER_DROP_SLOT_TYPE = 'voodbuilder-inner-drop-slot';
export const INNER_DROP_SLOTS_VISIBLE_CLASS = 'voodbuilder-inner-drop-slots-visible';
export const INNER_DROP_SLOTS_STORAGE_KEY = 'voodbuilder:inner-drop-slots-visible';
export const INNER_DROP_DRAG_BODY_CLASS = 'voodbuilder-editor-inner-drop-dragging';

const MAX_INNER_DROP_HOSTS = 160;

const LAYOUT_CLASS_HINTS = [
    'flex',
    'inline-flex',
    'grid',
    'voodbuilder-editor-container',
    'vb-layout-block',
    'vb-layout-div',
];

const LAYOUT_CLASS_PREFIXES = [
    'gap-',
    'space-y-',
    'space-x-',
];

const LAYOUT_ATTR_KINDS = new Set(['section', 'container', 'block', 'div']);
const LAYOUT_TYPES = new Set([
    'voodbuilder-section',
    'voodbuilder-container',
    'voodbuilder-layout-block',
    'voodbuilder-layout-div',
]);

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

function isAnimatedLayoutHost(component) {
    const attrs = component?.getAttributes?.() ?? {};

    if (
        attrs['data-voodbuilder-animated-stats'] != null
        || attrs['data-voodbuilder-animated-counter'] != null
        || attrs['data-voodbuilder-animated-cta'] != null
        || attrs['data-voodbuilder-logo-scroll'] != null
        || attrs['data-vb-items-root'] != null
        || attrs['data-vb-item'] != null
        || attrs['data-vb-count-to'] != null
    ) {
        return true;
    }

    const type = String(component?.get?.('type') ?? '');

    return type.startsWith('voodbuilder-animated-') || type === 'voodbuilder-logo-scroll';
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

    if (attrs[INNER_DROP_SLOT_ATTR] || attrs['data-voodbuilder-top-drop-spacer'] || attrs['data-voodbuilder-bottom-drop-spacer']) {
        return false;
    }

    if (attrs['data-voodbuilder-role'] === 'media' || attrs['data-voodbuilder-role'] === 'shade') {
        return false;
    }

    if (attrs['data-voodbuilder-chrome-shell-locked'] != null) {
        return false;
    }

    if (isAnimatedLayoutHost(component)) {
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

    if (LAYOUT_ATTR_KINDS.has(String(attrs['data-voodbuilder-layout'] ?? ''))) {
        return true;
    }

    if (LAYOUT_TYPES.has(String(component.get?.('type') ?? ''))) {
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
 * @returns {boolean}
 */
export function readInnerDropSlotsVisiblePreference() {
    try {
        return localStorage.getItem(INNER_DROP_SLOTS_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * @param {boolean} active
 */
export function saveInnerDropSlotsVisiblePreference(active) {
    try {
        localStorage.setItem(INNER_DROP_SLOTS_STORAGE_KEY, active ? '1' : '0');
    } catch {
        // Ignore storage errors.
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {boolean} visible
 * @param {HTMLElement|null|undefined} shellRoot
 */
export function setInnerDropSlotsVisible(editor, visible, shellRoot = null) {
    editor.__voodbuilderInnerDropSlotsVisible = visible === true;
    shellRoot?.classList.toggle('is-inner-drop-slots-visible', visible === true);

    editor?.Canvas?.getFrames?.()?.forEach((frame) => {
        frame.view?.getBody()?.classList.toggle(INNER_DROP_SLOTS_VISIBLE_CLASS, visible === true);
    });

    if (visible) {
        mountInnerDropSlots(editor);
    } else if (! editor?.__voodbuilderInnerDropSlotsDragging) {
        clearInnerDropSlots(editor);
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {boolean} active
 */
function setInnerDropDragBodyClass(editor, active) {
    const canvasBody = editor?.Canvas?.getDocument?.()?.body;
    canvasBody?.classList?.toggle(INNER_DROP_DRAG_BODY_CLASS, active === true);
    document.body.classList.toggle(INNER_DROP_DRAG_BODY_CLASS, active === true);
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function clearInnerDropSlots(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        setInnerDropDragBodyClass(editor, false);
        editor.__voodbuilderInnerDropSlotsActive = false;

        return;
    }

    for (const slot of safeFindComponents(wrapper, `[${INNER_DROP_SLOT_ATTR}]`)) {
        slot.remove({ silent: true });
    }

    // Also purge leaked DOM nodes that lost their Grapes model.
    try {
        const doc = editor.Canvas?.getDocument?.();

        doc?.querySelectorAll?.(`[${INNER_DROP_SLOT_ATTR}], .voodbuilder-editor-inner-drop-slot`)
            ?.forEach((node) => node.remove());
    } catch {
        // Canvas may be unavailable during destroy.
    }

    setInnerDropDragBodyClass(editor, false);
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
        // Prefer incremental append over clear+rebuild so slots appear immediately.
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        const existingHosts = new Set();

        for (const slot of safeFindComponents(wrapper, `[${INNER_DROP_SLOT_ATTR}]`)) {
            const host = slot.parent?.();

            if (host) {
                existingHosts.add(host);
            }
        }

        const hosts = [];

        walkComponentTree(wrapper, (component) => {
            if (! isInnerDropLayoutContainer(component)) {
                return;
            }

            // Empty containers already use Editor "inside" placement.
            if ((component.components?.()?.length ?? 0) === 0) {
                return;
            }

            if (lastChildIsSlot(component) || existingHosts.has(component)) {
                return;
            }

            hosts.push(component);
        });

        hosts.slice(0, MAX_INNER_DROP_HOSTS).forEach((host) => {
            host.append({
                type: INNER_DROP_SLOT_TYPE,
            }, { silent: true });
        });

        // Only the active drag session should apply the drag body class. Mounting
        // for the "show dropzones" toggle must not leave the top spacer painted green.
        setInnerDropDragBodyClass(editor, editor.__voodbuilderInnerDropSlotsDragging === true);
        editor.__voodbuilderInnerDropSlotsActive = true;
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
                    class: 'voodbuilder-editor-inner-drop-slot',
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
    editor.__voodbuilderInnerDropSlotsVisible = readInnerDropSlotsVisiblePreference();
    editor.__voodbuilderInnerDropSlotsDragging = false;

    const scheduleClear = () => {
        editor.__voodbuilderInnerDropSlotsDragging = false;
        window.requestAnimationFrame(() => {
            // Always drop the drag body class — it also drives section-gap dropzones.
            setInnerDropDragBodyClass(editor, false);

            if (editor.__voodbuilderInnerDropSlotsVisible) {
                mountInnerDropSlots(editor);

                return;
            }

            clearInnerDropSlots(editor);
        });
    };

    const startDrag = () => {
        // Layers panel reorder uses the same sorter events — do not mount canvas
        // drop slots or show section-gap cues (that also remounts the layer tree).
        if (editor.__voodbuilderLayerTreeSorting) {
            return;
        }

        editor.__voodbuilderInnerDropSlotsDragging = true;
        setInnerDropDragBodyClass(editor, true);
        mountInnerDropSlots(editor);
    };

    // Mount synchronously so Editor sorter dimensions include the slots.
    editor.on('block:drag:start', startDrag);
    editor.on('component:drag:start', startDrag);
    editor.on('sorter:drag:start', startDrag);
    editor.on('block:drag:stop', scheduleClear);
    editor.on('sorter:drag:end', scheduleClear);
    editor.on('component:drag:end', scheduleClear);
    editor.on('load', () => {
        clearInnerDropSlots(editor);

        if (editor.__voodbuilderInnerDropSlotsVisible) {
            mountInnerDropSlots(editor);
        }
    });
    editor.on('canvas:frame:load', () => {
        clearInnerDropSlots(editor);
        setInnerDropSlotsVisible(
            editor,
            editor.__voodbuilderInnerDropSlotsVisible === true,
        );
    });
    editor.on('destroy', () => clearInnerDropSlots(editor));
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function detachInnerDropSlotsForExport(editor) {
    clearInnerDropSlots(editor);
}
