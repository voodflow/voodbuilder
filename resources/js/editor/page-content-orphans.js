/**
 * Orphan text / inline fragments at the page-content root (outside any section).
 * They serialize as bare text in builder_payload, render on canvas, but are not layerable.
 *
 * Foundation Utilities / Basic / Media dropped on the page root are intentional and must
 * survive `purgeOrphanPageContentNodes` (runs on block:drag:stop in chrome-shell).
 */

import { COMPONENT_ATTR, COMPONENT_TYPE } from './component-instance-type.js';

const TOP_DROP_SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const BOTTOM_DROP_SPACER_ATTR = 'data-voodbuilder-bottom-drop-spacer';
const INNER_DROP_SLOT_ATTR = 'data-voodbuilder-inner-drop';

/** Markers used by Basic / Media / Utilities / Animated blocks. */
const FOUNDATION_ROOT_ATTRS = [
    'data-voodbuilder-reading-time',
    'data-voodbuilder-progress',
    'data-voodbuilder-social-share',
    'data-voodbuilder-text',
    'data-voodbuilder-rich-text',
    'data-voodbuilder-icon',
    'data-voodbuilder-divider',
    'data-voodbuilder-cta',
    'data-voodbuilder-image-gallery',
    'data-voodbuilder-audio',
    'data-voodbuilder-carousel',
    'data-voodbuilder-slider',
    'data-voodbuilder-animated-cta',
    'data-voodbuilder-animated-counter',
    'data-voodbuilder-animated-stats',
    'data-voodbuilder-logo-scroll',
    'data-voodbuilder-section-block',
    'data-voodbuilder-block',
    'data-voodbuilder-layout',
];

const FOUNDATION_ROOT_TYPES = new Set([
    'voodbuilder-reading-time',
    'voodbuilder-reading-progress',
    'voodbuilder-social-share',
    'voodbuilder-text',
    'voodbuilder-rich-text',
    'voodbuilder-icon',
    'voodbuilder-text-link',
    'voodbuilder-image-gallery',
    'voodbuilder-carousel',
    'voodbuilder-slider',
    'voodbuilder-animated-cta',
    'voodbuilder-animated-counter',
    'voodbuilder-animated-stats',
    'voodbuilder-logo-scroll',
    'voodbuilder-section',
    'voodbuilder-layout-container',
    'voodbuilder-layout-block',
    'voodbuilder-container',
    'voodbuilder-dynamic',
    'image',
    'video',
    'link',
]);

/** Semantic root tags authors drop from Basic (Grapes `text` type on headings/paragraphs). */
const FOUNDATION_ROOT_TAGS = new Set([
    'section',
    'header',
    'footer',
    'main',
    'article',
    'aside',
    'nav',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'p',
    'hr',
    'img',
    'figure',
    'picture',
    'video',
    'audio',
    'ul',
    'ol',
    'blockquote',
    'table',
    'form',
    'button',
]);

function componentAttrs(component) {
    return component?.getAttributes?.() ?? {};
}

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentType(component) {
    return String(component?.get?.('type') ?? '');
}

function hasFoundationRootAttr(attrs) {
    return FOUNDATION_ROOT_ATTRS.some((name) => Object.prototype.hasOwnProperty.call(attrs, name));
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
 * Page-content slot may hold sections, layout/dynamic blocks, foundation utilities,
 * Basic/Media elements, saved components, and editor sentinels — not bare textnodes.
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

    // Bare Grapes text nodes (orphan copy after a bad delete) — never keep at root.
    if (type === 'textnode') {
        return false;
    }

    if (attrs[COMPONENT_ATTR] || type === COMPONENT_TYPE) {
        return true;
    }

    if (FOUNDATION_ROOT_TYPES.has(type)) {
        return true;
    }

    if (hasFoundationRootAttr(attrs)) {
        return true;
    }

    if (FOUNDATION_ROOT_TAGS.has(tag)) {
        return true;
    }

    // Grapes `text` on a semantic tag (heading / paragraph) is a real Basic drop.
    if (type === 'text' && FOUNDATION_ROOT_TAGS.has(tag)) {
        return true;
    }

    // Social share / CTA-style anchors & buttons with utility classes.
    if (
        (tag === 'div' || tag === 'a' || tag === 'span')
        && (
            String(attrs.class ?? '').includes('vb-social-share')
            || String(attrs.class ?? '').includes('vb-reading-progress')
            || String(attrs.class ?? '').includes('vb-reading-time')
            || String(attrs.class ?? '').includes('vp-social-links')
        )
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
