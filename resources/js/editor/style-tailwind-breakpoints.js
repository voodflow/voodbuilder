/**
 * Style panel ↔ canvas device: mobile-first Tailwind breakpoint prefixes.
 *
 * Device mapping (Grapes widthMedia ≈ Tailwind):
 *   mobilePortrait → base (no prefix)
 *   tablet         → md:
 *   desktop        → lg:
 */

/** Prefixes the Style panel can author (v1). */
export const STYLE_BREAKPOINT_PREFIXES = Object.freeze(['', 'md:', 'lg:']);

/** Strip sm:/md:/lg:/xl:/2xl: from a utility name. */
export const RESPONSIVE_VARIANT_PREFIX_RE = /^(?:sm:|md:|lg:|xl:|2xl:)/;

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
 * @param {string|null|undefined} prefix
 * @returns {''|'md:'|'lg:'}
 */
export function normalizeBreakpointPrefix(prefix) {
    const raw = String(prefix ?? '').trim();

    if (raw === 'md:' || raw === 'md') {
        return 'md:';
    }

    if (raw === 'lg:' || raw === 'lg') {
        return 'lg:';
    }

    return '';
}

/**
 * Read cascade: exact breakpoint → smaller breakpoints → base.
 *
 * @param {string|null|undefined} prefix
 * @returns {Array<''|'md:'|'lg:'>}
 */
export function cascadePrefixesFor(prefix) {
    const bp = normalizeBreakpointPrefix(prefix);

    if (bp === 'lg:') {
        return ['lg:', 'md:', ''];
    }

    if (bp === 'md:') {
        return ['md:', ''];
    }

    return [''];
}

/**
 * @param {string|null|undefined} name
 * @returns {string}
 */
export function stripResponsivePrefix(name) {
    return String(name ?? '').trim().replace(RESPONSIVE_VARIANT_PREFIX_RE, '');
}

/**
 * @param {string|null|undefined} value bare utility (e.g. text-6xl)
 * @param {string|null|undefined} prefix
 * @returns {string}
 */
export function prefixedUtility(value, prefix = '') {
    const bare = String(value ?? '').trim();

    if (bare === '') {
        return '';
    }

    if (RESPONSIVE_VARIANT_PREFIX_RE.test(bare)) {
        return bare;
    }

    return `${normalizeBreakpointPrefix(prefix)}${bare}`;
}

/**
 * Exact match at one breakpoint (no cascade). Used for compound spacing state.
 *
 * @param {Iterable<string>|string[]} classes
 * @param {Array<{value: string, label?: string}>} options
 * @param {string|null|undefined} prefix
 * @returns {string}
 */
export function resolveGroupValueExact(classes, options, prefix = '') {
    const bp = normalizeBreakpointPrefix(prefix);
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
 * Resolve the option value for the active Style breakpoint (with cascade).
 * Returns the bare option value (no prefix) for select binding.
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
 * Replace one exclusive utility group at a single breakpoint only.
 * Does not touch the same family at other breakpoints.
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

    const bp = normalizeBreakpointPrefix(prefix);
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
 * Clear (or set on base only) a group across Style-authored breakpoints.
 * Used for mode switches (e.g. exit text gradient) that should not leave orphans.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass applied only at base when non-empty
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
export function replaceClassGroupAllBreakpoints(component, groupSet, nextClass = null, options = {}) {
    if (! component || ! groupSet) {
        return;
    }

    const next = nextClass == null || String(nextClass).trim() === ''
        ? null
        : String(nextClass).trim();

    for (const bp of STYLE_BREAKPOINT_PREFIXES) {
        replaceClassGroupAtBreakpoint(
            component,
            groupSet,
            bp === '' ? next : null,
            bp,
            options,
        );
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
