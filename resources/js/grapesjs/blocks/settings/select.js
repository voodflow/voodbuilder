/**
 * Inspector selection resolution for block settings.
 * Layout chrome resolution uses the component model tree only (no DOM).
 */

import { ATTR } from '../../core/attrs.js';
import {
    readBlockId,
    findBlockRoot,
    findPrimaryBlock,
    findPrimaryBlockInContainer,
    findPrimaryBlockInChromeDropZone,
} from '../../core/block-tree.js';
import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
} from '../../chrome-content-slot-utils.js';

export {
    readBlockId,
    findBlockRoot,
    findPrimaryBlock,
    findPrimaryBlockInContainer,
    findPrimaryBlockInModelTree,
} from '../../core/block-tree.js';

export { BLOCK_ID_ATTR, ATTR } from '../../core/attrs.js';

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function isInsideChromeDropZone(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (current.getAttributes?.()?.[ATTR.dropZone]) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * Resolve nav/footer block root when selection is inside a layout chrome drop zone.
 * Also accepts the block root itself (with or without a drop-zone ancestor) so
 * settings keep working after load / dynamic refresh before zones are reconciled.
 *
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function findLayoutChromeZoneBlockRoot(component, editor) {
    if (! component || ! isChromeLayoutModeEditor(editor)) {
        return null;
    }

    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const zone = current.getAttributes?.()?.[ATTR.dropZone];

        if (zone === 'nav' || zone === 'footer') {
            return findPrimaryBlockInChromeDropZone(current);
        }

        const blockId = readBlockId(current);

        if (blockId !== '') {
            if (isInsideChromeDropZone(current)) {
                return current;
            }

            // Top-level / pre-wrap load: block root still owns settings.
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isBlockRoot(component) {
    return readBlockId(component) !== '';
}

/**
 * Restore layer/selection flags on a block root for canvas highlight only.
 * Settings rendering does not require these flags.
 *
 * @param {object} root
 */
export function ensureRootInspectable(root) {
    if (! root || ! isBlockRoot(root)) {
        return;
    }

    root.set({
        selectable: true,
        hoverable: true,
        highlightable: true,
        layerable: true,
    }, { silent: true });
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function findInspectableRoot(component, editor) {
    if (! component) {
        return null;
    }

    if (
        isChromeLayoutModeEditor(editor)
        && isChromeLayoutContentSlotComponent(component)
    ) {
        return null;
    }

    const ownBlockId = readBlockId(component);

    if (ownBlockId !== '') {
        return component;
    }

    const zone = component.getAttributes?.()?.[ATTR.dropZone];

    if (zone === 'nav' || zone === 'footer') {
        return findPrimaryBlockInChromeDropZone(component) ?? findBlockRoot(component);
    }

    const nestedInSelection = findPrimaryBlockInContainer(component);

    if (nestedInSelection) {
        return nestedInSelection;
    }

    const blockRoot = findBlockRoot(component);

    if (blockRoot) {
        return blockRoot;
    }

    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        const parentZone = current.getAttributes?.()?.[ATTR.dropZone];

        if (parentZone === 'nav' || parentZone === 'footer') {
            return findPrimaryBlockInChromeDropZone(current);
        }

        const nested = findPrimaryBlockInContainer(current);

        if (nested) {
            return nested;
        }

        current = current.parent?.();
    }

    return findLayoutChromeZoneBlockRoot(component, editor);
}

/** @deprecated */
export const resolveInspectableBlockRoot = findInspectableRoot;

/**
 * Block roots stay the inspector settings target even when layer filters mark them
 * non-selectable (e.g. chrome shell children hidden by layers-chrome-filter).
 *
 * @param {object|null|undefined} raw
 * @param {object|null|undefined} root
 * @param {object|null|undefined} [editor]
 * @returns {boolean}
 */
export function shouldPromoteSelectionToRoot(raw, root, editor = null) {
    if (! raw || ! root || raw === root || root.isRemoved?.()) {
        return false;
    }

    // Layout editor: keep the clicked chrome child selected so Style/Classes can
    // target inner containers (e.g. py-* on the footer inner wrapper). Content
    // settings still resolve the block root via findInspectableRoot.
    if (editor?.__voodbuilderChromeLayoutMode) {
        const activeTab = editor.__voodbuilderInspectorActiveTab ?? 'content';

        if (activeTab === 'style' || activeTab === 'selectors' || activeTab === 'layers') {
            return false;
        }

        // Even on Content, allow selecting stylable chrome children without steal.
        const rawTag = String(raw.get?.('tagName') ?? '').toLowerCase();
        const rawClasses = [...(raw.getClasses?.() ?? [])].map((name) => String(name ?? ''));

        if (
            rawTag === 'div'
            || rawTag === 'section'
            || rawTag === 'nav'
            || rawTag === 'header'
            || rawTag === 'footer'
            || rawTag === 'a'
            || rawTag === 'p'
            || rawTag === 'span'
            || rawClasses.some((name) => /(?:^|:)(?:py|px|pt|pb|p|bg|text|border)-/.test(name))
        ) {
            return false;
        }
    }

    // Keep smart CTA buttons selectable — they own Content traits (URL / page / menu).
    const rawType = String(raw.get?.('type') ?? '');

    if (
        rawType === 'voodbuilder-cta-button'
        || rawType === 'voodbuilder-animated-counter'
        || rawType === 'voodbuilder-container'
        || rawType === 'voodbuilder-section'
        || rawType === 'voodbuilder-layout-block'
        || rawType === 'voodbuilder-layout-div'
        || rawType === 'voodbuilder-icon'
        || rawType === 'voodbuilder-text-link'
        || rawType === 'voodbuilder-text'
        || rawType === 'voodbuilder-rich-text'
    ) {
        return false;
    }

    const rawAttrs = raw.getAttributes?.() ?? {};

    if (
        rawAttrs['data-voodbuilder-layout'] === 'section'
        || rawAttrs['data-voodbuilder-layout'] === 'container'
        || rawAttrs['data-voodbuilder-layout'] === 'block'
        || rawAttrs['data-voodbuilder-layout'] === 'div'
    ) {
        return false;
    }
    if (
        rawAttrs['data-voodbuilder-animated-counter'] != null
        || rawAttrs['data-vb-count-to'] != null
        || (raw.getClasses?.() ?? []).includes('vb-animated-counter')
    ) {
        return false;
    }

    // Animated utilities (spin/plasma/etc.): keep the exact node so Style → Animation works.
    const rawClasses = [...(raw.getClasses?.() ?? [])].map((name) => String(name ?? ''));

    if (rawClasses.some((name) => /(?:^|:)animate-[\w-]+/.test(name))) {
        return false;
    }

    // Layer tree pick: never steal selection to the block root.
    if (
        editor?.__voodbuilderLayersSelectionPin
        && Date.now() < Number(editor.__voodbuilderLayersSelectionPinUntil ?? 0)
    ) {
        return false;
    }

    // Keep inline RTE targets selectable — promote-to-block-root stole the
    // newly created <a>/<span> so Link had nowhere to edit the URL and Wrap
    // for styles could not receive Style Manager classes.
    const rawTag = String(raw.get?.('tagName') ?? '').toLowerCase();
    const inlineTags = new Set([
        'a', 'span', 'strong', 'em', 'b', 'i', 'u', 's', 'mark', 'code', 'small', 'sub', 'sup',
    ]);

    if (inlineTags.has(rawTag) || rawType === 'link' || rawType === 'textnode' || rawType === 'text') {
        return false;
    }

    if (
        (rawTag === 'a' && rawAttrs['data-voodbuilder-cta'] === 'true')
        || (rawTag === 'button' && rawAttrs['data-voodbuilder-cta'] === 'true')
    ) {
        return false;
    }

    const rawId = readBlockId(raw);
    const rootId = readBlockId(root);

    if (rootId === '') {
        return false;
    }

    return rawId === '' || rawId !== rootId;
}
