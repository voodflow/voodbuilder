/**
 * Tailwind-style custom selects for Editor inspector (traits + style manager).
 * Uses public DOM hooks only — no patches to node_modules/grapesjs.
 */

const CHEVRON_SVG = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>';

const PORTAL_LISTS = new WeakMap();

let refreshFrame = null;
let refreshTimer = null;

function isUnitSelect(select) {
    return Boolean(select.closest('.gjs-field-units'));
}

function isFontFamilySelect(select) {
    if (select.dataset.vbFontSearch === '1') {
        return true;
    }

    if (select.options.length < 6) {
        return false;
    }

    const sample = String(select.options[0]?.value ?? '');
    const second = String(select.options[1]?.value ?? '');

    // Skip empty "—" first option used by Style panel typography.
    const probe = sample.includes(',') || /sans-serif|serif|monospace|cursive/i.test(sample)
        ? sample
        : second;

    return probe.includes(',') || /sans-serif|serif|monospace|cursive/i.test(probe);
}

function isSearchableSelect(select) {
    return select.dataset.vbSearch === '1' || isFontFamilySelect(select);
}

function shouldEnhanceSelect(select) {
    if (!(select instanceof HTMLSelectElement)) {
        return false;
    }

    return ! select.closest('.voodbuilder-editor-bindings-modal, .voodbuilder-code-editor-modal');
}

function triggerLabel(trigger) {
    return trigger.querySelector('.voodbuilder-editor-select-trigger-label');
}

function findGrapesView(el) {
    let node = el;

    while (node) {
        if (node.__gjsv) {
            return node.__gjsv;
        }

        node = node.parentElement;
    }

    return null;
}

function setNativeSelectValue(select, value) {
    const options = Array.from(select.options);
    const match = options.find((option) => option.value === value);

    if (match) {
        select.value = value;

        return;
    }

    const index = options.findIndex((option) => option.value === value);

    if (index >= 0) {
        select.selectedIndex = index;
    }
}

function commitSelectValue(select, value) {
    setNativeSelectValue(select, value);

    // Style panel Tailwind selects are custom markup (not Grapes PropertyView).
    // Never route through SM inputValueChanged — that swallows `change` and blocks
    // font-size / font-weight / decorations apply.
    if (
        select.matches?.(
            '[data-voodbuilder-tw-group], [data-voodbuilder-tw-font-family], [data-vb-font-search], [data-vb-search]',
        )
    ) {
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));

        return;
    }

    const integerField = select.closest('.gjs-field-integer');

    if (integerField && isUnitSelect(select)) {
        const view = findGrapesView(integerField);

        if (view?.handleUnitChange) {
            view.handleUnitChange({ target: select, stopPropagation: () => {} });

            return;
        }

        if (view?.model?.set) {
            view.model.set('unit', value);
            view.elementUpdated?.();

            return;
        }
    }

    const propertyEl = select.closest('.gjs-sm-property');

    if (propertyEl && ! propertyEl.classList.contains('voodbuilder-editor-anim-property')) {
        const view = findGrapesView(propertyEl);

        if (view?.inputValueChanged) {
            view.inputValueChanged({ target: select, stopPropagation: () => {} });

            return;
        }

        if (view?.model?.upValue) {
            view.model.upValue(value);

            return;
        }
    }

    const traitEl = select.closest('.gjs-trt-trait');

    if (traitEl) {
        const view = findGrapesView(traitEl);

        if (view?.onChange) {
            view.onChange({ target: select });

            return;
        }

        if (view?.model?.set) {
            view.model.set('value', value);

            return;
        }
    }

    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

