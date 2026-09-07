/**
 * Style Manager spacing sync: when inline padding/margin is set, drop conflicting
 * Tailwind box-spacing utilities (p-*, m-*, px-*, …).
 *
 * Never strip gap-* — that is flex/grid track spacing (e.g. Layout Container gap-4).
 * Content-width toolbar sets margin-left/right and must not wipe author padding (p-6).
 */

/** Section presets that also conflict with Style Manager padding. */
export const SECTION_PADDING_CLASSES = ['py-0', 'py-8', 'py-12', 'py-16', 'py-20', 'py-24'];

/**
 * Margin/padding utilities only (responsive prefixes included).
 * Intentionally excludes gap / gap-x / gap-y.
 */
export const TAILWIND_BOX_SPACING_CLASS = /^(?:sm:|md:|lg:|xl:|2xl:)?(?:[pm][xytblr]?)-/;

const VARIANT_PREFIX = /^(?:sm:|md:|lg:|xl:|2xl:)/;

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
 * Map a CSS spacing property to the Tailwind prefixes that conflict with it.
 *
 * @param {string} property
 * @returns {string[]|null} null = unknown / not spacing
 */
export function boxSpacingPrefixesForProperty(property) {
    const name = String(property ?? '').trim().toLowerCase();

    switch (name) {
        case 'padding':
            return ['p', 'px', 'py', 'pt', 'pr', 'pb', 'pl'];
        case 'padding-top':
            return ['p', 'py', 'pt'];
        case 'padding-right':
            return ['p', 'px', 'pr'];
        case 'padding-bottom':
            return ['p', 'py', 'pb'];
        case 'padding-left':
            return ['p', 'px', 'pl'];
        case 'margin':
            return ['m', 'mx', 'my', 'mt', 'mr', 'mb', 'ml'];
        case 'margin-top':
            return ['m', 'my', 'mt'];
        case 'margin-right':
            return ['m', 'mx', 'mr'];
        case 'margin-bottom':
            return ['m', 'my', 'mb'];
        case 'margin-left':
            return ['m', 'mx', 'ml'];
        default:
            return null;
    }
}

/**
 * @param {string} className
 * @returns {string} bare prefix (p, px, mt, …) without responsive variant
 */
export function boxSpacingClassPrefix(className) {
    const token = String(className ?? '').trim().replace(VARIANT_PREFIX, '');
    const match = token.match(/^([pm][xytblr]?)-/);

    return match ? match[1] : '';
}

/**
 * True when a utility conflicts with any of the given CSS properties.
 *
 * @param {string} className
 * @param {Iterable<string>|string[]} properties
 * @returns {boolean}
 */
export function boxSpacingClassConflictsWithProperties(className, properties) {
    const token = String(className ?? '').trim();

    if (token === '' || ! isTailwindBoxSpacingClass(token)) {
        return false;
    }

    const prefixes = new Set();

    for (const property of properties ?? []) {
        const forProp = boxSpacingPrefixesForProperty(property);

        if (! forProp) {
            continue;
        }

        for (const prefix of forProp) {
            prefixes.add(prefix);
        }
    }

    if (prefixes.size === 0) {
        return false;
    }

    if (SECTION_PADDING_CLASSES.includes(token) || SECTION_PADDING_CLASSES.includes(token.replace(VARIANT_PREFIX, ''))) {
        return prefixes.has('py') || prefixes.has('p') || prefixes.has('pt') || prefixes.has('pb');
    }

    const classPrefix = boxSpacingClassPrefix(token);

    return classPrefix !== '' && prefixes.has(classPrefix);
}

/**
 * Drop box-spacing utilities that conflict with the given style updates.
 *
 * When `changedProperties` is omitted/empty, keeps legacy behaviour (strip all
 * box-spacing). Prefer passing the styleUpdate property list so content-width
 * margin-left/right never wipe author `p-6`.
 *
 * @param {Iterable<string>|string[]} classes
 * @param {Iterable<string>|string[]|null|undefined} [changedProperties]
 * @returns {string[]}
 */
export function filterOutConflictingBoxSpacingClasses(classes, changedProperties = null) {
    const props = changedProperties == null
        ? null
        : [...changedProperties].map((name) => String(name ?? '').trim()).filter(Boolean);

    const scoped = Array.isArray(props) && props.length > 0
        && props.some((property) => boxSpacingPrefixesForProperty(property) != null);

    return [...(classes ?? [])]
        .map((name) => String(name ?? '').trim())
        .filter((name) => {
            if (name === '') {
                return false;
            }

            if (! isTailwindBoxSpacingClass(name)) {
                return true;
            }

            if (! scoped) {
                return false;
            }

            return ! boxSpacingClassConflictsWithProperties(name, props);
        });
}
