/**
 * Editor boot phase + programmatic-mutation guards.
 *
 * Contract:
 * - `startEditorBoot` / `finishEditorBoot` (editor-build-status) gate the splash overlay.
 * - `shouldDeferCssRebuild` is the single defer check for page/component Tailwind JIT.
 * - Flags like `__voodbuilderLayersChromeFilterSyncing` are set only around bounded
 *   sync work — never leave them stuck across frames.
 */

export {
    finishEditorBoot,
    startEditorBoot,
    waitForEditorBootTasks,
} from './editor-build-status.js';

/** @param {object|null|undefined} editor */
export function isEditorBooting(editor) {
    return editor?.__voodbuilderBooting === true;
}

/**
 * True while programmatic canvas/inspector work is in flight — page JIT must not run.
 *
 * @param {object|null|undefined} editor
 */
export function shouldDeferCssRebuild(editor) {
    return Boolean(
        isEditorBooting(editor)
        || editor?.__voodbuilderLayoutStyleSilent
        || editor?.__voodbuilderChromeShellRefreshing
        || editor?.__voodbuilderLayersChromeFilterSyncing
        || editor?.__voodbuilderBulkStructureUpdate
        || (editor?.__voodbuilderCssRebuildSuspendDepth ?? 0) > 0
    );
}

/**
 * Layers chrome filter + render should pause during boot and heavy structure refresh.
 *
 * @param {object|null|undefined} editor
 * @param {{ syncing?: boolean }} [extra]
 */
export function shouldSuppressLayersSync(editor, extra = {}) {
    return Boolean(
        extra.syncing
        || shouldDeferCssRebuild(editor)
        || (editor?.__voodbuilderDynamicBlockRefreshing ?? 0) > 0
        || editor?.__voodbuilderLayoutStructureRefreshing
        || editor?.__voodbuilderLayoutDynamicRefreshPending
        || editor?.__voodbuilderActiveBlockDrag
    );
}

/**
 * Inspector MutationObserver scans (class chips, custom selects) pause during boot.
 *
 * @param {object|null|undefined} editor
 * @param {{ busy?: boolean }} [extra]
 */
export function shouldSuppressInspectorDomScan(editor, extra = {}) {
    return Boolean(extra.busy || isEditorBooting(editor));
}
