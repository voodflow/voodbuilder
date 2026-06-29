/**
 * GrapesJS "Make dynamic" UI — binds selected components to server data sources.
 */

import { lucideIcon } from './editor-icons.js';

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
let bindingsPreviewListValues = null;

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

function isTextNodeComponent(component) {
    const type = String(component?.get?.('type') ?? '').toLowerCase();

    return type === 'textnode' || type === 'text';
}

function hasElementChildren(component) {
    const children = component?.components?.();

    if (! children || children.length === 0) {
        return false;
    }

    return children.some((child) => ! isTextNodeComponent(child));
}

function hasStructuralChildren(component) {
    return hasElementChildren(component);
}

function isRepeatContainer(component) {
    return Boolean(component?.getAttributes?.()['data-vpress-repeat']);
}

function isInsideRepeatTemplate(component) {
    let current = component?.parent?.();

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs['data-vpress-repeat'] || attrs['data-vpress-repeat-item']) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isRepeatHost(component) {
    if (! hasElementChildren(component) || isInsideRepeatTemplate(component)) {
        return false;
    }

    if (isLayoutRow(component)) {
        return true;
    }

    return component.components().some((child) => isLayoutRow(child));
}

function componentClassNames(component) {
    const fromModel = component.getClasses?.()?.join(' ') ?? '';
    const fromAttr = component.getAttributes?.()['class'] ?? '';

    return `${fromModel} ${fromAttr}`.trim();
}

function isLayoutRow(component) {
    const classes = componentClassNames(component);

    return /\bflex-wrap\b/.test(classes) || /\bgrid\b/.test(classes);
}

function resolveRepeatTargetContainer(component) {
    if (isLayoutRow(component)) {
        return component;
    }

    const nested = findLayoutRowDescendant(component);

    if (nested) {
        return nested;
    }

    return component;
}

function findLayoutRowDescendant(component) {
    for (const child of component.components?.()?.models ?? []) {
        if (isLayoutRow(child)) {
            return child;
        }

        const nested = findLayoutRowDescendant(child);

        if (nested) {
            return nested;
        }
    }

    return null;
}

function migrateRepeatPlacement(component) {
    const repeatKey = component.getAttributes?.()['data-vpress-repeat'];

    if (! repeatKey) {
        return resolveRepeatTargetContainer(component);
    }

    const repeatTarget = resolveRepeatTargetContainer(component);

    if (repeatTarget === component) {
        return repeatTarget;
    }

    repeatTarget.addAttributes({
        'data-vpress-repeat': repeatKey,
        'data-vpress-repeat-limit': component.getAttributes()['data-vpress-repeat-limit'] ?? '3',
        'data-vpress-repeat-sort': component.getAttributes()['data-vpress-repeat-sort'] ?? 'id',
        'data-vpress-repeat-sort-dir': component.getAttributes()['data-vpress-repeat-sort-dir'] ?? 'desc',
    });
    repeatTarget.removeAttributes('data-vpress-repeat-item');
    component.removeAttributes('data-vpress-repeat');
    component.removeAttributes('data-vpress-repeat-limit');
    component.removeAttributes('data-vpress-repeat-sort');
    component.removeAttributes('data-vpress-repeat-sort-dir');

    return repeatTarget;
}

function clearRepeatAttributes(component) {
    component.removeAttributes('data-vpress-repeat');
    component.removeAttributes('data-vpress-repeat-limit');
    component.removeAttributes('data-vpress-repeat-sort');
    component.removeAttributes('data-vpress-repeat-sort-dir');
    component.find('[data-vpress-repeat-item]').forEach((child) => {
        child.removeAttributes('data-vpress-repeat-item');
    });
}

function repeatSortFromContainer(container) {
    return {
        sort: container?.getAttributes?.()['data-vpress-repeat-sort'] || 'id',
        sortDir: container?.getAttributes?.()['data-vpress-repeat-sort-dir'] || 'desc',
    };
}

function repeatListValuesKey(repeatKey, sort, sortDir) {
    return `${repeatKey}|${sort || 'id'}|${sortDir || 'desc'}`;
}

