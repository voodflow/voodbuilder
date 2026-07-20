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

const LOGO_FIELD_KEYS = [
    { key: 'logo_desktop_light', prop: 'vpressLogoDesktopLight', labelKey: 'logoDesktopLight', fallback: 'Logo desktop light' },
    { key: 'logo_desktop_dark', prop: 'vpressLogoDesktopDark', labelKey: 'logoDesktopDark', fallback: 'Logo desktop dark' },
    { key: 'logo_mobile_light', prop: 'vpressLogoMobileLight', labelKey: 'logoMobileLight', fallback: 'Logo mobile light' },
    { key: 'logo_mobile_dark', prop: 'vpressLogoMobileDark', labelKey: 'logoMobileDark', fallback: 'Logo mobile dark' },
];

export function chromeLogoFieldDefs() {
    return LOGO_FIELD_KEYS;
}

/**
 * Image URL field with optional GrapesJS AssetManager picker.
 */
export function createImageUrlField({
    label,
    name,
    value = '',
    editor = null,
    chooseLabel = 'Choose',
    clearLabel = 'Clear',
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
    row.className = 'voodbuilder-gjs-image-url-row';

    const preview = document.createElement('div');
    preview.className = 'voodbuilder-gjs-image-url-preview';
    preview.setAttribute('aria-hidden', 'true');

    const input = document.createElement('input');
    input.id = id;
    input.type = 'url';
    input.name = name;
    input.className = 'voodbuilder-gjs-input';
    input.value = value ?? '';
    input.placeholder = 'https://… or /storage/…';
    input.dataset.setting = name;

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

    input.addEventListener('change', emit);
    input.addEventListener('blur', emit);

    chooseBtn.addEventListener('click', () => {
        const assets = editor?.Assets ?? editor?.AssetManager;

        if (! assets || typeof assets.open !== 'function') {
            input.focus();

            return;
        }

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

                if (complete && typeof assets.close === 'function') {
                    assets.close();
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

/**
 * Append the four chrome logo fields to a settings fields container.
 */
export function appendChromeLogoFields({ fields, root, editor, applyChange, labelFn }) {
    const resolveLabel = typeof labelFn === 'function'
        ? labelFn
        : (_key, fallback) => fallback;

    for (const def of LOGO_FIELD_KEYS) {
        fields.append(
            createImageUrlField({
                label: resolveLabel(def.labelKey, def.fallback),
                name: def.prop,
                value: root.get(def.prop) ?? '',
                editor,
                chooseLabel: resolveLabel('logoChoose', 'Choose'),
                clearLabel: resolveLabel('logoClear', 'Clear'),
                onChange: (url) => applyChange(def.prop, url),
            }),
        );
    }
}
