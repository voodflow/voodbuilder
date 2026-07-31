/**
 * Style Manager spacing sync: when inline padding/margin is set, drop conflicting
 * Tailwind box-spacing utilities (p-*, m-*, px-*, …).
 *
 * Never strip gap-* — that is flex/grid track spacing (e.g. Layout Container gap-4).
 * Content-width toolbar sets margin-left/right and must not wipe layout gaps.
 */

/** Section presets that also conflict with Style Manager padding. */
export const SECTION_PADDING_CLASSES = ['py-0', 'py-8', 'py-12', 'py-16', 'py-20', 'py-24'];

/**
 * Margin/padding utilities only (responsive prefixes included).
 * Intentionally excludes gap / gap-x / gap-y.
 */
export const TAILWIND_BOX_SPACING_CLASS = /^(?:sm:|md:|lg:|xl:|2xl:)?(?:[pm][xytblr]?)-/;

/**
 * @param {string} className
 * @returns {boolean}
 */
export function isTailwindBoxSpacingClass(className) {
    const token = String(className ?? '');

    return TAILWIND_BOX_SPACING_CLASS.test(token)
        || SECTION_PADDING_CLASSES.includes(token);
}

/**
 * @param {Iterable<string>|string[]} classes
 * @returns {string[]}
 */
export function filterOutConflictingBoxSpacingClasses(classes) {
    return [...(classes ?? [])]
        .map((name) => String(name ?? '').trim())
        .filter((name) => name !== '' && ! isTailwindBoxSpacingClass(name));
}