function sortFieldsForRepeatSource(catalog, repeatSourceId) {
    const source = (catalog?.repeatSources ?? []).find((item) => item.id === repeatSourceId);

    if (source?.sortFields?.length) {
        return source.sortFields;
    }

    return [
        { id: 'id', label: 'ID' },
        { id: 'created_at', label: 'Created date' },
        { id: 'updated_at', label: 'Updated date' },
    ];
}

function collectRepeatPreviewConfigs(editor) {
    const configs = new Map();

    editor?.getWrapper?.().find('[data-vpress-repeat]').forEach((component) => {
        const repeatTarget = migrateRepeatPlacement(component);
        const repeatKey = repeatTarget.getAttributes()['data-vpress-repeat'];

        if (! repeatKey) {
            return;
        }

        const { sort, sortDir } = repeatSortFromContainer(repeatTarget);
        const limit = Math.max(
            12,
            Number(repeatTarget.getAttributes()['data-vpress-repeat-limit'] || 12),
        );
        const cacheKey = repeatListValuesKey(repeatKey, sort, sortDir);

        configs.set(cacheKey, {
            key: repeatKey,
            sort,
            dir: sortDir,
            limit,
        });
    });

    return [...configs.values()];
}

function formatRepeatSummary(repeatKey, limit, sort, sortDir, catalog, labels) {
    const sortLabel = sortFieldsForRepeatSource(catalog, repeatKey)
        .find((field) => field.id === sort)?.label ?? sort;
    const dirLabel = sortDir === 'asc'
        ? (labels.repeatSortAsc ?? 'Ascending')
        : (labels.repeatSortDesc ?? 'Descending');

    return `${repeatKey} (${limit}) · ${sortLabel} · ${dirLabel}`;
}

function shouldOfferBindingSource(sourceId, component) {
    if (isRepeatListSource(sourceId)) {
        return false;
    }

    if (isInsideRepeatTemplate(component)) {
        return isRepeatItemSource(sourceId);
    }

    if (isRepeatItemSource(sourceId)) {
        return false;
    }

    return true;
}

function defaultItemSourceId(component, catalog) {
    let current = component?.parent?.();

    while (current) {
        const repeatKey = current.getAttributes?.()['data-vpress-repeat'];

        if (repeatKey) {
            return repeatKey.replace(/\.list$/, '.item');
        }

        current = current.parent?.();
    }

    const itemSource = (catalog?.repeatSources ?? [])
        .map((source) => source.id.replace(/\.list$/, '.item'))
        .find((sourceId) => (catalog?.groups ?? []).some((group) => (group.sources ?? []).some((source) => source.id === sourceId)));

    return itemSource ?? null;
}

function canAcceptFieldBinding(component, fieldType) {
    const tag = componentTag(component);

    if (fieldType === 'url') {
        return tag === 'a' || tag === 'button';
    }

    if (fieldType === 'image') {
        return tag === 'img';
    }

    if (tag === 'a' || tag === 'button') {
        return true;
    }

    return ! hasElementChildren(component);
}

function isRepeatListSource(sourceId) {
    return typeof sourceId === 'string' && sourceId.endsWith('.list');
}

function isRepeatItemSource(sourceId) {
    return typeof sourceId === 'string' && sourceId.endsWith('.item');
}

function migrateLatestToItemKey(bindingKey) {
    return String(bindingKey).replace(/\.latest\./, '.item.');
}

function bindingKeyForRepeatContext(bindingKey, component) {
    if (isInsideRepeatTemplate(component) && bindingKey.includes('.latest.')) {
        return migrateLatestToItemKey(bindingKey);
    }

    return bindingKey;
}

function repeatCardIndex(component) {
    let current = component?.parent?.();

    while (current) {
        const repeatKey = current.getAttributes?.()['data-vpress-repeat'];

        if (repeatKey) {
            const container = current;
            let card = component;

            while (card?.parent?.() && card.parent() !== container) {
                card = card.parent();
            }

            const siblings = container.components().models;
            const index = siblings.indexOf(card);

            return {
                container,
                repeatKey,
                index: index >= 0 ? index : 0,
                ...repeatSortFromContainer(container),
            };
        }

        current = current.parent?.();
    }

    let repeatItem = null;
    current = component;

    while (current) {
        if (current.getAttributes?.()['data-vpress-repeat-item']) {
            repeatItem = current;
            break;
        }

        current = current.parent?.();
    }

    if (! repeatItem?.parent) {
        return null;
    }

    const container = repeatItem.parent();
    const repeatKey = container.getAttributes()['data-vpress-repeat'] ?? null;
    const siblings = container.components().models.filter(
        (child) => child.getAttributes()['data-vpress-repeat-item'],
    );
    const index = siblings.indexOf(repeatItem);

    return {
        container,
        repeatKey,
        index: index >= 0 ? index : 0,
        ...repeatSortFromContainer(container),
    };
}

