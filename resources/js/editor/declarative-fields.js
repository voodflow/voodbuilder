/**
 * Declarative inspector fields from HTML markers.
 *
 * Authors mark editable slots in block HTML; VoodBuilder builds the Content panel
 * and persists values into the canvas (save/load is the page HTML).
 *
 * Markers:
 * - data-vb-field="key" — required field id
 * - data-vb-field-type="text|textarea|icon|number" — default text
 * - data-vb-field-label="Title" — optional panel label
 *
 * Repeating cards: data-vb-items-root + data-vb-item (see section-item-count.js).
 * Optional data-vb-items-layout="preserve" keeps author grid/card classes intact.
 */

import { registerBlockSettings } from './blocks/settings/index.js';
import {
    applyIconToComponent,
    createIconPicker,
    findIconHost,
    isIconComponent,
    readIconColor,
} from './basic-elements-settings.js';
import {
    createFormSection,
    createTextField,
    createTextareaField,
} from './editor-form-ui.js';
import {
    DEFAULT_TABLER_ICON,
    DEFAULT_TABLER_ICON_STROKE,
    DEFAULT_TABLER_ICON_STYLE,
    resolveTablerIconName,
    resolveTablerIconStroke,
    resolveTablerIconStyle,
} from './tabler-icons-catalog.js';

const FIELD_ATTR = 'data-vb-field';
const FIELD_TYPE_ATTR = 'data-vb-field-type';
const FIELD_LABEL_ATTR = 'data-vb-field-label';
const ITEM_ATTR = 'data-vb-item';

/**
 * @param {object} component
 * @returns {boolean}
 */