function portalList(wrap, list) {
    const rect = wrap.getBoundingClientRect();
    const compact = wrap.classList.contains('voodbuilder-editor-select-wrap--compact');
    const fontList = list.classList.contains('voodbuilder-editor-select-list--font');
    const gutter = 8;
    const gap = 4;
    const preferredMax = fontList ? 16 * 16 : 12 * 16;

    list.classList.add('voodbuilder-editor-select-list--portal');
    // Prefer modal / editor root so chrome palette tokens (not site theme) apply.
    const modalHost = wrap.closest('.voodbuilder-editor-modal');
    const host = modalHost ?? wrap.closest('.voodbuilder-editor-root') ?? document.body;
    host.appendChild(list);

    const width = compact ? Math.max(rect.width, 72) : rect.width;
    let left = Math.max(gutter, compact ? rect.right - width : rect.left);

    if (left + width > window.innerWidth - gutter) {
        left = Math.max(gutter, window.innerWidth - gutter - width);
    }

    const spaceBelow = Math.max(0, window.innerHeight - rect.bottom - gutter);
    const spaceAbove = Math.max(0, rect.top - gutter);
    const openUp = spaceBelow < Math.min(preferredMax, 168) && spaceAbove > spaceBelow;
    const available = Math.max(0, (openUp ? spaceAbove : spaceBelow) - gap);
    const maxHeight = Math.max(96, Math.min(preferredMax, available || preferredMax));

    list.style.position = 'fixed';
    list.style.left = `${left}px`;
    list.style.width = `${width}px`;
    list.style.zIndex = modalHost ? '10120' : '10050';
    list.style.maxHeight = `${maxHeight}px`;
    list.style.overflow = 'auto';
    list.style.overscrollBehavior = 'contain';

    if (openUp) {
        list.style.top = 'auto';
        list.style.bottom = `${Math.max(gutter, window.innerHeight - rect.top + gap)}px`;
    } else {
        list.style.bottom = 'auto';
        list.style.top = `${rect.bottom + gap}px`;
    }

    PORTAL_LISTS.set(wrap, list);
}

function restoreList(wrap, list) {
    list.classList.remove('voodbuilder-editor-select-list--portal');
    list.style.cssText = '';
    wrap.appendChild(list);
    PORTAL_LISTS.delete(wrap);
}

function closeOpenSelects(exceptWrap = null) {
    document.querySelectorAll('.voodbuilder-editor-select-wrap.is-open').forEach((wrap) => {
        if (wrap === exceptWrap) {
            return;
        }

        const list = wrap.querySelector('.voodbuilder-editor-select-list')
            ?? PORTAL_LISTS.get(wrap);

        wrap.classList.remove('is-open');
        wrap.querySelector('.voodbuilder-editor-select-trigger')?.setAttribute('aria-expanded', 'false');

        if (list) {
            list.hidden = true;

            if (list.classList.contains('voodbuilder-editor-select-list--portal')) {
                restoreList(wrap, list);
            }
        }
    });

    // Orphan portal lists (wrap destroyed while open) would otherwise stay on <body>.
    document.querySelectorAll('.voodbuilder-editor-select-list--portal').forEach((list) => {
        if (exceptWrap && (list.parentElement === exceptWrap || PORTAL_LISTS.get(exceptWrap) === list)) {
            return;
        }

        list.hidden = true;
        list.remove();
    });
}

/**
 * Close every custom select, including orphaned portal menus.
 * Call before remounting inspector/settings DOM.
 */
export function closeAllInspectorSelects() {
    closeOpenSelects();
}

function isSelectUiTarget(target) {
    return Boolean(target?.closest?.('.voodbuilder-editor-select-wrap, .voodbuilder-editor-select-list'));
}

function formatSelectTriggerLabel(select, rawText) {
    const propertyEl = select.closest('.gjs-sm-property');

    if (! propertyEl) {
        return rawText;
    }

    const view = findGrapesView(propertyEl);
    const property = String(view?.model?.get?.('property') ?? '');

    if (property === 'font-weight') {
        return String(rawText ?? '').toLowerCase();
    }

    return rawText;
}

