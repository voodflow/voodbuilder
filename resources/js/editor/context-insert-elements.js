/**
 * Curated atomic inserts for the canvas context menu.
 * Mirrors foundation Basic + Media BlockManager tiles (not full section library).
 */

import { isCtaLikeComponent } from './dropzone-types.js';
import {
    canRemoveChromeEditorComponent,
    isChromeEditorProtectedComponent,
} from './chrome-editor-guards.js';

/**
 * @typedef {{id: string, labelKey: string, fallback: string, content?: object|string}} ContextInsertElement
 * @typedef {{id: string, labelKey: string, fallback: string, elements: ContextInsertElement[]}} ContextInsertGroup
 */

/** @type {ContextInsertGroup[]} */
export const CONTEXT_INSERT_GROUPS = [
    {
        id: 'basic',
        labelKey: 'contextInsertBasic',
        fallback: 'Basic',
        elements: [
            { id: 'voodbuilder-heading', labelKey: 'contextInsertHeading', fallback: 'Heading' },
            { id: 'voodbuilder-text', labelKey: 'contextInsertBasicText', fallback: 'Basic Text' },
            { id: 'voodbuilder-rich-text', labelKey: 'contextInsertRichText', fallback: 'Rich Text' },
            { id: 'voodbuilder-text-link', labelKey: 'contextInsertTextLink', fallback: 'Text link' },
            { id: 'voodbuilder-button', labelKey: 'contextInsertButton', fallback: 'Button' },
            { id: 'voodbuilder-icon', labelKey: 'contextInsertIcon', fallback: 'Icon' },
            { id: 'voodbuilder-divider', labelKey: 'contextInsertDivider', fallback: 'Divider' },
            { id: 'voodbuilder-code-block', labelKey: 'contextInsertCodeBlock', fallback: 'Code block' },
        ],
    },
    {
        id: 'media',
        labelKey: 'contextInsertMedia',
        fallback: 'Media',
        elements: [
            { id: 'image', labelKey: 'contextInsertImage', fallback: 'Image' },
            { id: 'video', labelKey: 'contextInsertVideo', fallback: 'Video' },
            { id: 'voodbuilder-image-gallery', labelKey: 'contextInsertImageGallery', fallback: 'Image gallery' },
            { id: 'voodbuilder-audio', labelKey: 'contextInsertAudio', fallback: 'Audio' },
            { id: 'voodbuilder-carousel', labelKey: 'contextInsertCarousel', fallback: 'Carousel' },
            { id: 'voodbuilder-slider', labelKey: 'contextInsertSlider', fallback: 'Slider' },
        ],
    },
];

/** Flat list of every insertable element (Basic + Media). */
export const CONTEXT_INSERT_ELEMENT_IDS = CONTEXT_INSERT_GROUPS.flatMap((group) => group.elements);

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
 * @param {ContextInsertElement} entry
 * @param {object} labels
 * @returns {{id: string, label: string, content: object|string}|null}
 */
function resolveInsertEntry(editor, entry, labels = {}) {
    const manager = editor?.BlockManager;
    const block = manager?.get?.(entry.id) ?? null;
    const content = block
        ? (typeof block.get === 'function' ? block.get('content') : block.content)
        : entry.content;

    if (content == null || content === '') {
        return null;
    }

    return {
        id: entry.id,
        label: labels[entry.labelKey]
            ?? (typeof block?.get === 'function' ? block.get('label') : null)
            ?? entry.fallback,
        content,
    };
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {object} labels
 * @returns {Array<{id: string, label: string, content: object|string}>}
 */
export function resolveContextInsertElements(editor, labels = {}) {
    const items = [];

    for (const entry of CONTEXT_INSERT_ELEMENT_IDS) {
        const resolved = resolveInsertEntry(editor, entry, labels);

        if (resolved) {
            items.push(resolved);
        }
    }

    return items;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {object} labels
 * @returns {Array<{id: string, label: string, elements: Array<{id: string, label: string, content: object|string}>}>}
 */
export function resolveContextInsertGroups(editor, labels = {}) {
    const groups = [];

    for (const group of CONTEXT_INSERT_GROUPS) {
        const elements = [];

        for (const entry of group.elements) {
            const resolved = resolveInsertEntry(editor, entry, labels);

            if (resolved) {
                elements.push(resolved);
            }
        }

        if (elements.length === 0) {
            continue;
        }

        groups.push({
            id: group.id,
            label: labels[group.labelKey] ?? group.fallback,
            elements,
        });
    }

    return groups;
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
    const groups = resolveContextInsertGroups(editor, labels);

    if (groups.length === 0) {
        return null;
    }

    return {
        id: 'insert-element',
        label: labels.contextInsert ?? 'Insert',
        children: groups.map((group) => ({
            id: `insert-group-${group.id}`,
            label: group.label,
            children: group.elements.map((entry) => ({
                id: `insert-${entry.id}`,
                label: entry.label,
                onSelect: () => {
                    insertContentNearComponent(editor, component, entry.content);
                },
            })),
        })),
    };
}
