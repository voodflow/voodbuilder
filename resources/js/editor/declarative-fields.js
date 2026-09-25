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

import { debugSwallowed } from './debug-swallowed.js';
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
export function findClosestItem(component) {
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
 * Resolve which repeating item the current canvas selection belongs to.
 *
 * @param {object[]} items
 * @param {object|null|undefined} selected
 * @returns {{ item: object, index: number }|null}
 */
export function resolveFocusedItem(items, selected) {
    if (! Array.isArray(items) || items.length === 0 || ! selected) {
        return null;
    }

    const focused = findClosestItem(selected);

    if (! focused) {
        return null;
    }

    const index = items.findIndex((item) => item === focused
        || (item?.cid != null && item.cid === focused.cid));

    if (index < 0) {
        return null;
    }

    return { item: items[index], index };
}

/**
 * Hint when the section is selected but no repeating item is focused.
 *
 * @param {HTMLElement} mount
 * @param {string} [message]
 */
export function appendSelectItemHint(mount, message = 'Select an item on the canvas to edit its content.') {
    if (! mount) {
        return;
    }

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.setAttribute('data-vb-select-item-hint', '');
    hint.textContent = message;
    mount.appendChild(hint);
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
 * True when `node` is `root` or nested under it.
 *
 * @param {object|null|undefined} node
 * @param {object|null|undefined} root
 * @returns {boolean}
 */
function isUnderComponent(node, root) {
    if (! node || ! root) {
        return false;
    }

    let current = node;

    while (current) {
        if (current === root) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * Update a text leaf without `components(html)` when possible.
 *
 * GrapesJS `component.components(string)` remounts the DOM and resets the caret
 * to the start — typing then appears RTL / backwards, and canvas RTE can stick
 * until Save remounts the frame (see GrapesJS discussion #4417).
 *
 * @param {object} component
 * @param {string} text
 * @returns {boolean}
 */
function writeTextLeafInPlace(component, text) {
    if (! component) {
        return false;
    }

    const type = String(component.get?.('type') ?? '');

    if (type === 'textnode') {
        component.set?.('content', text);

        return true;
    }

    if (typeof component.set === 'function' && typeof component.get?.('content') === 'string') {
        component.set('content', text);
    }

    const el = component.getEl?.() ?? component.getView?.()?.el ?? component.view?.el;

    if (el && el.childElementCount === 0) {
        el.textContent = text;

        return true;
    }

    return false;
}

/**
 * Exit canvas RTE if the field being rewritten is currently being edited inline.
 *
 * @param {object|null|undefined} editor
 * @param {object} component
 */
function releaseCanvasRteIfEditing(editor, component) {
    const editing = editor?.getEditing?.();

    if (! editing || ! component) {
        return;
    }

    if (editing !== component && ! isUnderComponent(editing, component) && ! isUnderComponent(component, editing)) {
        return;
    }

    try {
        editing.view?.disableEditing?.();
    } catch (error) {
        // Best-effort — avoid blocking Content-panel writes.
        debugSwallowed(error);
    }
}

/**
 * @param {object} component
 * @param {string} value
 * @param {object|null} [editor]
 */
export function writeFieldText(component, value, editor = null) {
    const text = String(value ?? '');

    const apply = () => {
        releaseCanvasRteIfEditing(editor, component);

        const children = [...(component.components?.() ?? [])];
        const textNodes = children.filter((child) => String(child.get?.('type') ?? '') === 'textnode');

        // Single textnode child: update in place — never remount via components().
        if (textNodes.length === 1 && children.length === 1) {
            textNodes[0].set?.('content', text);

            const el = component.getEl?.() ?? component.getView?.()?.el ?? component.view?.el;

            if (el) {
                // 3 === Node.TEXT_NODE (avoid Node global — Vitest node env).
                if (el.firstChild?.nodeType === 3 && el.childNodes.length === 1) {
                    el.firstChild.textContent = text;
                } else if (el.childElementCount === 0) {
                    el.textContent = text;
                }
            }

            return;
        }

        const onlyTextish = children.length === 0
            || children.every((child) => {
                const type = String(child.get?.('type') ?? '');

                return type === 'textnode' || type === 'text'
                    || (! child.components?.()?.length && typeof child.get?.('content') === 'string');
            });

        if (onlyTextish && children.length === 0) {
            if (writeTextLeafInPlace(component, text)) {
                return;
            }

            component.components?.(text);

            return;
        }

        if (onlyTextish && textNodes.length >= 1) {
            writeTextLeafInPlace(textNodes[0], text);

            // Drop extra text nodes left from prior remounts without rewriting the host.
            for (let index = 1; index < textNodes.length; index += 1) {
                textNodes[index].remove?.();
            }

            const el = component.getEl?.();

            if (el && el.childElementCount === 0) {
                el.textContent = text;
            }

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
                if (! writeTextLeafInPlace(node, text)) {
                    node.components?.(text);
                }

                updated = true;
            }
        });

        if (! updated) {
            component.components?.(text);
        }
    };

    if (! editor) {
        apply();

        return;
    }

    // Mirror writeComponentHtml / settings guards so Content writes do not
    // remount the inspector (which also resets caret / selection).
    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        apply();
    } finally {
        // Keep the flag through pending component:update rAFs so the Content
        // form is not remounted mid-keystroke (caret → start / stuck RTE).
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                const next = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
                editor.__voodbuilderSettingsChangeDepth = next;

                if (next <= 0) {
                    editor.__voodbuilderSettingsChange = false;
                    delete editor.__voodbuilderSettingsChangeDepth;
                }
            });
        });
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

        input.addEventListener('input', () => writeFieldText(field.component, input.value, editor));

        return wrap;
    }

    const { field: wrap, input } = createTextField({
        label: field.label,
        name: `vbField_${field.key}`,
        value: readFieldText(field.component),
        type: field.type === 'number' ? 'number' : 'text',
    });

    input.addEventListener('input', () => writeFieldText(field.component, input.value, editor));

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
 * @param {{ selected?: object|null, focusOnly?: boolean, selectHint?: string }} [options]
 */
