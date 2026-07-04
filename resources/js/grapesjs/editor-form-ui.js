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
    select.className = 'voodbuilder-gjs-select';
    select.dataset.setting = name;

    for (const option of options) {
        const node = document.createElement('option');
        node.value = option.value;
        node.textContent = option.label;
        node.selected = option.value === value;
        select.appendChild(node);
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

export function createCheckboxField({ label, name, checked, onChange }) {
    const id = fieldId(name);
    const field = document.createElement('div');
    field.className = 'voodbuilder-gjs-form-field voodbuilder-gjs-form-field--checkbox';

    const row = document.createElement('div');
    row.className = 'voodbuilder-gjs-checkbox-row';

    const input = document.createElement('input');
    input.type = 'checkbox';
    input.id = id;
    input.className = 'voodbuilder-gjs-checkbox';
    input.checked = checked;
    input.dataset.setting = name;

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
