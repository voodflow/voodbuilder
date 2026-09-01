/**
 * Orphan text / inline fragments at the page-content root (outside any section).
 * They serialize as bare text in builder_payload, render on canvas, but are not layerable.
 */

import { COMPONENT_ATTR, COMPONENT_TYPE } from './component-instance-type.js';

const TOP_DROP_SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const BOTTOM_DROP_SPACER_ATTR = 'data-voodbuilder-bottom-drop-spacer';
const INNER_DROP_SLOT_ATTR = 'data-voodbuilder-inner-drop';

function componentAttrs(component) {
    return component?.getAttributes?.() ?? {};
}

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentType(component) {
    return String(component?.get?.('type') ?? '');
}

export function isEditorDropSentinelComponent(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = componentAttrs(component);
    const type = componentType(component);

    return Boolean(
        attrs[TOP_DROP_SPACER_ATTR]
        || attrs[BOTTOM_DROP_SPACER_ATTR]
        || attrs[INNER_DROP_SLOT_ATTR]
        || type === 'voodbuilder-top-drop-spacer'
        || type === 'voodbuilder-bottom-drop-spacer'
        || type === 'voodbuilder-inner-drop-slot',
    );
}

/**
 * Page-content slot should only hold sections, layout blocks, dynamic blocks, and editor sentinels.
 *
 * @param {import('grapesjs').Component} component
 * @returns {boolean}
 */
export function isAllowedPageContentRoot(component) {
    if (! component?.get) {
        return false;
    }

    if (isEditorDropSentinelComponent(component)) {
        return true;
    }

    const attrs = componentAttrs(component);
    const tag = componentTag(component);
    const type = componentType(component);

    if (type === 'textnode' || type === 'text') {
        return false;
    }

    if (tag === 'section' || attrs['data-voodbuilder-section-block']) {
        return true;
    }

    if (attrs['data-voodbuilder-block']) {
        return true;
    }

    if (attrs[COMPONENT_ATTR] || type === COMPONENT_TYPE) {
        return true;
    }

    if (attrs['data-voodbuilder-layout']) {
        return true;
    }

    if (
        type === 'voodbuilder-section'
        || type === 'voodbuilder-layout-container'
        || type === 'voodbuilder-layout-block'
        || type === 'voodbuilder-container'
        || type === 'voodbuilder-dynamic'
    ) {
        return true;
    }

    return false;
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {boolean}
 */
export function isOrphanPageContentNode(component) {
    return ! isAllowedPageContentRoot(component);
}

/**
 * @param {import('grapesjs').Component | null | undefined} slot
 */
export function purgeOrphanPageContentNodes(slot) {
    if (! slot?.components) {
        return;
    }

    const removable = [];

    slot.components().forEach((child) => {
        if (isOrphanPageContentNode(child)) {
            removable.push(child);
        }
    });

    removable.forEach((component) => {
        try {
            component.remove();
        } catch {
            // Already detached.
        }
    });
}

/**
 * Saved HTML with no markup is orphan text (e.g. "→ Explore products" after a bad delete).
 *
 * @param {string|null|undefined} html
 * @returns {boolean}
 */
export function isOrphanPageContentHtml(html) {
    const trimmed = String(html ?? '').trim();

    if (trimmed === '') {
        return false;
    }

    return ! /<[a-z][\s\S]*>/i.test(trimmed);
}

/**
 * @param {string|null|undefined} html
 * @returns {string}
 */
export function stripOrphanPageContentHtml(html) {
    return isOrphanPageContentHtml(html) ? '' : String(html ?? '');
}
