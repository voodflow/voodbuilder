/**
 * Theme scope policy for Style panel authoring.
 *
 * Only background paints may carry a `dark:` variant:
 *   - background color + its Color opacity
 *   - background image (size / position / repeat)
 *   - linear / radial / conic background gradients (direction, stops, positions, opacity)
 *
 * They are the only properties an author legitimately wants to differ per theme.
 * Every other Style group (typography, spacing, borders, shadows, layout, text
 * color…) is authored once and must apply to both themes. The Dark toggle used to
 * prefix all of them (`currentStyleVariantPrefix`), so `lg:my-20` became
 * `dark:lg:my-20`: the canvas forces `.dark` from the editor theme and looked
 * right, but the published page starts light and never applied the utility.
 */

import { debugSwallowed } from './debug-swallowed.js';
import {
    STYLE_UTILITY_GROUPS,
    classSetFromOptions,
    componentClassList,
} from './style-tailwind-class-groups.js';
import {
    currentStyleBreakpointPrefix,
    currentStyleVariantPrefix,
    stripVariantPrefixes,
} from './style-tailwind-breakpoints.js';

/** Style groups allowed to be scoped to the Light/Dark theme (background paints). */
export const THEME_SCOPED_STYLE_GROUP_IDS = Object.freeze([
    'background',
    'background-opacity',
    'gradient-direction',
    'gradient-opacity',
    'gradient-from',
    'gradient-via',
    'gradient-to',
    'gradient-from-pos',
    'gradient-via-pos',
    'gradient-to-pos',
    'bg-size',
    'bg-position',
    'bg-repeat',
]);

/**
 * @param {string|null|undefined} groupId
 * @returns {boolean}
 */
export function isThemeScopedStyleGroupId(groupId) {
    return THEME_SCOPED_STYLE_GROUP_IDS.includes(String(groupId ?? '').trim());
}

/**
 * Variant prefix a Style group write/read must use.
 *
 * Background paints keep the full theme + breakpoint prefix; everything else is
 * breakpoint-only so the Dark toggle cannot make a value dark-only.
 *
 * @param {object|null|undefined} editor
 * @param {string|null|undefined} groupId
 * @returns {string} e.g. '', 'lg:', 'dark:', 'dark:md:'
 */
export function styleGroupVariantPrefix(editor, groupId) {
    return isThemeScopedStyleGroupId(groupId)
        ? currentStyleVariantPrefix(editor)
        : currentStyleBreakpointPrefix(editor);
}

/** Bare utility names authored by theme-scoped (background paint) groups. */
const THEME_SCOPED_UTILITY_NAMES = new Set(
    STYLE_UTILITY_GROUPS
        .filter((group) => isThemeScopedStyleGroupId(group.id))
        .flatMap((group) => [...classSetFromOptions(group.options)]),
);

/**
 * Bare utility names authored by non-theme groups, minus the ones shared with a
 * theme-scoped group.
 *
 * Shared names (e.g. `from-purple-500`, used by background *and* text gradients,
 * `bg-none` by both gradient directions) stay out of the heal set: without the
 * surrounding context we cannot know whether the author meant a background paint.
 *
 * @type {Set<string>}
 */
const HEALABLE_NEUTRAL_UTILITY_NAMES = new Set(
    STYLE_UTILITY_GROUPS
        .filter((group) => ! isThemeScopedStyleGroupId(group.id))
        .flatMap((group) => [...classSetFromOptions(group.options)])
        .filter((name) => ! THEME_SCOPED_UTILITY_NAMES.has(name)),
);

/**
 * @param {string|null|undefined} name bare utility (no variant prefix)
 * @returns {boolean}
 */
export function isHealableNeutralStyleUtility(name) {
    return HEALABLE_NEUTRAL_UTILITY_NAMES.has(String(name ?? '').trim());
}

/**
 * Drop theme prefixes from non-background utilities.
 *
 * `dark:lg:my-20` → `lg:my-20`, `dark:text-lg` → `text-lg`.
 * Background paints and unknown/hand-written classes are returned untouched.
 *
 * @param {Iterable<string>|string[]} classes
 * @returns {{ classes: string[], removed: string[] }}
 */
export function stripNonThemeScopedThemePrefixes(classes) {
    const current = [];
    const seen = new Set();

    for (const item of classes ?? []) {
        const name = String(item ?? '').trim();

        if (name === '' || seen.has(name)) {
            continue;
        }

        seen.add(name);
        current.push(name);
    }

    const removed = [];
    const next = [];

    for (const name of current) {
        if (! name.startsWith('dark:')) {
            next.push(name);

            continue;
        }

        if (! isHealableNeutralStyleUtility(stripVariantPrefixes(name))) {
            next.push(name);

            continue;
        }

        removed.push(name);

        // Keep the breakpoint part: dark:lg:my-20 → lg:my-20.
        const neutral = name.slice('dark:'.length);

        if (neutral !== '' && ! seen.has(neutral)) {
            seen.add(neutral);
            next.push(neutral);
        }
    }

    return { classes: next, removed };
}

/**
 * Heal one component in place (same class path as the CLASSES panel).
 *
 * @param {object|null|undefined} component
 * @returns {number} healed class count
 */
export function healNonThemeScopedThemeClasses(component) {
    if (! component) {
        return 0;
    }

    const before = componentClassList(component);
    const { classes, removed } = stripNonThemeScopedThemePrefixes(before);

    if (removed.length === 0) {
        return 0;
    }

    for (const name of removed) {
        component.removeClass?.(name);
    }

    for (const name of classes) {
        if (! before.includes(name)) {
            component.addClass?.(name);
        }
    }

    try {
        component.view?.updateClasses?.();
    } catch (error) {
        // View may be unavailable during export.
        debugSwallowed(error);
    }

    return removed.length;
}

/**
 * Save-time healing pass: strips dark: from non-background utilities that an
 * earlier policy authored, so a page with `dark:lg:my-20` self-corrects on the
 * first Save.
 *
 * @param {object|null|undefined} editor
 * @param {{ root?: object }} [options]
 * @returns {number} healed class count
 */
export function healNonThemeScopedThemeClassesForExport(editor, options = {}) {
    const root = options.root ?? editor?.getWrapper?.();

    if (! root?.onAll) {
        return 0;
    }

    const wasApplying = Boolean(editor?.__voodbuilderTwStyleApplying);

    if (editor) {
        editor.__voodbuilderTwStyleApplying = true;
    }

    let healed = 0;

    try {
        root.onAll((component) => {
            healed += healNonThemeScopedThemeClasses(component);
        });
    } finally {
        if (editor && ! wasApplying) {
            editor.__voodbuilderTwStyleApplying = false;
        }
    }

    return healed;
}
