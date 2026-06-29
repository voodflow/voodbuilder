/**
 * GrapesJS "Make dynamic" UI — binds selected components to server data sources.
 */

export const NEUTRAL_IMAGE_PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Dynamic image</text>'
    + '</svg>',
);

const CMD_MAKE_DYNAMIC = 'vpress-make-dynamic';
const CMD_CLEAR_DYNAMIC = 'vpress-clear-dynamic';

let bindingsCatalog = null;
let bindingsPreviewValues = null;

function componentTag(component) {
    return String(component.get('tagName') ?? '').toLowerCase();
}

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

function parseBindingKey(bindingKey, catalog) {
    if (! bindingKey) {
        return null;
    }

    const sources = (catalog?.groups ?? [])
        .flatMap((group) => group.sources ?? [])
        .sort((left, right) => right.id.length - left.id.length);

    for (const source of sources) {
        const prefix = `${source.id}.`;

        if (! bindingKey.startsWith(prefix)) {
            continue;
        }

        const fieldId = bindingKey.slice(prefix.length);

        if (! fieldId) {
            continue;
        }

        const hasField = (source.fields ?? []).some((field) => field.id === fieldId);

        if (! hasField) {
            continue;
        }

        return { sourceId: source.id, fieldId };
    }

    return null;
}

function normalizeBindingKeyForUi(bindingKey, catalog) {
    const parsed = parseBindingKey(bindingKey, catalog);

    if (parsed) {
        return parsed;
    }

    if (bindingKey === 'vtuts.latest.excerpt') {
        return { sourceId: 'vtuts.latest', fieldId: 'introduction' };
    }

    return null;
}

function resolveBindingFieldType(bindingKey, catalog) {
    const option = findBindingOption(catalog, bindingKey)
        ?? (bindingKey === 'vtuts.latest.excerpt'
            ? findBindingOption(catalog, 'vtuts.latest.introduction')
            : null);

    return option?.field?.type ?? 'text';
}

function isInteractiveUrlBinding(bindingKey, catalog) {
    return resolveBindingFieldType(bindingKey, catalog) === 'url';
}

function boundComponentLabel(bindingKey, catalog) {
    const option = findBindingOption(catalog, bindingKey)
        ?? (bindingKey === 'vtuts.latest.excerpt'
            ? findBindingOption(catalog, 'vtuts.latest.introduction')
            : null);

    if (option?.label) {
        return option.label;
    }

    return bindingKey;
}

function extractInteractiveLabel(component) {
    const element = component.getView()?.el;

    if (element?.textContent?.trim()) {
        return element.textContent.trim();
    }

    if (component.get('text')) {
        return String(component.get('text'));
    }

    if (component.get('content')) {
        return String(component.get('content'));
    }

    return 'Button';
}

function resolveLinkComponentType(editor) {
    const domComponents = editor?.DomComponents;

    if (! domComponents) {
        return 'default';
    }

    if (domComponents.getType('link')) {
        return 'link';
    }

    return 'default';
}

/**
 * GrapesJS forms "button" uses a Text trait, not inline editing.
 * URL-bound CTAs become <a role="button"> so double-click edits the label.
 */
function morphUrlButtonToAnchor(editor, component) {
    if (componentTag(component) !== 'button') {
        return;
    }

    const label = extractInteractiveLabel(component);
    const attributes = { ...component.getAttributes() };

    delete attributes.type;
    delete attributes.onclick;

    attributes.href = attributes.href && attributes.href !== '' ? attributes.href : '#';
    attributes.role = 'button';

    component.set({
        tagName: 'a',
        type: resolveLinkComponentType(editor),
        editable: true,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: 'Dynamic link',
    });
    component.setAttributes(attributes);
    component.components(label);
    component.set('content', label);
}

function placeholderForBinding(sourceLabel, fieldLabel) {
    return `[${sourceLabel}: ${fieldLabel}]`;
}

function isPlaceholderText(text) {
    const value = String(text ?? '').trim();

    return value === '' || /^\[[^:]+:[^\]]+\]$/.test(value);
}

function paintPreviewOnElement(component, value, fieldType) {
    if (value == null || value === '') {
        return;
    }

    const view = component.getView();
    const element = view?.el;

    if (! element) {
        return;
    }

    const tag = componentTag(component);
    const text = String(value);

    if (fieldType === 'image' && tag === 'img') {
        element.setAttribute('src', text);
        component.addAttributes({ src: text }, { silent: true });

        return;
    }

    if (fieldType === 'url') {
        if (tag === 'a') {
            element.setAttribute('href', text);
            component.addAttributes({ href: text }, { silent: true });
        }

        if (tag === 'button') {
            const onclick = `window.location.href=${JSON.stringify(text)}`;
            element.setAttribute('onclick', onclick);
            component.addAttributes({ onclick }, { silent: true });
        }

        return;
    }

    if (tag === 'img') {
        element.setAttribute('alt', text);
        component.addAttributes({ alt: text }, { silent: true });

        return;
    }

    if (tag === 'button' || tag === 'a') {
        return;
    }

    element.textContent = text;
    component.set('content', text, { silent: true });
}

