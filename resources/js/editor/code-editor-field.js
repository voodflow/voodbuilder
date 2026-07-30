/**
 * Lazy facade for CodeMirror code fields.
 * Heavy deps (@codemirror/*, js-beautify) load only when a code dialog opens.
 */

/** @type {Promise<typeof import('./code-editor-field-cm.js')>|null} */
let implPromise = null;

function loadImpl() {
    implPromise ??= import('./code-editor-field-cm.js');

    return implPromise;
}

/**
 * Prefetch the CodeMirror chunk (e.g. when opening the dialog shell).
 *
 * @returns {Promise<void>}
 */
export async function ensureCodeEditorReady() {
    await loadImpl();
}

/**
 * @param {Parameters<typeof import('./code-editor-field-cm.js').formatCodeForEditor>} args
 * @returns {Promise<string>}
 */
export async function formatCodeForEditor(...args) {
    const impl = await loadImpl();

    return impl.formatCodeForEditor(...args);
}

/**
 * @param {Parameters<typeof import('./code-editor-field-cm.js').createCodeEditorField>[0]} options
 * @returns {Promise<ReturnType<typeof import('./code-editor-field-cm.js').createCodeEditorField>>}
 */
export async function createCodeEditorField(options) {
    const impl = await loadImpl();

    return impl.createCodeEditorField(options);
}

/**
 * @returns {Promise<void>}
 */
export async function destroyCodeEditorFields() {
    if (! implPromise) {
        return;
    }

    const impl = await implPromise;
    impl.destroyCodeEditorFields();
}
