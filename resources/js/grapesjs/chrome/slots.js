/**
 * Content slot resolution — layout, page, any.
 */

import { ATTR } from '../../core/attrs.js';
import {
    findChromeContentSlotComponents,
    findPageContentSlotInEditor,
    isChromeLayoutContentSlotComponent,
    isPageContentSlotComponent,
} from '../../chrome-content-slot-utils.js';

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isLayoutSlot(component) {
    return isChromeLayoutContentSlotComponent(component);
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isPageSlot(component) {
    return isPageContentSlotComponent(component);
}

/**
 * @param {object} editor
 * @param {'layout'|'page'|'any'} [mode='any']
 * @returns {object|null}
 */
export function findSlot(editor, mode = 'any') {
    if (mode === 'page') {
        return findPageContentSlotInEditor(editor);
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const slots = findChromeContentSlotComponents(
        wrapper,
        `[${ATTR.pageContent}], [${ATTR.contentSlot}]`,
    );

    if (mode === 'layout') {
        return slots.find((slot) => isLayoutSlot(slot)) ?? null;
    }

    return slots.find((slot) => isPageSlot(slot))
        ?? slots.find((slot) => isLayoutSlot(slot))
        ?? slots[0]
        ?? null;
}

/** @deprecated */
export const findContentSlot = (editor) => findSlot(editor, 'layout');

/** @deprecated */
export const findPageContentSlot = (editor) => findSlot(editor, 'page');

/**
 * Whether component is chrome bleed (nav/footer/button chrome in content slot).
 *
 * @param {object|null|undefined} component
 * @param {function} [looksLikeChrome]
 * @returns {boolean}
 */
export function isChromeBleed(component, looksLikeChrome) {
    if (! component) {
        return false;
    }

    if (typeof looksLikeChrome === 'function' && looksLikeChrome(component)) {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const blockId = String(attrs[ATTR.block] ?? '');
    const type = String(component.get?.('type') ?? '');
    const gjsType = String(attrs['data-gjs-type'] ?? '');

    if (
        blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header'
        || attrs['data-mobile-nav']
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs[ATTR.shellPart]
        || attrs[ATTR.shellLocked]
        || type === 'voodbuilder-chrome-button'
        || gjsType === 'voodbuilder-chrome-button'
        || type === 'voodbuilder-dynamic'
    ) {
        return true;
    }

    if (tag === 'button') {
        return true;
    }

    if (! component?.get) {
        return false;
    }

    const text = String(component.get('content') ?? component.get('text') ?? '').trim();
    const normalized = text.replace(/\s+/g, '');

    if (
        normalized === 'Button'
        || normalized === 'Notifications'
        || /^(?:Button|Notifications)+$/.test(normalized)
    ) {
        return true;
    }

    return false;
}
