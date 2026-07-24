/**
 * GrapesJS "Make dynamic" UI — binds selected components to server data sources.
 */

import { alertDialog } from './editor-dialog.js';
import { lucideIcon } from './editor-icons.js';
import { ensureTextLabel, extractButtonLabel } from './grapesjs-button-link.js';
import { safeFindComponents } from './tailwind-visual-style.js';
import {
    CMD_CLEAR_DYNAMIC,
    CMD_MAKE_DYNAMIC,
} from './canvas-component-toolbar.js';
import { shouldSuppressChromeSlotInspector } from './chrome-content-slot-utils.js';
import {
    inspectorSelectionNotice,
} from './chrome-editor-guards.js';
import {
    createInspectorEmptyState,
    inspectorSelectElementMessage,
} from './inspector-empty-state.js';

export const NEUTRAL_IMAGE_PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Dynamic image</text>'
    + '</svg>',
);

let bindingsCatalog = null;
let bindingsPreviewValues = null;
let bindingsPreviewListValues = null;

function componentTag(component) {
    return String(component.get('tagName') ?? '').toLowerCase();
}

function isCtaButton(component) {
    return component?.getAttributes?.()?.['data-voodbuilder-cta'] === 'true';
}

function isLinkableInteractive(component) {
    const tag = componentTag(component);

    return tag === 'button' || tag === 'a';
}

function fieldTypeMatchesComponent(fieldType, component) {
    const tag = componentTag(component);

    if (fieldType === 'image') {
        return tag === 'img';
    }

    if (fieldType === 'url') {
        return tag === 'a'
            || tag === 'button'
            || Boolean(component.getAttributes?.()['data-voodbuilder-repeat-item']);
    }

    if (tag === 'button' || isCtaButton(component)) {
        return false;
    }

    if (tag === 'a') {
        return true;
    }

    if (TEXT_BINDING_TAGS.has(tag)) {
        return ! hasBindBlockingChildren(component);
    }

    return ! hasBindBlockingChildren(component);
}

function fieldTypeLabel(type, labels = {}) {
    const map = {
        text: labels.fieldTypeText ?? 'Text',
        url: labels.fieldTypeUrl ?? 'URL',
        image: labels.fieldTypeImage ?? 'Image',
    };

    return map[type] ?? type ?? 'Text';
}

function formatFieldOptionLabel(field, labels = {}) {
    const type = fieldTypeLabel(field?.type, labels);

    return `[${type}] ${field.label}`;
}

function bindingPreviewSnippet(bindingKey, catalog, previewValues = {}) {
    const value = previewValues?.[bindingKey];

    if (value == null || value === '') {
        return null;
    }

    const text = String(value).trim();
    const type = resolveBindingFieldType(bindingKey, catalog);

    if (type === 'image') {
        const href = normalizeEditorAssetUrl(text);
        const label = href.split('/').pop()?.split('?')[0] || 'image';

        return { kind: 'image', href, label };
    }

    if (type === 'url') {
        const href = absolutePreviewUrl(text);
        const label = text.length > 48 ? `${text.slice(0, 45)}…` : text;

        return { kind: 'link', href, label };
    }

    const snippet = text.length <= 72 ? text : `${text.slice(0, 69)}…`;

    return { kind: 'text', text: snippet };
}

function normalizeEditorAssetUrl(value) {
    const text = String(value ?? '').trim();

    if (! text) {
        return text;
    }

    if (text.startsWith('//')) {
        return text;
    }

    try {
        if (/^https?:\/\//i.test(text)) {
            const url = new URL(text);

            if (url.origin === window.location.origin) {
                return `${url.pathname}${url.search}`;
            }
        }
    } catch {
        // Keep original value when URL parsing fails.
    }

    if (text.startsWith('/')) {
        return text;
    }

    return text;
}

function absolutePreviewUrl(value) {
    const text = normalizeEditorAssetUrl(value);

    if (text.startsWith('//')) {
        return `${window.location.protocol}${text}`;
    }

    if (text.startsWith('/')) {
        return `${window.location.origin}${text}`;
    }

    return text;
}

function renderCurrentBindingSummary(currentEl, bindingKey, catalog, previewValues, labels) {
    if (! currentEl) {
        return;
    }

    const formatted = bindingPreviewSnippet(bindingKey, catalog, previewValues);
    const type = resolveBindingFieldType(bindingKey, catalog);
    const summary = `${boundComponentLabel(bindingKey, catalog)} · ${fieldTypeLabel(type, labels)}`;
    const prefix = `${labels.currentBinding ?? 'Current'}: ${summary}`;

    currentEl.hidden = false;
    currentEl.replaceChildren();

    if (formatted?.kind === 'image') {
        currentEl.append(document.createTextNode(`${prefix} — `));
        const label = document.createElement('span');
        label.className = 'voodbuilder-gjs-dynamic-panel__current-filename';
        label.textContent = formatted.label;
        label.title = formatted.href;
        currentEl.append(label);

        return;
    }

    if (formatted?.kind === 'link') {
        currentEl.append(document.createTextNode(`${prefix} — `));
        const link = document.createElement('a');
        link.href = formatted.href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'voodbuilder-gjs-dynamic-panel__current-link';
        link.textContent = formatted.label;
        currentEl.append(link);

        return;
    }

    if (formatted?.kind === 'text') {
        currentEl.textContent = `${prefix} — “${formatted.text}”`;

        return;
    }

    currentEl.textContent = prefix;
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
    return extractButtonLabel(component);
}

function ensureInteractiveLabel(component, label) {
    ensureTextLabel(component, label);
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
    const isCta = isCtaButton(component);
    const attributes = { ...component.getAttributes() };

    delete attributes.type;
    delete attributes.onclick;

    attributes.href = attributes.href && attributes.href !== '' ? attributes.href : '#';
    attributes.role = 'button';

    if (isCta) {
        attributes['data-voodbuilder-cta'] = 'true';
    }

    component.set({
        tagName: 'a',
        type: isCta ? 'voodbuilder-cta-button' : resolveLinkComponentType(editor),
        editable: true,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: 'Dynamic link',
    });
    component.setAttributes(attributes);
    ensureInteractiveLabel(component, label);
}

/**
 * List Item cards that are not anchors become <a> when binding a URL field,
 * so the whole card can link without inserting a separate Link node.
 */
function morphRepeatItemToAnchor(editor, component) {
    const tag = componentTag(component);

    if (tag === 'a' || tag === 'button') {
        return;
    }

    if (! component.getAttributes?.()['data-voodbuilder-repeat-item']) {
        return;
    }

    const attributes = { ...component.getAttributes() };

    delete attributes.onclick;
    attributes.href = attributes.href && attributes.href !== '' ? attributes.href : '#';

    component.set({
        tagName: 'a',
        type: resolveLinkComponentType(editor),
        editable: true,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: 'List Item',
    });
    component.setAttributes(attributes);
}

function placeholderForBinding(sourceLabel, fieldLabel) {
    return `[${sourceLabel}: ${fieldLabel}]`;
}

function isPlaceholderText(text) {
    const value = String(text ?? '').trim();

    return value === '' || /^\[[^:]+:[^\]]+\]$/.test(value);
}

function isPlaceholderAlt(text) {
    const value = String(text ?? '').trim();

    return value === '' || value === 'Dynamic image' || isPlaceholderText(value);
}

const IMAGE_ALT_FIELD_IDS = ['title', 'name', 'label', 'company_name', 'slug'];

const IMAGE_BINDING_FIELD_IDS = new Set([
    'image',
    'featured_image',
    'cover_image',
    'logo',
    'thumbnail',
    'author_avatar',
    'avatar',
]);

