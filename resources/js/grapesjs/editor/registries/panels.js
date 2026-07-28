/**
 * Editor panel contribution registry.
 *
 * @deprecated-bridge remove-by 0.2.0
 */

/** @type {Map<string, object>} */
const panels = new Map();

/**
 * @param {object} panel
 */
export function registerEditorPanel(panel) {
    if (! panel?.id) {
        throw new Error('registerEditorPanel requires an id.');
    }

    panels.set(panel.id, panel);
}

/**
 * @param {{ mode?: string }} [context]
 */
export function resolveEditorPanels(context = {}) {
    return [...panels.values()].filter((panel) => {
        if (typeof panel.when === 'function') {
            return panel.when(context) === true;
        }

        return true;
    });
}

export function listEditorPanels() {
    return [...panels.keys()];
}

export function clearEditorPanels() {
    panels.clear();
}
