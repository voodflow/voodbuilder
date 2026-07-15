/**
 * DOM attribute constants — single source of truth for Voodbuilder chrome/blocks.
 */

export const ATTR = {
    block: 'data-voodbuilder-block',
    config: 'data-voodbuilder-config',
    dropZone: 'data-voodbuilder-chrome-drop-zone',
    contentSlot: 'data-voodbuilder-content-slot',
    pageContent: 'data-voodbuilder-page-content',
    shellPart: 'data-voodbuilder-chrome-shell-part',
    shellLocked: 'data-voodbuilder-chrome-shell-locked',
    shell: 'data-voodbuilder-chrome-shell',
    editorScope: 'data-voodbuilder-editor-scope',
    siteHeader: 'data-voodbuilder-gjs-site-header',
    menu: 'data-voodbuilder-menu',
    brand: 'data-voodbuilder-brand',
    chrome: 'data-voodbuilder-chrome',
    hydrateSlots: 'data-voodbuilder-hydrate-slots',
};

/** @deprecated Use ATTR.block */
export const BLOCK_ID_ATTR = ATTR.block;

/** @deprecated Use ATTR.dropZone */
export const CHROME_DROP_ZONE_ATTR = ATTR.dropZone;

/** @deprecated Use ATTR.contentSlot */
export const CONTENT_SLOT_ATTR = ATTR.contentSlot;

/** @deprecated Use ATTR.pageContent */
export const PAGE_CONTENT_ATTR = ATTR.pageContent;

/** @deprecated Use ATTR.shellPart */
export const CHROME_SHELL_PART_ATTR = ATTR.shellPart;

/** @deprecated Use ATTR.shellLocked */
export const CHROME_SHELL_LOCKED_ATTR = ATTR.shellLocked;
