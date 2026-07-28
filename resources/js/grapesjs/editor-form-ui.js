/**
 * Tailwind UI–style form controls for the GrapesJS inspector (input groups pattern).
 * @see https://tailwindcss.com/plus/ui-blocks/application-ui/forms/input-groups
 */

function fieldId(name) {
    return `voodbuilder-gjs-field-${String(name).replace(/[^a-z0-9_-]/gi, '-')}`;
}

export function createFormSection(title) {
    const section = document.createElement('div');
    section.className = 'voodbuilder-gjs-form';

    if (title) {
        const heading = document.createElement('p');
        heading.className = 'voodbuilder-gjs-form__heading';
        heading.textContent = title;
        section.appendChild(heading);
    }

    const fields = document.createElement('div');
    fields.className = 'voodbuilder-gjs-form__fields';
    section.appendChild(fields);

    return { section, fields };
}

/**
 * Segmented tabs for inspector settings panels (nav Layout/Brand, footer Brand/Layout/Columns, …).
 * Pass only the tabs needed for the current block variant; panels are keyed by tab id.
 *
 * @param {{ id: string, label: string }[]} tabs
 * @param {{ activeId?: string, onActiveChange?: (id: string) => void }} [options]
 * @returns {{ root: HTMLElement, panels: Record<string, HTMLElement>, setActive: (id: string) => void }}
 */
export function createFormTabs(tabs, { activeId, onActiveChange } = {}) {
    const list = Array.isArray(tabs) ? tabs.filter((tab) => tab?.id && tab?.label) : [];
    const root = document.createElement('div');
    root.className = 'voodbuilder-gjs-form-tabs';

    const bar = document.createElement('div');
    bar.className = 'voodbuilder-gjs-form-tabs__bar voodbuilder-gjs-segmented';
    bar.setAttribute('role', 'tablist');

    const panelsHost = document.createElement('div');
    panelsHost.className = 'voodbuilder-gjs-form-tabs__panels';

    /** @type {Record<string, HTMLElement>} */
    const panels = {};
    /** @type {HTMLButtonElement[]} */
    const buttons = [];

    const initialId = list.some((tab) => tab.id === activeId)
        ? activeId
        : (list[0]?.id ?? null);

    const setActive = (id) => {
        for (const btn of buttons) {
            const active = btn.dataset.tabId === id;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.tabIndex = active ? 0 : -1;
        }

        for (const [panelId, panel] of Object.entries(panels)) {
            const active = panelId === id;
            panel.hidden = ! active;
            panel.classList.toggle('is-active', active);
        }

        if (typeof onActiveChange === 'function' && id) {
            onActiveChange(id);
        }
    };

    for (const tab of list) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'voodbuilder-gjs-segmented__btn voodbuilder-gjs-form-tabs__tab';
        btn.textContent = tab.label;
        btn.dataset.tabId = tab.id;
        btn.id = `voodbuilder-gjs-form-tab-${tab.id}`;
        btn.setAttribute('role', 'tab');
        btn.setAttribute('aria-controls', `voodbuilder-gjs-form-panel-${tab.id}`);
        btn.addEventListener('click', () => setActive(tab.id));
        buttons.push(btn);
        bar.appendChild(btn);

        const panel = document.createElement('div');
        panel.className = 'voodbuilder-gjs-form-tabs__panel voodbuilder-gjs-form__fields';
        panel.id = `voodbuilder-gjs-form-panel-${tab.id}`;
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', btn.id);
        panel.hidden = true;
        panels[tab.id] = panel;
        panelsHost.appendChild(panel);
    }

    if (list.length > 0) {
        root.append(bar, panelsHost);
        setActive(initialId);
    }

    return { root, panels, setActive };
}