function syncCustomSelect(wrap) {
    const select = wrap.querySelector('select');
    const trigger = wrap.querySelector('.voodbuilder-editor-select-trigger');
    const list = wrap.querySelector('.voodbuilder-editor-select-list')
        ?? PORTAL_LISTS.get(wrap);

    if (! select || ! trigger || ! list) {
        return;
    }

    const selected = select.options[select.selectedIndex];
    const rawLabel = selected?.textContent?.trim() || selected?.value || '-';
    const labelText = formatSelectTriggerLabel(select, rawLabel);
    const hex = selected?.getAttribute?.('data-hex') || '';
    const label = triggerLabel(trigger);

    if (label) {
        label.replaceChildren();

        if (hex) {
            const swatch = document.createElement('span');
            swatch.className = 'voodbuilder-editor-select-swatch voodbuilder-editor-select-swatch--trigger';
            swatch.style.background = hex;
            swatch.setAttribute('aria-hidden', 'true');
            label.appendChild(swatch);
            label.classList.add('voodbuilder-editor-select-trigger-label--swatch');
        } else {
            label.classList.remove('voodbuilder-editor-select-trigger-label--swatch');
        }

        label.appendChild(document.createTextNode(labelText));
        label.style.fontFamily = select.value || '';
    } else {
        trigger.textContent = labelText;
    }

    wrap.classList.toggle('voodbuilder-editor-select-wrap--has-swatch', Boolean(hex));

    list.querySelectorAll('.voodbuilder-editor-select-option').forEach((option) => {
        const active = option.dataset.value === select.value;
        option.classList.toggle('is-selected', active);
        option.setAttribute('aria-selected', active ? 'true' : 'false');
    });
}

/**
 * @param {HTMLSelectElement} select
 * @param {HTMLElement} list
 * @param {HTMLElement} wrap
 */
function buildOptionList(select, list, wrap) {
    const searchRow = list.querySelector('.voodbuilder-editor-select-search-item');
    const searchValue = searchRow?.querySelector('input')?.value ?? '';

    list.querySelectorAll('.voodbuilder-editor-select-option').forEach((node) => node.remove());
    list.querySelector('.voodbuilder-editor-select-empty')?.remove();

    const previewFont = wrap.classList.contains('voodbuilder-editor-select-wrap--font');

    for (const option of select.options) {
        const label = option.textContent?.trim() || option.value || '-';
        const value = option.value;
        const hex = option.getAttribute('data-hex');

        const item = document.createElement('li');
        item.className = 'voodbuilder-editor-select-option';
        item.role = 'option';
        item.dataset.value = value;
        item.dataset.label = label.toLowerCase();
        item.tabIndex = -1;

        if (hex) {
            item.classList.add('voodbuilder-editor-select-option--swatch');
            const swatch = document.createElement('span');
            swatch.className = 'voodbuilder-editor-select-swatch';
            swatch.style.background = hex;
            item.append(swatch, document.createTextNode(label));
        } else {
            item.textContent = label;
        }

        if (previewFont && value) {
            item.style.fontFamily = value;
        }

        item.addEventListener('mousedown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            closeOpenSelects();
            commitSelectValue(select, value);
            syncCustomSelect(wrap);
        });

        if (previewFont && value) {
            item.addEventListener('mouseenter', () => {
                select.dispatchEvent(new CustomEvent('vb:font-preview', {
                    bubbles: true,
                    detail: { value },
                }));
            });
        }

        list.appendChild(item);
    }

    if (searchRow) {
        list.prepend(searchRow);
    }

    applyFontSearchFilter(list, wrap, searchValue);
    syncCustomSelect(wrap);
}

/**
 * Filter in place — never recreate the search input (keeps focus while typing).
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} wrap
 * @param {string} [query]
 */
function applyFontSearchFilter(list, wrap, query = '') {
    const previewFont = wrap.classList.contains('voodbuilder-editor-select-wrap--font');
    const needle = String(query ?? '').trim().toLowerCase();
    let visible = 0;

    list.querySelectorAll('.voodbuilder-editor-select-option').forEach((item) => {
        const haystack = `${item.dataset.label ?? ''} ${item.dataset.value ?? ''}`.toLowerCase();
        const match = needle === '' || haystack.includes(needle);
        item.hidden = ! match;

        if (match) {
            visible += 1;
        }
    });

    let empty = list.querySelector('.voodbuilder-editor-select-empty');

    if (previewFont && visible === 0) {
        if (! empty) {
            empty = document.createElement('li');
            empty.className = 'voodbuilder-editor-select-empty';
            empty.setAttribute('role', 'presentation');
            empty.textContent = 'Nessun font';
            list.appendChild(empty);
        }
    } else {
        empty?.remove();
    }
}

/**
 * @param {HTMLSelectElement} select
 * @param {HTMLElement} list
 * @param {HTMLElement} wrap
 */
