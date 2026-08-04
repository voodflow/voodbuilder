/**
 * Searchable font-family control for GrapesJS Style Manager.
 */

import { cssSafeFontStack, findFontByStack, styleManagerFontOptions } from './catalog.js';

const SECTOR_ID = 'typography';
const PROPERTY_ID = 'font-family';

/**
 * @param {string} value
 * @returns {string}
 */
function canonicalStack(value) {
    const safe = cssSafeFontStack(value);
    const font = findFontByStack(safe) ?? findFontByStack(value);

    return font?.stack ?? safe;
}

/**
 * @param {Array<{ id?: string, value?: string, label?: string, name?: string }>} options
 * @param {string} query
 * @returns {Array<{ id: string, label: string }>}
 */
function filterOptions(options, query) {
    const list = (Array.isArray(options) ? options : []).map((option) => ({
        id: String(option.id ?? option.value ?? ''),
        label: String(option.label ?? option.name ?? option.id ?? ''),
    })).filter((option) => option.id !== '');

    const needle = String(query ?? '').trim().toLowerCase();

    if (needle === '') {
        return list;
    }

    return list.filter((option) => (
        option.label.toLowerCase().includes(needle)
        || option.id.toLowerCase().includes(needle)
    ));
}

/**
 * @param {HTMLSelectElement} select
 * @param {Array<{ id: string, label: string }>} options
 * @param {string} selectedId
 */
function fillSelect(select, options, selectedId) {
    const current = canonicalStack(selectedId);
    select.replaceChildren();

    for (const option of options) {
        const el = document.createElement('option');
        el.value = option.id;
        el.textContent = option.label;
        el.style.fontFamily = option.id;
        select.appendChild(el);
    }

    if (current && [...select.options].some((option) => option.value === current)) {
        select.value = current;
    } else if (current) {
        const orphan = document.createElement('option');
        orphan.value = current;
        orphan.textContent = findFontByStack(current)?.family ?? current;
        orphan.style.fontFamily = current;
        select.insertBefore(orphan, select.firstChild);
        select.value = current;
    }
}

/**
 * @param {HTMLElement|null|undefined} el
 * @param {HTMLElement|null|undefined} createdEl
 * @returns {{ search: HTMLInputElement, select: HTMLSelectElement, allOptions: Function }|null}
 */
function resolveUi(el, createdEl) {
    if (createdEl?.__voodbuilderFontSelect) {
        return createdEl.__voodbuilderFontSelect;
    }

    const wrap = el?.querySelector?.('.voodbuilder-font-select');

    return wrap?.__voodbuilderFontSelect ?? null;
}

/**
 * Register custom Style Manager type `font-select` and switch font-family to it.
 *
 * @param {object} editor
 * @param {{ searchPlaceholder?: string }} [options]
 */
export function registerFontSelectType(editor, options = {}) {
    const sm = editor?.StyleManager;

    if (! sm?.addType || editor.__voodbuilderFontSelectTypeBound) {
        return;
    }

    editor.__voodbuilderFontSelectTypeBound = true;

    const searchPlaceholder = options.searchPlaceholder ?? 'Cerca font…';

    sm.addType('font-select', {
        create({ props, change }) {
            const wrap = document.createElement('div');
            wrap.className = 'voodbuilder-font-select';

            const search = document.createElement('input');
            search.type = 'search';
            search.className = 'voodbuilder-font-select__search';
            search.placeholder = searchPlaceholder;
            search.autocomplete = 'off';
            search.spellcheck = false;
            search.setAttribute('aria-label', searchPlaceholder);

            const select = document.createElement('select');
            select.className = 'voodbuilder-font-select__list';
            select.setAttribute('aria-label', 'Font family');

            wrap.append(search, select);

            const allOptions = () => {
                const fromProps = props?.get?.('options') ?? props?.attributes?.options ?? props?.options;
                return Array.isArray(fromProps) && fromProps.length > 0
                    ? fromProps
                    : styleManagerFontOptions();
            };

            fillSelect(select, filterOptions(allOptions(), ''), props?.get?.('value') ?? '');

            search.addEventListener('input', () => {
                const selected = select.value;
                fillSelect(select, filterOptions(allOptions(), search.value), selected);
            });

            search.addEventListener('keydown', (event) => {
                event.stopPropagation();

                if (event.key === 'Escape') {
                    search.value = '';
                    fillSelect(select, filterOptions(allOptions(), ''), select.value);
                }
            });

            search.addEventListener('mousedown', (event) => {
                event.stopPropagation();
            });

            select.addEventListener('change', (event) => {
                change({ event });
            });

            wrap.__voodbuilderFontSelect = { search, select, allOptions };

            return wrap;
        },

        emit({ updateStyle }, { event }) {
            const value = event?.target?.value;

            if (value == null || value === '') {
                return;
            }

            updateStyle(canonicalStack(value));
        },

        update({ value, el, createdEl }) {
            const ui = resolveUi(el, createdEl);

            if (! ui) {
                return;
            }

            const stack = canonicalStack(value ?? '');
            fillSelect(ui.select, filterOptions(ui.allOptions(), ui.search.value), stack);

            if (stack && ui.select.value !== stack) {
                ui.select.value = stack;
            }
        },

        destroy() {},
    });
}

/**
 * Point the typography font-family property at the searchable type.
 * No-op when Style Manager has no typography sector (Tailwind panel owns fonts).
 *
 * @param {object} editor
 */
export function applyFontSelectProperty(editor) {
    const sm = editor?.StyleManager;

    if (! sm?.addProperty) {
        return;
    }

    // STYLE_MANAGER_SECTORS is intentionally empty — Grapes logs
    // "'typography' sector not found" on every getSector/addProperty miss.
    // Enumerate sectors instead of looking up by id.
    let hasSector = false;

    try {
        const sectors = sm.getSectors?.();
        const list = sectors?.models ?? sectors ?? [];
        hasSector = [...list].some((sector) => String(sector?.get?.('id') ?? sector?.id ?? '') === SECTOR_ID);
    } catch {
        hasSector = false;
    }

    if (! hasSector) {
        return;
    }

    const options = styleManagerFontOptions();
    const existing = sm.getProperty?.(SECTOR_ID, PROPERTY_ID)
        ?? sm.getSector?.(SECTOR_ID)?.getProperty?.(PROPERTY_ID);

    const prev = existing?.attributes
        ? { ...existing.attributes }
        : {};

    // Avoid recreating on every load if already wired.
    if (existing?.get?.('type') === 'font-select') {
        existing.set?.({ options, full: true });

        return;
    }

    let at;

    try {
        const props = sm.getProperties?.(SECTOR_ID);
        const models = props?.models ?? props ?? [];
        const index = [...models].findIndex((prop) => (
            prop.get?.('property') === PROPERTY_ID || prop.get?.('id') === PROPERTY_ID
        ));
        at = index >= 0 ? index : undefined;
    } catch {
        at = undefined;
    }

    if (existing) {
        sm.removeProperty?.(SECTOR_ID, PROPERTY_ID);
    }

    sm.addProperty(SECTOR_ID, {
        ...prev,
        type: 'font-select',
        property: PROPERTY_ID,
        name: prev.name ?? prev.label ?? 'Font Family',
        label: prev.label ?? prev.name ?? 'Font Family',
        options,
        defaults: prev.defaults ?? prev.default ?? '',
        full: true,
    }, at == null ? {} : { at });
}