function inferRepeatListKey(bindingKey, catalog) {
    const alias = String(bindingKey).split('.')[0];

    if (! alias) {
        return catalog?.repeatSources?.[0]?.id ?? null;
    }

    const match = (catalog?.repeatSources ?? []).find((source) => source.id === `${alias}.list`);

    return match?.id ?? `${alias}.list`;
}

function collapseRepeatTemplate(container) {
    const target = resolveRepeatTargetContainer(container);
    const children = target.components();

    if (children.length === 0) {
        return null;
    }

    const first = children.at(0);
    first.addAttributes({ 'data-vpress-repeat-item': '1' });

    while (target.components().length > 1) {
        target.components().at(target.components().length - 1)?.remove();
    }

    target.components().forEach((child, index) => {
        if (index === 0) {
            child.addAttributes({ 'data-vpress-repeat-item': '1' });
        } else {
            child.removeAttributes('data-vpress-repeat-item');
        }
    });

    return first;
}

function migrateBindingsInTree(editor, component, catalog) {
    component?.find?.('[data-vpress-bind]')?.forEach((bound) => {
        const bindingKey = bound.getAttributes()['data-vpress-bind'];

        if (! bindingKey?.includes('.latest.')) {
            return;
        }

        const migrated = migrateLatestToItemKey(bindingKey);

        bound.addAttributes({ 'data-vpress-bind': migrated });
        configureBoundComponent(editor, bound, catalog);
    });
}

function resolvePreviewValue(bindingKey, component, values, listValues, catalog) {
    if (bindingKey.includes('.item.')) {
        const repeatContext = repeatCardIndex(component);
        const fieldId = bindingKey.split('.').pop();
        const repeatKey = repeatContext?.repeatKey ?? inferRepeatListKey(bindingKey, catalog);
        const index = repeatContext?.index ?? 0;
        const listKey = repeatListValuesKey(
            repeatKey,
            repeatContext?.sort,
            repeatContext?.sortDir,
        );
        const row = listValues?.[listKey]?.[index] ?? listValues?.[repeatKey]?.[index];

        if (row?.[fieldId]) {
            return row[fieldId];
        }

        const fallback = listValues?.[listKey]?.[0]?.[fieldId]
            ?? listValues?.[repeatKey]?.[0]?.[fieldId];

        if (fallback) {
            return fallback;
        }
    }

    return values[bindingKey]
        ?? (bindingKey === 'vtuts.latest.excerpt' ? values['vtuts.latest.introduction'] : undefined);
}

function normalizeRepeatContainers(editor, catalog) {
    editor.getWrapper().find('[data-vpress-repeat]').forEach((container) => {
        const repeatTarget = migrateRepeatPlacement(container);
        const template = collapseRepeatTemplate(repeatTarget);

        if (template) {
            migrateBindingsInTree(editor, template, catalog);
        }
    });

    const seen = new Set();

    editor.getWrapper().find('[data-vpress-repeat-item]').forEach((item) => {
        const container = item.parent();

        if (! container || seen.has(container.cid)) {
            return;
        }

        const repeatItems = container.components().models.filter(
            (child) => child.getAttributes()['data-vpress-repeat-item'],
        );

        if (repeatItems.length <= 1) {
            return;
        }

        seen.add(container.cid);

        const repeatTarget = resolveRepeatTargetContainer(container);

        if (! repeatTarget.getAttributes()['data-vpress-repeat']) {
            const sampleBinding = repeatTarget.find('[data-vpress-bind]')[0]?.getAttributes?.()['data-vpress-bind'];
            const repeatKey = sampleBinding ? inferRepeatListKey(sampleBinding, catalog) : catalog?.repeatSources?.[0]?.id;

            if (repeatKey) {
                repeatTarget.addAttributes({
                    'data-vpress-repeat': repeatKey,
                    'data-vpress-repeat-limit': repeatTarget.getAttributes()['data-vpress-repeat-limit'] ?? '3',
                    'data-vpress-repeat-sort': repeatTarget.getAttributes()['data-vpress-repeat-sort'] ?? 'id',
                    'data-vpress-repeat-sort-dir': repeatTarget.getAttributes()['data-vpress-repeat-sort-dir'] ?? 'desc',
                });
            }
        }

        const template = collapseRepeatTemplate(repeatTarget);

        if (template) {
            migrateBindingsInTree(editor, template, catalog);
        }
    });
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

    if (hasStructuralChildren(component)) {
        return;
    }

    element.textContent = text;
    component.set('content', text, { silent: true });
}

