/**
 * Style panel ↔ canvas device + theme: mobile-first Tailwind prefixes.
 *
 * UI labels (Mobile / Tablet / Desktop) — CSS stays mobile-first:
 *   Mobile  (mobilePortrait canvas) → no breakpoint prefix
 *   Tablet                          → md:
 *   Desktop                         → lg:
 *
 * Theme comes from the top-bar toggle (not a second Style strip). Only background
 * paints are theme-scoped (see style-theme-scope.js), so the `dark:` prefixes below
 * are used for background color/image/gradients — never for typography, spacing,
 * borders, shadows or layout:
 *   Light → base / md: / lg:
 *   Dark  → dark: / dark:md: / dark:lg:   (background paints only)
 */

import { debugSwallowed } from './debug-swallowed.js';

/** Breakpoint prefixes the Style panel can author (v1). */
export const STYLE_BREAKPOINT_PREFIXES = Object.freeze(['', 'md:', 'lg:']);

/** Strip sm:/md:/lg:/xl:/2xl: from a utility name. */
export const RESPONSIVE_VARIANT_PREFIX_RE = /^(?:sm:|md:|lg:|xl:|2xl:)/;

/** Strip optional dark: then a responsive prefix. */
export const STYLE_VARIANT_PREFIX_RE = /^(?:dark:)?(?:sm:|md:|lg:|xl:|2xl:)?/;

/**
 * @param {string|null|undefined} deviceId
 * @returns {''|'md:'|'lg:'}
 */
export function deviceIdToBreakpointPrefix(deviceId) {
    const id = String(deviceId ?? '').trim();

    if (id === 'tablet') {
        return 'md:';
    }

    if (id === 'mobilePortrait' || id === 'mobile') {
        return '';
    }

    // desktop (default shell) and unknown → lg:
    return 'lg:';
}

/**
 * @param {string|null|undefined} prefix
 * @returns {'mobilePortrait'|'tablet'|'desktop'}
 */
export function deviceIdFromBreakpointPrefix(prefix) {
    const bp = normalizeBreakpointPrefix(prefix);

    if (bp === 'md:') {
        return 'tablet';
    }

    if (bp === 'lg:') {
        return 'desktop';
    }

    return 'mobilePortrait';
}

/**
 * Whether Style edits should author `dark:` variants / dark page wallpaper.
 *
 * Prefer the live editor flag set by the top-bar theme toggle — Filament and
 * localStorage `theme` can race, which painted dark wallpapers onto light `#id`.
 *
 * @param {object|null|undefined} editor
 * @returns {boolean}
 */
export function isStyleEditingDark(editor = null) {
    if (typeof editor?.__voodbuilderStyleThemeDark === 'boolean') {
        return editor.__voodbuilderStyleThemeDark;
    }

    try {
        const stored = window.localStorage?.getItem('theme');

        if (stored === 'dark') {
            return true;
        }

        if (stored === 'light') {
            return false;
        }
    } catch (error) {
        // Ignore storage failures.
        debugSwallowed(error);
    }

    try {
        if (document.documentElement.classList.contains('dark')) {
            return true;
        }
    } catch (error) {
        // Host document may be unavailable.
        debugSwallowed(error);
    }

    try {
        const doc = editor?.Canvas?.getDocument?.();

        if (doc?.documentElement) {
            return doc.documentElement.classList.contains('dark');
        }
    } catch (error) {
        // Frame may be unavailable during boot.
        debugSwallowed(error);
    }

    return false;
}

/**
 * Which dark-scope warning the Style panel should show, if any.
 *
 * Only background paints carry a `dark:` variant, and the canvas forces `.dark`
 * from the editor theme, so those paints always look right while authoring — even
 * on a page that publishes light. Two cases are worth telling the author about:
 *
 * - `never`: the site has no way to render a dark page (theme toggle hidden and
 *   a light/solid default) — the dark background paints are dead code.
 * - `only-dark`: the page *can* be dark but does not start dark, so the paint is
 *   a dark-only override the normal published view never uses.
 *
 * @param {object|null|undefined} editor
 * @returns {''|'never'|'only-dark'}
 */
export function publishedDarkWarningKind(editor = null) {
    if (! isStyleEditingDark(editor)) {
        return '';
    }

    if (editor?.__voodbuilderPublishedDarkScope === false) {
        return 'never';
    }

    if (editor?.__voodbuilderPublishedDefaultDark === false) {
        return 'only-dark';
    }

    return '';
}

