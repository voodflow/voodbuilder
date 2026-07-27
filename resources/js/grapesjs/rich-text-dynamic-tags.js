/**
 * Bricks-like dynamic data picker for the Rich Text Content editor.
 * Inserts inline nodes with data-voodbuilder-bind (model integrations / binding catalog).
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
    return `[${sourceLabel}: ${fieldLabel}]`;
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
    const label = String(field?.label ?? field?.id ?? 'Field');
    const group = String(field?.group ?? '').trim();

    return group ? `${group} · ${label}` : label;
}

/**
 * @param {{ bindingKey: string, sourceLabel: string, fieldLabel: string, fieldType: string }} args
 * @returns {string}
 */
export function buildRichTextDynamicTagHtml({ bindingKey, sourceLabel, fieldLabel, fieldType }) {
    const key = String(bindingKey ?? '').trim();
    const placeholder = escapeHtml(placeholderForRichTextBinding(sourceLabel, fieldLabel));
    const safeKey = escapeHtml(key);

    if (String(fieldType ?? 'text').toLowerCase() === 'url') {
        const classes = escapeHtml(['voodbuilder-gjs-bound', ...RICH_TEXT_LINK_CLASSES].join(' '));

        return `<a href="#" class="${classes}" contenteditable="false" data-voodbuilder-bind="${safeKey}">${placeholder}</a>`;
    }

    return `<span class="voodbuilder-gjs-bound vb-rich-text-dynamic" contenteditable="false" data-voodbuilder-bind="${safeKey}">${placeholder}</span>`;
}

/**
 * @param {object} catalog
 * @param {(pick: { bindingKey: string, source: object, field: object }) => void} onPick
 * @returns {Array<object>}
 */
export function buildRichTextDynamicTagMenuItems(catalog, onPick) {
    const sources = listRichTextBindingSources(catalog)
        .map((source) => {
            const fields = (Array.isArray(source?.fields) ? source.fields : [])
                .filter(isInsertableRichTextField);

            if (fields.length === 0) {
                return null;
            }

            return {
                id: `source-${source.id}`,
                label: String(source.label ?? source.id ?? 'Source'),
                children: fields.map((field) => ({
                    id: `field-${source.id}.${field.id}`,
                    label: fieldMenuLabel(field),
                    onSelect: () => {
                        onPick?.({
                            bindingKey: `${source.id}.${field.id}`,
                            source,
                            field,
                        });
                    },
                })),
            };
        })
        .filter(Boolean);

    return sources.sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' }));
}

/**
 * @param {{
 *   editor: object,
 *   anchorEl: HTMLElement,
 *   labels?: object,
 *   onInsert: (html: string) => void,
 * }} args
 */
export function openRichTextDynamicTagPicker({ editor, anchorEl, labels = {}, onInsert }) {
    const catalog = editor?.__voodbuilderBindingsCatalog ?? { groups: [], sources: [] };
    const items = buildRichTextDynamicTagMenuItems(catalog, ({ bindingKey, source, field }) => {
        const html = buildRichTextDynamicTagHtml({
            bindingKey,
            sourceLabel: source?.label ?? 'Dynamic',
            fieldLabel: field?.label ?? bindingKey,
            fieldType: field?.type ?? 'text',
        });
        onInsert?.(html);
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