function resolveImageAltValue(bindingKey, component, values, listValues, catalog) {
    const parts = String(bindingKey ?? '').split('.');
    const fieldId = parts.pop();

    if (! IMAGE_BINDING_FIELD_IDS.has(fieldId ?? '')) {
        return null;
    }

    const sourcePrefix = parts.join('.');

    for (const altFieldId of IMAGE_ALT_FIELD_IDS) {
        const altKey = `${sourcePrefix}.${altFieldId}`;
        const altValue = resolvePreviewValue(altKey, component, values, listValues, catalog);

        if (altValue != null && String(altValue).trim() !== '') {
            return String(altValue).trim();
        }
    }

    return null;
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

const INLINE_FORMATTING_TAGS = new Set([
    'span', 'strong', 'em', 'b', 'i', 'u', 'mark', 'small', 'sub', 'sup', 'br', 'wbr', 'code', 'kbd',
]);

const TEXT_BINDING_TAGS = new Set([
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'figcaption', 'blockquote', 'label', 'li', 'td', 'th',
]);

function isInlineFormattingComponent(component) {
    return INLINE_FORMATTING_TAGS.has(componentTag(component));
}

function hasBindBlockingChildren(component) {
    for (const child of component?.components?.()?.models ?? []) {
        if (isTextNodeComponent(child)) {
            continue;
        }

        if (isInlineFormattingComponent(child)) {
            if (hasBindBlockingChildren(child)) {
                return true;
            }

            continue;
        }

        return true;
    }

    return false;
}

function isRepeatContainer(component) {
    if (Boolean(component?.getAttributes?.()['data-voodbuilder-repeat'])) {
        return true;
    }

    if (component?.get?.('vpressRepeatMeta')?.key) {
        return true;
    }

    const element = component?.getEl?.() ?? component?.getView?.()?.el;

    return Boolean(element?.getAttribute?.('data-voodbuilder-repeat'));
}

function isInsideRepeatTemplate(component) {
    let current = component?.parent?.();

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-repeat'] || attrs['data-voodbuilder-repeat-item']) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isRepeatHost(component) {
    if (isInsideRepeatTemplate(component)) {
        return false;
    }

    // After Apply list repeat only the template item remains — still a list host.
    if (isRepeatContainer(component)) {
        return true;
    }

    if (! hasElementChildren(component)) {
        return false;
    }

    return Boolean(findRepeatListContainer(component));
}

function isListRepeatContainer(component) {
    if (component?.getAttributes?.()['data-voodbuilder-repeat']) {
        return true;
    }

    if (isLogoScrollItemsTrack(component)) {
        const children = listItemChildren(component);

        return children.length >= 1
            || Boolean(component.getAttributes?.()['data-voodbuilder-repeat']);
    }

    const children = listItemChildren(component);

    if (children.length < 2) {
        return false;
    }

    const classes = componentClassNames(component);
    const isLayoutType = component?.get?.('type') === 'voodbuilder-layout-container';

    // Vertical stacks (Featured sidebar, article lists) — prefer over outer grids.
    if (/\bflex-col\b/.test(classes) || /\bdivide-y\b/.test(classes) || /\bspace-y-/i.test(classes)) {
        return true;
    }

    // Peer cards of the same tag inside a layout/flex/grid/gap stack.
    // Featured grids that mix a large card (`a`) with a sidebar (`div`) stay excluded.
    if (childrenLookLikePeerItems(children)) {
        if (isLayoutType || /\bflex\b|\binline-flex\b|\bgrid\b|\bgap-|\bspace-[xy]-/.test(classes)) {
            return true;
        }
    }

    // Grid / flex-wrap rows of peer cards only.
    if (isLayoutRow(component)) {
        return childrenLookLikePeerItems(children);
    }

    return false;
}

function componentClassNames(component) {
    const fromModel = component.getClasses?.()?.join(' ') ?? '';
    const fromAttr = component.getAttributes?.()['class'] ?? '';

    return `${fromModel} ${fromAttr}`.trim();
}

function isLayoutRow(component) {
    const classes = componentClassNames(component);

    return /\bflex-wrap\b/.test(classes)
        || /\bgrid\b/.test(classes)
        || /\bgrid-cols-/i.test(classes);
}

function listItemChildren(component) {
    return (component?.components?.()?.models ?? []).filter((child) => {
        if (isTextNodeComponent(child)) {
            return false;
        }

        const attrs = child.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-inner-drop'] || attrs['data-voodbuilder-repeat-empty']) {
            return false;
        }

        return true;
    });
}

/**
 * Logo scroll track is a horizontal marquee (`flex` without flex-wrap) that still
 * needs List repeat for dynamic partner logos.
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function isLogoScrollItemsTrack(component) {
    if (! component) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (Object.prototype.hasOwnProperty.call(attrs, 'data-vb-items-root')) {
        let current = component;

        while (current) {
            const currentAttrs = current.getAttributes?.() ?? {};

            if (Object.prototype.hasOwnProperty.call(currentAttrs, 'data-voodbuilder-logo-scroll')) {
                return true;
            }

            current = current.parent?.();
        }
    }

    return /\bvb-logo-scroll__track\b/.test(componentClassNames(component));
}

/**
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
function findLogoScrollRootFrom(component) {
    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll')) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Keep Logo scroll settings Source in sync when List repeat is applied/cleared.
 *
 * @param {object|null|undefined} component
 * @param {'static'|'dynamic'} source
 */
function syncLogoScrollSourceNear(component, source) {
    const root = findLogoScrollRootFrom(component);

    if (! root) {
        return;
    }

    root.set?.('data-vb-logo-source', source);
    root.addAttributes?.({ 'data-vb-logo-source': source });
}

/**
 * @param {object[]} children
 * @returns {boolean}
 */
function childrenLookLikePeerItems(children) {
    if (children.length < 2) {
        return false;
    }

    const tags = children.map((child) => componentTag(child)).filter(Boolean);

    if (tags.length < 2) {
        return false;
    }

    return tags.every((tag) => tag === tags[0]);
}

function findRepeatListContainer(component) {
    if (! component) {
        return null;
    }

    if (isListRepeatContainer(component)) {
        return component;
    }

    for (const child of component.components?.()?.models ?? []) {
        const found = findRepeatListContainer(child);

        if (found) {
            return found;
        }
    }

    return null;
}

function resolveRepeatTargetContainer(component) {
    const listContainer = findRepeatListContainer(component);

    if (listContainer) {
        return listContainer;
    }

    if (isLayoutRow(component)) {
        return component;
    }

    const nested = findLayoutRowDescendant(component);

    if (nested) {
        return nested;
    }

    return component;
}

function bindingRejectionMessage(component, fieldType, labels) {
    if (hasBindBlockingChildren(component) && (isRepeatHost(component) || findRepeatListContainer(component))) {
        return labels.repeatListInstead
            ?? 'Use List repeat on this container, then List item inside the card.';
    }

    if (fieldType !== 'url' && (componentTag(component) === 'button' || isCtaButton(component))) {
        return labels.buttonUrlOnly
            ?? 'Buttons keep a static label such as “Read more”. Bind a URL field to make the link dynamic, then double-click the button to edit the label.';
    }

    const tag = componentTag(component);

    return labels.bindingNeedsLeaf
        ?? `Bind text and images on the inner element (h2, p, img, a), not on the ${tag || 'container'}.`;
}

function preferredFieldIdForComponent(fields, component) {
    const compatible = (fields ?? []).filter((field) => fieldTypeMatchesComponent(field?.type ?? 'text', component));

    if (compatible.length === 0) {
        return null;
    }

    const urlField = compatible.find((field) => field.type === 'url'
        || ['url', 'slug', 'link', 'permalink', 'href'].includes(String(field.id ?? '').toLowerCase()));

    return urlField?.id ?? compatible[0]?.id ?? null;
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

function repeatMetaFromAttributes(attrs = {}) {
    const key = attrs['data-voodbuilder-repeat'];

    if (! key) {
        return null;
    }

    return {
        key,
        limit: attrs['data-voodbuilder-repeat-limit'] ?? '3',
        offset: attrs['data-voodbuilder-repeat-offset'] ?? '0',
        sort: attrs['data-voodbuilder-repeat-sort'] ?? 'id',
        sortDir: attrs['data-voodbuilder-repeat-sort-dir'] ?? 'desc',
        filters: parseRepeatFiltersAttribute(attrs['data-voodbuilder-repeat-filter']),
    };
}

function parseRepeatFiltersAttribute(raw) {
    if (! raw || typeof raw !== 'string') {
        return {};
    }

    try {
        const parsed = JSON.parse(raw);

        if (! parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            return {};
        }

        const filters = {};

        for (const [key, value] of Object.entries(parsed)) {
            const id = String(key ?? '').trim();
            const selected = String(value ?? '').trim();

            if (id && selected) {
                filters[id] = selected;
            }
        }

        return filters;
    } catch {
        return {};
    }
}

function serializeRepeatFiltersAttribute(filters = {}) {
    const clean = {};

    for (const [key, value] of Object.entries(filters ?? {})) {
        const id = String(key ?? '').trim();
        const selected = String(value ?? '').trim();

        if (id && selected) {
            clean[id] = selected;
        }
    }

    const keys = Object.keys(clean).sort();

    if (keys.length === 0) {
        return null;
    }

    const ordered = {};

    for (const key of keys) {
        ordered[key] = clean[key];
    }

    return JSON.stringify(ordered);
}

function applyRepeatMetaToComponent(component, meta) {
    if (! component || ! meta?.key) {
        return;
    }

    component.set('vpressRepeatMeta', meta, { silent: true });

    const attributes = {
        'data-voodbuilder-repeat': meta.key,
        'data-voodbuilder-repeat-limit': String(meta.limit ?? '3'),
        'data-voodbuilder-repeat-offset': String(meta.offset ?? '0'),
        'data-voodbuilder-repeat-sort': meta.sort ?? 'id',
        'data-voodbuilder-repeat-sort-dir': meta.sortDir ?? 'desc',
    };
    const filterAttr = serializeRepeatFiltersAttribute(meta.filters);

    if (filterAttr) {
        attributes['data-voodbuilder-repeat-filter'] = filterAttr;
    } else {
        component.removeAttributes('data-voodbuilder-repeat-filter');
    }

    component.addAttributes(attributes);
}

function syncRepeatMetaFromAttributes(component) {
    const meta = repeatMetaFromAttributes(component.getAttributes?.() ?? {});

    if (meta) {
        component.set('vpressRepeatMeta', meta, { silent: true });
    }
}

function restoreRepeatMetaOnComponent(component) {
    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-repeat']) {
        syncRepeatMetaFromAttributes(component);

        return;
    }

    const meta = component.get('vpressRepeatMeta');

    if (meta?.key) {
        applyRepeatMetaToComponent(component, meta);
    }
}