export function createSelectField({ label, name, value, options, onChange }) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field';

    const labelEl = document.createElement('label');
    labelEl.className = 'voodbuilder-gjs-form-label';
    labelEl.htmlFor = id;
    labelEl.textContent = label;

    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-select-wrap';

    const select = document.createElement('select');
    select.id = id;
    select.name = name;
    select.className = 'voodbuilder-gjs-select';
    select.dataset.setting = name;

    const groups = [...new Set(options.map((option) => option.group).filter(Boolean))];

    if (groups.length > 0) {
        const ungrouped = options.filter((option) => ! option.group);

        for (const option of ungrouped) {
            const node = document.createElement('option');
            node.value = option.value;
            node.textContent = option.label;
            node.selected = option.value === value;
            select.appendChild(node);
        }

        for (const group of groups) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = group;

            for (const option of options.filter((item) => item.group === group)) {
                const node = document.createElement('option');
                node.value = option.value;
                node.textContent = option.label;
                node.selected = option.value === value;
                optgroup.appendChild(node);
            }

            select.appendChild(optgroup);
        }
    } else {
        for (const option of options) {
            const node = document.createElement('option');
            node.value = option.value;
            node.textContent = option.label;
            node.selected = option.value === value;
            select.appendChild(node);
        }
    }

    const chevron = document.createElement('span');
    chevron.className = 'voodbuilder-gjs-select-chevron';
    chevron.setAttribute('aria-hidden', 'true');
    chevron.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>';

    wrap.append(select, chevron);
    field.append(labelEl, wrap);

    if (typeof onChange === 'function') {
        select.addEventListener('change', () => {
            onChange(select.value);
        });
    }

    return field;
}

export function createTextField({ label, name, value = '', type = 'text', placeholder = '', required = false, min, max }) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field';

    const labelEl = document.createElement('label');
    labelEl.className = 'voodbuilder-gjs-form-label';
    labelEl.htmlFor = id;
    labelEl.textContent = label;

    const input = document.createElement('input');
    input.id = id;
    input.type = type;
    input.name = name;
    input.className = 'voodbuilder-gjs-input';
    input.value = value ?? '';
    input.placeholder = placeholder;
    input.required = required;

    if (min != null) {
        input.min = String(min);
    }

    if (max != null) {
        input.max = String(max);
    }

    field.append(labelEl, input);

    return { field, input };
}

export function createTextareaField({ label, name, value = '', placeholder = '', rows = 3, required = false }) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field';

    const labelEl = document.createElement('label');
    labelEl.className = 'voodbuilder-gjs-form-label';
    labelEl.htmlFor = id;
    labelEl.textContent = label;

    const input = document.createElement('textarea');
    input.id = id;
    input.name = name;
    input.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--textarea';
    input.value = value ?? '';
    input.placeholder = placeholder;
    input.rows = rows;
    input.required = required;

    field.append(labelEl, input);

    return { field, input };
}

export function createCheckboxField({ label, name, checked, onChange }) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field voodbuilder-gjs-form-field--checkbox';

    const row = document.createElement('div');
    row.className = 'voodbuilder-gjs-checkbox-row';

    const input = document.createElement('input');
    input.type = 'checkbox';
    input.id = id;
    input.name = name;
    input.className = 'voodbuilder-gjs-checkbox';
    input.checked = checked;

    const labelEl = document.createElement('label');
    labelEl.className = 'voodbuilder-gjs-form-label voodbuilder-gjs-form-label--checkbox';
    labelEl.htmlFor = id;
    labelEl.textContent = label;

    row.append(input, labelEl);
    field.appendChild(row);

    if (typeof onChange === 'function') {
        input.addEventListener('change', () => {
            onChange(input.checked);
        });
    }

    return field;
}

/**
 * Two-column checkbox grid for brand/visibility toggles.
 *
 * @param {HTMLElement[]} fields
 * @returns {HTMLElement}
 */
export function createCheckboxGrid(fields = []) {
    const grid = document.createElement('div');
    grid.className = 'voodbuilder-gjs-checkbox-grid';

    for (const field of fields) {
        if (field) {
            grid.appendChild(field);
        }
    }

    return grid;
}

const LOGO_FIELD_KEYS = [
    { key: 'logo_desktop_light', prop: 'vpressLogoDesktopLight', labelKey: 'logoDesktopLight', fallback: 'Logo desktop light' },
    { key: 'logo_desktop_dark', prop: 'vpressLogoDesktopDark', labelKey: 'logoDesktopDark', fallback: 'Logo desktop dark' },
    { key: 'logo_mobile_light', prop: 'vpressLogoMobileLight', labelKey: 'logoMobileLight', fallback: 'Logo mobile light' },
    { key: 'logo_mobile_dark', prop: 'vpressLogoMobileDark', labelKey: 'logoMobileDark', fallback: 'Logo mobile dark' },
];

