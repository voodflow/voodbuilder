/**
 * @deprecated Shim — use `core/block-tree.js` and `blocks/settings/select.js`.
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
} from '../blocks/settings/select.js';