function syncRepeatAttributesToDom(component) {
    const meta = repeatMetaFromAttributes(component.getAttributes?.() ?? {});

    if (! meta) {
        return;
    }

    const element = component.getView()?.el;

    if (! element) {
        return;
    }

    element.setAttribute('data-voodbuilder-repeat', meta.key);
    element.setAttribute('data-voodbuilder-repeat-limit', String(meta.limit));
    element.setAttribute('data-voodbuilder-repeat-offset', String(meta.offset ?? '0'));
    element.setAttribute('data-voodbuilder-repeat-sort', meta.sort);
    element.setAttribute('data-voodbuilder-repeat-sort-dir', meta.sortDir);

    const filterAttr = serializeRepeatFiltersAttribute(meta.filters);

    if (filterAttr) {
        element.setAttribute('data-voodbuilder-repeat-filter', filterAttr);
    } else {
        element.removeAttribute('data-voodbuilder-repeat-filter');
    }
}

function migrateRepeatPlacement(component) {
    const repeatKey = component.getAttributes?.()['data-voodbuilder-repeat'];

    if (! repeatKey) {
        return resolveRepeatTargetContainer(component);
    }

    const repeatTarget = resolveRepeatTargetContainer(component);

    if (repeatTarget === component) {
        syncRepeatMetaFromAttributes(repeatTarget);

        return repeatTarget;
    }

    const meta = repeatMetaFromAttributes(component.getAttributes?.() ?? {})
        ?? component.get('vpressRepeatMeta')
        ?? { key: repeatKey };

    applyRepeatMetaToComponent(repeatTarget, meta);
    repeatTarget.removeAttributes('data-voodbuilder-repeat-item');
    component.removeAttributes('data-voodbuilder-repeat');
    component.removeAttributes('data-voodbuilder-repeat-limit');
    component.removeAttributes('data-voodbuilder-repeat-offset');
    component.removeAttributes('data-voodbuilder-repeat-sort');
    component.removeAttributes('data-voodbuilder-repeat-sort-dir');
    component.removeAttributes('data-voodbuilder-repeat-filter');

    return repeatTarget;
}

function clearRepeatAttributes(component) {
    component.removeAttributes('data-voodbuilder-repeat');
    component.removeAttributes('data-voodbuilder-repeat-limit');
    component.removeAttributes('data-voodbuilder-repeat-offset');
    component.removeAttributes('data-voodbuilder-repeat-sort');
    component.removeAttributes('data-voodbuilder-repeat-sort-dir');
    component.removeAttributes('data-voodbuilder-repeat-filter');
    safeFindComponents(component, '[data-voodbuilder-repeat-item]').forEach((child) => {
        child.removeAttributes('data-voodbuilder-repeat-item');
    });
}

function repeatSortFromContainer(container) {
    return {
        sort: container?.getAttributes?.()['data-voodbuilder-repeat-sort'] || 'id',
        sortDir: container?.getAttributes?.()['data-voodbuilder-repeat-sort-dir'] || 'desc',
    };
}

function repeatOffsetFromContainer(container) {
    return Number(container?.getAttributes?.()['data-voodbuilder-repeat-offset'] || 0);
}

function repeatFiltersFromContainer(container) {
    return parseRepeatFiltersAttribute(container?.getAttributes?.()['data-voodbuilder-repeat-filter']);
}

function filtersFingerprint(filters = {}) {
    const clean = {};

    for (const [key, value] of Object.entries(filters ?? {})) {
        const id = String(key ?? '').trim();
        const selected = String(value ?? '').trim();

        if (id && selected) {
            clean[id] = selected;
        }
    }

    const keys = Object.keys(clean).sort();

    if (keys.length === 0) {
        return '';
    }

    return keys.map((key) => `${key}:${clean[key]}`).join(',');
}

function repeatListValuesKey(repeatKey, sort, sortDir, offset = 0, filters = {}) {
    const base = `${repeatKey}|${sort || 'id'}|${sortDir || 'desc'}|${Number(offset) || 0}`;
    const fingerprint = filtersFingerprint(filters);

    return fingerprint ? `${base}|${fingerprint}` : base;
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

    safeFindComponents(editor?.getWrapper?.(), '[data-voodbuilder-repeat]').forEach((component) => {
        const repeatKey = component.getAttributes()['data-voodbuilder-repeat'];

        if (! repeatKey) {
            return;
        }

        const repeatTarget = component;

        const { sort, sortDir } = repeatSortFromContainer(repeatTarget);
        const offset = repeatOffsetFromContainer(repeatTarget);
        const filters = repeatFiltersFromContainer(repeatTarget);
        const limit = Math.max(
            12,
            Number(repeatTarget.getAttributes()['data-voodbuilder-repeat-limit'] || 12),
        );
        const cacheKey = repeatListValuesKey(repeatKey, sort, sortDir, offset, filters);

        configs.set(cacheKey, {
            key: repeatKey,
            sort,
            dir: sortDir,
            limit,
            offset,
            filters,
        });
    });

    return [...configs.values()];
}

function formatRepeatSummary(repeatKey, limit, sort, sortDir, catalog, labels, offset = 0, filters = {}) {
    const sortLabel = sortFieldsForRepeatSource(catalog, repeatKey)
        .find((field) => field.id === sort)?.label ?? sort;
    const dirLabel = sortDir === 'asc'
        ? (labels.repeatSortAsc ?? 'Ascending')
        : (labels.repeatSortDesc ?? 'Descending');
    const offsetPart = Number(offset) > 0
        ? ` · ${labels.repeatOffset ?? 'Offset'} ${offset}`
        : '';
    const filterDefs = filtersForRepeatSource(catalog, repeatKey);
    const filterParts = [];

    for (const [filterId, value] of Object.entries(filters ?? {})) {
        if (! value) {
            continue;
        }

        const definition = filterDefs.find((item) => item.id === filterId);
        const optionLabel = definition?.options?.find((option) => option.value === String(value))?.label
            ?? value;
        const filterLabel = definition?.label ?? filterId;

        filterParts.push(`${filterLabel}: ${optionLabel}`);
    }

    const filterPart = filterParts.length > 0
        ? ` · ${filterParts.join(', ')}`
        : '';

    return `${repeatKey} (${limit})${offsetPart}${filterPart} · ${sortLabel} · ${dirLabel}`;
}

function filtersForRepeatSource(catalog, repeatSourceId) {
    const source = (catalog?.repeatSources ?? []).find((item) => item.id === repeatSourceId);

    return Array.isArray(source?.filters) ? source.filters : [];
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
        const repeatKey = current.getAttributes?.()['data-voodbuilder-repeat'];

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
    return fieldTypeMatchesComponent(fieldType, component);
}

function resolveFieldBindingTarget(component, fieldType) {
    if (canAcceptFieldBinding(component, fieldType)) {
        return component;
    }

    const matches = [];

    const visit = (node) => {
        for (const child of node.components?.()?.models ?? []) {
            if (isTextNodeComponent(child)) {
                continue;
            }

            if (canAcceptFieldBinding(child, fieldType)) {
                matches.push(child);
            }

            visit(child);
        }
    };

    visit(component);

    return matches.length === 1 ? matches[0] : null;
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
        const repeatKey = current.getAttributes?.()['data-voodbuilder-repeat'];

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
                offset: repeatOffsetFromContainer(container),
                filters: repeatFiltersFromContainer(container),
                ...repeatSortFromContainer(container),
            };
        }

        current = current.parent?.();
    }

    let repeatItem = null;
    current = component;

    while (current) {
        if (current.getAttributes?.()['data-voodbuilder-repeat-item']) {
            repeatItem = current;
            break;
        }

        current = current.parent?.();
    }

    if (! repeatItem?.parent) {
        return null;
    }

    const container = repeatItem.parent();
    const repeatKey = container.getAttributes()['data-voodbuilder-repeat'] ?? null;
    const siblings = container.components().models.filter(
        (child) => child.getAttributes()['data-voodbuilder-repeat-item'],
    );
    const index = siblings.indexOf(repeatItem);

    return {
        container,
        repeatKey,
        index: index >= 0 ? index : 0,
        offset: repeatOffsetFromContainer(container),
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
    first.addAttributes({ 'data-voodbuilder-repeat-item': '1' });

    while (target.components().length > 1) {
        target.components().at(target.components().length - 1)?.remove();
    }

    target.components().forEach((child, index) => {
        if (index === 0) {
            child.addAttributes({ 'data-voodbuilder-repeat-item': '1' });
        } else {
            child.removeAttributes('data-voodbuilder-repeat-item');
        }
    });

    return first;
}

