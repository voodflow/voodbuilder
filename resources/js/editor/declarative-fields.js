/**
 * Declarative inspector fields from HTML markers.
 *
 * Authors mark editable slots in block HTML; VoodBuilder builds the Content panel
 * and persists values into the canvas (save/load is the page HTML).
 *
 * Markers:
 * - data-vb-field="key" — required field id
 * - data-vb-field-type="text|textarea|icon|number|rich|status" — default text
 * - data-vb-field-label="Title" — optional panel label
 * - data-vb-status="yes|no" — with type=status (✓ / ✕ toggle in the inspector)
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
    createLightRichTextEditor,
    readComponentHtml,
    writeComponentHtml,
} from './rich-text-content-settings.js';
import {
    DEFAULT_TABLER_ICON,
    DEFAULT_TABLER_ICON_STROKE,
    DEFAULT_TABLER_ICON_STYLE,
    resolveTablerIconName,
    resolveTablerIconStroke,
    resolveTablerIconStyle,
} from './tabler-icons-catalog.js';
import { findRichTextHost, lockRichTextChildren } from './text-elements.js';

const FIELD_ATTR = 'data-vb-field';
const FIELD_TYPE_ATTR = 'data-vb-field-type';
const FIELD_LABEL_ATTR = 'data-vb-field-label';
const STATUS_ATTR = 'data-vb-status';
const ITEM_ATTR = 'data-vb-item';

// Fixed semantic colors — never follow site brand (✓ green / ✕ red).
const STATUS_YES_HTML = '<span class="inline-flex items-center justify-center text-emerald-500" aria-label="Yes"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>';
const STATUS_NO_HTML = '<span class="inline-flex items-center justify-center text-red-500" aria-label="No"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>';

/**
 * @param {object} component
 * @returns {boolean}
 */
function hasFieldAttr(component) {
    const attrs = {
        ...(component?.get?.('attributes') ?? {}),
        ...(component?.getAttributes?.() ?? {}),
    };

    return Object.prototype.hasOwnProperty.call(attrs, FIELD_ATTR);
}

function hasItemAttr(component) {
    const attrs = {
        ...(component?.get?.('attributes') ?? {}),
        ...(component?.getAttributes?.() ?? {}),
    };

    return Object.prototype.hasOwnProperty.call(attrs, ITEM_ATTR);
}

function isTableRowComponent(component) {
    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();
    const type = String(component?.get?.('type') ?? '');

    return tag === 'tr' || type === 'row';
}

function isInsideThead(component) {
    let current = component;

    while (current) {
        const tag = String(current.get?.('tagName') ?? '').toLowerCase();
        const type = String(current.get?.('type') ?? '');

        if (tag === 'thead' || type === 'thead') {
            return true;
        }

        if (tag === 'table' || type === 'table' || tag === 'section') {
            return false;
        }

        current = current.parent?.();
    }

    return false;
}

function isTableBodyRow(component) {
    return isTableRowComponent(component) && ! isInsideThead(component);
}

function isTableCellComponent(component) {
    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();
    const type = String(component?.get?.('type') ?? '');

    return tag === 'td' || tag === 'th' || type === 'cell';
}

