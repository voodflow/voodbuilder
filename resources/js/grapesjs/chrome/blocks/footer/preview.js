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
 * Layout editor: allow Style/Classes on the chrome tree while keeping menu/brand
 * slots and chrome icon buttons non-editable as free-form content.
 *
 * @param {object} component
 */
function enableChromeLayoutStylingTree(component) {
    const attributes = component.getAttributes?.() ?? {};
    const protectedSlot = Boolean(attributes[ATTR.menu] || attributes[ATTR.brand]);
    const chromeIcon = Boolean(
        attributes['data-voodbuilder-search-open'] != null
        || attributes['data-voodbuilder-notification-bell-preview'] != null
        || attributes['data-voodbuilder-profile-menu-toggle'] != null
        || attributes['data-mobile-nav-toggle'] != null
        || attributes['data-mobile-nav-close'] != null
        || attributes['data-theme-toggle'] != null
        || (component.getClasses?.() ?? []).includes('voodbuilder-header-icon-btn'),
    );
    const lockContent = protectedSlot || chromeIcon;

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: ! lockContent,
        hoverable: ! lockContent,
        highlightable: ! lockContent,
        layerable: ! lockContent,
        editable: false,
        stylable: ! lockContent,
        badgable: ! lockContent,
    }, { silent: true });

    component.components().forEach((child) => {
        enableChromeLayoutStylingTree(child);
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

    if (layoutMode) {
        enableChromeLayoutStylingTree(component);
        component.set({
            selectable: true,
            highlightable: true,
            hoverable: true,
            layerable: true,
            stylable: true,
            badgable: true,
        }, { silent: true });

        return true;
    }

    if (shellMode) {
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
