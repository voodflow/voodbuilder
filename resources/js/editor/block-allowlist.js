/**
 * Community / Pro sidebar allowlist for **client-registered** BlockManager.add calls
 * (layout, utilities, animated tiles, companion form contact, …).
 *
 * Server-loaded catalog blocks must NOT use this gate — PHP
 * {@see EditorCommunityBlockCatalog::filterEditorBlocks} already filtered them and
 * intentionally keeps third-party companion IDs (vforms managed forms, etc.).
 *
 * null / missing = full library (Pro without Elements accordion limit) or host override.
 *
 * Catalog constants may use logical ids (`site_nav_simple`) while BlockManager uses
 * `voodbuilder-site_nav_simple` — match either form.
 */

/**
 * @param {string} blockId
 * @returns {string[]}
 */
export function blockIdAliases(blockId) {
    const id = String(blockId ?? '').trim();

    if (! id) {
        return [];
    }

    const aliases = [id];

    if (id.startsWith('voodbuilder-')) {
        aliases.push(id.slice('voodbuilder-'.length));
    } else {
        aliases.push(`voodbuilder-${id}`);
    }

    return [...new Set(aliases.filter(Boolean))];
}

/**
 * @param {string[]|null|undefined} allowlist
 * @param {string} blockId
 * @returns {boolean}
 */
export function allowlistIncludesBlockId(allowlist, blockId) {
    if (! Array.isArray(allowlist)) {
        return true;
    }

    return blockIdAliases(blockId).some((alias) => allowlist.includes(alias));
}

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
    return allowlistIncludesBlockId(editor?.__voodbuilderBlockAllowlist, blockId);
}
