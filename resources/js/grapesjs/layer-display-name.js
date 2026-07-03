/**
 * Layer tree display names (visual only — never mutates element id or data attributes).
 */

import { COMPONENT_ATTR } from './component-instance-type.js';
import { resolveBlockLabel } from './section-block-meta.js';

function humanizeToken(value) {
    return String(value ?? '')
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function resolveBlockLayerLabel(blockId, editor = null) {
    const id = String(blockId ?? '').trim();

    if (! id) {
        return 'Block';
    }

    const fromMeta = resolveBlockLabel(id, '');

    if (fromMeta) {
        return fromMeta;
    }

    const block = editor?.BlockManager?.get?.(id);

    if (block) {
        const label = String(block.get?.('label') ?? '').trim();

        if (label) {
            return label;
        }
    }

    return humanizeToken(id);
}

export function resolveComponentCatalogName(component, catalog = []) {
    const componentId = component?.getAttributes?.()?.[COMPONENT_ATTR];

    if (! componentId) {
        return null;
    }

    const item = catalog.find((entry) => String(entry.id) === String(componentId));

    return item?.name ? String(item.name).trim() : null;
}

export function resolveLayerDisplayName(component, editor = null) {
    if (! component) {
        return 'Element';
    }

    const bindingKey = component.getAttributes?.()['data-voodbuilder-bind'];

    if (bindingKey) {
        const current = String(component.getName?.() ?? '').trim();

        if (current && ! current.startsWith('Dynamic:')) {
            return current;
        }

        return current || 'Dynamic field';
    }

    const blockId = component.getAttributes?.()['data-voodbuilder-block'];

    if (blockId) {
        return resolveBlockLayerLabel(blockId, editor);
    }

    const sectionBlockId = component.getAttributes?.()['data-voodbuilder-section-block'];

    if (sectionBlockId) {
        return resolveBlockLayerLabel(sectionBlockId, editor);
    }

    const catalogName = resolveComponentCatalogName(component, editor?.__voodbuilderComponentsCatalog ?? []);

    if (catalogName) {
        return catalogName;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const type = String(component.get('type') ?? '').toLowerCase();

    if (type.includes('image') || tag === 'img') {
        return 'Image';
    }

    if (tag === 'section') {
        return 'Section';
    }

    const current = String(component.getName?.() ?? '').trim();

    return current || humanizeToken(tag || 'element');
}

export function collectLayerDisplayNames(editor, excludeComponent = null) {
    const names = new Set();
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return names;
    }

    wrapper.find('*').forEach((component) => {
        if (excludeComponent && component === excludeComponent) {
            return;
        }

        const name = String(component.getName?.() ?? '').trim();

        if (name) {
            names.add(name.toLowerCase());
        }
    });

    return names;
}

export function uniquifyLayerDisplayName(name, takenNames) {
    const base = String(name ?? '').trim() || 'Element';
    let candidate = base;
    let index = 2;

    while (takenNames.has(candidate.toLowerCase())) {
        candidate = `${base} (${index})`;
        index += 1;
    }

    takenNames.add(candidate.toLowerCase());

    return candidate;
}

export function applyLayerDisplayName(editor, component, name) {
    if (! editor || ! component || ! name) {
        return null;
    }

    const taken = collectLayerDisplayNames(editor, component);
    const unique = uniquifyLayerDisplayName(name, taken);

    editor.LayerManager?.setName?.(component, unique);
    component.set('name', unique);

    return unique;
}

export function syncLayerDisplayName(component, editor = null) {
    if (! component) {
        return;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-layer-label'] === 'custom') {
        return;
    }

    const displayName = resolveLayerDisplayName(component, editor);

    if (! displayName) {
        return;
    }

    component.set('name', displayName);
}

export function syncAllLayerDisplayNames(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    wrapper.find('[data-voodbuilder-block], [data-voodbuilder-section-block], [data-voodbuilder-component], [data-voodbuilder-component-scope]').forEach((component) => {
        syncLayerDisplayName(component, editor);
    });
}
