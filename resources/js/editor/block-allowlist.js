/**
 * Community / Pro sidebar allowlist for **client-registered** BlockManager.add calls
 * (layout, utilities, animated tiles, companion form contact, …).
 *
 * Server-loaded catalog blocks must NOT use this gate — PHP
 * {@see EditorCommunityBlockCatalog::filterEditorBlocks} already filtered them and
 * intentionally keeps third-party companion IDs (vforms managed forms, etc.).
 *
 * null / missing = full library (Pro without Elements accordion limit) or host override.
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
