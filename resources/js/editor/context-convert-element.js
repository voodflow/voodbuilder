/**
 * Context-menu “Convert to…” for textual canvas nodes.
 * Tag swaps (p ↔ h*) and Basic Text ↔ Rich Text keep author copy when possible.
 */

import { resolveBlockLabel } from './section-block-meta.js';
import {
    findRichTextHost,
    isBasicTextComponent,
    isRichTextComponent,
    lockRichTextChildren,
} from './text-elements.js';
import { readComponentHtml, sanitizeRichTextHtml } from './rich-text-content-settings.js';

/** @typedef {'heading' | 'paragraph' | 'basic-text' | 'rich-text'} ConvertKind */

/**
 * @typedef {{
 *   id: string,
 *   kind: ConvertKind,
 *   tagName: string,
 *   labelKey: string,
 *   fallback: string,
 * }} ConvertTarget
 */

/** @type {ConvertTarget[]} */
export const CONTEXT_CONVERT_TARGETS = [
    { id: 'h1', kind: 'heading', tagName: 'h1', labelKey: 'contextConvertH1', fallback: 'Heading 1' },
    { id: 'h2', kind: 'heading', tagName: 'h2', labelKey: 'contextConvertH2', fallback: 'Heading 2' },
    { id: 'h3', kind: 'heading', tagName: 'h3', labelKey: 'contextConvertH3', fallback: 'Heading 3' },
    { id: 'h4', kind: 'heading', tagName: 'h4', labelKey: 'contextConvertH4', fallback: 'Heading 4' },
    { id: 'h5', kind: 'heading', tagName: 'h5', labelKey: 'contextConvertH5', fallback: 'Heading 5' },
    { id: 'h6', kind: 'heading', tagName: 'h6', labelKey: 'contextConvertH6', fallback: 'Heading 6' },
    { id: 'p', kind: 'paragraph', tagName: 'p', labelKey: 'contextConvertParagraph', fallback: 'Paragraph' },
    { id: 'basic-text', kind: 'basic-text', tagName: 'p', labelKey: 'contextConvertBasicText', fallback: 'Basic Text' },
    { id: 'rich-text', kind: 'rich-text', tagName: 'div', labelKey: 'contextConvertRichText', fallback: 'Rich Text' },
];

