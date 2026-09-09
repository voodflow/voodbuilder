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
    const editableText = isFooterEditableTextNode(attributes);
    const lockContent = (protectedSlot || chromeIcon) && ! editableText;

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: ! lockContent,
        hoverable: ! lockContent,
        highlightable: ! lockContent,
        layerable: ! lockContent,
        editable: editableText,
        stylable: ! lockContent || editableText,
        badgable: ! lockContent || editableText,
    }, { silent: true });

    component.components().forEach((child) => {
        enableChromeLayoutStylingTree(child);
    });
}

/**
 * Tagline / copyright are plain text the author can edit in the layout canvas.
 *
 * @param {Record<string, unknown>} attributes
 * @returns {boolean}
 */
function isFooterEditableTextNode(attributes) {
    if (! attributes || typeof attributes !== 'object') {
        return false;
    }

    if (attributes['data-voodbuilder-footer-tagline'] != null
        || attributes['data-voodbuilder-footer-copyright'] != null) {
        return true;
    }

    const chrome = String(attributes['data-voodbuilder-chrome'] ?? '');

    return chrome === 'tagline'
        || chrome === 'footer-tagline'
        || chrome === 'copyright';
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
        highlightable: layoutMode,
        hoverable: layoutMode,
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
        // Page editor: chrome is read-only preview — no hover outline (layout edits only).
        suppressChromeBlockDescendants(component);
        component.set({
            selectable: true,
            hoverable: false,
            highlightable: false,
            editable: false,
            stylable: false,
            badgable: false,
        }, { silent: true });

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