function applyPreviewValue(component, bindingKey, option, value) {
    const fieldType = option?.field?.type ?? 'text';

    paintPreviewOnElement(component, value, fieldType);
}

function applyBindingToComponent(editor, component, bindingKey, option, labels = {}) {
    const tag = componentTag(component);
    const sourceLabel = option?.source?.label ?? 'Dynamic';
    const fieldLabel = option?.field?.label ?? bindingKey;
    const fieldType = option?.field?.type ?? 'text';
    const sourceId = option?.source?.id ?? '';
    const placeholder = placeholderForBinding(sourceLabel, fieldLabel);

    if (hasStructuralChildren(component) && component.getAttributes()['data-vpress-repeat']) {
        window.alert(labels.repeatContainerNoBind ?? 'List repeat containers cannot hold a field binding. Bind title, text and links inside the card template instead.');

        return;
    }

    if (isRepeatListSource(sourceId)) {
        window.alert(labels.repeatListNotField ?? 'Repeat list sources are for containers only. Use the List repeat section, or pick a list item field.');

        return;
    }

    if (! canAcceptFieldBinding(component, fieldType)) {
        window.alert(labels.bindingNeedsLeaf ?? 'Bind text and images on the inner element (h2, p, img, a) — not on the grid container.');

        return;
    }

    if (hasStructuralChildren(component) && ! isRepeatItemSource(sourceId) && fieldType === 'text') {
        window.alert(labels.bindingNeedsLeaf ?? 'Bind text and images on the inner element (h2, p, img, a) — not on the grid container.');

        return;
    }

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

    for (const [typeName, matcher] of [
        ['vpress-repeat-host', (element) => element?.hasAttribute?.('data-vpress-repeat') === true],
        ['vpress-repeat-item', (element) => element?.hasAttribute?.('data-vpress-repeat-item') === true],
    ]) {
        domComponents.addType(typeName, {
            isComponent: matcher,
            model: {
                defaults: {
                    draggable: true,
                    droppable: true,
                    removable: true,
                    copyable: true,
                },
            },
        });
    }

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
    mountBindingForm(editor, component, catalog, labels, onApplied, { mode: 'modal' });
}

