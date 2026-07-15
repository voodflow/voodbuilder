/**
 * Block settings — public module API.
 */

export {
    readBlockId,
    findBlockRoot,
    findPrimaryBlock,
    findPrimaryBlockInModelTree,
    findPrimaryBlockInContainer,
    findInspectableRoot,
    resolveInspectableBlockRoot,
    shouldPromoteSelectionToRoot,
    isBlockRoot,
    ensureRootInspectable,
    BLOCK_ID_ATTR,
} from './select.js';

export {
    registerBlockSettings,
    resolveSettings,
    resolveBlockSettingsTarget,
    resolveDescriptorForRoot,
    listRegisteredBlockSettings,
} from './registry.js';

export {
    registerSettingsUi,
    registerBlockSettingsUi,
    refreshBlockSettingsUi,
    promoteRoot,
    promoteInspectableBlockSelection,
    runWithSettingsChangeGuard,
} from './ui.js';
