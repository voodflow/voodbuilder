/**
 * Declarative optional blocks (`data-vb-optional`).
 *
 * An element marked with `data-vb-optional="<key>"` toggles visibility from an
 * inspector checkbox. The checkbox shows at the panel root that owns the element:
 * a section block root or a `data-vb-panel` container (see declarative-fields.js).
 *
 * `data-vb-optional-scope="section"` pins the toggle to the section panel, so a
 * card inside a column keeps its toggle out of the column panel.
 */

/** Marker for an optional that only belongs to the section-level panel. */
export const OPTIONAL_SCOPE_ATTR = 'data-vb-optional-scope';

export const SECTION_SCOPE = 'section';

/**
 * @param {object|null|undefined} component
 * @returns {Record<string, string>}
 */
export function readComponentAttrs(component) {
    return {
        ...(component?.get?.('attributes') ?? {}),
        ...(component?.getAttributes?.() ?? {}),
    };
}

/**
 * @param {object} root
 * @param {(component: object) => void} visit
 * @returns {void}
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
 * @param {object} root
 * @returns {boolean}
 */
export function isSectionPanelRoot(root) {
    const attrs = readComponentAttrs(root);

    return Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-section-block')
        || Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-block');
}

/**
 * @param {object} panelRoot
 * @returns {string}
 */
export function panelScopeId(panelRoot) {
    const attrs = readComponentAttrs(panelRoot);

    return String(attrs['data-vb-panel'] ?? '').trim();
}

/**
 * Whether an optional element's toggle belongs to `panelRoot`.
 *
 * @param {object} panelRoot
 * @param {Record<string, string>} optionalAttrs
 * @returns {boolean}
 */
export function optionalBelongsToPanel(panelRoot, optionalAttrs) {
    const scope = String(optionalAttrs[OPTIONAL_SCOPE_ATTR] ?? '').trim();

    if (scope === SECTION_SCOPE) {
        return isSectionPanelRoot(panelRoot);
    }

    if (scope === '') {
        return true;
    }

    if (scope === panelScopeId(panelRoot)) {
        return true;
    }

    // A scoped optional still shows on the section panel as the fallback owner.
    return isSectionPanelRoot(panelRoot);
}

/**
 * @param {object} section
 * @param {string} key
 * @returns {boolean}
 */
export function optionalVisible(section, key) {
    const raw = section.getAttributes?.()?.[`data-vb-show-${key}`];

    return raw !== '0' && raw !== 'false';
}

/**
 * @param {object} section
 * @param {object} component
 * @param {boolean} hidden
 * @returns {void}
 */
function setComponentHidden(component, hidden) {
    if (hidden) {
        component.addAttributes?.({ hidden: '' });
        component.addClass?.('hidden');
    } else {
        component.removeAttributes?.('hidden');
        component.removeClass?.('hidden');
    }
}

/**
 * Cell role for tables authored with `data-vb-table-col`.
 *
 * @param {object} component
 * @returns {string|null}
 */
function tableColRole(component) {
    const attrs = readComponentAttrs(component);

    if (! Object.prototype.hasOwnProperty.call(attrs, 'data-vb-table-col')) {
        return null;
    }

    const value = attrs['data-vb-table-col'];

    if (value === 'action' || value === 'label') {
        return value;
    }

    return 'data';
}

/**
 * @param {object} section
 * @param {string} key
 * @param {boolean} visible
 * @returns {void}
 */
export function applySectionOptional(section, key, visible) {
    section.addAttributes?.({
        [`data-vb-show-${key}`]: visible ? '1' : '0',
    });

    walkComponents(section, (component) => {
        if (component === section) {
            return;
        }

        const optional = readComponentAttrs(component)['data-vb-optional'];
        const isActionCell = key === 'select' && tableColRole(component) === 'action';

        if (optional === key || isActionCell) {
            setComponentHidden(component, ! visible);
        }
    });
}

/**
 * Optionals owned by `root`, deduplicated by key.
 *
 * @param {object} root
 * @returns {{ key: string, label: string }[]}
 */
export function collectSectionOptionals(root) {
    const found = [];
    const seen = new Set();

    walkComponents(root, (component) => {
        if (component === root) {
            return;
        }

        const attrs = readComponentAttrs(component);
        const key = String(attrs['data-vb-optional'] ?? '').trim();

        if (! key || seen.has(key) || ! optionalBelongsToPanel(root, attrs)) {
            return;
        }

        seen.add(key);
        found.push({
            key,
            label: String(attrs['data-vb-optional-label'] || attrs['data-vb-field-label'] || key).trim() || key,
        });
    });

    return found;
}