/** @type {Record<string, { height: string, square: string, desktopMax: string, mobileMax: string }>} */
export const CHROME_LOGO_SIZES = {
    sm: { height: 'h-6', square: 'h-6 w-6', desktopMax: 'max-w-[140px]', mobileMax: 'max-w-[100px]' },
    md: { height: 'h-8', square: 'h-8 w-8', desktopMax: 'max-w-[180px]', mobileMax: 'max-w-[120px]' },
    lg: { height: 'h-10', square: 'h-10 w-10', desktopMax: 'max-w-[220px]', mobileMax: 'max-w-[120px]' },
    xl: { height: 'h-12', square: 'h-12 w-12', desktopMax: 'max-w-[260px]', mobileMax: 'max-w-[160px]' },
};

export const CHROME_LOGO_SIZE_PROP = 'vpressLogoSize';
export const CHROME_LOGO_SIZE_KEY = 'logo_size';
export const CHROME_LOGO_SIZE_MOBILE_PROP = 'vpressLogoSizeMobile';
export const CHROME_LOGO_SIZE_MOBILE_KEY = 'logo_size_mobile';
export const CHROME_LOGO_FULL_WIDTH_PROP = 'vpressLogoFullWidth';
export const CHROME_LOGO_FULL_WIDTH_KEY = 'logo_full_width';
export const CHROME_LOGO_DEFAULT_SIZE = 'lg';

/**
 * @param {unknown} size
 * @returns {keyof typeof CHROME_LOGO_SIZES}
 */
export function normalizeChromeLogoSize(size) {
    const value = typeof size === 'string' ? size.trim().toLowerCase() : '';

    return Object.prototype.hasOwnProperty.call(CHROME_LOGO_SIZES, value) ? value : CHROME_LOGO_DEFAULT_SIZE;
}

/**
 * @returns {{ value: string, label: string }[]}
 */
export function chromeLogoSizeOptions(labelFn) {
    const label = typeof labelFn === 'function' ? labelFn : (_key, fallback) => fallback;

    return [
        { value: 'sm', label: label('logoSizeSm', 'Small (h-6)') },
        { value: 'md', label: label('logoSizeMd', 'Medium (h-8)') },
        { value: 'lg', label: label('logoSizeLg', 'Large (h-10)') },
        { value: 'xl', label: label('logoSizeXl', 'Extra large (h-12)') },
    ];
}

export function chromeLogoFieldDefs() {
    return LOGO_FIELD_KEYS;
}

const LOGO_VARIANT_SELECTORS = {
    logo_desktop_light: '.vb-brand-logo--desktop.vb-brand-logo--light',
    logo_desktop_dark: '.vb-brand-logo--desktop.vb-brand-logo--dark',
    logo_mobile_light: '.vb-brand-logo--mobile.vb-brand-logo--light',
    logo_mobile_dark: '.vb-brand-logo--mobile.vb-brand-logo--dark',
};

/**
 * Push logo URLs from component props into live canvas <img> tags immediately
 * (before / without waiting for a full dynamic-block remount).
 *
 * @param {ParentNode} scope
 * @param {{ get?: Function }} root
 */
export function applyChromeLogoUrlsPreview(scope, root) {
    if (! scope?.querySelectorAll || typeof root?.get !== 'function') {
        return;
    }

    for (const def of LOGO_FIELD_KEYS) {
        const url = String(root.get(def.prop) ?? '').trim();
        const selector = LOGO_VARIANT_SELECTORS[def.key];

        if (! selector) {
            continue;
        }

        scope.querySelectorAll(selector).forEach((img) => {
            if (! (img instanceof HTMLImageElement)) {
                return;
            }

            if (url === '') {
                return;
            }

            if (img.getAttribute('src') !== url) {
                img.setAttribute('src', url);
            }

            img.closest('[data-voodbuilder-chrome-part="logo"]')
                ?.querySelector('[data-voodbuilder-brand-placeholder]')
                ?.classList.add('hidden');
        });
    }
}