function migrateBindingsInTree(editor, component, catalog) {
    component?.find?.('[data-voodbuilder-bind]')?.forEach((bound) => {
        const bindingKey = bound.getAttributes()['data-voodbuilder-bind'];

        if (! bindingKey?.includes('.latest.')) {
            return;
        }

        const migrated = migrateLatestToItemKey(bindingKey);

        bound.addAttributes({ 'data-voodbuilder-bind': migrated });
        configureBoundComponent(editor, bound, catalog);
    });
}

function itemFieldIdFromBindingKey(bindingKey, catalog = null) {
    const parsed = catalog ? parseBindingKey(bindingKey, catalog) : null;

    if (parsed?.fieldId) {
        return parsed.fieldId;
    }

    const match = String(bindingKey ?? '').match(/\.(?:item|latest)\.(.+)$/);

    if (match?.[1]) {
        return match[1];
    }

    return String(bindingKey ?? '').split('.').pop() ?? '';
}

function resolvePreviewValue(bindingKey, component, values, listValues, catalog) {
    if (bindingKey.includes('.item.')) {
        const repeatContext = repeatCardIndex(component);
        const fieldId = itemFieldIdFromBindingKey(bindingKey, catalog);
        const repeatKey = repeatContext?.repeatKey ?? inferRepeatListKey(bindingKey, catalog);
        const index = repeatContext?.index ?? 0;
        const listKey = repeatListValuesKey(
            repeatKey,
            repeatContext?.sort,
            repeatContext?.sortDir,
            repeatContext?.offset,
            repeatContext?.filters,
        );
        const row = listValues?.[listKey]?.[index] ?? listValues?.[repeatKey]?.[index];
        const value = row?.[fieldId]
            ?? row?.[bindingKey.split('.').pop()];

        if (value) {
            return value;
        }

        const fallbackRow = listValues?.[listKey]?.[0] ?? listValues?.[repeatKey]?.[0];
        const fallback = fallbackRow?.[fieldId]
            ?? fallbackRow?.[bindingKey.split('.').pop()];

        if (fallback) {
            return fallback;
        }
    }

    return values[bindingKey]
        ?? (bindingKey === 'vtuts.latest.excerpt' ? values['vtuts.latest.introduction'] : undefined);
}

function ensureRepeatContainers(editor, catalog) {
    const processed = new Set();

    const finalizeRepeatTarget = (repeatTarget) => {
        if (! repeatTarget || processed.has(repeatTarget.cid)) {
            return;
        }

        processed.add(repeatTarget.cid);
        restoreRepeatMetaOnComponent(repeatTarget);
        syncRepeatAttributesToDom(repeatTarget);
        refreshListContainerLayerName(repeatTarget);

        const template = collapseRepeatTemplate(repeatTarget);

        if (template) {
            migrateBindingsInTree(editor, template, catalog);
        }
    };

    const restoreMissingRepeat = (repeatTarget) => {
        if (repeatTarget.getAttributes()['data-voodbuilder-repeat']) {
            return;
        }

        const storedMeta = repeatTarget.get('vpressRepeatMeta');
        const sampleBinding = safeFindComponents(repeatTarget, '[data-voodbuilder-bind]')[0]?.getAttributes?.()['data-voodbuilder-bind'];
        const repeatKey = storedMeta?.key
            ?? (sampleBinding ? inferRepeatListKey(sampleBinding, catalog) : catalog?.repeatSources?.[0]?.id);

        if (! repeatKey) {
            return;
        }

        applyRepeatMetaToComponent(repeatTarget, {
            key: repeatKey,
            limit: storedMeta?.limit ?? repeatTarget.getAttributes()['data-voodbuilder-repeat-limit'] ?? '3',
            offset: storedMeta?.offset ?? repeatTarget.getAttributes()['data-voodbuilder-repeat-offset'] ?? '0',
            sort: storedMeta?.sort ?? repeatTarget.getAttributes()['data-voodbuilder-repeat-sort'] ?? 'id',
            sortDir: storedMeta?.sortDir ?? repeatTarget.getAttributes()['data-voodbuilder-repeat-sort-dir'] ?? 'desc',
            filters: storedMeta?.filters ?? repeatFiltersFromContainer(repeatTarget),
        });
    };

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-repeat]').forEach((container) => {
        finalizeRepeatTarget(migrateRepeatPlacement(container));
    });

    const seen = new Set();

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-repeat-item]').forEach((item) => {
        const container = item.parent();

        if (! container || seen.has(container.cid)) {
            return;
        }

        seen.add(container.cid);

        const repeatTarget = resolveRepeatTargetContainer(container);

        restoreMissingRepeat(repeatTarget);
        finalizeRepeatTarget(repeatTarget);

        const repeatItems = container.components().models.filter(
            (child) => child.getAttributes()['data-voodbuilder-repeat-item'],
        );

        if (repeatItems.length <= 1) {
            return;
        }

        collapseRepeatTemplate(repeatTarget);
        finalizeRepeatTarget(repeatTarget);
    });
}

let repeatMaintainTimer = null;

function scheduleRepeatMaintenance(editor, catalog, previewOptions) {
    window.clearTimeout(repeatMaintainTimer);
    repeatMaintainTimer = window.setTimeout(() => {
        ensureRepeatContainers(editor, catalog);

        const visit = (component) => {
            refreshListContainerLayerName(component);

            for (const child of component?.components?.()?.models ?? []) {
                visit(child);
            }
        };

        visit(editor?.getWrapper?.());
        void refreshBindingPreviews(editor, previewOptions);
    }, 120);
}

function paintPreviewOnElement(component, value, fieldType, { altText = null } = {}) {
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
        const src = normalizeEditorAssetUrl(text);
        element.setAttribute('src', src);
        component.addAttributes({ src }, { silent: true });
        component.set('src', src, { silent: true });

        const resolvedAlt = altText ?? (isPlaceholderAlt(component.getAttributes()?.alt) ? '' : component.getAttributes()?.alt);

        if (resolvedAlt) {
            element.setAttribute('alt', resolvedAlt);
            component.addAttributes({ alt: resolvedAlt }, { silent: true });
            component.set('alt', resolvedAlt, { silent: true });
        }

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

    if (hasBindBlockingChildren(component)) {
        return;
    }

    element.textContent = text;
    component.set('content', text, { silent: true });
}

function applyPreviewValue(component, bindingKey, option, value, previewContext = {}) {
    const fieldType = option?.field?.type ?? 'text';

    if (fieldType === 'image') {
        const altText = resolveImageAltValue(
            bindingKey,
            component,
            previewContext.values ?? {},
            previewContext.listValues ?? {},
            previewContext.catalog ?? null,
        );

        paintPreviewOnElement(component, value, fieldType, { altText });

        return;
    }

    paintPreviewOnElement(component, value, fieldType);
}

function applyBindingToComponent(editor, component, bindingKey, option, labels = {}) {
    const sourceLabel = option?.source?.label ?? 'Dynamic';
    const fieldLabel = option?.field?.label ?? bindingKey;
    const fieldType = option?.field?.type ?? 'text';
    const sourceId = option?.source?.id ?? '';
    const placeholder = placeholderForBinding(sourceLabel, fieldLabel);

    if (hasStructuralChildren(component) && component.getAttributes()['data-voodbuilder-repeat']) {
        void alertDialog({
            message: labels.repeatContainerNoBind ?? 'Bind fields inside the card, not on the list container.',
            labels,
        });

        return;
    }

    if (isRepeatListSource(sourceId)) {
        void alertDialog({
            message: labels.repeatListNotField ?? 'Use List repeat on the container, not a field binding.',
            labels,
        });

        return;
    }

    const bindTarget = resolveFieldBindingTarget(component, fieldType);

    if (! bindTarget) {
        void alertDialog({
            message: bindingRejectionMessage(component, fieldType, labels),
            labels,
        });

        return null;
    }

    const effectiveTag = componentTag(bindTarget);

    if (fieldType === 'url' && effectiveTag === 'button') {
        morphUrlButtonToAnchor(editor, bindTarget);
    }

    if (
        fieldType === 'url'
        && componentTag(bindTarget) !== 'a'
        && componentTag(bindTarget) !== 'button'
        && bindTarget.getAttributes?.()['data-voodbuilder-repeat-item']
    ) {
        morphRepeatItemToAnchor(editor, bindTarget);
    }

    bindTarget.addAttributes({
        'data-voodbuilder-bind': bindingKey,
    });
    bindTarget.addClass('voodbuilder-gjs-bound');

    const boundTag = componentTag(bindTarget);
    const isRepeatItem = Boolean(bindTarget.getAttributes?.()['data-voodbuilder-repeat-item']);
    const urlOnInteractive = fieldType === 'url' && (boundTag === 'button' || boundTag === 'a');

    bindTarget.set({
        editable: urlOnInteractive && ! isRepeatItem,
        highlightable: true,
        selectable: true,
        layerable: true,
        name: isRepeatItem && fieldType === 'url'
            ? 'List Item'
            : (urlOnInteractive ? 'Dynamic link' : `Dynamic: ${fieldLabel}`),
    });

    if (boundTag === 'img' && fieldType === 'image') {
        bindTarget.addAttributes({
            src: NEUTRAL_IMAGE_PLACEHOLDER,
            alt: '',
        });
        paintPreviewOnElement(bindTarget, NEUTRAL_IMAGE_PLACEHOLDER, 'image');

        return bindTarget;
    }

    if (fieldType === 'url') {
        bindTarget.addAttributes({ href: '#' });
        bindTarget.removeAttributes('onclick');

        if (! isRepeatItem) {
            ensureInteractiveLabel(bindTarget, extractInteractiveLabel(bindTarget));
        }

        return bindTarget;
    }

    if (fieldType === 'text') {
        paintPreviewOnElement(bindTarget, placeholder, 'text');
    }

    return bindTarget;
}

