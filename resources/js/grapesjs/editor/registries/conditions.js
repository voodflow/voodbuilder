/**
 * Editor condition-type contribution registry (mirrors PHP GrapesJsConditionHooks options).
 *
 * @deprecated-bridge remove-by 0.2.0
 */

/** @type {Map<string, object>} */
const types = new Map();

/**
 * @param {object} type
 */
export function registerEditorConditionType(type) {
    if (! type?.id && ! type?.key) {
        throw new Error('registerEditorConditionType requires id or key.');
    }

    const id = String(type.id ?? type.key);
    types.set(id, { ...type, id });
}

export function resolveEditorConditionTypes() {
    return [...types.values()];
}

export function listEditorConditionTypes() {
    return [...types.keys()];
}

export function clearEditorConditionTypes() {
    types.clear();
}
