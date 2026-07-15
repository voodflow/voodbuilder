/**
 * Backward-compatible re-export — prefer `./block-settings/index.js` for new code.
 */

export {
    registerBlockSettings,
    registerBlockSettingsUi,
    refreshBlockSettingsUi,
    resolveInspectableBlockRoot,
    promoteInspectableBlockSelection,
} from './block-settings/index.js';
