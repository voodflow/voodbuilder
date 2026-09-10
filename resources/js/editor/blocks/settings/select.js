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
    isDescendantOf,
} from '../../core/block-tree.js';
import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
} from '../../chrome-content-slot-utils.js';
import { isMediaHeroId } from '../../media-hero.js';

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

        if (nested && isDescendantOf(component, nested)) {
            return nested;
        }

        current = current.parent?.();
    }

    return findLayoutChromeZoneBlockRoot(component, editor);
}

/** @deprecated */
export const resolveInspectableBlockRoot = findInspectableRoot;

/**
 * Media heroes: promote only when the click lands on the
 * background media layer — never when editing copy/CTAs in the content stack.
 *
 * @param {object|null|undefined} raw
 * @param {object|null|undefined} root
 * @returns {boolean}
 */
export function isMediaHeroBackgroundHit(raw, root) {
    if (! raw || ! root || raw === root) {
        return false;
    }

    let current = raw;

    while (current && current !== root && current.get?.('type') !== 'wrapper') {
        const attrs = current.getAttributes?.() ?? {};
        const role = String(attrs['data-voodbuilder-role'] ?? '').trim();
        const classes = [...(current.getClasses?.() ?? [])].map((name) => String(name ?? ''));

        // Content stack / dropzones win — keep text, headings, buttons selectable.
        if (
            role === 'content'
            || attrs['data-voodbuilder-dropzone'] != null
            || attrs['data-voodbuilder-text'] != null
            || attrs['data-voodbuilder-rich-text'] != null
            || attrs['data-voodbuilder-cta'] === 'true'
            || attrs['data-voodbuilder-social-share'] != null
            || attrs['data-vb-share-item'] === 'true'
            || attrs['data-network'] != null
            || classes.includes('vb-rich-text')
            || classes.includes('vb-social-share')
            || classes.includes('vb-social-share__btn')
        ) {
            return false;
        }

        if (
            role === 'media'
            || role === 'shade'
            || classes.includes('voodbuilder-hero-media')
            || classes.some((name) => name.startsWith('voodbuilder-hero-media__'))
            || attrs['data-vb-embed-bg'] != null
        ) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

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

    const rawId = readBlockId(raw);
    const rootId = readBlockId(root);

    if (rootId === '') {
        return false;
    }

    // Layer tree pick: never steal selection to the block root.
    if (
        editor?.__voodbuilderLayersSelectionPin
        && Date.now() < Number(editor.__voodbuilderLayersSelectionPinUntil ?? 0)
    ) {
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

        // Content tab + plain chrome child: promote so nav/footer settings open.
        return rawId === '' || rawId !== rootId;
    }

    const rawType = String(raw.get?.('type') ?? '');

    // Media heroes: promote only background-layer hits so Background settings open,
    // while headings / copy / CTAs in the content stack stay selectable.
    if (
        isMediaHeroId(rootId)
        && rawType !== 'voodbuilder-cta-button'
        && rawType !== 'link'
        && rawType !== 'voodbuilder-social-share'
        && rawType !== 'voodbuilder-social-share-item'
        && isMediaHeroBackgroundHit(raw, root)
    ) {
        return true;
    }

    // Page / popup editor: keep the exact clicked node (img, card, text, …).
    // Block Content settings still resolve via findInspectableRoot without re-select.
    return false;
}
