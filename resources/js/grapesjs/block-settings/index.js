/**
 * Block settings — public module API.
 */

export {
    readBlockId,
    findBlockRoot,
    findPrimaryBlockInContainer,
    resolveInspectableBlockRoot,
    shouldPromoteSelectionToRoot,
    BLOCK_ID_ATTR,
} from './selection.js';

export {
    registerBlockSettings,
    resolveBlockSettingsTarget,
    resolveDescriptorForRoot,
    listRegisteredBlockSettings,
} from './registry.js';

export {
    registerBlockSettingsUi,
    refreshBlockSettingsUi,
    promoteInspectableBlockSelection,
} from './ui.js';