function mountBindingForm(editor, component, catalog, labels, onApplied, { mode = 'inline', mount = null } = {}) {
    const groups = catalog?.groups ?? [];

    if (groups.length === 0) {
        window.alert(labels.noSources);

        return null;
    }

    const isModal = mode === 'modal';
    const overlay = isModal ? createModal(labels) : null;
    const host = isModal ? overlay : mount;

    if (! host) {
        return null;
    }

    if (! isModal) {
        host.innerHTML = `
            <div class="vpress-gjs-dynamic-panel">
                <p class="vpress-gjs-dynamic-panel__hint"></p>
                <p class="vpress-gjs-dynamic-panel__current" hidden></p>
                <div class="vpress-gjs-dynamic-panel__field-bind" data-field-bind-panel>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text"></span>
                        <select class="vpress-gjs-bindings-modal__select" data-bind-source></select>
                    </label>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text"></span>
                        <select class="vpress-gjs-bindings-modal__select" data-bind-field disabled></select>
                    </label>
                    <div class="vpress-gjs-dynamic-panel__actions">
                        <button type="button" class="vpress-gjs-bindings-modal__button vpress-gjs-bindings-modal__button--compact" data-bind-clear></button>
                    </div>
                </div>
                <div class="vpress-gjs-dynamic-panel__repeat" data-repeat-panel hidden>
                    <p class="vpress-gjs-dynamic-panel__repeat-title"></p>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text" data-repeat-source-label></span>
                        <select class="vpress-gjs-bindings-modal__select" data-repeat-source></select>
                    </label>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text" data-repeat-limit-label></span>
                        <input type="number" min="1" max="24" class="vpress-gjs-bindings-modal__input" data-repeat-limit value="3" />
                    </label>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text" data-repeat-sort-label></span>
                        <select class="vpress-gjs-bindings-modal__select" data-repeat-sort></select>
                    </label>
                    <label class="vpress-gjs-bindings-modal__label">
                        <span class="vpress-gjs-bindings-modal__label-text" data-repeat-sort-dir-label></span>
                        <select class="vpress-gjs-bindings-modal__select" data-repeat-sort-dir>
                            <option value="desc"></option>
                            <option value="asc"></option>
                        </select>
                    </label>
                    <div class="vpress-gjs-dynamic-panel__actions">
                        <button type="button" class="vpress-gjs-bindings-modal__button vpress-gjs-bindings-modal__button--compact" data-repeat-apply></button>
                        <button type="button" class="vpress-gjs-bindings-modal__button vpress-gjs-bindings-modal__button--compact" data-repeat-clear></button>
                    </div>
                    <p class="vpress-gjs-dynamic-panel__current" data-repeat-current hidden></p>
                </div>
            </div>
        `;

        host.querySelector('.vpress-gjs-dynamic-panel__hint').textContent = isRepeatHost(component)
            ? (labels.repeatContainerHint ?? labels.inspectorHint ?? 'Use List repeat on this container, then bind fields inside each card with “List item”.')
            : isInsideRepeatTemplate(component)
                ? (labels.repeatItemHint ?? 'Choose List item and pick the field for this element (title, description, slug…).')
                : (labels.inspectorHint ?? 'Connect the selected element to live data from your packages.');

        const fieldBindPanel = host.querySelector('[data-field-bind-panel]');

        if (fieldBindPanel && isRepeatHost(component)) {
            fieldBindPanel.hidden = true;
        }
        host.querySelectorAll('.vpress-gjs-bindings-modal__label-text')[0].textContent = labels.modalSource;
        host.querySelectorAll('.vpress-gjs-bindings-modal__label-text')[1].textContent = labels.modalField;
        host.querySelector('[data-bind-clear]').textContent = labels.clearDynamic ?? 'Clear binding';

        const repeatPanel = host.querySelector('[data-repeat-panel]');

        if (repeatPanel) {
            repeatPanel.querySelector('.vpress-gjs-dynamic-panel__repeat-title').textContent = labels.repeatList ?? 'List repeat';
            repeatPanel.querySelector('[data-repeat-source-label]').textContent = labels.repeatSource ?? 'Repeat list';
            repeatPanel.querySelector('[data-repeat-limit-label]').textContent = labels.repeatLimit ?? 'Items';
            repeatPanel.querySelector('[data-repeat-sort-label]').textContent = labels.repeatSort ?? 'Sort by';
            repeatPanel.querySelector('[data-repeat-sort-dir-label]').textContent = labels.repeatSortDir ?? 'Direction';
            repeatPanel.querySelector('[data-repeat-sort-dir] option[value="desc"]').textContent = labels.repeatSortDesc ?? 'Descending';
            repeatPanel.querySelector('[data-repeat-sort-dir] option[value="asc"]').textContent = labels.repeatSortAsc ?? 'Ascending';
            repeatPanel.querySelector('[data-repeat-apply]').textContent = labels.applyRepeat ?? 'Apply list repeat';
            repeatPanel.querySelector('[data-repeat-clear]').textContent = labels.clearRepeat ?? 'Clear list repeat';
        }
    }

    const repeatPanel = host.querySelector('[data-repeat-panel]');
    const repeatSourceSelect = host.querySelector('[data-repeat-source]');
    const repeatLimitInput = host.querySelector('[data-repeat-limit]');
    const repeatSortSelect = host.querySelector('[data-repeat-sort]');
    const repeatSortDirSelect = host.querySelector('[data-repeat-sort-dir]');
    const repeatApplyButton = host.querySelector('[data-repeat-apply]');
    const repeatClearButton = host.querySelector('[data-repeat-clear]');
    const repeatCurrentEl = host.querySelector('[data-repeat-current]');
    const sourceSelect = host.querySelector('[data-bind-source]');
    const fieldSelect = host.querySelector('[data-bind-field]');
    const applyButton = host.querySelector('[data-bind-apply]');
    const clearButton = host.querySelector('[data-bind-clear]');
    const currentEl = host.querySelector('.vpress-gjs-dynamic-panel__current');

    const close = () => {
        if (overlay) {
            overlay.remove();
        }
    };

    for (const group of groups) {
        const optgroup = document.createElement('optgroup');
        optgroup.label = group.package_label ?? group.package;

        for (const source of group.sources ?? []) {
            if (! shouldOfferBindingSource(source.id, component)) {
                continue;
            }

            const option = document.createElement('option');
            option.value = source.id;
            option.textContent = source.label;
            optgroup.appendChild(option);
        }

        sourceSelect.appendChild(optgroup);
    }

    const populateFields = (autoApply = false) => {
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

        if (applyButton) {
            applyButton.disabled = ! source || fieldSelect.options.length === 0;
        }

        if (autoApply && fieldSelect.options.length > 0) {
            commitBinding();
        }
    };

    const commitBinding = () => {
        if (! component) {
            if (isModal) {
                window.alert(labels.selectComponent ?? 'Select an element on the canvas first.');
            }

            return;
        }

        if (! sourceSelect.value || ! fieldSelect.value) {
            return;
        }

        const bindingKey = bindingKeyForRepeatContext(
            `${sourceSelect.value}.${fieldSelect.value}`,
            component,
        );
        const option = findBindingOption(catalog, bindingKey)
            ?? (bindingKey === 'vtuts.latest.excerpt'
                ? findBindingOption(catalog, 'vtuts.latest.introduction')
                : null);

        applyBindingToComponent(editor, component, bindingKey, option, labels);
        editor.select(component);

        if (typeof onApplied === 'function') {
            onApplied(component, bindingKey, option);
        }

        if (currentEl) {
            currentEl.hidden = false;
            currentEl.textContent = `${labels.currentBinding ?? 'Current'}: ${boundComponentLabel(bindingKey, catalog)}`;
        }
    };

    sourceSelect.addEventListener('change', () => populateFields(! isModal));
    populateFields(false);

    const existingBinding = component?.getAttributes?.()['data-vpress-bind'];
    const parsedBinding = normalizeBindingKeyForUi(existingBinding, catalog);

    if (parsedBinding) {
        sourceSelect.value = parsedBinding.sourceId;
        populateFields(false);
        fieldSelect.value = parsedBinding.fieldId;
    } else if (isInsideRepeatTemplate(component)) {
        const preferredSource = defaultItemSourceId(component, catalog);

        if (preferredSource && [...sourceSelect.options].some((option) => option.value === preferredSource)) {
            sourceSelect.value = preferredSource;
            populateFields(false);
        }
    }

    if (currentEl && existingBinding) {
        currentEl.hidden = false;
        currentEl.textContent = `${labels.currentBinding ?? 'Current'}: ${boundComponentLabel(existingBinding, catalog)}`;
    } else if (currentEl) {
        currentEl.hidden = true;
    }

    if (isModal) {
        host.querySelector('[data-bind-cancel]').addEventListener('click', close);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                close();
            }
        });
    }

    clearButton?.addEventListener('click', () => {
        if (! component) {
            return;
        }

        clearBindingFromComponent(component);
        editor.select(component);

        if (typeof onApplied === 'function') {
            onApplied(component, null, null);
        }

        if (isModal) {
            close();
        } else if (currentEl) {
            currentEl.hidden = true;
        }
    });

    fieldSelect.addEventListener('change', () => {
        if (! isModal) {
            commitBinding();
        }
    });

    if (repeatPanel && repeatSourceSelect && ! isModal) {
        const repeatSources = catalog.repeatSources ?? [];

        if (repeatSources.length > 0 && isRepeatHost(component)) {
            repeatPanel.hidden = false;
            const repeatTarget = migrateRepeatPlacement(component);

            const populateRepeatSortFields = (repeatSourceId, selectedSort = 'id') => {
                if (! repeatSortSelect) {
                    return;
                }

                repeatSortSelect.innerHTML = '';

                for (const field of sortFieldsForRepeatSource(catalog, repeatSourceId)) {
                    const option = document.createElement('option');
                    option.value = field.id;
                    option.textContent = field.label;
                    repeatSortSelect.appendChild(option);
                }

                if ([...repeatSortSelect.options].some((option) => option.value === selectedSort)) {
                    repeatSortSelect.value = selectedSort;
                }
            };

            for (const source of repeatSources) {
                const option = document.createElement('option');
                option.value = source.id;
                option.textContent = source.label;
                repeatSourceSelect.appendChild(option);
            }

            const existingRepeat = repeatTarget.getAttributes()['data-vpress-repeat'];
            const existingSort = repeatTarget.getAttributes()['data-vpress-repeat-sort'] || 'id';
            const existingSortDir = repeatTarget.getAttributes()['data-vpress-repeat-sort-dir'] || 'desc';
            const existingLimit = repeatTarget.getAttributes()['data-vpress-repeat-limit'];

            if (existingRepeat) {
                repeatSourceSelect.value = existingRepeat;
                populateRepeatSortFields(existingRepeat, existingSort);
                repeatCurrentEl.hidden = false;
                repeatCurrentEl.textContent = `${labels.currentRepeat ?? 'Current repeat'}: ${formatRepeatSummary(
                    existingRepeat,
                    existingLimit || repeatLimitInput.value || 3,
                    existingSort,
                    existingSortDir,
                    catalog,
                    labels,
                )}`;
            } else {
                populateRepeatSortFields(repeatSourceSelect.value || repeatSources[0]?.id);
            }

            if (existingLimit) {
                repeatLimitInput.value = existingLimit;
            }

            if (repeatSortDirSelect) {
                repeatSortDirSelect.value = existingSortDir;
            }

            repeatSourceSelect.addEventListener('change', () => {
                populateRepeatSortFields(repeatSourceSelect.value);
            });

            repeatApplyButton?.addEventListener('click', () => {
                if (! component || ! repeatSourceSelect.value) {
                    return;
                }

                const limit = Math.max(1, Math.min(24, Number(repeatLimitInput.value || 3)));
                const sort = repeatSortSelect?.value || 'id';
                const sortDir = repeatSortDirSelect?.value || 'desc';

                if (component.getAttributes()['data-vpress-bind']) {
                    clearBindingFromComponent(component);
                }

                if (component !== repeatTarget) {
                    clearRepeatAttributes(component);
                }

                repeatTarget.addAttributes({
                    'data-vpress-repeat': repeatSourceSelect.value,
                    'data-vpress-repeat-limit': String(limit),
                    'data-vpress-repeat-sort': sort,
                    'data-vpress-repeat-sort-dir': sortDir,
                });

                const template = collapseRepeatTemplate(repeatTarget);

                if (template) {
                    migrateBindingsInTree(editor, template, catalog);
                }

                editor.select(template ?? repeatTarget);

                if (repeatCurrentEl) {
                    repeatCurrentEl.hidden = false;
                    repeatCurrentEl.textContent = `${labels.currentRepeat ?? 'Current repeat'}: ${formatRepeatSummary(
                        repeatSourceSelect.value,
                        limit,
                        sort,
                        sortDir,
                        catalog,
                        labels,
                    )}`;
                }

                if (currentEl) {
                    currentEl.hidden = true;
                }

                if (typeof onApplied === 'function') {
                    onApplied(template ?? repeatTarget, null, null);
                }
            });

            repeatClearButton?.addEventListener('click', () => {
                if (! component) {
                    return;
                }

                clearRepeatAttributes(repeatTarget);

                if (component !== repeatTarget) {
                    clearRepeatAttributes(component);
                }

                editor.select(component);

                if (repeatCurrentEl) {
                    repeatCurrentEl.hidden = true;
                }
            });
        }
    }

    applyButton?.addEventListener('click', () => {
        commitBinding();

        if (isModal) {
            close();
        }
    });

    if (isModal) {
        document.body.appendChild(overlay);
    }

    return host;
}

