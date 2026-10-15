/**
 * Shared Tailwind class rules for content-width shells.
 *
 * Editor mirror of `EditorBlockClasses` (PHP). Two questions come up whenever a
 * shell is captured or restored around a dynamic block:
 *
 * 1. Is this node an author content *measure*, or is it the block's own layout?
 *    Before 1.11.76 the width toolbar stamped the shell markers
 *    (`voodbuilder-editor-container`, `data-voodbuilder-role="content"`,
 *    `mx-auto w-full max-w-[80rem]`) onto the block's inner preview layout row.
 *    Capturing that node re-stamps the poison onto the freshly rendered preview at
 *    every dynamic refresh, and the server then wraps the whole block in its own
 *    flex row on publish.
 * 2. Which tokens may be carried over? Only measure tokens; the layout of the node
 *    is owned by the Blade / Livewire render.
 */

export const CONTENT_SHELL_SELECTOR = '.voodbuilder-editor-container, .container, [data-voodbuilder-role="content"]';

/** Exact utility names that only ever appear on a layout node. */
const LAYOUT_UTILITY_NAMES = new Set(['flex', 'inline-flex', 'grid', 'inline-grid', 'grow', 'shrink']);

/** Utility prefixes that only ever appear on a layout node. */
const LAYOUT_UTILITY_PREFIXES = [
    'flex-',
    'grid-',
    'justify-',
    'items-',
    'content-',
    'self-',
    'place-',
    'gap-',
    'space-x-',
    'space-y-',
    'order-',
    'col-',
    'row-',
    'basis-',
];

/** Measure tokens the shell stamp adds (and the heal removes). */
const SHELL_MEASURE_TOKENS = new Set([
    'voodbuilder-editor-container',
    'container',
    'mx-auto',
    'w-full',
    'max-w-none',
    'max-w-[80rem]',
]);

/**
 * Drop Tailwind variant segments: `sm:flex-row` → `flex-row`, `dark:lg:my-20` → `my-20`.
 *
 * Only `[a-z0-9_-]+:` segments are stripped, so an arbitrary value containing a colon
 * (`bg-[url(https://…)]`) is left alone — it never looks like a layout anyway.
 *
 * @param {string} token
 * @returns {string}
 */
export function stripClassVariants(token) {
    let bare = String(token ?? '').trim();

    while (/^[a-z0-9_-]+:/i.test(bare)) {
        bare = bare.replace(/^[a-z0-9_-]+:/i, '');
    }

    return bare;
}

/**
 * @param {string} token
 * @returns {boolean}
 */
export function isLayoutUtilityClass(token) {
    const bare = stripClassVariants(token);

    if (bare === '') {
        return false;
    }

    if (LAYOUT_UTILITY_NAMES.has(bare)) {
        return true;
    }

    return LAYOUT_UTILITY_PREFIXES.some((prefix) => bare.startsWith(prefix));
}

/**
 * @param {string[]|string|null|undefined} classList
 * @returns {boolean}
 */
export function classListHasLayoutUtilities(classList) {
    const classes = Array.isArray(classList)
        ? classList
        : String(classList ?? '').split(/\s+/).filter(Boolean);

    return classes.some((token) => isLayoutUtilityClass(token));
}

/**
 * @param {object|null|undefined} node
 * @returns {string[]}
 */
function nodeClasses(node) {
    if (typeof node?.getClasses === 'function') {
        return [...node.getClasses()];
    }

    const attrs = node?.getAttributes?.() ?? {};

    return String(attrs.class ?? '').split(/\s+/).filter(Boolean);
}

/**
 * A real content shell never hosts a layout of its own.
 *
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function isGenuineContentShellNode(node) {
    if (! node) {
        return false;
    }

    return ! classListHasLayoutUtilities(nodeClasses(node));
}

/**
 * @param {string} token
 * @returns {boolean}
 */
export function isShellMeasureToken(token) {
    return SHELL_MEASURE_TOKENS.has(token) || token.startsWith('max-w-[');
}

/**
 * Remove the shell stamps from a block layout node, keeping the layout itself
 * (flex/grid classes and children are owned by the server render).
 *
 * @param {object|null|undefined} node
 * @returns {number} removed class count (0 when the node is not a layout node)
 */
export function unstampLayoutContentShellNode(node) {
    if (! node || isGenuineContentShellNode(node)) {
        return 0;
    }

    const classes = nodeClasses(node);
    const kept = classes.filter((token) => ! isShellMeasureToken(token));

    if (kept.length !== classes.length) {
        if (typeof node.setClass === 'function') {
            node.setClass(kept);
        } else {
            node.addAttributes?.({ class: kept.join(' ') });
        }
    }

    const attrs = node.getAttributes?.() ?? {};

    if (String(attrs['data-voodbuilder-role'] ?? '').trim() === 'content') {
        node.removeAttributes?.('data-voodbuilder-role');
    }

    node.removeAttributes?.('data-voodbuilder-content-width');

    return classes.length - kept.length;
}