/**
 * Pin Style theme to the top-bar toggle (avoids Filament/localStorage races).
 *
 * @param {object|null|undefined} editor
 * @param {boolean} isDark
 */
export function setStyleEditingDark(editor, isDark) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderStyleThemeDark = Boolean(isDark);
}

/**
 * @param {object|null|undefined} editor
 * @returns {''|'md:'|'lg:'}
 */
export function currentStyleBreakpointPrefix(editor) {
    let id = '';

    try {
        id = String(
            editor?.Devices?.getSelected?.()?.get?.('id')
            ?? editor?.getDevice?.()
            ?? '',
        ).trim();
    } catch {
        id = '';
    }

    return deviceIdToBreakpointPrefix(id || 'desktop');
}

/**
 * Compose dark: + breakpoint prefix for Style writes/reads.
 *
 * @param {{ dark?: boolean, breakpoint?: string }} [options]
 * @returns {string} e.g. '', 'lg:', 'dark:', 'dark:md:'
 */
export function composeVariantPrefix(options = {}) {
    const dark = Boolean(options.dark);
    const bp = normalizeBreakpointPrefix(options.breakpoint);

    return dark ? `dark:${bp}` : bp;
}

/**
 * Active Style variant prefix from top-bar theme + canvas device.
 *
 * @param {object|null|undefined} editor
 * @returns {string}
 */
export function currentStyleVariantPrefix(editor) {
    return composeVariantPrefix({
        dark: isStyleEditingDark(editor),
        breakpoint: currentStyleBreakpointPrefix(editor),
    });
}

/**
 * @param {string|null|undefined} prefix
 * @returns {''|'md:'|'lg:'}
 */
export function normalizeBreakpointPrefix(prefix) {
    const raw = String(prefix ?? '').trim();
    const withoutDark = raw.startsWith('dark:') ? raw.slice(5) : raw;

    if (withoutDark === 'md:' || withoutDark === 'md') {
        return 'md:';
    }

    if (withoutDark === 'lg:' || withoutDark === 'lg') {
        return 'lg:';
    }

    return '';
}

/**
 * Normalize a full Style variant prefix (theme + breakpoint).
 *
 * @param {string|null|undefined} prefix
 * @returns {string}
 */
export function normalizeVariantPrefix(prefix) {
    const raw = String(prefix ?? '').trim();
    const dark = raw === 'dark' || raw === 'dark:' || raw.startsWith('dark:');
    const bp = normalizeBreakpointPrefix(raw);

    return composeVariantPrefix({ dark, breakpoint: bp });
}

/**
 * Read cascade for a variant prefix.
 * Dark prefers dark:* then falls back to light cascade so selects show inheritance.
 *
 * @param {string|null|undefined} prefix
 * @returns {string[]}
 */
export function cascadePrefixesFor(prefix) {
    const full = normalizeVariantPrefix(prefix);
    const dark = full.startsWith('dark:');
    const bp = normalizeBreakpointPrefix(full);
    const lightCascade = bp === 'lg:'
        ? ['lg:', 'md:', '']
        : bp === 'md:'
            ? ['md:', '']
            : [''];

    if (! dark) {
        return lightCascade;
    }

    const darkCascade = lightCascade.map((item) => `dark:${item}`);

    return [...darkCascade, ...lightCascade];
}

/**
 * @param {string|null|undefined} name
 * @returns {string}
 */
export function stripResponsivePrefix(name) {
    return String(name ?? '').trim().replace(RESPONSIVE_VARIANT_PREFIX_RE, '');
}

/**
 * Strip dark: and responsive prefixes → bare utility.
 *
 * @param {string|null|undefined} name
 * @returns {string}
 */
export function stripVariantPrefixes(name) {
    let next = String(name ?? '').trim();

    if (next.startsWith('dark:')) {
        next = next.slice(5);
    }

    return next.replace(RESPONSIVE_VARIANT_PREFIX_RE, '');
}

/**
 * @param {string|null|undefined} value bare utility (e.g. text-6xl)
 * @param {string|null|undefined} prefix variant prefix (e.g. dark:lg:)
 * @returns {string}
 */
