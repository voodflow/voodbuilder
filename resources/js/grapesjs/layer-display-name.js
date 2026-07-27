/**
 * Layer tree display names (visual only — never mutates element id).
 * Custom names persist via data-voodbuilder-layer-* attrs (survive data-gjs strip).
 */

import { COMPONENT_ATTR } from './component-instance-type.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { walkComponentTree } from './tailwind-visual-style.js';

export const LAYER_LABEL_ATTR = 'data-voodbuilder-layer-label';
export const LAYER_NAME_ATTR = 'data-voodbuilder-layer-name';

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

/**
 * @param {object|null|undefined} component
 * @returns {string}
 */
export function readCustomLayerName(component) {
    if (! component) {
        return '';
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs[LAYER_LABEL_ATTR] !== 'custom') {
        return '';
    }

    const stored = String(attrs[LAYER_NAME_ATTR] ?? '').trim();

    if (stored) {
        return stored;
    }

    return String(component.get?.('custom-name') ?? component.getName?.() ?? '').trim();
}

export function resolveLayerDisplayName(component, editor = null) {
    if (! component) {
        return 'Element';
    }

    const custom = readCustomLayerName(component);

    if (custom) {
        return custom;
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

    walkComponentTree(wrapper, (component) => {
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

/**
 * Persist a custom layer label on the component (HTML attrs + Grapes model).
 *
 * @param {object} editor
 * @param {object} component
 * @param {string} name
 * @param {{ uniquify?: boolean }} [options]
 * @returns {string|null}
 */
export function applyLayerDisplayName(editor, component, name, options = {}) {
    if (! editor || ! component || ! name) {
        return null;
    }

    const uniquify = options.uniquify !== false;
    const next = uniquify
        ? uniquifyLayerDisplayName(name, collectLayerDisplayNames(editor, component))
        : String(name).trim();

    if (! next) {
        return null;
    }

    editor.__voodbuilderSyncingLayerNames = true;

    try {
        component.addAttributes({
            [LAYER_LABEL_ATTR]: 'custom',
            [LAYER_NAME_ATTR]: next,
        });
        component.set('custom-name', next);
        component.set('name', next);
        editor.LayerManager?.setName?.(component, next);
    } finally {
        editor.__voodbuilderSyncingLayerNames = false;
    }

    return next;
}

export function syncLayerDisplayName(component, editor = null, options = {}) {
    if (! component) {
        return;
    }

    const force = options.force === true;
    const custom = readCustomLayerName(component);

    if (custom) {
        // After HTML reload, Grapes `custom-name` is gone — restore from our attr.
        if (String(component.get?.('custom-name') ?? '') !== custom) {
            component.set('custom-name', custom, { silent: true });
        }

        if (String(component.get?.('name') ?? '') !== custom) {
            component.set('name', custom, { silent: true });
        }

        return;
    }

    // GrapesJS move() re-fires component:add; never clobber an existing layer title
    // (e.g. "Vb Nasa Spotlight" → "Spotlight split" from BlockManager).
    const existingCustom = String(component.get?.('custom-name') ?? '').trim();

    if (existingCustom && ! force) {
        return;
    }

    const existingName = String(component.get?.('name') ?? '').trim();

    if (existingName && ! force) {
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

    walkComponentTree(wrapper, (component) => {
        const attrs = component.getAttributes?.() ?? {};

        if (
            attrs[LAYER_LABEL_ATTR] === 'custom'
            || attrs[LAYER_NAME_ATTR]
            || attrs['data-voodbuilder-block']
            || attrs['data-voodbuilder-section-block']
            || attrs['data-voodbuilder-component']
            || attrs['data-voodbuilder-component-scope']
        ) {
            syncLayerDisplayName(component, editor);
        }
    });
}

/**
 * GrapesJS layer dblclick rename sets `custom-name` only — persist it in HTML attrs
 * so Save/reload keeps the label (data-gjs-* is stripped server-side).
 *
 * @param {object} editor
 */
export function registerLayerDisplayNamePersistence(editor) {
    if (! editor || editor.__voodbuilderLayerNamePersistenceRegistered) {
        return;
    }

    editor.__voodbuilderLayerNamePersistenceRegistered = true;

    const bind = (component) => {
        if (! component || component.__voodbuilderLayerNameBound) {
            return;
        }

        component.__voodbuilderLayerNameBound = true;

        component.on('change:custom-name', () => {
            if (editor.__voodbuilderSyncingLayerNames) {
                return;
            }

            const name = String(component.get('custom-name') ?? '').trim();

            if (! name) {
                return;
            }

            applyLayerDisplayName(editor, component, name, { uniquify: false });
        });
    };

    const bindTree = () => {
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        walkComponentTree(wrapper, bind);
    };

    editor.on('load', bindTree);
    editor.on('component:add', bind);
    bindTree();
}
