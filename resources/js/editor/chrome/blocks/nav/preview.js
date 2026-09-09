/**
 * Nav block preview locking for layout vs page shell editors.
 */

import { ATTR } from '../../../core/attrs.js';
import { isHeaderBlock, isNavBlock, resolveNavId } from '../../ids.js';

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

function isNavInteractiveComponent(component) {
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};

    if (tag === 'a' || tag === 'p') {
        return true;
    }

    if (tag === 'span' && ! attrs['data-voodbuilder-nav-mobile-chevron']) {
        return true;
    }

    if (tag === 'button' && (attrs['data-voodbuilder-nav-dropdown-toggle'] || attrs['data-voodbuilder-nav-mobile-toggle'])) {
        return true;
    }

    if (tag === 'svg') {
        return true;
    }

    return false;
}

function lockNavPreviewTree(component) {
    const interactive = isNavInteractiveComponent(component);
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: interactive,
        hoverable: interactive,
        layerable: interactive,
        editable: interactive && (tag === 'span' || tag === 'a' || tag === 'p'),
        stylable: interactive,
        highlightable: interactive,
    }, { silent: true });

    component.components().forEach((child) => {
        lockNavPreviewTree(child);
    });
}

/**
 * Migrate legacy nav block ids on the component model.
 *
 * @param {object} component
 */
export function migrateNavId(component) {
    const blockId = component.getAttributes()[ATTR.block];

    if (! isHeaderBlock(blockId)) {
        return;
    }

    const resolvedId = resolveNavId(blockId);

    if (resolvedId !== blockId) {
        component.addAttributes({ [ATTR.block]: resolvedId });
    }
}

/**
 * @param {object} component
 * @param {object} [editor]
 * @param {{ normalizeMenuButtons?: Function, normalizeChromeButtons?: Function, resolveBlockLayerLabel?: Function }} [opts]
 */
export function lockNavPreview(component, editor, opts = {}) {
    const blockId = component.getAttributes()[ATTR.block];
    const layoutMode = Boolean(component.em?.__voodbuilderChromeLayoutMode ?? editor?.__voodbuilderChromeLayoutMode);
    const shellMode = Boolean(component.em?.__voodbuilderChromeShellMode ?? editor?.__voodbuilderChromeShellMode);

    if (! isNavBlock(blockId) && ! isHeaderBlock(blockId)) {
        return false;
    }

    const { resolveBlockLayerLabel } = opts;

    component.set({
        selectable: true,
        highlightable: layoutMode,
        hoverable: layoutMode,
        layerable: true,
        name: typeof resolveBlockLayerLabel === 'function'
            ? resolveBlockLayerLabel(blockId)
            : component.get('name'),
    }, { silent: true });
    migrateNavId(component);

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
        opts.normalizeMenuButtons?.(component);
        opts.normalizeChromeButtons?.(component);

        return true;
    }

    if (shellMode) {
        // Page editor: match footer — no hover outlines on chrome preview.
        suppressChromeBlockDescendants(component);
        component.set({
            selectable: true,
            hoverable: false,
            highlightable: false,
            editable: false,
            stylable: false,
            badgable: false,
        }, { silent: true });
        opts.normalizeMenuButtons?.(component);
        opts.normalizeChromeButtons?.(component);

        return true;
    }

    component.components().forEach((child) => {
        lockNavPreviewTree(child);
    });
    opts.normalizeMenuButtons?.(component);
    opts.normalizeChromeButtons?.(component);

    return true;
}
