/**
 * Curated atomic inserts for the canvas context menu.
 * Keep this short — full section library stays in the left Blocks panel.
 */

import { isCtaLikeComponent } from './dropzone-types.js';
import {
    canRemoveChromeEditorComponent,
    isChromeEditorProtectedComponent,
} from './chrome-editor-guards.js';

/** @type {Array<{id: string, labelKey: string, fallback: string, content?: object|string}>} */
export const CONTEXT_INSERT_ELEMENT_IDS = [
    { id: 'voodbuilder-button', labelKey: 'contextInsertButton', fallback: 'Button' },
    { id: 'voodbuilder-text-link', labelKey: 'contextInsertTextLink', fallback: 'Text link' },
    { id: 'voodbuilder-icon', labelKey: 'contextInsertIcon', fallback: 'Icon' },
    { id: 'voodbuilder-divider', labelKey: 'contextInsertDivider', fallback: 'Divider' },
    {
        id: 'text',
        labelKey: 'contextInsertText',
        fallback: 'Text',
        content: {
            type: 'text',
            tagName: 'p',
            classes: ['text-vp-text-2'],
            content: 'Text',
        },
    },
    { id: 'image', labelKey: 'contextInsertImage', fallback: 'Image' },
    {
        id: 'link',
        labelKey: 'contextInsertLink',
        fallback: 'Link',
        content: {
            type: 'link',
            classes: ['text-vp-brand-1', 'underline', 'underline-offset-2'],
            attributes: { href: '#' },
            content: 'Link',
        },
    },
];

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function isDroppableHost(component) {
    if (! component?.get) {
        return false;
    }

    const droppable = component.get('droppable');

    if (droppable === false) {
        return false;
    }

    const tag = componentTag(component);

    if (['img', 'svg', 'br', 'hr', 'input', 'textarea', 'video', 'iframe'].includes(tag)) {
        return false;
    }

    if (isCtaLikeComponent(component) || tag === 'a' || tag === 'button') {
        return false;
    }

    return true;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {object} labels
 * @returns {Array<{id: string, label: string, content: object|string}>}
 */
export function resolveContextInsertElements(editor, labels = {}) {
    const manager = editor?.BlockManager;
    const items = [];

    for (const entry of CONTEXT_INSERT_ELEMENT_IDS) {
        const block = manager?.get?.(entry.id) ?? null;
        const content = block
            ? (typeof block.get === 'function' ? block.get('content') : block.content)
            : entry.content;

        if (content == null || content === '') {
            continue;
        }

        items.push({
            id: entry.id,
            label: labels[entry.labelKey]
                ?? (typeof block?.get === 'function' ? block.get('label') : null)
                ?? entry.fallback,
            content,
        });
    }

    return items;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} target
 * @param {object|string} content
 * @returns {import('grapesjs').Component | null}
 */
export function insertContentNearComponent(editor, target, content) {
    if (! editor || ! target || content == null || content === '') {
        return null;
    }

    if (isChromeEditorProtectedComponent(target, editor) && ! canRemoveChromeEditorComponent(target, editor)) {
        return null;
    }

    let created = null;

    if (isDroppableHost(target)) {
        created = target.append(content)?.[0] ?? null;
    } else {
        const parent = target.parent?.();

        if (! parent) {
            return null;
        }

        const index = parent.components().indexOf(target);
        const options = index >= 0 ? { at: index + 1 } : undefined;
        created = parent.append(content, options)?.[0] ?? null;
    }

    if (created) {
        editor.select(created);
    }

    return created ?? null;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} component
 * @param {object} labels
 * @returns {{id: string, label: string, children: Array<object>}|null}
 */
export function buildContextInsertSubmenu(editor, component, labels = {}) {
    const elements = resolveContextInsertElements(editor, labels);

    if (elements.length === 0) {
        return null;
    }

    return {
        id: 'insert-element',
        label: labels.contextInsert ?? 'Insert',
        children: elements.map((entry) => ({
            id: `insert-${entry.id}`,
            label: entry.label,
            onSelect: () => {
                insertContentNearComponent(editor, component, entry.content);
            },
        })),
    };
}
