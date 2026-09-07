/**
 * Community / Pro sidebar allowlist for BlockManager.add.
 * null / missing = full library (Pro) or host override.
 */

/**
 * @param {import('grapesjs').Editor} editor
 * @param {string[]|null|undefined} allowlist
 */
export function setEditorBlockAllowlist(editor, allowlist) {
    editor.__voodbuilderBlockAllowlist = Array.isArray(allowlist) ? allowlist : null;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {string} blockId
 * @returns {boolean}
 */
export function isEditorBlockAllowed(editor, blockId) {
    const allowlist = editor?.__voodbuilderBlockAllowlist;

    if (! Array.isArray(allowlist)) {
        return true;
    }

    return allowlist.includes(blockId);
}