function ensureFontSearchRow(select, list, wrap) {
    const searchable = wrap.classList.contains('voodbuilder-editor-select-wrap--font')
        || wrap.classList.contains('voodbuilder-editor-select-wrap--searchable');

    if (! searchable) {
        return null;
    }

    let row = list.querySelector('.voodbuilder-editor-select-search-item');

    if (row) {
        return row.querySelector('input');
    }

    row = document.createElement('li');
    row.className = 'voodbuilder-editor-select-search-item';
    row.setAttribute('role', 'presentation');

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'voodbuilder-editor-select-search';
    search.placeholder = select.dataset.vbFontSearchPlaceholder
        || select.dataset.vbSearchPlaceholder
        || 'Search…';
    search.autocomplete = 'off';
    search.spellcheck = false;
    search.setAttribute('aria-label', search.placeholder);

    search.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    search.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    search.addEventListener('keydown', (event) => {
        event.stopPropagation();

        if (event.key === 'Escape') {
            if (search.value !== '') {
                search.value = '';
                applyFontSearchFilter(list, wrap, '');
                event.preventDefault();
            }
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            list.querySelector('.voodbuilder-editor-select-option:not([hidden])')?.focus?.();
        }
    });

    search.addEventListener('input', () => {
        applyFontSearchFilter(list, wrap, search.value);
    });

    row.appendChild(search);
    list.prepend(row);

    return search;
}

function enhanceSelect(select) {
    if (! shouldEnhanceSelect(select)) {
        return;
    }

    if (select.dataset.vbInspectorSelect === '1') {
        const wrap = select.closest('.voodbuilder-editor-select-wrap');

        if (wrap) {
            const list = wrap.querySelector('.voodbuilder-editor-select-list')
                ?? PORTAL_LISTS.get(wrap);

            if (list) {
                const search = list.querySelector('.voodbuilder-editor-select-search');

                // Never rebuild while the user is typing in the font search.
                if (search && document.activeElement === search) {
                    syncCustomSelect(wrap);

                    return;
                }

                const optionCount = list.querySelectorAll('.voodbuilder-editor-select-option').length;

                if (optionCount !== select.options.length) {
                    buildOptionList(select, list, wrap);
                } else {
                    syncCustomSelect(wrap);
                }
            }
        }

        return;
    }

    if (select.closest('.voodbuilder-editor-select-wrap')) {
        const existingWrap = select.closest('.voodbuilder-editor-select-wrap');

        if (existingWrap?.querySelector('.voodbuilder-editor-select-trigger')) {
            return;
        }

        if (! select.closest('.voodbuilder-editor-form')) {
            return;
        }
    }

    const compact = isUnitSelect(select);
    const fontList = ! compact && isFontFamilySelect(select);
    const searchable = ! compact && isSearchableSelect(select);
    const existingFormWrap = select.closest('.voodbuilder-editor-form .voodbuilder-editor-select-wrap');
    const reuseWrap = Boolean(
        existingFormWrap
        && ! existingFormWrap.querySelector('.voodbuilder-editor-select-trigger'),
    );

    select.dataset.vbInspectorSelect = '1';
    select.classList.add('voodbuilder-editor-select', 'voodbuilder-editor-select--native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const wrap = reuseWrap ? existingFormWrap : document.createElement('div');

    if (! reuseWrap) {
        wrap.className = 'voodbuilder-editor-select-wrap';
    }

    if (compact) {
        wrap.classList.add('voodbuilder-editor-select-wrap--compact');
    }

    if (fontList) {
        wrap.classList.add('voodbuilder-editor-select-wrap--font');
    }

    if (searchable) {
        wrap.classList.add('voodbuilder-editor-select-wrap--searchable');
    }

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'voodbuilder-editor-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const label = document.createElement('span');
    label.className = 'voodbuilder-editor-select-trigger-label';
    trigger.appendChild(label);

    const chevron = document.createElement('span');
    chevron.className = 'voodbuilder-editor-select-chevron';
    chevron.innerHTML = CHEVRON_SVG;

    const list = document.createElement('ul');
    list.className = 'voodbuilder-editor-select-list';

    if (fontList) {
        list.classList.add('voodbuilder-editor-select-list--font');
    }

    if (searchable) {
        list.classList.add('voodbuilder-editor-select-list--searchable');
    }

    list.role = 'listbox';
    list.hidden = true;

    if (reuseWrap) {
        wrap.querySelector('.voodbuilder-editor-select-chevron')?.remove();
        wrap.prepend(trigger);
        wrap.append(chevron, list);
    } else {
        select.parentNode?.insertBefore(wrap, select);
        wrap.append(trigger, select, chevron, list);
    }

    const openDropdown = () => {
        closeOpenSelects(wrap);
        wrap.classList.add('is-open');
        portalList(wrap, list);
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');

        const search = searchable ? ensureFontSearchRow(select, list, wrap) : null;

        if (search) {
            search.value = '';
            applyFontSearchFilter(list, wrap, '');
            window.requestAnimationFrame(() => {
                search.focus();
            });
        }

        list.querySelector('.voodbuilder-editor-select-option.is-selected')?.scrollIntoView?.({ block: 'nearest' });
    };

    const closeDropdown = () => {
        wrap.classList.remove('is-open');
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');

        const search = list.querySelector('.voodbuilder-editor-select-search');

        if (search && search.value !== '') {
            search.value = '';
            applyFontSearchFilter(list, wrap, '');
        }

        if (list.classList.contains('voodbuilder-editor-select-list--portal')) {
            restoreList(wrap, list);
        }
    };

    trigger.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        event.preventDefault();

        if (wrap.classList.contains('is-open')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();

            if (! wrap.classList.contains('is-open')) {
                openDropdown();
            }
        }

        if (event.key === 'Escape') {
            closeDropdown();
            closeOpenSelects();
        }
    });

    select.addEventListener('change', () => syncCustomSelect(wrap));
    select.addEventListener('vb:options-changed', () => {
        buildOptionList(select, list, wrap);
    });

    if (searchable) {
        ensureFontSearchRow(select, list, wrap);
    }

    buildOptionList(select, list, wrap);

    const integerField = select.closest('.gjs-field-integer');

    if (integerField) {
        syncIntegerFieldUnits(integerField);
    }
}

