/**
 * Bricks-like dynamic data picker for the Rich Text Content editor.
 *
 * Top-level groups:
 * - **User profile** (`{alias}.auth`) — logged-in session user (Bricks “User profile”)
 * - **Model name** (`Users`, `Tutorials`, …) — `.latest` outside a list, `.item` inside
 *   a matching `data-voodbuilder-repeat`
 */

import { openContextMenu } from './context-menu.js';
import { alertDialog } from './editor-dialog.js';
import { RICH_TEXT_LINK_CLASSES } from './link-picker-dialog.js';

const SOURCE_CONTEXT_SUFFIX_RE = /\s·\s*(Latest record|List item|Logged-in user|User profile|Ultimo record|Elemento lista|Utente autenticato|Profilo utente)\s*$/i;

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
        .replace(SOURCE_CONTEXT_SUFFIX_RE, '')
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
    return String(sourceId ?? '').replace(/\.(latest|item|auth)$/i, '');
}

/**
 * @param {string} label
 * @param {string} alias
 * @returns {string}
 */
function modelMenuLabel(label, alias) {
    const cleaned = String(label ?? '')
        .replace(SOURCE_CONTEXT_SUFFIX_RE, '')
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
 * @param {Array<object>} sourcesForModel
 * @returns {{ auth?: object, latest?: object, item?: object }}
 */
export function splitModelBindingSources(sourcesForModel) {
    const sources = Array.isArray(sourcesForModel) ? sourcesForModel : [];

    return {
        auth: sources.find((source) => String(source?.id ?? '').endsWith('.auth')),
        latest: sources.find((source) => String(source?.id ?? '').endsWith('.latest')),
        item: sources.find((source) => String(source?.id ?? '').endsWith('.item')),
    };
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
 * Prefer list item inside a matching repeat; for Authenticatable models prefer
 * logged-in user; otherwise latest DB record.
 *
 * @param {Array<object>} sourcesForModel
 * @param {object|null|undefined} contextComponent
 * @returns {object|null}
 */
export function pickBindingSourceForContext(sourcesForModel, contextComponent) {
    const sources = Array.isArray(sourcesForModel) ? sourcesForModel : [];
    const { auth, latest, item } = splitModelBindingSources(sources);
    const alias = modelAliasFromSourceId(item?.id ?? auth?.id ?? latest?.id ?? sources[0]?.id ?? '');

    if (item && isComponentInsideModelRepeat(contextComponent, alias)) {
        return item;
    }

    if (auth) {
        return auth;
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
        } else if (String(source?.id ?? '').endsWith('.latest') || String(source?.id ?? '').endsWith('.item')) {
            // Prefer model integration name over standalone "User profile" for the DB-record group.
            const candidate = modelMenuLabel(source?.label, alias);
            const current = String(entry.label ?? '');

            if (
                candidate
                && candidate !== alias
                && ! /^(User profile|Profilo utente)$/i.test(candidate)
                && (/^(User profile|Profilo utente)$/i.test(current) || current === alias)
            ) {
                entry.label = candidate;
            }
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
 * @param {object} source
 * @param {object} field
 * @param {string} modelLabel
 * @param {(pick: { bindingKey: string, source: object, field: object, modelLabel: string }) => void} onPick
 * @param {string} [menuLabel]
 * @returns {object|null}
 */
function fieldMenuItem(source, field, modelLabel, onPick, menuLabel = null) {
    if (! source?.id || ! field?.id) {
        return null;
    }

    return {
        id: `field-${source.id}.${field.id}`,
        label: menuLabel ?? fieldMenuLabel(field),
        onSelect: () => {
            onPick?.({
                bindingKey: `${source.id}.${field.id}`,
                source,
                field,
                modelLabel,
            });
        },
    };
}

/**
 * @param {object} catalog
 * @param {object|null|undefined} contextComponent
 * @param {(pick: { bindingKey: string, source: object, field: object, modelLabel: string }) => void} onPick
 * @param {object} [labels]
 * @returns {Array<object>}
 */
export function buildRichTextDynamicTagMenuItems(catalog, contextComponent, onPick, labels = {}) {
    const models = groupBindingSourcesByModel(listRichTextBindingSources(catalog));
    const profileLabel = labels.richTextDynamicUserProfile
        ?? labels.userProfile
        ?? 'User profile';
    /** @type {Array<object>} */
    const items = [];

    // Bricks-style: dedicated "User profile" group → session user (`.auth`).
    for (const model of models) {
        const { auth } = splitModelBindingSources(model.sources);

        if (! auth) {
            continue;
        }

        /** @type {Array<object>} */
        const children = [];

        for (const field of model.fields) {
            const entry = fieldMenuItem(auth, field, profileLabel, onPick);

            if (entry) {
                children.push(entry);
            }
        }

        if (children.length > 0) {
            items.push({
                id: `auth-${model.alias}`,
                label: profileLabel,
                children,
            });
        }
    }

    // Model groups: latest (hero / byline) or item (inside matching list repeat).
    for (const model of models) {
        const { auth, latest, item } = splitModelBindingSources(model.sources);
        const insideList = isComponentInsideModelRepeat(contextComponent, model.alias);
        const source = insideList && item
            ? item
            : (latest ?? (! auth ? item : null));

        if (! source) {
            continue;
        }

        /** @type {Array<object>} */
        const children = [];

        for (const field of model.fields) {
            const entry = fieldMenuItem(source, field, model.label, onPick);

            if (entry) {
                children.push(entry);
            }
        }

        if (children.length === 0) {
            continue;
        }

        items.push({
            id: `model-${model.alias}`,
            label: model.label,
            children,
        });
    }

    return items;
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
    }, labels);

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