export function prefixedUtility(value, prefix = '') {
    const bare = String(value ?? '').trim();

    if (bare === '') {
        return '';
    }

    if (stripVariantPrefixes(bare) !== bare) {
        return bare;
    }

    return `${normalizeVariantPrefix(prefix)}${bare}`;
}

/**
 * Exact match at one variant prefix (no cascade).
 *
 * @param {Iterable<string>|string[]} classes
 * @param {Array<{value: string, label?: string}>} options
 * @param {string|null|undefined} prefix
 * @returns {string}
 */
export function resolveGroupValueExact(classes, options, prefix = '') {
    const bp = normalizeVariantPrefix(prefix);
    const set = new Set(
        [...(classes ?? [])].map((name) => String(name ?? '').trim()).filter(Boolean),
    );

    for (const opt of options ?? []) {
        const bare = String(opt?.value ?? '').trim();

        if (bare !== '' && set.has(`${bp}${bare}`)) {
            return bare;
        }
    }

    return '';
}

/**
 * Resolve the option value for the active Style variant (with cascade).
 *
 * @param {Iterable<string>|string[]} classes
 * @param {Array<{value: string, label?: string}>} options
 * @param {string|null|undefined} prefix
 * @returns {string}
 */
export function resolveGroupValueAtBreakpoint(classes, options, prefix = '') {
    const set = new Set(
        [...(classes ?? [])].map((name) => String(name ?? '').trim()).filter(Boolean),
    );

    for (const bp of cascadePrefixesFor(prefix)) {
        for (const opt of options ?? []) {
            const bare = String(opt?.value ?? '').trim();

            if (bare !== '' && set.has(`${bp}${bare}`)) {
                return bare;
            }
        }
    }

    return '';
}

/**
 * Replace one exclusive utility group at a single variant prefix only.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet bare utility names
 * @param {string|null|undefined} nextClass bare utility or empty to clear
 * @param {string|null|undefined} prefix
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
export function replaceClassGroupAtBreakpoint(component, groupSet, nextClass, prefix = '', options = {}) {
    if (! component || ! groupSet) {
        return;
    }

    const bp = normalizeVariantPrefix(prefix);
    const clearSets = [groupSet, ...(options.alsoClear ?? [])];
    const drop = new Set();

    for (const set of clearSets) {
        for (const name of set ?? []) {
            if (name) {
                drop.add(`${bp}${name}`);
            }
        }
    }

    const next = nextClass == null || String(nextClass).trim() === ''
        ? ''
        : prefixedUtility(nextClass, bp);

    const before = listComponentClasses(component);

    for (const name of before) {
        if (drop.has(name) && name !== next) {
            component.removeClass?.(name);
        }
    }

    if (next !== '' && ! listComponentClasses(component).includes(next)) {
        component.addClass?.(next);
    }

    const present = new Set(listComponentClasses(component));

    if (next !== '' && ! present.has(next)) {
        component.addClass?.(next);
        present.add(next);
    }

    for (const name of before) {
        if (drop.has(name) && name !== next && present.has(name)) {
            component.removeClass?.(name);
        }
    }
}

/**
 * Clear (or set on light base only) a group across Style-authored variants.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass applied only at light base when non-empty
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
export function replaceClassGroupAllBreakpoints(component, groupSet, nextClass = null, options = {}) {
    if (! component || ! groupSet) {
        return;
    }

    const next = nextClass == null || String(nextClass).trim() === ''
        ? null
        : String(nextClass).trim();

    for (const dark of [false, true]) {
        for (const bp of STYLE_BREAKPOINT_PREFIXES) {
            const prefix = composeVariantPrefix({ dark, breakpoint: bp });
            replaceClassGroupAtBreakpoint(
                component,
                groupSet,
                ! dark && bp === '' ? next : null,
                prefix,
                options,
            );
        }
    }
}

/**
 * @param {object|null|undefined} component
 * @returns {string[]}
 */
function listComponentClasses(component) {
    const raw = component?.getClasses?.() ?? [];
    const names = [];

    for (const item of raw) {
        let name = '';

        if (typeof item === 'string') {
            name = item;
        } else if (item && typeof item === 'object') {
            name = String(
                item.getLabel?.()
                ?? item.get?.('name')
                ?? item.id
                ?? '',
            );
        }

        name = name.trim().replace(/^\./, '');

        if (name !== '' && ! names.includes(name)) {
            names.push(name);
        }
    }

    return names;
}
