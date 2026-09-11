/**
 * Layout editor drop-zone drag helpers — breaks plugin ↔ canvas-drag cycle.
 */

import { ATTR } from '../../core/attrs.js';
import { findChromeContentSlotComponents, isChromeDropZoneComponent } from '../../chrome-content-slot-utils.js';

function getLastDragPoint(editor) {
    return editor.__voodbuilderLastDragPoint ?? editor.__voodbuilderLastDropPoint ?? null;
}

function blockTargetsFooterZone(block) {
    const blockId = String(
        block?.get?.('attributes')?.[ATTR.block]
        ?? block?.get?.('id')
        ?? block?.id
        ?? '',
    ).toLowerCase();

    return blockId.includes('footer') || blockId.startsWith('site_footer');
}

function blockTargetsProgressZone(block) {
    const blockId = String(
        block?.get?.('attributes')?.[ATTR.block]
        ?? block?.get?.('id')
        ?? block?.id
        ?? '',
    ).toLowerCase();

    return blockId === 'voodbuilder-reading-progress'
        || blockId.includes('reading-progress');
}

function isLayoutContentSlot(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[ATTR.contentSlot]) && ! attrs[ATTR.pageContent];
}

function findLayoutContentSlot(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const slots = findChromeContentSlotComponents(wrapper, `[${ATTR.contentSlot}]`);

    return slots.find((slot) => isLayoutContentSlot(slot)) ?? slots[0] ?? null;
}

function findZone(editor, zone) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    return wrapper.components().find((component) => component.getAttributes?.()[ATTR.dropZone] === zone) ?? null;
}

/**
 * @param {object} editor
 * @returns {object|null}
 */
export function findDropZoneAtPointer(editor) {
    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = getLastDragPoint(editor);

    if (! doc || ! frame || ! point) {
        return null;
    }

    const rect = frame.getBoundingClientRect();
    const x = point.x - rect.left + (frame.contentWindow?.scrollX ?? 0);
    const y = point.y - rect.top + (frame.contentWindow?.scrollY ?? 0);
    const target = doc.elementFromPoint(x, y);
    const zoneEl = target?.closest?.(`[${ATTR.dropZone}]`);

    if (! zoneEl) {
        return null;
    }

    const zone = zoneEl.getAttribute(ATTR.dropZone);

    return findZone(editor, zone);
}

/**
 * @param {object} editor
 * @returns {object|null}
 */
export function findLayoutDropZoneForPointer(editor) {
    const pointerZone = findDropZoneAtPointer(editor);

    if (pointerZone) {
        return pointerZone;
    }

    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = getLastDragPoint(editor);
    const navZone = findZone(editor, 'nav');
    const progressZone = findZone(editor, 'progress');
    const slot = findLayoutContentSlot(editor);
    const footerZone = findZone(editor, 'footer');

    if (! doc || ! frame || ! point || ! slot) {
        return null;
    }

    const slotEl = slot.getEl?.() ?? doc.querySelector(`[${ATTR.contentSlot}]`);

    if (! slotEl) {
        return null;
    }

    const rect = frame.getBoundingClientRect();
    const y = point.y - rect.top + (frame.contentWindow?.scrollY ?? 0);
    const slotTop = slotEl.offsetTop;
    const slotBottom = slotTop + slotEl.offsetHeight;

    if (y < slotTop) {
        const navEl = navZone?.getEl?.();
        const progressEl = progressZone?.getEl?.();

        if (progressEl) {
            const progressTop = progressEl.offsetTop;
            const progressBottom = progressTop + Math.max(progressEl.offsetHeight, 24);

            if (y >= progressTop - 12 && y <= progressBottom + 12) {
                return progressZone;
            }
        }

        if (navEl) {
            const navBottom = navEl.offsetTop + navEl.offsetHeight;

            if (y <= navBottom + 8) {
                return navZone;
            }
        }

        // Gap between header and page content → reading-progress strip.
        return progressZone ?? navZone;
    }

    if (y > slotBottom) {
        return footerZone;
    }

    return null;
}

function resolveLayoutDropZone(editor, block) {
    if (blockTargetsProgressZone(block)) {
        return findZone(editor, 'progress')
            ?? findLayoutDropZoneForPointer(editor)
            ?? findZone(editor, 'nav');
    }

    const pointerZone = findLayoutDropZoneForPointer(editor);

    if (pointerZone) {
        const zoneName = pointerZone.getAttributes?.()?.[ATTR.dropZone];

        // Keep non-progress blocks out of the progress strip.
        if (zoneName === 'progress') {
            return findZone(editor, 'nav') ?? findZone(editor, 'footer');
        }

        return pointerZone;
    }

    const navZone = findZone(editor, 'nav');
    const footerZone = findZone(editor, 'footer');

    if (blockTargetsFooterZone(block)) {
        return footerZone ?? navZone;
    }

    return navZone ?? footerZone;
}

/**
 * @param {object} editor
 * @param {object} block
 * @returns {object|null}
 */
export function insertBlockIntoLayoutZone(editor, block) {
    const content = block?.get?.('content') ?? block?.getContent?.();

    if (! content) {
        return null;
    }

    const zone = resolveLayoutDropZone(editor, block);

    if (! zone) {
        return null;
    }

    const added = zone.append(content);
    const component = Array.isArray(added) ? added[0] : added;

    if (component) {
        editor.select?.(component);
    }

    return component ?? null;
}

/** @deprecated Use findZone */
export const findDropZone = findZone;

/** @deprecated */
export function isDropZoneComponent(component) {
    return isChromeDropZoneComponent(component);
}