function mountDynamicInspectorPanel(editor, mount, catalog, labels, previewOptions) {
    if (! mount) {
        return;
    }

    const renderPanel = () => {
        const selected = editor.getSelected();

        mount.innerHTML = '';

        if (! selected) {
            const empty = document.createElement('p');
            empty.className = 'vpress-gjs-dynamic-panel__empty';
            empty.textContent = labels.selectComponent ?? 'Select an element on the canvas first.';
            mount.appendChild(empty);

            return;
        }

        mountBindingForm(editor, selected, catalog, labels, () => refreshBindingPreviews(editor, previewOptions), {
            mode: 'inline',
            mount,
        });
    };

    editor.on('component:selected', renderPanel);
    editor.on('component:deselected', renderPanel);
    renderPanel();
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

async function loadBindingsPreview(bindingsPreviewUrl, force = false, repeatConfigs = []) {
    if (! bindingsPreviewUrl) {
        return {};
    }

    if (bindingsPreviewValues && ! force) {
        return bindingsPreviewValues;
    }

    const url = new URL(bindingsPreviewUrl, window.location.origin);

    if (repeatConfigs.length > 0) {
        url.searchParams.set('repeats', JSON.stringify(repeatConfigs));
    }

    const response = await fetch(url.toString(), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error(`Bindings preview request failed (${response.status})`);
    }

    const payload = await response.json();
    bindingsPreviewValues = payload.values ?? {};
    bindingsPreviewListValues = payload.listValues ?? {};

    return bindingsPreviewValues;
}

export async function refreshBindingPreviews(editor, options = {}) {
    const catalog = options.catalog ?? bindingsCatalog ?? await loadBindingsCatalog(options.bindingsUrl).catch(() => null);

    if (! catalog || ! editor?.getWrapper) {
        return;
    }

    const values = await loadBindingsPreview(
        options.bindingsPreviewUrl,
        true,
        collectRepeatPreviewConfigs(editor),
    ).catch((error) => {
        console.error('Vpress GrapesJS: could not load binding preview.', error);

        return {};
    });
    const listValues = bindingsPreviewListValues ?? {};

    editor.getWrapper().find('[data-vpress-bind]').forEach((component) => {
        const bindingKey = component.getAttributes()['data-vpress-bind'];

        if (! bindingKey) {
            return;
        }

        const option = findBindingOption(catalog, bindingKey)
            ?? (bindingKey === 'vtuts.latest.excerpt'
                ? findBindingOption(catalog, 'vtuts.latest.introduction')
                : null);
        const value = resolvePreviewValue(bindingKey, component, values, listValues, catalog);
        const element = component.getView()?.el;
        const tag = componentTag(component);
        const currentText = element?.textContent?.trim() ?? '';

        if (value == null || value === '') {
            return;
        }

        if (tag !== 'img' && tag !== 'button' && tag !== 'a' && ! isPlaceholderText(currentText) && currentText !== '') {
            return;
        }

        if (hasStructuralChildren(component)) {
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

    const values = await loadBindingsPreview(
        options.bindingsPreviewUrl,
        true,
        options.editor ? collectRepeatPreviewConfigs(options.editor) : [],
    );
    const listValues = bindingsPreviewListValues ?? {};
    const option = findBindingOption(catalog, bindingKey)
        ?? (bindingKey === 'vtuts.latest.excerpt'
            ? findBindingOption(catalog, 'vtuts.latest.introduction')
            : null);
    const value = resolvePreviewValue(bindingKey, component, values, listValues, catalog);
    const element = component.getView()?.el;
    const tag = componentTag(component);

    if (! element || value == null || value === '') {
        return;
    }

    if (hasStructuralChildren(component) && resolveBindingFieldType(bindingKey, catalog) === 'text') {
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
        editor,
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
        className: 'vpress-gjs-pn-btn',
        label: lucideIcon('link'),
        command: CMD_MAKE_DYNAMIC,
        attributes: { title: labels.makeDynamic ?? 'Make dynamic' },
    });

    editor.Panels.addButton('options', {
        id: 'vpress-clear-dynamic',
        className: 'vpress-gjs-pn-btn',
        label: lucideIcon('unlink'),
        command: CMD_CLEAR_DYNAMIC,
        attributes: { title: labels.clearDynamic ?? 'Clear dynamic binding' },
    });

    mountDynamicInspectorPanel(editor, options.dynamicMount, catalog, labels, previewOptions);

    editor.on('load', () => {
        normalizeRepeatContainers(editor, catalog);
        void refreshBindingPreviews(editor, previewOptions);
    });

    return catalog;
}
