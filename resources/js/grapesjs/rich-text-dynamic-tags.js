/**
 * Bricks-like dynamic data picker for the Rich Text Content editor.
 *
 * Menu groups by model (Users, Tutorials, …). Latest vs List-item is chosen
 * from context: inside a matching `data-voodbuilder-repeat` → `.item`, else `.latest`.
 */

import { openContextMenu } from './context-menu.js';
import { alertDialog } from './editor-dialog.js';
import { RICH_TEXT_LINK_CLASSES } from './link-picker-dialog.js';

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * @param {string} sourceLabel
 * @param {string} fieldLabel
 * @returns {string}
 */
export function placeholderForRichTextBinding(sourceLabel, fieldLabel) {
    const model = String(sourceLabel ?? 'Dynamic')
        .replace(/\s·\s*(Latest record|List item|Ultimo record|Elemento lista)\s*$/i, '')
        .trim() || 'Dynamic';
    const field = String(fieldLabel ?? '').trim() || 'Field';

    return `[${model}: ${field}]`;
}

/**
 * @param {object} catalog
 * @returns {Array<object>}
 */
export function listRichTextBindingSources(catalog) {
    const fromFlat = Array.isArray(catalog?.sources) ? catalog.sources : [];

    if (fromFlat.length > 0) {
        return fromFlat;
    }

    const groups = Array.isArray(catalog?.groups) ? catalog.groups : [];

    return groups.flatMap((group) => (Array.isArray(group?.sources) ? group.sources : []));
}

/**
 * @param {string} sourceId
 * @returns {string}
 */
export function modelAliasFromSourceId(sourceId) {
    return String(sourceId ?? '').replace(/\.(latest|item)$/i, '');
}

/**
 * @param {string} label
 * @param {string} alias
 * @returns {string}
 */
function modelMenuLabel(label, alias) {
    const cleaned = String(label ?? '')
        .replace(/\s·\s*(Latest record|List item|Ultimo record|Elemento lista)\s*$/i, '')
        .trim();

    return cleaned || alias || 'Source';
}

/**
 * @param {object} field
 * @returns {boolean}
 */
function isInsertableRichTextField(field) {
    const type = String(field?.type ?? 'text').toLowerCase();

    return type === 'text' || type === 'url';
}

/**
 * @param {object} field
 * @returns {string}
 */
function fieldMenuLabel(field) {
    const label = String(field?.label ?? field?.id ?? '').trim() || String(field?.id ?? 'Field');
    const group = String(field?.group ?? '').trim();

    return group ? `${group} · ${label}` : label;
}

/**
 * @param {object|null|undefined} component
 * @param {string} alias
 * @returns {boolean}
 */
