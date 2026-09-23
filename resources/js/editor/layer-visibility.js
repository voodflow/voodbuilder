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

export const LAYER_HIDDEN_ATTR = 'data-vb-layer-hidden';

const SILENT_HIDE_STYLE = { inline: true, noEvent: true };

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
    } catch {
        // Ignore LayerManager races during boot.
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
        } catch {
            // Marker + inline already applied.
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
            } catch {
                // Ignore hydrate races.
            }
        });
        window.setTimeout(() => {
            try {
                restoreLayerVisibilityFromAttributes(editor);
            } catch {
                // Ignore hydrate races.
            }
        }, 200);
    });
}