function hasFieldAttr(component) {
    return Object.prototype.hasOwnProperty.call(component?.getAttributes?.() ?? {}, FIELD_ATTR);
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function hasItemAttr(component) {
    return Object.prototype.hasOwnProperty.call(component?.getAttributes?.() ?? {}, ITEM_ATTR);
}

/**
 * Depth-first walk of GrapesJS component tree.
 *
 * @param {object} root
 * @param {(component: object) => void} visit
 */
function walkComponents(root, visit) {
    if (! root) {
        return;
    }

    visit(root);

    for (const child of [...(root.components?.() ?? [])]) {
        walkComponents(child, visit);
    }
}

/**
 * Collect fields under `scope`, skipping nested `data-vb-item` trees when
 * `scope` itself is not that item (section-level scan).
 *
 * @param {object} scope
 * @returns {object[]}
 */
export function findDeclarativeFields(scope) {
    return collectFields(scope, { stopAtNestedItems: ! hasItemAttr(scope) });
}

/**
 * Fields belonging to one data-vb-item (including the item root if marked).
 *
 * @param {object} item
 * @returns {object[]}
 */
export function findItemDeclarativeFields(item) {
    return collectFields(item, { stopAtNestedItems: true });
}

/**
 * @param {object} scope
 * @param {{ stopAtNestedItems?: boolean }} [options]
 * @returns {object[]}
 */
function collectFields(scope, { stopAtNestedItems = false } = {}) {
    const fields = [];

    const walk = (component) => {
        if (! component) {
            return;
        }

        if (hasFieldAttr(component)) {
            fields.push(describeField(component));
        }

        for (const child of [...(component.components?.() ?? [])]) {
            if (stopAtNestedItems && hasItemAttr(child) && child !== scope) {
                continue;
            }

            walk(child);
        }
    };

    walk(scope);

    return fields;
}

/**
 * @param {object} component
 * @returns {object|null}
 */
function findClosestItem(component) {
    let current = component;

    while (current) {
        if (hasItemAttr(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object} component
 * @returns {{ key: string, type: string, label: string, component: object }}
 */
function describeField(component) {
    const attrs = component.getAttributes?.() ?? {};
    const key = String(attrs[FIELD_ATTR] ?? '').trim() || 'field';
    const type = normalizeFieldType(attrs[FIELD_TYPE_ATTR], component);
    const label = String(attrs[FIELD_LABEL_ATTR] ?? '').trim() || humanizeKey(key);

    return { key, type, label, component };
}

/**
 * @param {unknown} raw
 * @param {object} component
 * @returns {string}
 */
function normalizeFieldType(raw, component) {
    const value = String(raw ?? '').trim().toLowerCase();

    if (['text', 'textarea', 'icon', 'number'].includes(value)) {
        return value;
    }

    if (isIconComponent(component) || findIconHost(component)) {
        return 'icon';
    }

    return 'text';
}

/**
 * @param {string} key
 * @returns {string}
 */
function humanizeKey(key) {
    return key
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * @param {object} component
 * @returns {string}
 */
export function readFieldText(component) {
    const direct = component.get?.('content');

    if (typeof direct === 'string' && direct.trim() !== '') {
        return direct;
    }

    const parts = [];

    walkComponents(component, (node) => {
        if (node === component) {
            return;
        }

        if (hasFieldAttr(node) || hasItemAttr(node) || isIconComponent(node)) {
            return;
        }

        const content = node.get?.('content');

        if (typeof content === 'string' && content !== '') {
            parts.push(content);
        }
    });

    if (parts.length > 0) {
        return parts.join('').trim();
    }

    const el = component.getEl?.() ?? component.getView?.()?.el ?? component.view?.el;

    return String(el?.textContent ?? '').trim();
}

/**
 * @param {object} component
 * @param {string} value
 */
export function writeFieldText(component, value) {
    const text = String(value ?? '');
    const children = [...(component.components?.() ?? [])];
    const onlyTextish = children.length === 0
        || children.every((child) => {
            const type = String(child.get?.('type') ?? '');

            return type === 'textnode' || type === 'text' || (! child.components?.()?.length && typeof child.get?.('content') === 'string');
        });

    if (onlyTextish) {
        component.components?.(text);

        return;
    }

    // Prefer updating the first text leaf so wrappers/icons stay intact.
    let updated = false;

    walkComponents(component, (node) => {
        if (updated || node === component || hasFieldAttr(node) || isIconComponent(node)) {
            return;
        }

        const type = String(node.get?.('type') ?? '');

        if (type === 'textnode' || type === 'text' || typeof node.get?.('content') === 'string') {
            node.components?.(text);
            updated = true;
        }
    });

    if (! updated) {
        component.components?.(text);
    }
}

/**
 * @param {HTMLElement} mount
 * @param {object[]} fields
 * @param {object} editor
 * @param {{ heading?: string }} [options]
 */
export function appendDeclarativeFields(mount, fields, editor, options = {}) {
    if (! mount || fields.length === 0) {
        return;
    }

    const { section, fields: host } = createFormSection(options.heading ?? 'Content');

    for (const field of fields) {
        host.appendChild(renderFieldControl(field, editor));
    }

    mount.appendChild(section);
}

/**
 * @param {{ key: string, type: string, label: string, component: object }} field
 * @param {object} editor
 * @returns {HTMLElement}
 */
function renderFieldControl(field, editor) {
    if (field.type === 'icon') {
        return renderIconField(field, editor);
    }

    if (field.type === 'textarea') {
        const { field: wrap, input } = createTextareaField({
            label: field.label,
            name: `vbField_${field.key}`,
            value: readFieldText(field.component),
            rows: 3,
        });

        input.addEventListener('input', () => writeFieldText(field.component, input.value));

        return wrap;
    }

    const { field: wrap, input } = createTextField({
        label: field.label,
        name: `vbField_${field.key}`,
        value: readFieldText(field.component),
        type: field.type === 'number' ? 'number' : 'text',
    });

    input.addEventListener('input', () => writeFieldText(field.component, input.value));

    return wrap;
}

/**
 * @param {{ key: string, label: string, component: object }} field
 * @param {object} editor
 * @returns {HTMLElement}
 */
function renderIconField(field, editor) {
    const host = findIconHost(field.component) ?? field.component;
    const attrs = host.getAttributes?.() ?? {};
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-editor-form-field';

    const picker = createIconPicker({
        value: resolveTablerIconName(attrs['data-vb-icon'] ?? DEFAULT_TABLER_ICON),
        style: resolveTablerIconStyle(attrs['data-vb-icon-style'] ?? DEFAULT_TABLER_ICON_STYLE),
        stroke: resolveTablerIconStroke(attrs['data-vb-icon-stroke'] ?? DEFAULT_TABLER_ICON_STROKE),
        color: readIconColor(host),
        labels: {
            iconName: field.label,
        },
        onChange: ({ name, style, stroke, color }) => {
            applyIconToComponent(host, editor, {
                name,
                style,
                stroke,
                color,
                sizeClass: attrs['data-vb-icon-size'] || 'size-5',
                forceGlyph: true,
            });
        },
    });

    wrap.appendChild(picker.field);

    return wrap;
}

/**
 * @param {HTMLElement} mount
 * @param {object[]} items
 * @param {object} editor
 */
export function appendDeclarativeItemEditors(mount, items, editor) {
    if (! mount || items.length === 0) {
        return;
    }

    items.forEach((item, index) => {
        const fields = findItemDeclarativeFields(item);

        if (fields.length === 0) {
            return;
        }

        appendDeclarativeFields(mount, fields, editor, {
            heading: `Item ${index + 1}`,
        });
    });
}

/**
 * True when the tree exposes at least one declarative field (section or items).
 *
 * @param {object} root
 * @returns {boolean}
 */
export function scopeHasDeclarativeFields(root) {
    let found = false;

    walkComponents(root, (component) => {
        if (found) {
            return;
        }

        if (hasFieldAttr(component)) {
            found = true;
        }
    });

    return found;
}

/**
 * Register Content-panel settings for sections/items that declare data-vb-field.
 * Item-count UI stays in section-item-count.js; this covers field-only roots and
 * complements item-count when that descriptor does not match.
 *
 * @param {object} editor
 */
export function registerDeclarativeFieldSettings(editor) {
    if (editor.__voodbuilderDeclarativeFieldSettingsRegistered) {
        return;
    }

    editor.__voodbuilderDeclarativeFieldSettingsRegistered = true;

    registerBlockSettings({
        id: 'declarative_fields',
        matchBlockId: () => false,
        findRoot: (component) => findDeclarativeSettingsRoot(component),
        matchesRoot: (root) => Boolean(root) && scopeHasDeclarativeFields(root),
        render: ({ mount, root, editor: ed }) => {
            const sectionFields = findDeclarativeFields(root).filter((field) => {
                const owner = findClosestItem(field.component);

                return ! owner || owner === root;
            });

            appendDeclarativeFields(mount, sectionFields, ed, { heading: 'Content' });

            const itemsRoot = root.find?.('[data-vb-items-root]')?.[0] ?? null;
            const items = itemsRoot
                ? [...(itemsRoot.components?.() ?? [])].filter((child) => hasItemAttr(child))
                : (hasItemAttr(root) ? [root] : []);

            appendDeclarativeItemEditors(mount, items, ed);
        },
    });
}

/**
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
function findDeclarativeSettingsRoot(component) {
    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        // Prefer the section / block that owns items, then a lone item, then any field host.
        if (
            Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')
            || Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-section-block')
            || Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-block')
        ) {
            if (scopeHasDeclarativeFields(current)) {
                return current;
            }
        }

        if (hasItemAttr(current) && findItemDeclarativeFields(current).length > 0) {
            return current;
        }

        if (hasFieldAttr(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}
