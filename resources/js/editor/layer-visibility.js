/**
 * Persist Layers eye-hide across save / CSS rebuild / sibling edits.
 *
 * Grapes LayerManager.setVisible() only sets style.display = none. That often
 * lands solely in CssComposer #id rules; getHtml() then omits inline display,
 * and a later JIT / bake / remount that drops the #id rule resurrects cards
 * (e.g. hide Forms → edit another badge → save → Forms visible again).
 *
 * Durable contract:
 * - data-vb-layer-hidden="1" on the node (survives id changes and CSS rebuilds)
 * - inline display:none (so getHtml carries hide without relying on #id CSS)
 */

import { debugSwallowed } from './debug-swallowed.js';

export const LAYER_HIDDEN_ATTR = 'data-vb-layer-hidden';
export const LAYER_NAME_ATTR = 'data-voodbuilder-layer-name';

const SILENT_HIDE_STYLE = { inline: true, noEvent: true };
const CORE_NODES_BLOCK_ID = 'voodflow_core_nodes_grid';

/**
 * @param {object} component
 * @returns {boolean}
 */
export function componentHasLayerHiddenMarker(component) {
    const attrs = component?.getAttributes?.({ noClass: true, noStyle: true }) ?? {};

    return String(attrs[LAYER_HIDDEN_ATTR] ?? '') === '1';
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function styleLooksHidden(component) {
    const inline = component?.getStyle?.({ inline: true }) ?? {};
    const merged = component?.getStyle?.() ?? {};
    const display = String(inline.display ?? merged.display ?? '').trim().toLowerCase();

    return display.indexOf('none') === 0;
}

/**
 * @param {object} editor
 * @param {object} component
 * @returns {boolean}
 */
export function isLayerHidden(editor, component) {
    if (! component) {
        return false;
    }

    if (componentHasLayerHiddenMarker(component)) {
        return true;
    }

    if (styleLooksHidden(component)) {
        return true;
    }

    try {
        if (editor?.LayerManager?.isVisible && ! editor.LayerManager.isVisible(component)) {
            return true;
        }
    } catch (error) {
        // Ignore LayerManager races during boot.
        debugSwallowed(error);
    }

    const id = component.getId?.();

    if (id && editor?.Css?.getIdRule) {
        const ruleDisplay = String(editor.Css.getIdRule(id)?.getStyle?.()?.display ?? '')
            .trim()
            .toLowerCase();

        if (ruleDisplay.indexOf('none') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * @param {object} component
 * @returns {string}
 */
export function readLayerName(component) {
    return String(component?.getAttributes?.()?.[LAYER_NAME_ATTR] ?? '').trim();
}

/**
 * Collect layer-names that are currently hidden under a root (dynamic remount).
 *
 * @param {object} root
 * @param {object} [editor]
 * @returns {string[]}
 */
export function captureHiddenLayerNames(root, editor = null) {
    const names = new Set();

    const visit = (component) => {
        if (! component) {
            return;
        }

        const name = readLayerName(component);

        if (name !== '' && isLayerHidden(editor, component)) {
            names.add(name);
        }

        const children = component.components?.() ?? [];

        for (const child of children) {
            visit(child);
        }
    };

    visit(root);

    return [...names];
}

/**
 * Re-apply hide after a dynamic block remount wiped inline styles.
 *
 * @param {object} root
 * @param {string[]} names
 */
export function restoreHiddenLayerNames(root, names) {
    const wanted = new Set((names ?? []).map((name) => String(name).trim()).filter(Boolean));

    if (wanted.size === 0 || ! root) {
        return;
    }

    const visit = (component) => {
        if (! component) {
            return;
        }

        const name = readLayerName(component);

        if (name !== '' && wanted.has(name)) {
            persistLayerHiddenMarker(component, true);
        }

        const children = component.components?.() ?? [];

        for (const child of children) {
            visit(child);
        }
    };

    visit(root);
}

/**
 * Keep Core nodes Blade config in sync when the Custom Nodes card is eye-hidden
 * so front remount does not resurrect it.
 *
 * @param {object} component
 * @param {boolean} hidden
 */
export function syncCoreNodesCustomCardConfig(component, hidden) {
    const name = readLayerName(component).toLowerCase();

    // Default marketing card title from CoreNodesGridBlock::defaultConfig().
    if (name !== 'custom nodes') {
        return;
    }

    let current = component;
    let blockRoot = null;

    while (current) {
        const blockId = String(current.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

        if (blockId === CORE_NODES_BLOCK_ID) {
            blockRoot = current;
            break;
        }

        current = current.parent?.();
    }

    if (! blockRoot) {
        return;
    }

    let config = {};

    try {
        const raw = blockRoot.getAttributes?.()?.['data-voodbuilder-config'] ?? '{}';
        config = typeof raw === 'string' ? JSON.parse(raw.replace(/&quot;/g, '"')) : { ...raw };
    } catch {
        config = {};
    }

    const next = {
        ...config,
        show_custom_nodes_card: ! hidden,
    };

    blockRoot.addAttributes({
        'data-voodbuilder-config': JSON.stringify(next),
    });
    blockRoot.set?.('voodbuilderConfig', next, { silent: true });
    // Force a fresh Blade render next time (config change must remount).
    delete blockRoot.__voodbuilderLastDynamicRenderFingerprint;
    delete blockRoot.__voodbuilderLastDynamicRenderHtml;
}

/**
 * @param {object} component
 * @param {boolean} hidden
 */
export function persistLayerHiddenMarker(component, hidden) {
    if (! component?.addAttributes) {
        return;
    }

    if (hidden) {
        component.addAttributes({ [LAYER_HIDDEN_ATTR]: '1' });
        component.addStyle?.({ display: 'none' }, SILENT_HIDE_STYLE);

        const id = component.getId?.();

        if (id && component.em?.Css?.setIdRule) {
            const existing = { ...(component.em.Css.getIdRule(id)?.getStyle?.() ?? {}) };
            component.em.Css.setIdRule(id, {
                ...existing,
                display: 'none',
            });
        }

        syncCoreNodesCustomCardConfig(component, true);

        return;
    }

    const attrs = { ...(component.getAttributes?.({ noClass: true, noStyle: true }) ?? {}) };

    if (Object.prototype.hasOwnProperty.call(attrs, LAYER_HIDDEN_ATTR)) {
        delete attrs[LAYER_HIDDEN_ATTR];
        component.setAttributes?.(attrs);
        component.removeAttributes?.(LAYER_HIDDEN_ATTR);
    }

    const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };

    if (String(inline.display ?? '').trim().toLowerCase().indexOf('none') === 0) {
        const prev = component.get?.('__prev-display');

        component.removeStyle?.('display');

        if (prev) {
            component.addStyle?.({ display: prev }, SILENT_HIDE_STYLE);
            component.unset?.('__prev-display');
        }
    }

    syncCoreNodesCustomCardConfig(component, false);
}

/**
 * Before getHtml(): force marker + inline display:none for every hidden node.
 *
 * @param {object} editor
 */
export function syncLayerVisibilityForExport(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper?.onAll) {
        return;
    }

    wrapper.onAll((component) => {
        if (! isLayerHidden(editor, component)) {
            return;
        }

        persistLayerHiddenMarker(component, true);
        syncCoreNodesCustomCardConfig(component, true);
    });
}

/**
 * After load / CSS hydrate: re-apply hide from marker or leftover #id rules.
 *
 * @param {object} editor
 */
export function restoreLayerVisibilityFromAttributes(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper?.onAll) {
        return;
    }

    wrapper.onAll((component) => {
        if (! isLayerHidden(editor, component)) {
            return;
        }

        persistLayerHiddenMarker(component, true);

        try {
            if (editor.LayerManager?.setVisible && editor.LayerManager.isVisible?.(component)) {
                editor.LayerManager.setVisible(component, false);
            }
        } catch (error) {
            // Marker + inline already applied.
            debugSwallowed(error);
        }
    });
}

/**
 * @param {object} editor
 */
export function registerLayerVisibilityPersistence(editor) {
    if (! editor || editor.__voodbuilderLayerVisibilityRegistered) {
        return;
    }

    editor.__voodbuilderLayerVisibilityRegistered = true;

    const lm = editor.LayerManager;

    if (lm && typeof lm.setVisible === 'function' && ! lm.__voodbuilderLayerVisibilityPatched) {
        const original = lm.setVisible.bind(lm);

        lm.setVisible = (component, value) => {
            original(component, value);
            persistLayerHiddenMarker(component, ! value);
        };
        lm.__voodbuilderLayerVisibilityPatched = true;
    }

    editor.on('load', () => {
        window.requestAnimationFrame(() => {
            try {
                restoreLayerVisibilityFromAttributes(editor);
            } catch (error) {
                // Ignore hydrate races.
                debugSwallowed(error);
            }
        });
        window.setTimeout(() => {
            try {
                restoreLayerVisibilityFromAttributes(editor);
            } catch (error) {
                // Ignore hydrate races.
                debugSwallowed(error);
            }
        }, 200);
    });
}