export function isComponentInsideModelRepeat(component, alias) {
    if (! component || ! alias) {
        return false;
    }

    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const repeat = String(current.getAttributes?.()?.['data-voodbuilder-repeat'] ?? '');

        if (repeat === `${alias}.list` || repeat.startsWith(`${alias}.`)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * Prefer `.item` inside a matching list repeat, otherwise `.latest` (hero, byline, …).
 *
 * @param {Array<object>} sourcesForModel
 * @param {object|null|undefined} contextComponent
 * @returns {object|null}
 */
export function pickBindingSourceForContext(sourcesForModel, contextComponent) {
    const sources = Array.isArray(sourcesForModel) ? sourcesForModel : [];
    const item = sources.find((source) => String(source?.id ?? '').endsWith('.item'));
    const latest = sources.find((source) => String(source?.id ?? '').endsWith('.latest'));
    const alias = modelAliasFromSourceId(item?.id ?? latest?.id ?? sources[0]?.id ?? '');

    if (item && isComponentInsideModelRepeat(contextComponent, alias)) {
        return item;
    }

    return latest ?? item ?? sources[0] ?? null;
}

/**
 * @param {Array<object>} sources
 * @returns {Array<{ alias: string, label: string, sources: Array<object>, fields: Array<object> }>}
 */
export function groupBindingSourcesByModel(sources) {
    /** @type {Map<string, { alias: string, label: string, sources: Array<object>, fieldsById: Map<string, object> }>} */
    const map = new Map();

    for (const source of sources) {
        const id = String(source?.id ?? '');
        const alias = modelAliasFromSourceId(id);

        if (! alias) {
            continue;
        }

        let entry = map.get(alias);

        if (! entry) {
            entry = {
                alias,
                label: modelMenuLabel(source?.label, alias),
                sources: [],
                fieldsById: new Map(),
            };
            map.set(alias, entry);
        }

        entry.sources.push(source);

        for (const field of Array.isArray(source?.fields) ? source.fields : []) {
            if (! isInsertableRichTextField(field)) {
                continue;
            }

            const fieldId = String(field?.id ?? '');

            if (! fieldId || entry.fieldsById.has(fieldId)) {
                continue;
            }

            entry.fieldsById.set(fieldId, field);
        }
    }

    return [...map.values()]
        .map((entry) => ({
            alias: entry.alias,
            label: entry.label,
            sources: entry.sources,
            fields: [...entry.fieldsById.values()],
        }))
        .filter((entry) => entry.fields.length > 0)
        .sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' }));
}

/**
 * @param {{ bindingKey: string, sourceLabel: string, fieldLabel: string, fieldType: string }} args
 * @returns {string}
 */
export function buildRichTextDynamicTagHtml({ bindingKey, sourceLabel, fieldLabel, fieldType }) {
    const key = String(bindingKey ?? '').trim();
    const placeholder = escapeHtml(placeholderForRichTextBinding(sourceLabel, fieldLabel));
    const safeKey = escapeHtml(key);

    if (! key) {
        return '';
    }

    if (String(fieldType ?? 'text').toLowerCase() === 'url') {
        const classes = escapeHtml(['voodbuilder-gjs-bound', 'vb-rich-text-dynamic', ...RICH_TEXT_LINK_CLASSES].join(' '));

        return `<a href="#" class="${classes}" contenteditable="false" data-voodbuilder-bind-href="${safeKey}" data-voodbuilder-hide-when-empty="1">${placeholder}</a>`;
    }

    return `<span class="voodbuilder-gjs-bound vb-rich-text-dynamic" contenteditable="false" data-voodbuilder-bind="${safeKey}" data-voodbuilder-hide-when-empty="1">${placeholder}</span>`;
}

/**
 * Insert HTML inline at the caret without splitting the paragraph (browser execCommand often does).
 *
 * @param {HTMLElement} rootEl
 * @param {string} html
 */
export function insertHtmlInlineAtCaret(rootEl, html) {
    if (! rootEl || ! html) {
        return;
    }

    rootEl.focus();

    const selection = window.getSelection?.();
    const range = selection?.rangeCount ? selection.getRangeAt(0) : null;

    if (! range || ! rootEl.contains(range.commonAncestorContainer)) {
        document.execCommand('insertHTML', false, html);

        return;
    }

    range.deleteContents();

    const template = document.createElement('template');
    template.innerHTML = html;
    const fragment = template.content;
    const last = fragment.lastChild;

    range.insertNode(fragment);

    if (last) {
        const after = document.createRange();
        after.setStartAfter(last);
        after.collapse(true);
        selection?.removeAllRanges?.();
        selection?.addRange?.(after);
    }
}

/**
 * @param {object} catalog
 * @param {object|null|undefined} contextComponent
 * @param {(pick: { bindingKey: string, source: object, field: object, modelLabel: string }) => void} onPick
 * @returns {Array<object>}
 */
export function buildRichTextDynamicTagMenuItems(catalog, contextComponent, onPick) {
    const models = groupBindingSourcesByModel(listRichTextBindingSources(catalog));

    return models.map((model) => ({
        id: `model-${model.alias}`,
        label: model.label,
        children: model.fields.map((field) => ({
            id: `field-${model.alias}.${field.id}`,
            label: fieldMenuLabel(field),
            onSelect: () => {
                const source = pickBindingSourceForContext(model.sources, contextComponent);

                if (! source?.id || ! field?.id) {
                    return;
                }

                onPick?.({
                    bindingKey: `${source.id}.${field.id}`,
                    source,
                    field,
                    modelLabel: model.label,
                });
            },
        })),
    }));
}

/**
 * @param {{
 *   editor: object,
 *   component?: object|null,
 *   anchorEl: HTMLElement,
 *   labels?: object,
 *   onInsert: (html: string) => void,
 * }} args
 */
export function openRichTextDynamicTagPicker({ editor, component = null, anchorEl, labels = {}, onInsert }) {
    const catalog = editor?.__voodbuilderBindingsCatalog ?? { groups: [], sources: [] };
    const contextComponent = component ?? editor?.getSelected?.() ?? null;

    const items = buildRichTextDynamicTagMenuItems(catalog, contextComponent, ({ bindingKey, source, field, modelLabel }) => {
        const html = buildRichTextDynamicTagHtml({
            bindingKey,
            sourceLabel: modelLabel ?? source?.label ?? 'Dynamic',
            fieldLabel: field?.label ?? field?.id ?? bindingKey,
            fieldType: field?.type ?? 'text',
        });

        if (html) {
            onInsert?.(html);
        }
    });

    if (items.length === 0) {
        void alertDialog({
            message: labels.noSources
                ?? labels.richTextDynamicEmpty
                ?? 'No dynamic data sources are registered yet.',
            labels,
        });

        return;
    }

    const rect = anchorEl?.getBoundingClientRect?.();
    const x = rect ? rect.left : 24;
    const y = rect ? rect.bottom + 4 : 24;

    openContextMenu({
        x,
        y,
        items,
        context: null,
    });
}