const SEMANTIC_TEXT_TAGS = new Set(['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);

const RICH_MARKER_CLASSES = new Set(['vb-rich-text']);

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentType(component) {
    return String(component?.get?.('type') ?? '');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Prefer the Rich Text host when an inner locked child is the menu target.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function resolveConvertibleTextComponent(component) {
    if (! component?.get) {
        return null;
    }

    const richHost = findRichTextHost(component);

    if (richHost) {
        return richHost;
    }

    if (isBasicTextComponent(component)) {
        return component;
    }

    const type = componentType(component);
    const tag = componentTag(component);

    if (type === 'text' || type === 'textnode' || SEMANTIC_TEXT_TAGS.has(tag)) {
        return type === 'textnode' ? (component.parent?.() ?? null) : component;
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isConvertibleTextComponent(component) {
    return Boolean(resolveConvertibleTextComponent(component));
}

/**
 * @param {object} component
 * @returns {string}
 */
export function describeConvertibleTargetId(component) {
    const source = resolveConvertibleTextComponent(component);

    if (! source) {
        return '';
    }

    if (isRichTextComponent(source)) {
        return 'rich-text';
    }

    if (isBasicTextComponent(source)) {
        return 'basic-text';
    }

    const tag = componentTag(source);

    if (tag.startsWith('h') && SEMANTIC_TEXT_TAGS.has(tag)) {
        return tag;
    }

    if (tag === 'p') {
        return 'p';
    }

    return tag || '';
}

/**
 * @param {object} component
 * @returns {string[]}
 */
function readClasses(component) {
    return [...(component.getClasses?.() ?? [])]
        .map((name) => String(name ?? '').trim())
        .filter(Boolean);
}

/**
 * @param {object} component
 * @returns {string}
 */
export function readConvertiblePlainText(component) {
    const source = resolveConvertibleTextComponent(component) ?? component;
    const el = source?.getEl?.();

    if (el && typeof el.textContent === 'string') {
        return String(el.textContent).replace(/\s+/g, ' ').trim();
    }

    const content = source?.get?.('content');

    if (typeof content === 'string' && content.trim() !== '') {
        return content.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    }

    const parts = [];

    source?.components?.()?.forEach?.((child) => {
        if (child.get?.('type') === 'textnode') {
            parts.push(String(child.get('content') ?? ''));

            return;
        }

        parts.push(readConvertiblePlainText(child));
    });

    return parts.join(' ').replace(/\s+/g, ' ').trim();
}

/**
 * @param {object} component
 * @returns {string}
 */
function htmlForRichText(component) {
    if (isRichTextComponent(component)) {
        return readComponentHtml(component);
    }

    const el = component.getEl?.();
    const tag = componentTag(component) || 'p';

    if (el?.innerHTML != null && String(el.innerHTML).trim() !== '') {
        const inner = String(el.innerHTML).trim();

        if (SEMANTIC_TEXT_TAGS.has(tag)) {
            return sanitizeRichTextHtml(`<${tag}>${inner}</${tag}>`);
        }

        return sanitizeRichTextHtml(inner.includes('<') ? inner : `<p>${inner}</p>`);
    }

    const text = readConvertiblePlainText(component) || 'Text';

    if (SEMANTIC_TEXT_TAGS.has(tag) && tag !== 'p') {
        return sanitizeRichTextHtml(`<${tag}>${escapeHtml(text)}</${tag}>`);
    }

    return sanitizeRichTextHtml(`<p>${escapeHtml(text)}</p>`);
}

/**
 * @param {ConvertTarget} target
 * @param {object} source
 * @returns {object}
 */
function buildReplacementDefinition(target, source) {
    const classes = readClasses(source).filter((name) => ! RICH_MARKER_CLASSES.has(name));

    if (target.kind === 'rich-text') {
        const richClasses = [...classes];

        if (! richClasses.includes('vb-rich-text')) {
            richClasses.push('vb-rich-text');
        }

        for (const name of ['space-y-3', 'text-base', 'leading-relaxed', 'text-vp-text-2']) {
            if (! richClasses.includes(name)) {
                richClasses.push(name);
            }
        }

        return {
            type: 'voodbuilder-rich-text',
            tagName: 'div',
            name: resolveBlockLabel('voodbuilder-rich-text', 'Rich Text'),
            classes: richClasses,
            attributes: {
                'data-voodbuilder-rich-text': '',
            },
            components: htmlForRichText(source),
            editable: false,
            droppable: false,
            selectable: true,
            hoverable: true,
            highlightable: true,
        };
    }

    const text = readConvertiblePlainText(source) || 'Text';

    if (target.kind === 'basic-text') {
        return {
            type: 'voodbuilder-text',
            tagName: target.tagName || 'p',
            name: resolveBlockLabel('voodbuilder-text', 'Basic Text'),
            classes,
            attributes: {
                'data-voodbuilder-text': '',
            },
            content: text,
            editable: true,
            droppable: false,
        };
    }

    // Heading / paragraph — Grapes `text` (same family as Basic → Heading tile).
    return {
        type: 'text',
        tagName: target.tagName,
        name: target.kind === 'heading'
            ? (target.fallback ?? 'Heading')
            : 'Paragraph',
        classes,
        content: text,
        editable: true,
        droppable: false,
    };
}

/**
 * Fast path: only the HTML tag changes (h2 → h3, p → h2) on a simple text node.
 *
 * @param {object} source
 * @param {ConvertTarget} target
 * @returns {boolean}
 */
function canTagSwapInPlace(source, target) {
    if (target.kind === 'rich-text' || target.kind === 'basic-text') {
        return false;
    }

    if (isRichTextComponent(source) || isBasicTextComponent(source)) {
        return false;
    }

    const type = componentType(source);

    return type === 'text' || SEMANTIC_TEXT_TAGS.has(componentTag(source));
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {object} component
 * @param {string} targetId
 * @returns {object|null}
 */
export function convertTextComponent(editor, component, targetId) {
    const source = resolveConvertibleTextComponent(component);
    const target = CONTEXT_CONVERT_TARGETS.find((entry) => entry.id === targetId) ?? null;

    if (! editor || ! source || ! target) {
        return null;
    }

    if (describeConvertibleTargetId(source) === target.id) {
        editor.select?.(source);

        return source;
    }

    if (canTagSwapInPlace(source, target)) {
        source.set({
            tagName: target.tagName,
            type: 'text',
            editable: true,
            name: target.kind === 'heading' ? (target.fallback ?? 'Heading') : 'Paragraph',
        });

        const attrs = { ...(source.getAttributes?.() ?? {}) };
        delete attrs['data-voodbuilder-text'];
        delete attrs['data-voodbuilder-rich-text'];
        source.setAttributes?.(attrs);
        editor.select?.(source);

        return source;
    }

    const parent = source.parent?.();

    if (! parent?.components) {
        return null;
    }

    const index = parent.components().indexOf(source);

    if (index < 0) {
        return null;
    }

    const definition = buildReplacementDefinition(target, source);

    source.remove();

    const created = parent.append(definition, { at: index })?.[0] ?? null;

    if (! created) {
        return null;
    }

    if (target.kind === 'rich-text') {
        lockRichTextChildren(created);
    }

    editor.select?.(created);

    return created;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {object} component
 * @param {object} labels
 * @returns {{id: string, label: string, children: Array<object>}|null}
 */
export function buildContextConvertSubmenu(editor, component, labels = {}) {
    const source = resolveConvertibleTextComponent(component);

    if (! source) {
        return null;
    }

    const currentId = describeConvertibleTargetId(source);

    return {
        id: 'convert-element',
        label: labels.contextConvert ?? 'Convert to',
        children: CONTEXT_CONVERT_TARGETS.map((entry) => ({
            id: `convert-${entry.id}`,
            label: labels[entry.labelKey] ?? entry.fallback,
            disabled: entry.id === currentId,
            onSelect: () => {
                convertTextComponent(editor, source, entry.id);
            },
        })),
    };
}