function isActionTableCell(component) {
    const attrs = {
        ...(component?.get?.('attributes') ?? {}),
        ...(component?.getAttributes?.() ?? {}),
    };

    if (attrs['data-vb-table-col'] === 'action' || attrs['data-vb-optional'] === 'select') {
        return true;
    }

    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();
    const children = [...(component?.components?.() ?? [])];

    if (children.some((child) => String(child.get?.('tagName') ?? '').toLowerCase() === 'input')) {
        return true;
    }

    return tag === 'th' && children.length === 0 && readFieldText(component) === '';
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
    // Always stop at nested data-vb-item trees so section/category panels
    // never dump child item fields (e.g. FAQ questions inside a category).
    return collectFields(scope, { stopAtNestedItems: true });
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
        } else if (isTableCellComponent(component) && ! isActionTableCell(component)) {
            const childHasField = [...(component.components?.() ?? [])].some((child) => hasFieldAttr(child));

            if (! childHasField) {
                const tag = String(component.get?.('tagName') ?? '').toLowerCase();

                fields.push({
                    key: String(component.getAttributes?.()?.[FIELD_ATTR] ?? '').trim()
                        || `cell-${component.cid ?? fields.length}`,
                    type: 'text',
                    label: tag === 'th' ? 'Column' : 'Cell',
                    component,
                });
            }
        }

        for (const child of [...(component.components?.() ?? [])]) {
            if (stopAtNestedItems && child !== scope && (hasItemAttr(child) || isTableBodyRow(child))) {
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
        if (hasItemAttr(current) || isTableBodyRow(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Resolve which repeating item the current canvas selection belongs to.
 *
 * Walks ancestor `data-vb-item` nodes until one is in `items`. Nested lists
 * (FAQ question inside category) otherwise match the innermost item, which is
 * not in the outer categories list — so the Content panel never drills in.
 *
 * @param {object[]} items
 * @param {object|null|undefined} selected
 * @returns {{ item: object, index: number }|null}
 */
export function resolveFocusedItem(items, selected) {
    if (! Array.isArray(items) || items.length === 0 || ! selected) {
        return null;
    }

    let current = selected;

    while (current) {
        const index = items.findIndex((item) => item === current
            || (item?.cid != null && item.cid === current.cid));

        if (index >= 0) {
            return { item: items[index], index };
        }

        current = current.parent?.();
    }

    // After Content writes Grapes may remount inner text nodes; match by containment.
    for (let index = 0; index < items.length; index += 1) {
        const item = items[index];

        if (item && isUnderComponent(selected, item)) {
            return { item, index };
        }
    }

    // Remounted / detached models: fall back to live DOM ancestry.
    const selectedEl = selected.getEl?.() ?? selected.getView?.()?.el ?? selected.view?.el ?? null;

    if (selectedEl?.nodeType === 1 || selectedEl?.nodeType === 3) {
        const el = selectedEl.nodeType === 3 ? selectedEl.parentElement : selectedEl;

        if (el) {
            for (let index = 0; index < items.length; index += 1) {
                const itemEl = items[index]?.getEl?.()
                    ?? items[index]?.getView?.()?.el
                    ?? items[index]?.view?.el
                    ?? null;

                if (itemEl && (itemEl === el || itemEl.contains?.(el))) {
                    return { item: items[index], index };
                }
            }
        }
    }

    return null;
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

    if (['status', 'check', 'boolean', 'yesno'].includes(value)) {
        return 'status';
    }

    if (['text', 'textarea', 'icon', 'number', 'rich', 'richeditor', 'html'].includes(value)) {
        return value === 'richeditor' || value === 'html' ? 'rich' : value;
    }

    const attrs = component?.getAttributes?.() ?? {};

    if (Object.prototype.hasOwnProperty.call(attrs, STATUS_ATTR)) {
        return 'status';
    }

    if (isIconComponent(component) || findIconHost(component)) {
        return 'icon';
    }

    if (findRichTextHost(component)) {
        return 'rich';
    }

    return 'text';
}

/**
 * @param {object} component
 * @returns {'yes'|'no'}
 */
export function readStatusValue(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const raw = String(attrs[STATUS_ATTR] ?? '').trim().toLowerCase();

    if (raw === 'yes' || raw === 'no') {
        return raw;
    }

    const el = component?.getEl?.() ?? component?.getView?.()?.el ?? component?.view?.el;
    const labeled = el?.querySelector?.('[aria-label]')?.getAttribute?.('aria-label')
        ?? el?.getAttribute?.('aria-label');
    const label = String(labeled ?? '').trim().toLowerCase();

    if (label === 'yes' || label === 'no') {
        return label;
    }

    const html = String(el?.innerHTML ?? component?.get?.('content') ?? '');

    if (html.includes('aria-label="Yes"') || html.includes("aria-label='Yes'")) {
        return 'yes';
    }

    return 'no';
}

/**
 * @param {object} component
 * @param {'yes'|'no'|string} value
 * @param {object|null} [editor]
 */
export function writeStatusValue(component, value, editor = null) {
    if (! component) {
        return;
    }

    const status = String(value ?? '').trim().toLowerCase() === 'yes' ? 'yes' : 'no';
    const html = status === 'yes' ? STATUS_YES_HTML : STATUS_NO_HTML;

    const apply = () => {
        releaseCanvasRteIfEditing(editor, component);

        const attrs = {
            ...(component.getAttributes?.() ?? {}),
            [STATUS_ATTR]: status,
        };

        component.setAttributes?.(attrs);
        component.addAttributes?.({ [STATUS_ATTR]: status });
        component.components?.(html);
    };

    if (! editor) {
        apply();

        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;
    editor.__voodbuilderBulkStructureUpdate = true;

    try {
        apply();
    } finally {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                const next = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
                editor.__voodbuilderSettingsChangeDepth = next;

                if (next <= 0) {
                    editor.__voodbuilderSettingsChange = false;
                    delete editor.__voodbuilderSettingsChangeDepth;
                    editor.__voodbuilderFlushBlockSettingsRender?.();
                }

                editor.__voodbuilderBulkStructureUpdate = false;
            });
        });
    }
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
                    // Replay selection / updates that were deferred while writing.
                    editor.__voodbuilderFlushBlockSettingsRender?.();
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

    if (field.type === 'status') {
        return renderStatusField(field, editor);
    }

    if (field.type === 'rich') {
        return renderRichField(field, editor);
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
function renderStatusField(field, editor) {
    const current = readStatusValue(field.component);
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-editor-form-field';
    wrap.setAttribute('data-vb-status-field', field.key);

    const label = document.createElement('div');
    label.className = 'voodbuilder-editor-form-label';
    label.textContent = field.label;
    wrap.appendChild(label);

    const row = document.createElement('div');
    row.className = 'voodbuilder-editor-segmented';
    row.setAttribute('role', 'radiogroup');
    row.setAttribute('aria-label', field.label);

    /** @type {HTMLButtonElement[]} */
    const buttons = [];

    for (const option of [
        { value: 'yes', label: '✓ Yes' },
        { value: 'no', label: '✕ No' },
    ]) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'voodbuilder-editor-segmented__btn';
        btn.textContent = option.label;
        btn.dataset.value = option.value;
        btn.setAttribute('role', 'radio');
        btn.setAttribute('aria-checked', option.value === current ? 'true' : 'false');

        if (option.value === current) {
            btn.classList.add('is-active');
        }

        btn.addEventListener('click', () => {
            buttons.forEach((node) => {
                const active = node.dataset.value === option.value;
                node.classList.toggle('is-active', active);
                node.setAttribute('aria-checked', active ? 'true' : 'false');
            });
            writeStatusValue(field.component, option.value, editor);
        });

        buttons.push(btn);
        row.appendChild(btn);
    }

    wrap.appendChild(row);

    return wrap;
}

/**
 * @param {{ key: string, label: string, component: object }} field
 * @param {object} editor
 * @returns {HTMLElement}
 */
function renderRichField(field, editor) {
    const host = findRichTextHost(field.component) ?? field.component;
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-editor-form-field';
    wrap.setAttribute('data-vb-rich-field', field.key);

    const label = document.createElement('label');
    label.className = 'voodbuilder-editor-form-label';
    label.textContent = field.label;
    wrap.appendChild(label);

    let writeTimer = 0;
    const flushHtml = (html) => {
        if (writeTimer) {
            window.clearTimeout(writeTimer);
            writeTimer = 0;
        }

        writeComponentHtml(host, html, editor);
    };

    const editorUi = createLightRichTextEditor({
        value: readComponentHtml(host),
        labels: {},
        editor,
        component: host,
        onChange: (html) => {
            if (writeTimer) {
                window.clearTimeout(writeTimer);
            }

            writeTimer = window.setTimeout(() => {
                writeTimer = 0;
                writeComponentHtml(host, html, editor);
            }, 120);
        },
    });

    editorUi.root.querySelector('.voodbuilder-editor-rte__visual')
        ?.addEventListener('blur', () => flushHtml(editorUi.getHtml()), true);
    editorUi.root.querySelector('.voodbuilder-editor-rte__code')
        ?.addEventListener('blur', () => flushHtml(editorUi.getHtml()), true);

    wrap.appendChild(editorUi.root);
    lockRichTextChildren(host);

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
function headerLabelsForRow(row) {
    let table = row;

    while (table) {
        const tag = String(table.get?.('tagName') ?? '').toLowerCase();
        const type = String(table.get?.('type') ?? '');

        if (tag === 'table' || type === 'table') {
            break;
        }

        table = table.parent?.();
    }

    if (! table) {
        return [];
    }

    const labels = [];

    walkComponents(table, (component) => {
        if (! isTableCellComponent(component) || isActionTableCell(component) || ! isInsideThead(component)) {
            return;
        }

        const attrs = component.getAttributes?.() ?? {};
        labels.push(
            String(attrs[FIELD_LABEL_ATTR] ?? '').trim()
            || readFieldText(component)
            || 'Cell',
        );
    });

    return labels;
}

function withTableColumnLabels(fields, item) {
    if (! isTableBodyRow(item)) {
        return fields;
    }

    const headers = headerLabelsForRow(item);

    if (headers.length === 0) {
        return fields;
    }

    let cellIndex = 0;

    return fields.map((field) => {
        if (! isTableCellComponent(field.component) && ! hasFieldAttr(field.component)) {
            return field;
        }

        if (isTableCellComponent(field.component) && isActionTableCell(field.component)) {
            return field;
        }

        const isCell = isTableCellComponent(field.component)
            || isTableCellComponent(field.component.parent?.());

        if (! isCell) {
            return field;
        }

        const label = headers[cellIndex] || field.label;
        cellIndex += 1;

        return { ...field, label };
    });
}

/**
 * @param {HTMLElement} mount
 * @param {object[]} items
 * @param {object} editor
 * @param {{ selected?: object|null, focusOnly?: boolean, selectHint?: string, itemSingular?: string }} [options]
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

        const fields = withTableColumnLabels(findItemDeclarativeFields(focused.item), focused.item);

        if (fields.length === 0) {
            return;
        }

        const singular = String(options.itemSingular || 'Item').trim() || 'Item';

        appendDeclarativeFields(mount, fields, editor, {
            heading: `${singular} ${focused.index + 1}`,
        });

        return;
    }

    const singular = String(options.itemSingular || 'Item').trim() || 'Item';

    items.forEach((item, index) => {
        const fields = withTableColumnLabels(findItemDeclarativeFields(item), item);

        if (fields.length === 0) {
            return;
        }

        appendDeclarativeFields(mount, fields, editor, {
            heading: `${singular} ${index + 1}`,
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

        if (
            hasFieldAttr(component)
            || (isTableCellComponent(component) && ! isActionTableCell(component))
        ) {
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

    const unlockDeclarativeHost = (component) => {
        if (
            ! component?.set
            || (
                ! hasFieldAttr(component)
                && ! (isTableCellComponent(component) && ! isActionTableCell(component))
            )
        ) {
            return;
        }

        component.set({
            editable: true,
            selectable: true,
            hoverable: true,
            highlightable: true,
        }, { silent: true });
    };

    editor.on?.('component:add', unlockDeclarativeHost);
    editor.on?.('load', () => {
        const wrapper = editor.getWrapper?.();

        if (wrapper) {
            walkComponents(wrapper, unlockDeclarativeHost);
        }
    });

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