export function enhanceInspectorSelects(root = document) {
    if (! root) {
        return;
    }

    const scope = root instanceof Document ? root : root;

    scope.querySelectorAll?.('select').forEach((select) => {
        enhanceSelect(select);
    });
}

const INTEGER_KEYWORD_PATTERN = /^(normal|initial|inherit|auto|unset|medium|none|xx-small|x-small|small|large|x-large|xx-large|smaller|larger)$/i;

function integerValueIsNumeric(raw) {
    const value = String(raw ?? '').trim();

    if (value === '') {
        return false;
    }

    return ! Number.isNaN(Number.parseFloat(value.replace(',', '.')));
}

function syncIntegerFieldUnits(field) {
    const input = field.querySelector('.gjs-input-holder input');
    const units = field.querySelector('.gjs-field-units');

    if (! input || ! units) {
        return;
    }

    const raw = String(input.value ?? '').trim();
    const isKeyword = INTEGER_KEYWORD_PATTERN.test(raw);
    const hideUnits = isKeyword || (! integerValueIsNumeric(raw) && raw !== '');

    field.classList.toggle('voodbuilder-editor-input-group--keyword', hideUnits);
    units.hidden = hideUnits;
    units.setAttribute('aria-hidden', hideUnits ? 'true' : 'false');
}

function enhanceIntegerField(field) {
    field.classList.add('voodbuilder-editor-input-group');

    const input = field.querySelector('.gjs-input-holder input');

    if (! input) {
        syncIntegerFieldUnits(field);

        return;
    }

    if (input.dataset.vbIntegerSync !== '1') {
        input.dataset.vbIntegerSync = '1';
        input.title = 'Numero (es. 1.5) oppure parola chiave (normal, inherit, …)';
        input.addEventListener('input', () => syncIntegerFieldUnits(field));
        input.addEventListener('change', () => syncIntegerFieldUnits(field));
    }

    syncIntegerFieldUnits(field);
    syncOpacityNumberFromSlider(field);
}

