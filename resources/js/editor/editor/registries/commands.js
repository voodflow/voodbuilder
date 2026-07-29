/**
 * Editor command registry.
 *
 * Modules/plugins register command factories here; the compatibility bridge
 * still calls editor.Commands.add directly during the transition.
 *
 * @deprecated-bridge remove-by 0.2.0 once all Commands.add call sites migrate
 */

/** @type {Map<string, { id: string, factory: Function, source?: string }>} */
const commands = new Map();

/**
 * @param {string} id
 * @param {(editor: object, context?: object) => object|void} factory
 * @param {{ source?: string }} [meta]
 */
export function registerEditorCommand(id, factory, meta = {}) {
    if (! id || typeof factory !== 'function') {
        throw new Error('registerEditorCommand requires an id and factory.');
    }

    commands.set(id, { id, factory, source: meta.source ?? 'unknown' });
}

/**
 * @param {object} editor
 * @param {object} [context]
 */
export function applyEditorCommands(editor, context = {}) {
    if (! editor?.Commands?.add) {
        return;
    }

    for (const entry of commands.values()) {
        const definition = entry.factory(editor, context);

        if (definition && typeof definition === 'object') {
            editor.Commands.add(entry.id, definition);
        }
    }
}

/**
 * Compatibility helper: register immediately on the live editor and track the id.
 *
 * @param {object} editor
 * @param {string} id
 * @param {object} definition
 * @param {{ source?: string }} [meta]
 */
export function addEditorCommand(editor, id, definition, meta = {}) {
    registerEditorCommand(id, () => definition, meta);

    if (editor?.Commands?.add) {
        editor.Commands.add(id, definition);
    }
}

export function listEditorCommands() {
    return [...commands.keys()];
}

export function clearEditorCommands() {
    commands.clear();
}
