/**
 * Pure component-tree helpers — no Editor imports, no side effects.
 */

import { ATTR } from './attrs.js';

/**
 * @param {object|null|undefined} component
 * @returns {string}
 */
export function readBlockId(component) {
    if (! component) {
        return '';
    }

    const attrs = component.getAttributes?.() ?? {};
    const fromAttrs = attrs[ATTR.block]
        ?? attrs['data-voodbuilder-block']
        ?? attrs['data-voodbuilder-section-block']
        ?? '';

    if (String(fromAttrs).trim() !== '') {
        return String(fromAttrs).trim();
    }

    try {
        const fromGet = component.get?.(ATTR.block) ?? component.get?.('attributes')?.[ATTR.block];

        if (fromGet != null && String(fromGet).trim() !== '') {
            return String(fromGet).trim();
        }
    } catch {
        // Ignore model get failures.
    }

    try {
        const fromEl = component.getEl?.()?.getAttribute?.(ATTR.block)
            ?? component.getEl?.()?.getAttribute?.('data-voodbuilder-section-block');

        if (fromEl != null && String(fromEl).trim() !== '') {
            return String(fromEl).trim();
        }
    } catch {
        // Canvas frame may not be ready yet.
    }

    const type = String(component.get?.('type') ?? '');

    if (type === 'vb-bg-image' || type === 'vb-bg-video') {
        return type;
    }

    return '';
}

/**
 * Climb to nearest block root.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findBlockRoot(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (readBlockId(current) !== '') {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} ancestor
 * @returns {boolean}
 */
export function isDescendantOf(component, ancestor) {
    if (! component || ! ancestor) {
        return false;
    }

    let current = component;

    while (current) {
        if (current === ancestor) {
            return true;
        }

        if (current.get?.('type') === 'wrapper') {
            break;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * Walk model tree (no DOM).
 *
 * @param {object|null|undefined} container
 * @returns {object|null}
 */
export function findPrimaryBlock(container) {
    if (! container?.components) {
        return null;
    }

    const children = container.components?.()?.models ?? [...(container.components?.() ?? [])];

    for (const child of children) {
        if (readBlockId(child) !== '') {
            return child;
        }

        const nested = findPrimaryBlock(child);

        if (nested) {
            return nested;
        }
    }

    return null;
}

/** @deprecated Use findPrimaryBlock */
export const findPrimaryBlockInModelTree = findPrimaryBlock;

/**
 * Model tree first, optional DOM fallback via safeFindComponents.
 *
 * @param {object|null|undefined} container
 * @param {function} [domFallback]
 * @returns {object|null}
 */
export function findPrimaryBlockInContainer(container, domFallback) {
    if (! container) {
        return null;
    }

    const modelMatch = findPrimaryBlock(container);

    if (modelMatch) {
        return modelMatch;
    }

    if (typeof domFallback === 'function') {
        return domFallback(container);
    }

    return null;
}

/**
 * Primary block inside a chrome drop zone.
 *
 * @param {object|null|undefined} zone
 * @param {function} [domFallback]
 * @returns {object|null}
 */
export function findPrimaryBlockInChromeDropZone(zone, domFallback) {
    if (! zone) {
        return null;
    }

    return findPrimaryBlockInContainer(zone, domFallback);
}
