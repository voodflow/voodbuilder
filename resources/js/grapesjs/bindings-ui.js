/**
 * GrapesJS "Make dynamic" UI — binds selected components to server data sources.
 */

const NEUTRAL_IMAGE_PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Dynamic image</text>'
    + '</svg>',
);

const CMD_MAKE_DYNAMIC = 'vpress-make-dynamic';
const CMD_CLEAR_DYNAMIC = 'vpress-clear-dynamic';

let bindingsCatalog = null;

function flatBindingOptions(catalog) {
    const options = [];

    for (const group of catalog?.groups ?? []) {
        for (const source of group.sources ?? []) {
            for (const field of source.fields ?? []) {
                options.push({
                    id: `${source.id}.${field.id}`,
                    label: `${source.label} → ${field.label}`,
                    source,
                    field,
                });
            }
        }
    }

    return options;
}

function findBindingOption(catalog, bindingKey) {
    return flatBindingOptions(catalog).find((option) => option.id === bindingKey) ?? null;
}

function placeholderForBinding(sourceLabel, fieldLabel) {
    return `[${sourceLabel}: ${fieldLabel}]`;
}

function applyBindingToComponent(component, bindingKey, option) {
    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const sourceLabel = option?.source?.label ?? 'Dynamic';
    const fieldLabel = option?.field?.label ?? bindingKey;
    const fieldType = option?.field?.type ?? 'text';
    const placeholder = placeholderForBinding(sourceLabel, fieldLabel);

    component.addAttributes({
        'data-vpress-bind': bindingKey,
    });
    component.addClass('vpress-gjs-bound');

    if (tag === 'img' && fieldType === 'image') {
        component.addAttributes({
            src: NEUTRAL_IMAGE_PLACEHOLDER,
            alt: placeholder,
        });

        return;
    }

    if (tag === 'a' && fieldType === 'url') {
        component.addAttributes({ href: '#' });
    }

    if (fieldType === 'text' || fieldType === 'url') {
        component.set('content', placeholder);
    }
}

function clearBindingFromComponent(component) {
    component.removeAttributes('data-vpress-bind');
    component.removeClass('vpress-gjs-bound');
}

function createModal(labels) {
    const overlay = document.createElement('div');
    overlay.className = 'vpress-gjs-bindings-modal';
    overlay.innerHTML = `
        <div class="vpress-gjs-bindings-modal__dialog" role="dialog" aria-modal="true">
            <h2 class="vpress-gjs-bindings-modal__title"></h2>
            <label class="vpress-gjs-bindings-modal__label">
                <span class="vpress-gjs-bindings-modal__label-text"></span>
                <select class="vpress-gjs-bindings-modal__select" data-bind-source></select>
            </label>
            <label class="vpress-gjs-bindings-modal__label">
                <span class="vpress-gjs-bindings-modal__label-text"></span>
                <select class="vpress-gjs-bindings-modal__select" data-bind-field disabled></select>
            </label>
            <div class="vpress-gjs-bindings-modal__actions">
                <button type="button" class="vpress-gjs-bindings-modal__button" data-bind-cancel></button>
                <button type="button" class="vpress-gjs-bindings-modal__button vpress-gjs-bindings-modal__button--primary" data-bind-apply disabled></button>
            </div>
        </div>
    `;

    overlay.querySelector('.vpress-gjs-bindings-modal__title').textContent = labels.modalTitle;
    overlay.querySelectorAll('.vpress-gjs-bindings-modal__label-text')[0].textContent = labels.modalSource;
    overlay.querySelectorAll('.vpress-gjs-bindings-modal__label-text')[1].textContent = labels.modalField;
    overlay.querySelector('[data-bind-cancel]').textContent = labels.modalCancel;
    overlay.querySelector('[data-bind-apply]').textContent = labels.modalApply;

    return overlay;
}

function openBindingModal(editor, component, catalog, labels) {
    const overlay = createModal(labels);
    const sourceSelect = overlay.querySelector('[data-bind-source]');
    const fieldSelect = overlay.querySelector('[data-bind-field]');
    const applyButton = overlay.querySelector('[data-bind-apply]');
    const groups = catalog?.groups ?? [];

    if (groups.length === 0) {
        window.alert(labels.noSources);

        return;
    }

    const close = () => overlay.remove();

    for (const group of groups) {
        const optgroup = document.createElement('optgroup');
        optgroup.label = group.package_label ?? group.package;

        for (const source of group.sources ?? []) {
            const option = document.createElement('option');
            option.value = source.id;
            option.textContent = source.label;
            optgroup.appendChild(option);
        }

        sourceSelect.appendChild(optgroup);
    }

    const populateFields = () => {
        const sourceId = sourceSelect.value;
        const source = groups
            .flatMap((group) => group.sources ?? [])
            .find((item) => item.id === sourceId);

        fieldSelect.innerHTML = '';
        fieldSelect.disabled = ! source;

        for (const field of source?.fields ?? []) {
            const option = document.createElement('option');
            option.value = field.id;
            option.textContent = field.label;
            fieldSelect.appendChild(option);
        }

        applyButton.disabled = ! source || fieldSelect.options.length === 0;
    };

    sourceSelect.addEventListener('change', populateFields);
    populateFields();

    overlay.querySelector('[data-bind-cancel]').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });

    applyButton.addEventListener('click', () => {
        const bindingKey = `${sourceSelect.value}.${fieldSelect.value}`;
        const option = findBindingOption(catalog, bindingKey);

        applyBindingToComponent(component, bindingKey, option);
        editor.select(component);
        close();
    });

    document.body.appendChild(overlay);
}

async function loadBindingsCatalog(bindingsUrl) {
    if (! bindingsUrl) {
        return { groups: [], sources: [] };
    }

    if (bindingsCatalog) {
        return bindingsCatalog;
    }

    const response = await fetch(bindingsUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error(`Bindings request failed (${response.status})`);
    }

    bindingsCatalog = await response.json();

    return bindingsCatalog;
}

export async function registerBindingsUi(editor, options = {}) {
    const labels = options.labels ?? {};
    const catalog = await loadBindingsCatalog(options.bindingsUrl).catch((error) => {
        console.error('Vpress GrapesJS: could not load bindings catalog.', error);

        return { groups: [], sources: [] };
    });

    editor.Commands.add(CMD_MAKE_DYNAMIC, {
        async run(ed) {
            const selected = ed.getSelected();

            if (! selected) {
                window.alert(labels.selectComponent ?? 'Select an element first.');

                return;
            }

            openBindingModal(ed, selected, catalog, labels);
        },
    });

    editor.Commands.add(CMD_CLEAR_DYNAMIC, {
        run(ed) {
            const selected = ed.getSelected();

            if (! selected) {
                return;
            }

            clearBindingFromComponent(selected);
        },
    });

    editor.Panels.addButton('options', {
        id: 'vpress-make-dynamic',
        className: 'vpress-gjs-bindings-btn',
        label: '<span aria-hidden="true">🔗</span>',
        command: CMD_MAKE_DYNAMIC,
        attributes: { title: labels.makeDynamic ?? 'Make dynamic' },
    });

    editor.Panels.addButton('options', {
        id: 'vpress-clear-dynamic',
        className: 'vpress-gjs-bindings-btn',
        label: '<span aria-hidden="true">✕</span>',
        command: CMD_CLEAR_DYNAMIC,
        attributes: { title: labels.clearDynamic ?? 'Clear dynamic binding' },
    });
}
