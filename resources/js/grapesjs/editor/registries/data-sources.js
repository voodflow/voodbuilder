/**
 * Editor data-source contribution registry (bindings UI catalogue extensions).
 *
 * @deprecated-bridge remove-by 0.2.0
 */

/** @type {Map<string, object>} */
const sources = new Map();

/**
 * @param {object} source
 */
export function registerEditorDataSource(source) {
    if (! source?.id) {
        throw new Error('registerEditorDataSource requires an id.');
    }

    sources.set(source.id, source);
}

export function resolveEditorDataSources() {
    return [...sources.values()];
}

export function listEditorDataSources() {
    return [...sources.keys()];
}

export function clearEditorDataSources() {
    sources.clear();
}