function applyPreviewValue(component, bindingKey, option, value) {
    const fieldType = option?.field?.type ?? 'text';

    paintPreviewOnElement(component, value, fieldType);
}

function applyBindingToComponent(editor, component, bindingKey, option) {
    const tag = componentTag(component);
    const sourceLabel = option?.source?.label ?? 'Dynamic';
    const fieldLabel = option?.field?.label ?? bindingKey;
    const fieldType = option?.field?.type ?? 'text';
    const placeholder = placeholderForBinding(sourceLabel, fieldLabel);

    if (fieldType === 'url' && tag === 'button') {
        morphUrlButtonToAnchor(editor, component);
    }

    const effectiveTag = componentTag(component);

    component.addAttributes({
        'data-vpress-bind': bindingKey,
    });
    component.addClass('vpress-gjs-bound');

    const urlOnInteractive = fieldType === 'url' && (effectiveTag === 'button' || effectiveTag === 'a');

    component.set({
        editable: urlOnInteractive,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: urlOnInteractive ? 'Dynamic link' : `Dynamic: ${fieldLabel}`,
    });

    if (effectiveTag === 'img' && fieldType === 'image') {
        component.addAttributes({
            src: NEUTRAL_IMAGE_PLACEHOLDER,
            alt: placeholder,
        });
        paintPreviewOnElement(component, NEUTRAL_IMAGE_PLACEHOLDER, 'image');

        return;
    }

    if (fieldType === 'url') {
        component.addAttributes({ href: '#' });
        component.removeAttributes('onclick');

        return;
    }

    if (fieldType === 'text') {
        paintPreviewOnElement(component, placeholder, 'text');
    }
}

function clearBindingFromComponent(component) {
    component.removeAttributes('data-vpress-bind');
    component.removeAttributes('onclick');
    component.removeClass('vpress-gjs-bound');
    component.set({ editable: true });
}

function configureBoundComponent(editor, component, catalog) {
    const bindingKey = component.getAttributes()['data-vpress-bind'];

    if (! bindingKey) {
        return;
    }

    const fieldType = resolveBindingFieldType(bindingKey, catalog);

    if (fieldType === 'url' && componentTag(component) === 'button') {
        morphUrlButtonToAnchor(editor, component);
    }

    const tag = componentTag(component);
    const urlOnInteractive = fieldType === 'url' && (tag === 'button' || tag === 'a');
    const fieldLabel = boundComponentLabel(bindingKey, catalog).split(' → ').pop() ?? bindingKey;

    component.set({
        editable: urlOnInteractive,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: urlOnInteractive ? 'Dynamic link' : `Dynamic: ${fieldLabel}`,
    });
    component.addClass('vpress-gjs-bound');
}