/** Opacity: keep the number field readable (show slider value when Editor leaves it empty). */
function syncOpacityNumberFromSlider(field) {
    const property = field.closest('.gjs-sm-property__opacity');

    if (! property) {
        return;
    }

    const input = field.querySelector('.gjs-input-holder input');
    const range = property.querySelector('.gjs-field-range input[type="range"]');

    if (! input || ! range) {
        return;
    }

    const paint = () => {
        const raw = String(input.value ?? '').trim();

        if (raw === '') {
            input.placeholder = range.value || '1';
        } else {
            input.placeholder = '';
        }
    };

    if (input.dataset.vbOpacitySync !== '1') {
        input.dataset.vbOpacitySync = '1';
        range.addEventListener('input', paint);
        range.addEventListener('change', paint);
        input.addEventListener('input', paint);
        input.addEventListener('change', paint);
    }

    paint();
}

export function enhanceInspectorInputGroups(root = document) {
    if (! root) {
        return;
    }

    const scope = root instanceof Document ? root : root;

    scope.querySelectorAll?.('.gjs-field-integer').forEach((field) => {
        enhanceIntegerField(field);
    });
}

function scheduleInspectorSelectRefresh(callback) {
    if (refreshTimer != null) {
        window.clearTimeout(refreshTimer);
    }

    refreshTimer = window.setTimeout(() => {
        refreshTimer = null;
        callback();
    }, 180);
}

function hasRelevantMutation(mutations) {
    return mutations.some((mutation) => {
        for (const node of mutation.addedNodes) {
            if (node instanceof HTMLSelectElement) {
                return true;
            }

            if (node instanceof Element && node.querySelector?.('select, .gjs-field-integer')) {
                return true;
            }
        }

        return false;
    });
}

export function registerInspectorSelectUi(editor, mounts = {}) {
    if (editor.__voodbuilderInspectorSelectUiRegistered) {
        return;
    }

    editor.__voodbuilderInspectorSelectUiRegistered = true;

    const roots = [
        mounts.traits,
        mounts.styles,
        mounts.selectors,
        mounts.conditions,
        mounts.dynamic,
        mounts.siteChromeSettings,
    ].filter(Boolean);

    let enhancing = false;

    const refresh = () => {
        if (document.querySelector('.voodbuilder-editor-select-wrap.is-open')) {
            return;
        }

        if (document.activeElement?.classList?.contains('voodbuilder-editor-select-search')) {
            return;
        }

        if (refreshFrame != null) {
            window.cancelAnimationFrame(refreshFrame);
        }

        refreshFrame = window.requestAnimationFrame(() => {
            refreshFrame = null;
            enhancing = true;

            try {
                for (const root of roots) {
                    enhanceInspectorInputGroups(root);
                    enhanceInspectorSelects(root);
                }
            } finally {
                window.queueMicrotask(() => {
                    enhancing = false;
                });
            }
        });
    };

    const debouncedRefresh = () => {
        if (enhancing) {
            return;
        }

        scheduleInspectorSelectRefresh(refresh);
    };

    editor.on('component:selected', debouncedRefresh);
    editor.on('trait:select', debouncedRefresh);
    editor.on('load', refresh);

    document.addEventListener('mousedown', (event) => {
        if (isSelectUiTarget(event.target)) {
            return;
        }

        closeOpenSelects();
    });

    window.addEventListener('resize', () => closeOpenSelects());
    // Inspector panels scroll independently — keep portal menus from detaching mid-scroll.
    document.addEventListener('scroll', (event) => {
        if (! document.querySelector('.voodbuilder-editor-select-wrap.is-open')) {
            return;
        }

        const target = event.target;

        if (target === document || target === document.documentElement || target === document.body) {
            closeOpenSelects();

            return;
        }

        if (target instanceof Element && target.closest?.('.voodbuilder-editor-shell__right, .voodbuilder-editor-styles-mount, .voodbuilder-editor-inspector-panel')) {
            closeOpenSelects();
        }
    }, true);

    for (const root of roots) {
        if (! root || root.__vbSelectObserver) {
            continue;
        }

        const observer = new MutationObserver((mutations) => {
            if (enhancing || ! hasRelevantMutation(mutations)) {
                return;
            }

            debouncedRefresh();
        });

        observer.observe(root, { childList: true, subtree: true });
        root.__vbSelectObserver = observer;
    }

    refresh();
}
