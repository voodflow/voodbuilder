/**
 * Footer block preview locking for layout vs page shell editors.
 */

import { safeFindComponents } from '../../../tailwind-visual-style.js';
import { ATTR } from '../../../core/attrs.js';
import { isFooterBlock } from '../../ids.js';

function lockComponentTree(component) {
    const attributes = component.getAttributes?.() ?? {};
    const protectedSlot = attributes[ATTR.menu] || attributes[ATTR.brand];

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: ! protectedSlot,
        hoverable: ! protectedSlot,
        layerable: false,
        editable: false,
        stylable: ! protectedSlot,
    });

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function suppressChromeBlockDescendants(component) {
    component.components().forEach((child) => {
        child.set({
            removable: false,
            draggable: false,
            copyable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
            layerable: false,
            editable: false,
            stylable: false,
        }, { silent: true });
        suppressChromeBlockDescendants(child);
    });
}

/**
 * @param {object} component
 * @param {object} [editor]
 * @param {{ resolveBlockLayerLabel?: Function }} [opts]
 */
export function lockFooterPreview(component, editor, opts = {}) {
    const blockId = component.getAttributes()[ATTR.block];
    const layoutMode = Boolean(component.em?.__voodbuilderChromeLayoutMode ?? editor?.__voodbuilderChromeLayoutMode);
    const shellMode = Boolean(component.em?.__voodbuilderChromeShellMode ?? editor?.__voodbuilderChromeShellMode);

    if (! isFooterBlock(blockId)) {
        return false;
    }

    component.set({
        selectable: true,
        highlightable: true,
        hoverable: true,
        layerable: true,
        name: typeof opts.resolveBlockLayerLabel === 'function'
            ? opts.resolveBlockLayerLabel(blockId)
            : component.get('name'),
    }, { silent: true });

    if (layoutMode || shellMode) {
        suppressChromeBlockDescendants(component);

        return true;
    }

    safeFindComponents(component, `[${ATTR.menu}], [${ATTR.brand}]`).forEach((slot) => {
        lockComponentTree(slot);
    });

    return true;
}

/**
 * Unified lock entry used by layout editor after drop.
 *
 * @param {object} component
 * @param {object} [editor]
 * @param {object} [opts]
 */
export function lockPreview(component, editor, opts = {}) {
    const blockId = component.getAttributes()[ATTR.block];

    if (isFooterBlock(blockId)) {
        return lockFooterPreview(component, editor, opts);
    }

    return false;
}