/**
 * Apply Tailwind height utilities for brand logos in the live canvas DOM.
 *
 * @param {ParentNode} scope
 * @param {string} sizeDesktop
 * @param {{ footerAvatar?: boolean, sizeMobile?: string, fullWidth?: boolean }} [opts]
 */
export function applyChromeLogoSizeClasses(scope, sizeDesktop, opts = {}) {
    const desktop = normalizeChromeLogoSize(sizeDesktop);
    const mobile = normalizeChromeLogoSize(opts.sizeMobile ?? sizeDesktop);
    const fullWidth = opts.fullWidth === true
        || scope.querySelector?.('[data-voodbuilder-brand-logo-full="1"]') != null;
    const defDesktop = CHROME_LOGO_SIZES[desktop];
    const defMobile = CHROME_LOGO_SIZES[mobile];
    const heightKeys = Object.values(CHROME_LOGO_SIZES).flatMap((entry) => [
        ...entry.height.split(/\s+/),
        ...entry.square.split(/\s+/),
        entry.desktopMax,
        entry.mobileMax,
        'max-w-full',
        'w-auto',
        'w-full',
        'w-6',
        'w-8',
        'w-10',
        'w-12',
        'rounded-full',
        'object-contain',
        'object-cover',
        'object-left',
    ]);

    scope.querySelectorAll?.('[data-voodbuilder-logo-size]').forEach((node) => {
        node.setAttribute('data-voodbuilder-logo-size', desktop);
        node.setAttribute('data-voodbuilder-logo-size-mobile', mobile);
        node.setAttribute('data-voodbuilder-brand-logo-full', fullWidth ? '1' : '0');
        node.classList.toggle('w-full', fullWidth);
        node.classList.toggle('min-w-0', fullWidth);
    });

    scope.querySelectorAll?.('img.vb-brand-logo').forEach((img) => {
        const isDesktop = img.classList.contains('vb-brand-logo--desktop');
        const def = isDesktop ? defDesktop : defMobile;
        const logoOnly = img.closest('[data-voodbuilder-brand-logo-only="1"]') != null;
        const footerContext = img.closest('[data-voodbuilder-footer-brand-link]') != null
            || opts.footerAvatar === true;
        const packageMark = String(img.getAttribute('src') ?? '').includes('voodbuilder-mark.svg');
        const wide = fullWidth || logoOnly;

        heightKeys.forEach((cls) => {
            if (cls) {
                img.classList.remove(cls);
            }
        });

        if (footerContext && ! wide && ! packageMark) {
            def.square.split(/\s+/).forEach((cls) => img.classList.add(cls));
            img.classList.add('rounded-full', 'object-cover');
        } else {
            img.classList.add(def.height, 'object-contain', 'object-left');

            if (wide) {
                img.classList.add('w-full', 'max-w-full');
            } else {
                img.classList.add('w-auto');
                img.classList.add(footerContext ? 'max-w-full' : (isDesktop ? def.desktopMax : def.mobileMax));
            }
        }
    });
}

/**
 * Append desktop/mobile size selects, full-width check, and logo media pickers (no path input).
 */
export function appendChromeLogoFields({ fields, root, editor, applyChange, labelFn }) {
    const resolveLabel = typeof labelFn === 'function'
        ? labelFn
        : (_key, fallback) => fallback;

    fields.append(
        createSelectField({
            label: resolveLabel('logoSizeDesktop', 'Logo size (desktop)'),
            name: CHROME_LOGO_SIZE_PROP,
            value: normalizeChromeLogoSize(root.get(CHROME_LOGO_SIZE_PROP)),
            options: chromeLogoSizeOptions(resolveLabel),
            onChange: (value) => applyChange(CHROME_LOGO_SIZE_PROP, value),
        }),
        createSelectField({
            label: resolveLabel('logoSizeMobile', 'Logo size (mobile)'),
            name: CHROME_LOGO_SIZE_MOBILE_PROP,
            value: normalizeChromeLogoSize(
                root.get(CHROME_LOGO_SIZE_MOBILE_PROP) ?? root.get(CHROME_LOGO_SIZE_PROP),
            ),
            options: chromeLogoSizeOptions(resolveLabel),
            onChange: (value) => applyChange(CHROME_LOGO_SIZE_MOBILE_PROP, value),
        }),
        createCheckboxField({
            label: resolveLabel('logoFullWidth', 'Full width in brand column'),
            name: CHROME_LOGO_FULL_WIDTH_PROP,
            checked: root.get(CHROME_LOGO_FULL_WIDTH_PROP) === true,
            onChange: (checked) => applyChange(CHROME_LOGO_FULL_WIDTH_PROP, checked),
        }),
    );

    for (const def of LOGO_FIELD_KEYS) {
        fields.append(
            createImageUrlField({
                label: resolveLabel(def.labelKey, def.fallback),
                name: def.prop,
                value: root.get(def.prop) ?? '',
                editor,
                chooseLabel: resolveLabel('logoChoose', 'Choose'),
                clearLabel: resolveLabel('logoClear', 'Clear'),
                hidePathInput: true,
                onChange: (url) => applyChange(def.prop, url),
            }),
        );
    }
}