export function registerBoundComponentType(editor) {
    const domComponents = editor.DomComponents;
    const defaultType = domComponents.getType('default');
    const defaultModel = defaultType?.model;

    domComponents.addType('vpress-bound', {
        extend: 'default',
        isComponent: (element) => element?.hasAttribute?.('data-vpress-bind') === true,
        model: {
            defaults: {
                ...(defaultModel?.prototype?.defaults ?? {}),
                name: 'Dynamic field',
                editable: true,
                droppable: true,
                draggable: true,
                removable: true,
                copyable: true,
                layerable: true,
            },
        },
    });
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

function openBindingModal(editor, component, catalog, labels, onApplied) {
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

    const existingBinding = component.getAttributes()['data-vpress-bind'];
    const parsedBinding = normalizeBindingKeyForUi(existingBinding, catalog);

    if (parsedBinding) {
        sourceSelect.value = parsedBinding.sourceId;
        populateFields();
        fieldSelect.value = parsedBinding.fieldId;
    }

    overlay.querySelector('[data-bind-cancel]').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });

    applyButton.addEventListener('click', () => {
        const bindingKey = `${sourceSelect.value}.${fieldSelect.value}`;
        const option = findBindingOption(catalog, bindingKey)
            ?? (bindingKey === 'vtuts.latest.excerpt'
                ? findBindingOption(catalog, 'vtuts.latest.introduction')
                : null);

        applyBindingToComponent(editor, component, bindingKey, option);
        editor.select(component);
        close();

        if (typeof onApplied === 'function') {
            onApplied(component, bindingKey, option);
        }
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

async function loadBindingsPreview(bindingsPreviewUrl, force = false) {
    if (! bindingsPreviewUrl) {
        return {};
    }

    if (bindingsPreviewValues && ! force) {
        return bindingsPreviewValues;
    }

    const response = await fetch(bindingsPreviewUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error(`Bindings preview request failed (${response.status})`);
    }

    const payload = await response.json();
    bindingsPreviewValues = payload.values ?? {};

    return bindingsPreviewValues;
}

export async function refreshBindingPreviews(editor, options = {}) {
    const catalog = options.catalog ?? bindingsCatalog ?? await loadBindingsCatalog(options.bindingsUrl).catch(() => null);

    if (! catalog || ! editor?.getWrapper) {
        return;
    }

    const values = await loadBindingsPreview(options.bindingsPreviewUrl, true).catch((error) => {
        console.error('Vpress GrapesJS: could not load binding preview.', error);

        return {};
    });

    editor.getWrapper().find('[data-vpress-bind]').forEach((component) => {
        const bindingKey = component.getAttributes()['data-vpress-bind'];

        if (! bindingKey) {
            return;
        }

        const option = findBindingOption(catalog, bindingKey)
            ?? (bindingKey === 'vtuts.latest.excerpt'
                ? findBindingOption(catalog, 'vtuts.latest.introduction')
                : null);
        const value = values[bindingKey]
            ?? (bindingKey === 'vtuts.latest.excerpt' ? values['vtuts.latest.introduction'] : undefined);
        const element = component.getView()?.el;
        const tag = componentTag(component);
        const currentText = element?.textContent?.trim() ?? '';

        if (value == null || value === '') {
            return;
        }

        if (tag !== 'img' && tag !== 'button' && tag !== 'a' && ! isPlaceholderText(currentText) && currentText !== '') {
            return;
        }

        applyPreviewValue(component, bindingKey, option, value);
    });
}

async function ensureBoundComponentVisible(component, options = {}) {
    const bindingKey = component.getAttributes()['data-vpress-bind'];

    if (! bindingKey) {
        return;
    }

    const catalog = options.catalog ?? bindingsCatalog ?? await loadBindingsCatalog(options.bindingsUrl).catch(() => null);

    if (! catalog) {
        return;
    }

    const values = await loadBindingsPreview(options.bindingsPreviewUrl, true);
    const option = findBindingOption(catalog, bindingKey)
        ?? (bindingKey === 'vtuts.latest.excerpt'
            ? findBindingOption(catalog, 'vtuts.latest.introduction')
            : null);
    const value = values[bindingKey]
        ?? (bindingKey === 'vtuts.latest.excerpt' ? values['vtuts.latest.introduction'] : undefined);
    const element = component.getView()?.el;
    const tag = componentTag(component);

    if (! element || value == null || value === '') {
        return;
    }

    const needsPaint = tag === 'img'
        ? ! element.getAttribute('src') || element.getAttribute('src')?.startsWith('data:image/svg')
        : (tag !== 'button' && tag !== 'a' && ! element.textContent?.trim());

    if (! needsPaint) {
        return;
    }

    applyPreviewValue(component, bindingKey, option, value);
}

export async function registerBindingsUi(editor, options = {}) {
    const labels = options.labels ?? {};
    const catalog = await loadBindingsCatalog(options.bindingsUrl).catch((error) => {
        console.error('Vpress GrapesJS: could not load bindings catalog.', error);

        return { groups: [], sources: [] };
    });

    const previewOptions = {
        catalog,
        bindingsUrl: options.bindingsUrl,
        bindingsPreviewUrl: options.bindingsPreviewUrl,
    };

    registerBoundComponentType(editor);

    editor.getWrapper().find('[data-vpress-bind]').forEach((component) => {
        configureBoundComponent(editor, component, catalog);
    });

    editor.on('component:add', (component) => {
        configureBoundComponent(editor, component, catalog);
    });

    editor.on('component:selected', (component) => {
        configureBoundComponent(editor, component, catalog);
        void ensureBoundComponentVisible(component, previewOptions);
    });

    const refreshPreviews = () => refreshBindingPreviews(editor, previewOptions);

    editor.Commands.add(CMD_MAKE_DYNAMIC, {
        async run(ed) {
            const selected = ed.getSelected();

            if (! selected) {
                window.alert(labels.selectComponent ?? 'Select an element first.');

                return;
            }

            openBindingModal(ed, selected, catalog, labels, refreshPreviews);
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

    return catalog;
}
