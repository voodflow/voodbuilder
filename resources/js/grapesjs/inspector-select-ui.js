/**
 * Tailwind-style custom selects for GrapesJS inspector (traits + style manager).
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
    if (select.options.length < 6) {
        return false;
    }

    const sample = String(select.options[0]?.value ?? '');

    return sample.includes(',') || /sans-serif|serif|monospace|cursive/i.test(sample);
}

function shouldEnhanceSelect(select) {
    if (!(select instanceof HTMLSelectElement)) {
        return false;
    }

    return ! select.closest('.voodbuilder-gjs-bindings-modal, .voodbuilder-code-editor-modal');
}

function triggerLabel(trigger) {
    return trigger.querySelector('.voodbuilder-gjs-select-trigger-label');
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

    if (propertyEl) {
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
    const compact = wrap.classList.contains('voodbuilder-gjs-select-wrap--compact');

    list.classList.add('voodbuilder-gjs-select-list--portal');
    document.body.appendChild(list);

    const width = compact ? Math.max(rect.width, 72) : rect.width;

    list.style.position = 'fixed';
    list.style.left = `${Math.max(8, compact ? rect.right - width : rect.left)}px`;
    list.style.top = `${rect.bottom + 4}px`;
    list.style.width = `${width}px`;
    list.style.zIndex = '10050';

    PORTAL_LISTS.set(wrap, list);
}

function restoreList(wrap, list) {
    list.classList.remove('voodbuilder-gjs-select-list--portal');
    list.style.cssText = '';
    wrap.appendChild(list);
    PORTAL_LISTS.delete(wrap);
}

function closeOpenSelects(exceptWrap = null) {
    document.querySelectorAll('.voodbuilder-gjs-select-wrap.is-open').forEach((wrap) => {
        if (wrap === exceptWrap) {
            return;
        }

        const list = wrap.querySelector('.voodbuilder-gjs-select-list')
            ?? PORTAL_LISTS.get(wrap);

        wrap.classList.remove('is-open');
        wrap.querySelector('.voodbuilder-gjs-select-trigger')?.setAttribute('aria-expanded', 'false');

        if (list) {
            list.hidden = true;

            if (list.classList.contains('voodbuilder-gjs-select-list--portal')) {
                restoreList(wrap, list);
            }
        }
    });
}

function isSelectUiTarget(target) {
    return Boolean(target?.closest?.('.voodbuilder-gjs-select-wrap, .voodbuilder-gjs-select-list'));
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
    const trigger = wrap.querySelector('.voodbuilder-gjs-select-trigger');
    const list = wrap.querySelector('.voodbuilder-gjs-select-list')
        ?? PORTAL_LISTS.get(wrap);

    if (! select || ! trigger || ! list) {
        return;
    }

    const selected = select.options[select.selectedIndex];
    const rawLabel = selected?.textContent?.trim() || selected?.value || '-';
    const labelText = formatSelectTriggerLabel(select, rawLabel);
    const label = triggerLabel(trigger);

    if (label) {
        label.textContent = labelText;
    } else {
        trigger.textContent = labelText;
    }

    list.querySelectorAll('.voodbuilder-gjs-select-option').forEach((option) => {
        const active = option.dataset.value === select.value;
        option.classList.toggle('is-selected', active);
        option.setAttribute('aria-selected', active ? 'true' : 'false');
    });
}

function buildOptionList(select, list, wrap) {
    list.replaceChildren();

    const previewFont = wrap.classList.contains('voodbuilder-gjs-select-wrap--font');

    for (const option of select.options) {
        const item = document.createElement('li');
        item.className = 'voodbuilder-gjs-select-option';
        item.role = 'option';
        item.dataset.value = option.value;
        item.textContent = option.textContent?.trim() || option.value || '-';
        item.tabIndex = -1;

        if (previewFont && option.value) {
            item.style.fontFamily = option.value;
        }

        item.addEventListener('mousedown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            commitSelectValue(select, option.value);
            syncCustomSelect(wrap);
            closeOpenSelects();
        });

        list.appendChild(item);
    }

    syncCustomSelect(wrap);
}

function enhanceSelect(select) {
    if (! shouldEnhanceSelect(select)) {
        return;
    }

    if (select.dataset.vbInspectorSelect === '1') {
        const wrap = select.closest('.voodbuilder-gjs-select-wrap');

        if (wrap) {
            const list = wrap.querySelector('.voodbuilder-gjs-select-list');

            if (list && list.childElementCount !== select.options.length) {
                buildOptionList(select, list, wrap);
            } else {
                syncCustomSelect(wrap);
            }
        }

        return;
    }

    if (select.closest('.voodbuilder-gjs-select-wrap')) {
        return;
    }

    const compact = isUnitSelect(select);
    const fontList = ! compact && isFontFamilySelect(select);

    select.dataset.vbInspectorSelect = '1';
    select.classList.add('voodbuilder-gjs-select', 'voodbuilder-gjs-select--native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-select-wrap';

    if (compact) {
        wrap.classList.add('voodbuilder-gjs-select-wrap--compact');
    }

    if (fontList) {
        wrap.classList.add('voodbuilder-gjs-select-wrap--font');
    }

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'voodbuilder-gjs-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const label = document.createElement('span');
    label.className = 'voodbuilder-gjs-select-trigger-label';
    trigger.appendChild(label);

    const chevron = document.createElement('span');
    chevron.className = 'voodbuilder-gjs-select-chevron';
    chevron.innerHTML = CHEVRON_SVG;

    const list = document.createElement('ul');
    list.className = 'voodbuilder-gjs-select-list';

    if (fontList) {
        list.classList.add('voodbuilder-gjs-select-list--font');
    }

    list.role = 'listbox';
    list.hidden = true;

    select.parentNode?.insertBefore(wrap, select);
    wrap.append(trigger, select, chevron, list);

    const openDropdown = () => {
        closeOpenSelects(wrap);
        wrap.classList.add('is-open');
        portalList(wrap, list);
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        list.querySelector('.voodbuilder-gjs-select-option.is-selected')?.scrollIntoView?.({ block: 'nearest' });
    };

    const closeDropdown = () => {
        wrap.classList.remove('is-open');
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');

        if (list.classList.contains('voodbuilder-gjs-select-list--portal')) {
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

    field.classList.toggle('voodbuilder-gjs-input-group--keyword', hideUnits);
    units.hidden = hideUnits;
    units.setAttribute('aria-hidden', hideUnits ? 'true' : 'false');
}

function enhanceIntegerField(field) {
    field.classList.add('voodbuilder-gjs-input-group');

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

    const refresh = () => {
        if (document.querySelector('.voodbuilder-gjs-select-wrap.is-open')) {
            return;
        }

        if (refreshFrame != null) {
            window.cancelAnimationFrame(refreshFrame);
        }

        refreshFrame = window.requestAnimationFrame(() => {
            refreshFrame = null;

            for (const root of roots) {
                enhanceInspectorInputGroups(root);
                enhanceInspectorSelects(root);
            }
        });
    };

    const debouncedRefresh = () => scheduleInspectorSelectRefresh(refresh);

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

    for (const root of roots) {
        if (! root || root.__vbSelectObserver) {
            continue;
        }

        const observer = new MutationObserver((mutations) => {
            if (! hasRelevantMutation(mutations)) {
                return;
            }

            debouncedRefresh();
        });

        observer.observe(root, { childList: true, subtree: true });
        root.__vbSelectObserver = observer;
    }

    refresh();
}