/**
 * Image media field with GrapesJS AssetManager picker.
 * Path/URL text input is hidden by default (local media only).
 */
export function createImageUrlField({
    label,
    name,
    value = '',
    editor = null,
    chooseLabel = 'Choose',
    clearLabel = 'Clear',
    hidePathInput = true,
    onChange,
}) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field';

    const labelEl = document.createElement('label');
    labelEl.className = 'voodbuilder-gjs-form-label';
    labelEl.htmlFor = id;
    labelEl.textContent = label;

    const row = document.createElement('div');
    row.className = hidePathInput
        ? 'voodbuilder-gjs-image-url-row voodbuilder-gjs-image-url-row--media'
        : 'voodbuilder-gjs-image-url-row';

    const preview = document.createElement('div');
    preview.className = 'voodbuilder-gjs-image-url-preview';
    preview.setAttribute('aria-hidden', 'true');

    const input = document.createElement('input');
    input.id = id;
    input.type = hidePathInput ? 'hidden' : 'url';
    input.name = name;
    input.className = hidePathInput ? '' : 'voodbuilder-gjs-input';
    input.value = value ?? '';
    input.dataset.setting = name;

    if (! hidePathInput) {
        input.placeholder = 'https://… or /storage/…';
    }

    const actions = document.createElement('div');
    actions.className = 'voodbuilder-gjs-image-url-actions';

    const chooseBtn = document.createElement('button');
    chooseBtn.type = 'button';
    chooseBtn.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost';
    chooseBtn.textContent = chooseLabel;

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost';
    clearBtn.textContent = clearLabel;

    const syncPreview = () => {
        const src = String(input.value ?? '').trim();

        if (src === '') {
            preview.replaceChildren();
            preview.hidden = true;

            return;
        }

        preview.hidden = false;
        let img = preview.querySelector('img');

        if (! img) {
            img = document.createElement('img');
            img.alt = '';
            preview.replaceChildren(img);
        }

        img.src = src;
    };

    const emit = () => {
        syncPreview();

        if (typeof onChange === 'function') {
            onChange(String(input.value ?? '').trim());
        }
    };

    if (! hidePathInput) {
        input.addEventListener('change', emit);
        input.addEventListener('blur', emit);
    }

    chooseBtn.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const assets = editor?.Assets ?? editor?.AssetManager;

        if (! assets || typeof assets.open !== 'function') {
            return;
        }

        const selectedBefore = editor?.getSelected?.() ?? null;

        assets.open({
            types: ['image'],
            select(asset, complete) {
                const src = typeof asset?.getSrc === 'function'
                    ? asset.getSrc()
                    : (asset?.get?.('src') ?? asset?.src ?? '');

                if (src) {
                    input.value = src;
                    emit();
                }

                if (typeof assets.close === 'function') {
                    assets.close();
                } else if (complete) {
                    // no-op
                }

                if (selectedBefore && editor?.getSelected?.() !== selectedBefore) {
                    window.requestAnimationFrame(() => {
                        editor.select?.(selectedBefore, { scroll: false });
                    });
                }
            },
        });
    });

    clearBtn.addEventListener('click', () => {
        input.value = '';
        emit();
    });

    actions.append(chooseBtn, clearBtn);
    row.append(preview, input, actions);
    field.append(labelEl, row);
    syncPreview();

    return field;
}