export function appendDeclarativeItemEditors(mount, items, editor, options = {}) {
    if (! mount || items.length === 0) {
        return;
    }

    const focusOnly = options.focusOnly !== false;
    const selected = options.selected ?? editor?.getSelected?.() ?? null;
    const focused = focusOnly ? resolveFocusedItem(items, selected) : null;

    if (focusOnly) {
        if (! focused) {
            appendSelectItemHint(mount, options.selectHint);

            return;
        }

        const fields = findItemDeclarativeFields(focused.item);

        if (fields.length === 0) {
            return;
        }

        appendDeclarativeFields(mount, fields, editor, {
            heading: `Item ${focused.index + 1}`,
        });

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
        render: ({ mount, root, editor: ed, selected }) => {
            const itemsRoot = root.find?.('[data-vb-items-root]')?.[0] ?? null;
            const items = itemsRoot
                ? [...(itemsRoot.components?.() ?? [])].filter((child) => hasItemAttr(child))
                : (hasItemAttr(root) ? [root] : []);
            const selection = selected ?? ed?.getSelected?.() ?? null;
            const focused = resolveFocusedItem(items, selection);

            // Selecting a repeating item: show only that item's fields.
            if (focused) {
                appendDeclarativeItemEditors(mount, items, ed, { selected: selection });

                return;
            }

            const sectionFields = findDeclarativeFields(root).filter((field) => {
                const owner = findClosestItem(field.component);

                return ! owner || owner === root;
            });

            appendDeclarativeFields(mount, sectionFields, ed, { heading: 'Content' });
            appendDeclarativeItemEditors(mount, items, ed, { selected: selection });
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