function clearBindingFromComponent(component) {
    component.removeAttributes('data-voodbuilder-bind');
    component.removeAttributes('onclick');
    component.removeClass('voodbuilder-gjs-bound');

    const tag = componentTag(component);
    const isCta = isCtaButton(component);

    component.set({
        editable: isCta || tag === 'a' || tag === 'button',
        name: isCta ? 'Button' : component.get('name'),
    });
}

function configureBoundComponent(editor, component, catalog) {
    const bindingKey = component.getAttributes()['data-voodbuilder-bind'];

    if (! bindingKey) {
        return;
    }

    const fieldType = resolveBindingFieldType(bindingKey, catalog);

    if (fieldType !== 'url' && (componentTag(component) === 'button' || isCtaButton(component))) {
        component.set({
            editable: true,
            highlightable: true,
            selectable: true,
            layerable: true,
            name: 'Button',
        });
        ensureInteractiveLabel(component, extractInteractiveLabel(component));

        return;
    }

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
    component.addClass('voodbuilder-gjs-bound');

    if (fieldType === 'url' && (tag === 'button' || tag === 'a')) {
        ensureInteractiveLabel(component, extractInteractiveLabel(component));
    }
}

export function registerBoundComponentType(editor) {
    if (editor.__voodbuilderBoundTypesRegistered) {
        return;
    }

    editor.__voodbuilderBoundTypesRegistered = true;

    const domComponents = editor.DomComponents;

    for (const [typeName, matcher] of [
        ['voodbuilder-repeat-host', (element) => element?.hasAttribute?.('data-voodbuilder-repeat') === true],
        ['voodbuilder-repeat-item', (element) => element?.hasAttribute?.('data-voodbuilder-repeat-item') === true],
    ]) {
        domComponents.addType(typeName, {
            isComponent: matcher,
            model: {
                defaults: {
                    name: typeName === 'voodbuilder-repeat-host' ? 'List repeat' : 'List item',
                    draggable: true,
                    droppable: true,
                    removable: true,
                    copyable: true,
                },
                init() {
                    restoreRepeatMetaOnComponent(this);
                    refreshListContainerLayerName(this);

                    for (const attribute of [
                        'data-voodbuilder-repeat',
                        'data-voodbuilder-repeat-limit',
                        'data-voodbuilder-repeat-offset',
                        'data-voodbuilder-repeat-sort',
                        'data-voodbuilder-repeat-sort-dir',
                        'data-voodbuilder-repeat-filter',
                    ]) {
                        this.on(`change:attributes:${attribute}`, () => {
                            syncRepeatMetaFromAttributes(this);
                            syncRepeatAttributesToDom(this);
                            refreshListContainerLayerName(this);
                        });
                    }
                },
            },
        });
    }

    const defaultType = domComponents.getType('default');
    const defaultModel = defaultType?.model;

    domComponents.addType('voodbuilder-bound-image', {
        extend: 'image',
        isComponent: (element) => element?.tagName === 'IMG'
            && element?.hasAttribute?.('data-voodbuilder-bind') === true,
        model: {
            defaults: {
                highlightable: true,
                selectable: true,
                layerable: true,
            },
        },
    });

    domComponents.addType('voodbuilder-bound', {
        extend: 'default',
        isComponent: (element) => element?.tagName !== 'IMG'
            && element?.hasAttribute?.('data-voodbuilder-bind') === true,
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

function createBindingPanelHtml({ includeModalChrome = false } = {}) {
    const panel = `
            <div class="voodbuilder-gjs-dynamic-panel">
                <p class="voodbuilder-gjs-dynamic-panel__hint" data-bind-hint></p>
                <p class="voodbuilder-gjs-dynamic-panel__current" hidden></p>
                <div class="voodbuilder-gjs-dynamic-panel__field-bind" data-field-bind-panel>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-bind-source-label></span>
                        <select class="voodbuilder-gjs-bindings-modal__select" data-bind-source></select>
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label" data-bind-field-search-wrap>
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-bind-field-search-label></span>
                        <input type="search" class="voodbuilder-gjs-bindings-modal__input" data-bind-field-search autocomplete="off" />
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-bind-field-label></span>
                        <select class="voodbuilder-gjs-bindings-modal__select" data-bind-field disabled></select>
                    </label>
                    <div class="voodbuilder-gjs-dynamic-panel__actions">
                        <button type="button" class="voodbuilder-gjs-bindings-modal__button voodbuilder-gjs-bindings-modal__button--compact" data-bind-clear></button>
                        <button type="button" class="voodbuilder-gjs-bindings-modal__button voodbuilder-gjs-bindings-modal__button--primary" data-bind-apply hidden></button>
                    </div>
                </div>
                <div class="voodbuilder-gjs-dynamic-panel__repeat" data-repeat-panel hidden>
                    <p class="voodbuilder-gjs-dynamic-panel__repeat-title"></p>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-repeat-source-label></span>
                        <select class="voodbuilder-gjs-bindings-modal__select" data-repeat-source></select>
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-repeat-limit-label></span>
                        <input type="number" min="1" max="24" class="voodbuilder-gjs-bindings-modal__input" data-repeat-limit value="3" />
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-repeat-offset-label></span>
                        <input type="number" min="0" max="100" class="voodbuilder-gjs-bindings-modal__input" data-repeat-offset value="0" />
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-repeat-sort-label></span>
                        <select class="voodbuilder-gjs-bindings-modal__select" data-repeat-sort></select>
                    </label>
                    <label class="voodbuilder-gjs-bindings-modal__label">
                        <span class="voodbuilder-gjs-bindings-modal__label-text" data-repeat-sort-dir-label></span>
                        <select class="voodbuilder-gjs-bindings-modal__select" data-repeat-sort-dir>
                            <option value="desc"></option>
                            <option value="asc"></option>
                        </select>
                    </label>
                    <div data-repeat-filters></div>
                    <p class="voodbuilder-gjs-dynamic-panel__hint" data-repeat-template-hint></p>
                    <div class="voodbuilder-gjs-dynamic-panel__actions">
                        <button type="button" class="voodbuilder-gjs-bindings-modal__button voodbuilder-gjs-bindings-modal__button--primary" data-repeat-apply></button>
                        <button type="button" class="voodbuilder-gjs-bindings-modal__button voodbuilder-gjs-bindings-modal__button--compact" data-repeat-clear></button>
                    </div>
                    <p class="voodbuilder-gjs-dynamic-panel__current" data-repeat-current hidden></p>
                </div>
            </div>
    `;

    if (! includeModalChrome) {
        return panel;
    }

    return `
        <div class="voodbuilder-gjs-bindings-modal__dialog" role="dialog" aria-modal="true">
            <h2 class="voodbuilder-gjs-bindings-modal__title" data-bind-modal-title></h2>
            ${panel}
            <div class="voodbuilder-gjs-bindings-modal__actions" data-bind-modal-footer>
                <button type="button" class="voodbuilder-gjs-bindings-modal__button" data-bind-cancel></button>
            </div>
        </div>
    `;
}

function createModal() {
    const overlay = document.createElement('div');
    overlay.className = 'voodbuilder-gjs-bindings-modal';
    overlay.innerHTML = createBindingPanelHtml({ includeModalChrome: true });

    return overlay;
}

function openBindingModal(editor, component, catalog, labels, onApplied) {
    mountBindingForm(editor, component, catalog, labels, onApplied, { mode: 'modal' });
}

function refreshListContainerLayerName(component) {
    if (! component?.set) {
        return;
    }

    if (component.getAttributes?.()['data-voodbuilder-repeat']) {
        component.set('name', 'List repeat');

        return;
    }

    if (isListRepeatContainer(component)) {
        const current = component.get('name');

        if (! current || current === 'Layout' || current === 'Div' || current === 'Box') {
            component.set('name', 'List');
        }
    }
}

function mountBindingForm(editor, component, catalog, labels, onApplied, { mode = 'inline', mount = null } = {}) {
    const groups = catalog?.groups ?? [];
    const repeatSources = catalog?.repeatSources ?? [];
    const listHost = isRepeatHost(component);

    if (groups.length === 0 && ! (listHost && repeatSources.length > 0)) {
        // Inline Dynamic tab: never block the editor with a modal when no sources exist.
        if (mode === 'inline' && mount) {
            mount.replaceChildren();
            const empty = document.createElement('p');
            empty.className = 'voodbuilder-gjs-dynamic-panel__empty';
            empty.textContent = labels.noSources
                ?? 'No dynamic data sources are registered yet.';
            mount.appendChild(empty);

            return null;
        }

        void alertDialog({
            message: labels.noSources,
            labels,
        });

        return null;
    }

    const isModal = mode === 'modal';
    const overlay = isModal ? createModal() : null;
    const host = isModal ? overlay : mount;

    if (! host) {
        return null;
    }

    if (! isModal) {
        host.innerHTML = createBindingPanelHtml();
    }

    const hintEl = host.querySelector('[data-bind-hint]') ?? host.querySelector('.voodbuilder-gjs-dynamic-panel__hint');

    if (hintEl) {
        hintEl.textContent = listHost
            ? (labels.repeatContainerHint ?? labels.inspectorHint ?? 'Configure the list, then bind fields inside the card.')
            : isLinkableInteractive(component) && (componentTag(component) === 'button' || isCtaButton(component))
                ? (labels.buttonUrlHint ?? 'Bind a URL field; double-click to edit the label.')
                : isInsideRepeatTemplate(component)
                    ? (labels.repeatItemHint ?? 'Pick a List item field. For a card link, bind [URL] Slug on List Item.')
                    : (labels.inspectorHint ?? 'Connect this element to live data.');
    }

    const fieldBindPanel = host.querySelector('[data-field-bind-panel]');

    if (fieldBindPanel && listHost) {
        fieldBindPanel.hidden = true;
    }

    const sourceLabel = host.querySelector('[data-bind-source-label]');
    const fieldLabel = host.querySelector('[data-bind-field-label]');
    const fieldSearchLabel = host.querySelector('[data-bind-field-search-label]');

    if (sourceLabel) {
        sourceLabel.textContent = labels.modalSource ?? 'Data source';
    }

    if (fieldSearchLabel) {
        fieldSearchLabel.textContent = labels.fieldSearch ?? 'Search fields';
    }

    if (fieldLabel) {
        fieldLabel.textContent = labels.modalField ?? 'Field';
    }

    const clearButton = host.querySelector('[data-bind-clear]');

    if (clearButton) {
        clearButton.textContent = labels.clearDynamic ?? 'Clear binding';
    }

    const applyButton = host.querySelector('[data-bind-apply]');

    if (applyButton && isModal && ! listHost) {
        applyButton.hidden = false;
        applyButton.textContent = labels.modalApply ?? 'Apply';
    }

    const modalTitle = host.querySelector('[data-bind-modal-title]');

    if (modalTitle) {
        modalTitle.textContent = listHost
            ? (labels.repeatList ?? 'List repeat')
            : (labels.modalTitle ?? 'Connect to live data');
    }

    const repeatPanel = host.querySelector('[data-repeat-panel]');

    if (repeatPanel) {
        const title = repeatPanel.querySelector('.voodbuilder-gjs-dynamic-panel__repeat-title');

        if (title) {
            title.textContent = labels.repeatList ?? 'List repeat';
        }

        repeatPanel.querySelector('[data-repeat-source-label]').textContent = labels.repeatSource ?? 'Repeat list';
        repeatPanel.querySelector('[data-repeat-limit-label]').textContent = labels.repeatLimit ?? 'Items';
        const offsetLabel = repeatPanel.querySelector('[data-repeat-offset-label]');

        if (offsetLabel) {
            offsetLabel.textContent = labels.repeatOffset ?? 'Skip first';
        }

        repeatPanel.querySelector('[data-repeat-sort-label]').textContent = labels.repeatSort ?? 'Sort by';
        repeatPanel.querySelector('[data-repeat-sort-dir-label]').textContent = labels.repeatSortDir ?? 'Direction';
        repeatPanel.querySelector('[data-repeat-sort-dir] option[value="desc"]').textContent = labels.repeatSortDesc ?? 'Descending';
        repeatPanel.querySelector('[data-repeat-sort-dir] option[value="asc"]').textContent = labels.repeatSortAsc ?? 'Ascending';
        const templateHint = repeatPanel.querySelector('[data-repeat-template-hint]');

        if (templateHint) {
            templateHint.textContent = labels.repeatTemplateHint
                ?? 'One template card in the editor; all items on the public page.';
        }

        repeatPanel.querySelector('[data-repeat-apply]').textContent = labels.applyRepeat ?? 'Apply list repeat';
        repeatPanel.querySelector('[data-repeat-clear]').textContent = labels.clearRepeat ?? 'Clear list repeat';
    }

    host.querySelectorAll('[data-bind-cancel]').forEach((button) => {
        button.textContent = labels.modalCancel ?? 'Cancel';

        if (isModal) {
            button.hidden = false;
        }
    });

    // One Cancel only (modal footer). Clear list / Clear binding stay as separate actions.
    if (isModal) {
        const footer = host.querySelector('[data-bind-modal-footer]');

        if (footer) {
            footer.hidden = false;
        }
    }

    const repeatSourceSelect = host.querySelector('[data-repeat-source]');
    const repeatLimitInput = host.querySelector('[data-repeat-limit]');
    const repeatOffsetInput = host.querySelector('[data-repeat-offset]');
    const repeatSortSelect = host.querySelector('[data-repeat-sort]');
    const repeatSortDirSelect = host.querySelector('[data-repeat-sort-dir]');
    const repeatApplyButton = host.querySelector('[data-repeat-apply]');
    const repeatClearButton = host.querySelector('[data-repeat-clear]');
    const repeatCurrentEl = host.querySelector('[data-repeat-current]');
    const sourceSelect = host.querySelector('[data-bind-source]');
    const fieldSearchInput = host.querySelector('[data-bind-field-search]');
    const fieldSelect = host.querySelector('[data-bind-field]');
    const currentEl = host.querySelector('.voodbuilder-gjs-dynamic-panel__current:not([data-repeat-current])')
        ?? host.querySelector('.voodbuilder-gjs-dynamic-panel__current');

    const close = () => {
        if (overlay) {
            overlay.remove();
        }
    };

    if (sourceSelect && ! listHost) {
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

            if (optgroup.children.length > 0) {
                sourceSelect.appendChild(optgroup);
            }
        }
    }

    const populateFields = (autoApply = false, { searchActive = false, forceFieldId = null } = {}) => {
        if (! sourceSelect || ! fieldSelect || listHost) {
            return;
        }

        const sourceId = sourceSelect.value;
        const source = groups
            .flatMap((group) => group.sources ?? [])
            .find((item) => item.id === sourceId);
        const query = String(fieldSearchInput?.value ?? '').trim().toLowerCase();
        const previousValue = forceFieldId || fieldSelect.value;

        fieldSelect.innerHTML = '';
        fieldSelect.disabled = ! source;

        const compatibleFields = [];

        for (const field of source?.fields ?? []) {
            const isForced = forceFieldId && field.id === forceFieldId;

            if (! isForced && ! fieldTypeMatchesComponent(field?.type ?? 'text', component)) {
                continue;
            }

            const haystack = `${field.label} ${field.id} ${field.type ?? ''} ${field.group ?? ''}`.toLowerCase();

            if (query && ! haystack.includes(query) && ! isForced) {
                continue;
            }

            compatibleFields.push(field);
        }

        const appendFieldOption = (field) => {
            const option = document.createElement('option');
            option.value = field.id;
            option.textContent = formatFieldOptionLabel(field, labels);
            fieldSelect.appendChild(option);
        };

        const ungrouped = compatibleFields.filter((field) => ! field.group);
        const grouped = new Map();

        for (const field of compatibleFields) {
            if (! field.group) {
                continue;
            }

            if (! grouped.has(field.group)) {
                grouped.set(field.group, []);
            }

            grouped.get(field.group).push(field);
        }

        ungrouped.forEach(appendFieldOption);

        for (const [groupLabel, fields] of grouped) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = groupLabel;
            fieldSelect.appendChild(optgroup);

            for (const field of fields) {
                const option = document.createElement('option');
                option.value = field.id;
                option.textContent = formatFieldOptionLabel(field, labels);
                optgroup.appendChild(option);
            }
        }

        const canRestorePrevious = previousValue
            && [...fieldSelect.options].some((option) => option.value === previousValue);
        const preferredFieldId = preferredFieldIdForComponent(source?.fields ?? [], component);

        if (canRestorePrevious) {
            fieldSelect.value = previousValue;
        } else if (preferredFieldId && [...fieldSelect.options].some((option) => option.value === preferredFieldId)) {
            fieldSelect.value = preferredFieldId;
        } else if (fieldSelect.options.length > 0) {
            fieldSelect.value = fieldSelect.options[0].value;
        }

        if (applyButton) {
            applyButton.disabled = ! source || fieldSelect.options.length === 0;
        }

        const shouldAutoApply = (autoApply || (! isModal && searchActive && fieldSelect.options.length > 0))
            && fieldSelect.value
            && ! forceFieldId;

        if (shouldAutoApply) {
            commitBinding();
        }
    };

    const commitBinding = () => {
        if (! component) {
            if (isModal) {
                void alertDialog({
                    message: labels.selectComponent ?? inspectorSelectElementMessage(labels),
                    labels,
                });
            }

            return;
        }

        if (! sourceSelect?.value || ! fieldSelect?.value) {
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

        const bound = applyBindingToComponent(editor, component, bindingKey, option, labels);

        if (bound) {
            editor.select(bound);

            const previewValue = resolvePreviewValue(
                bindingKey,
                bound,
                bindingsPreviewValues ?? {},
                bindingsPreviewListValues ?? {},
                catalog,
            );
            const previewContext = {
                values: bindingsPreviewValues ?? {},
                listValues: bindingsPreviewListValues ?? {},
                catalog,
            };

            if (previewValue) {
                applyPreviewValue(bound, bindingKey, option, previewValue, previewContext);
            }
        }

        if (typeof onApplied === 'function') {
            onApplied(bound ?? component, bindingKey, option);
        }

        if (currentEl) {
            renderCurrentBindingSummary(currentEl, bindingKey, catalog, bindingsPreviewValues ?? {}, labels);
        }
    };

    if (sourceSelect && fieldSelect && ! listHost) {
        sourceSelect.addEventListener('change', () => populateFields(! isModal));
        fieldSearchInput?.addEventListener('input', () => {
            const query = String(fieldSearchInput?.value ?? '').trim();
            populateFields(false, { searchActive: query.length > 0 });
        });
        populateFields(false);

        const existingBinding = component?.getAttributes?.()['data-voodbuilder-bind'];
        const parsedBinding = normalizeBindingKeyForUi(existingBinding, catalog);

        if (parsedBinding) {
            sourceSelect.value = parsedBinding.sourceId;
            populateFields(false, { forceFieldId: parsedBinding.fieldId });
            fieldSelect.value = parsedBinding.fieldId;
        } else if (isInsideRepeatTemplate(component)) {
            const preferredSource = defaultItemSourceId(component, catalog);

            if (preferredSource && [...sourceSelect.options].some((option) => option.value === preferredSource)) {
                sourceSelect.value = preferredSource;
                populateFields(false);
            }
        }

        if (currentEl && existingBinding) {
            renderCurrentBindingSummary(currentEl, existingBinding, catalog, bindingsPreviewValues ?? {}, labels);
            currentEl.hidden = false;
        } else if (currentEl) {
            currentEl.hidden = true;
        }

        fieldSelect.addEventListener('change', () => {
            if (! isModal) {
                commitBinding();
            }
        });
    } else if (currentEl) {
        currentEl.hidden = true;
    }

    if (isModal) {
        host.querySelectorAll('[data-bind-cancel]').forEach((button) => {
            button.addEventListener('click', close);
        });
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

    if (repeatPanel && repeatSourceSelect) {
        if (repeatSources.length > 0 && listHost) {
            repeatPanel.hidden = false;
            const repeatTarget = migrateRepeatPlacement(component);
            refreshListContainerLayerName(repeatTarget);

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

            const filtersHost = host.querySelector('[data-repeat-filters]');

            const readRepeatFiltersFromUi = () => {
                const filters = {};

                filtersHost?.querySelectorAll('[data-repeat-filter]')?.forEach((select) => {
                    const id = select.getAttribute('data-repeat-filter');
                    const value = String(select.value ?? '').trim();

                    if (id && value) {
                        filters[id] = value;
                    }
                });

                return filters;
            };

            const populateRepeatFilters = (repeatSourceId, selectedFilters = {}) => {
                if (! filtersHost) {
                    return;
                }

                filtersHost.innerHTML = '';
                const definitions = filtersForRepeatSource(catalog, repeatSourceId);

                for (const definition of definitions) {
                    if (! definition?.id || ! Array.isArray(definition.options) || definition.options.length === 0) {
                        continue;
                    }

                    const label = document.createElement('label');
                    label.className = 'voodbuilder-gjs-bindings-modal__label';

                    const labelText = document.createElement('span');
                    labelText.className = 'voodbuilder-gjs-bindings-modal__label-text';
                    labelText.textContent = definition.label ?? definition.id;

                    const select = document.createElement('select');
                    select.className = 'voodbuilder-gjs-bindings-modal__select';
                    select.setAttribute('data-repeat-filter', definition.id);

                    const anyOption = document.createElement('option');
                    anyOption.value = '';
                    anyOption.textContent = labels.repeatFilterAny ?? 'Any';
                    select.appendChild(anyOption);

                    for (const optionDef of definition.options) {
                        const option = document.createElement('option');
                        option.value = String(optionDef.value ?? '');
                        option.textContent = optionDef.label ?? option.value;
                        select.appendChild(option);
                    }

                    const selected = selectedFilters?.[definition.id];

                    if (selected && [...select.options].some((option) => option.value === String(selected))) {
                        select.value = String(selected);
                    }

                    label.appendChild(labelText);
                    label.appendChild(select);
                    filtersHost.appendChild(label);
                }
            };

            for (const source of repeatSources) {
                const option = document.createElement('option');
                option.value = source.id;
                option.textContent = source.label;
                repeatSourceSelect.appendChild(option);
            }

            const existingRepeat = repeatTarget.getAttributes()['data-voodbuilder-repeat'];
            const existingSort = repeatTarget.getAttributes()['data-voodbuilder-repeat-sort'] || 'id';
            const existingSortDir = repeatTarget.getAttributes()['data-voodbuilder-repeat-sort-dir'] || 'desc';
            const existingLimit = repeatTarget.getAttributes()['data-voodbuilder-repeat-limit'];
            const existingOffset = repeatTarget.getAttributes()['data-voodbuilder-repeat-offset'] || '0';
            const existingFilters = repeatFiltersFromContainer(repeatTarget);

            if (existingRepeat) {
                repeatSourceSelect.value = existingRepeat;
                populateRepeatSortFields(existingRepeat, existingSort);
                populateRepeatFilters(existingRepeat, existingFilters);
                repeatCurrentEl.hidden = false;
                repeatCurrentEl.textContent = `${labels.currentRepeat ?? 'Current repeat'}: ${formatRepeatSummary(
                    existingRepeat,
                    existingLimit || repeatLimitInput.value || 3,
                    existingSort,
                    existingSortDir,
                    catalog,
                    labels,
                    existingOffset,
                    existingFilters,
                )}`;
            } else {
                const preferred = repeatSources.find((source) => source.id === 'vtuts.list' || source.id === 'vtut.list')
                    ?? repeatSources[0];

                if (preferred) {
                    repeatSourceSelect.value = preferred.id;
                    populateRepeatSortFields(
                        preferred.id,
                        preferred.defaultSort || 'published_at',
                    );
                    populateRepeatFilters(preferred.id);

                    if (repeatSortDirSelect) {
                        repeatSortDirSelect.value = preferred.defaultDirection || 'desc';
                    }
                }
            }

            if (existingLimit) {
                repeatLimitInput.value = existingLimit;
            }

            if (repeatOffsetInput) {
                repeatOffsetInput.value = existingOffset;
            }

            if (repeatSortDirSelect) {
                repeatSortDirSelect.value = existingSortDir;
            }

            repeatSourceSelect.addEventListener('change', () => {
                populateRepeatSortFields(repeatSourceSelect.value);
                populateRepeatFilters(repeatSourceSelect.value);
            });

            repeatApplyButton?.addEventListener('click', () => {
                if (! component || ! repeatSourceSelect.value) {
                    return;
                }

                const limit = Math.max(1, Math.min(24, Number(repeatLimitInput.value || 3)));
                const offset = Math.max(0, Math.min(100, Number(repeatOffsetInput?.value || 0)));
                const sort = repeatSortSelect?.value || 'id';
                const sortDir = repeatSortDirSelect?.value || 'desc';
                const filters = readRepeatFiltersFromUi();
                const filterAttr = serializeRepeatFiltersAttribute(filters);

                if (component.getAttributes()['data-voodbuilder-bind']) {
                    clearBindingFromComponent(component);
                }

                if (component !== repeatTarget) {
                    clearRepeatAttributes(component);
                }

                const attributes = {
                    'data-voodbuilder-repeat': repeatSourceSelect.value,
                    'data-voodbuilder-repeat-limit': String(limit),
                    'data-voodbuilder-repeat-offset': String(offset),
                    'data-voodbuilder-repeat-sort': sort,
                    'data-voodbuilder-repeat-sort-dir': sortDir,
                };

                if (filterAttr) {
                    attributes['data-voodbuilder-repeat-filter'] = filterAttr;
                } else {
                    repeatTarget.removeAttributes('data-voodbuilder-repeat-filter');
                }

                repeatTarget.addAttributes(attributes);
                applyRepeatMetaToComponent(repeatTarget, {
                    key: repeatSourceSelect.value,
                    limit: String(limit),
                    offset: String(offset),
                    sort,
                    sortDir,
                    filters,
                });

                const template = collapseRepeatTemplate(repeatTarget);

                if (template) {
                    migrateBindingsInTree(editor, template, catalog);
                }

                syncLogoScrollSourceNear(repeatTarget, 'dynamic');
                refreshListContainerLayerName(repeatTarget);

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
                        offset,
                        filters,
                    )}`;
                }

                if (currentEl) {
                    currentEl.hidden = true;
                }

                if (typeof onApplied === 'function') {
                    onApplied(template ?? repeatTarget, null, null);
                }

                if (isModal) {
                    close();
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

                syncLogoScrollSourceNear(repeatTarget, 'static');
                refreshListContainerLayerName(repeatTarget);

                editor.select(component);

                if (repeatCurrentEl) {
                    repeatCurrentEl.hidden = true;
                }

                if (isModal) {
                    close();
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

    if (mount.dataset.voodbuilderDynamicPanelMounted === '1') {
        return;
    }

    mount.dataset.voodbuilderDynamicPanelMounted = '1';

    mount.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    let lastTarget = null;

    const workingTarget = () => {
        const current = editor.getSelected();

        if (isBindingPanelComponentAlive(current)) {
            return current;
        }

        if (isBindingPanelComponentAlive(lastTarget)) {
            return lastTarget;
        }

        return null;
    };

    const renderPanel = () => {
        const selected = workingTarget();

        mount.innerHTML = '';

        if (! selected) {
            mount.appendChild(createInspectorEmptyState({
                classNameExtra: 'voodbuilder-gjs-dynamic-panel__empty',
                labels,
            }));

            return;
        }

        const selectionNotice = inspectorSelectionNotice(selected, editor, labels);

        if (selectionNotice !== null) {
            mount.appendChild(createInspectorEmptyState({
                classNameExtra: 'voodbuilder-gjs-dynamic-panel__empty voodbuilder-gjs-chrome-layout-notice',
                message: selectionNotice,
            }));

            return;
        }

        if (shouldSuppressChromeSlotInspector(selected, editor)) {
            mount.appendChild(createInspectorEmptyState({
                classNameExtra: 'voodbuilder-gjs-dynamic-panel__empty',
                labels,
            }));

            return;
        }

        restoreRepeatMetaOnComponent(selected);
        refreshListContainerLayerName(selected);

        mountBindingForm(editor, selected, previewOptions.catalog ?? catalog, labels, () => refreshBindingPreviews(editor, previewOptions), {
            mode: 'inline',
            mount,
        });
    };

    editor.on('component:selected', (component) => {
        lastTarget = component;
        renderPanel();
    });

    editor.on('component:deselected', () => {
        if (workingTarget()) {
            return;
        }

        renderPanel();
    });

    editor.on('voodbuilder:inspector-panel:refresh', ({ tabId }) => {
        if (tabId === 'dynamic') {
            renderPanel();
        }
    });

    renderPanel();
}

function isBindingPanelComponentAlive(component) {
    if (! component) {
        return false;
    }

    try {
        const el = component.getEl?.();

        return el ? el.isConnected !== false : component.parent?.() != null;
    } catch {
        return false;
    }
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
        console.error('Voodbuilder GrapesJS: could not load binding preview.', error);

        return {};
    });
    const listValues = bindingsPreviewListValues ?? {};
    const previewContext = { values, listValues, catalog };

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-bind]').forEach((component) => {
        const bindingKey = component.getAttributes()['data-voodbuilder-bind'];

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

        if (value == null || value === '') {
            return;
        }

        if (tag === 'img') {
            applyPreviewValue(component, bindingKey, option, value, previewContext);

            return;
        }

        const currentText = element?.textContent?.trim() ?? '';

        if (tag !== 'button' && tag !== 'a' && ! isPlaceholderText(currentText) && currentText !== '') {
            return;
        }

        if (hasBindBlockingChildren(component)) {
            return;
        }

        applyPreviewValue(component, bindingKey, option, value, previewContext);
    });
}

async function ensureBoundComponentVisible(component, options = {}) {
    const bindingKey = component.getAttributes()['data-voodbuilder-bind'];

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
    const previewContext = { values, listValues, catalog };
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

    if (hasBindBlockingChildren(component) && resolveBindingFieldType(bindingKey, catalog) === 'text') {
        return;
    }

    const needsPaint = tag === 'img'
        ? ! element.getAttribute('src')
            || element.getAttribute('src')?.startsWith('data:image/svg')
            || isPlaceholderAlt(element.getAttribute('alt'))
            || (element.getAttribute('src')?.includes('/storage/') && bindingKey.includes('.latest.'))
        : (tag !== 'button' && tag !== 'a' && ! element.textContent?.trim());

    if (! needsPaint) {
        return;
    }

    applyPreviewValue(component, bindingKey, option, value, previewContext);
}

export async function registerBindingsUi(editor, options = {}) {
    if (editor.__voodbuilderBindingsUiRegistered) {
        return editor.__voodbuilderBindingsCatalog ?? { groups: [], sources: [] };
    }

    editor.__voodbuilderBindingsUiRegistered = true;

    const labels = options.labels ?? {};
    const previewOptions = {
        catalog: { groups: [], sources: [] },
        bindingsUrl: options.bindingsUrl,
        bindingsPreviewUrl: options.bindingsPreviewUrl,
        editor,
    };

    registerBoundComponentType(editor);

    mountDynamicInspectorPanel(editor, options.dynamicMount, previewOptions.catalog, labels, previewOptions);

    const catalog = await loadBindingsCatalog(options.bindingsUrl).catch((error) => {
        console.error('Voodbuilder GrapesJS: could not load bindings catalog.', error);

        return { groups: [], sources: [] };
    });

    previewOptions.catalog = catalog;
    editor.__voodbuilderBindingsCatalog = catalog;

    editor.trigger('voodbuilder:inspector-panel:refresh', { tabId: 'dynamic' });

    const wrapper = editor.getWrapper?.();

    if (wrapper) {
        safeFindComponents(wrapper, '[data-voodbuilder-bind]').forEach((component) => {
            configureBoundComponent(editor, component, catalog);
        });
    }

    editor.on('component:add', (component) => {
        configureBoundComponent(editor, component, catalog);
        scheduleRepeatMaintenance(editor, catalog, previewOptions);
    });

    editor.on('component:remove', () => {
        scheduleRepeatMaintenance(editor, catalog, previewOptions);
    });

    editor.on('component:update', (component) => {
        const attrs = component?.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-repeat']
            || attrs['data-voodbuilder-repeat-item']
            || attrs['data-voodbuilder-bind']
            || safeFindComponents(component, '[data-voodbuilder-repeat], [data-voodbuilder-repeat-item], [data-voodbuilder-bind]').length > 0) {
            scheduleRepeatMaintenance(editor, catalog, previewOptions);
        }
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
                void alertDialog({
                    message: labels.selectComponent ?? inspectorSelectElementMessage(labels),
                    labels,
                });

                return;
            }

            restoreRepeatMetaOnComponent(selected);

            // After reload, Layout type may still wrap a list host — prefer the real repeat target.
            const target = isRepeatHost(selected)
                ? selected
                : (findRepeatListContainer(selected) ?? selected);

            if (target !== selected && isRepeatHost(target)) {
                ed.select(target);
            }

            restoreRepeatMetaOnComponent(target);
            openBindingModal(ed, target, previewOptions.catalog, labels, refreshPreviews);
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

    editor.on('load', () => {
        ensureRepeatContainers(editor, previewOptions.catalog);
        void refreshBindingPreviews(editor, previewOptions);
    });

    return catalog;
}

export function syncBindingsForExport(editor) {
    if (! editor?.getWrapper) {
        return;
    }

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-bind]').forEach((component) => {
        const bindingKey = component.getAttributes()['data-voodbuilder-bind'];

        if (! bindingKey) {
            return;
        }

        component.addAttributes({ 'data-voodbuilder-bind': bindingKey });

        const element = component.getView()?.el;

        if (element) {
            element.setAttribute('data-voodbuilder-bind', bindingKey);
        }
    });
}

export function syncRepeatBindingsForExport(editor) {
    if (! editor?.getWrapper) {
        return;
    }

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-repeat]').forEach((component) => {
        restoreRepeatMetaOnComponent(component);
        syncRepeatAttributesToDom(component);
    });

    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-repeat-item]').forEach((item) => {
        const repeatTarget = resolveRepeatTargetContainer(item.parent?.() ?? item);

        restoreRepeatMetaOnComponent(repeatTarget);
        syncRepeatAttributesToDom(repeatTarget);
    });
}
