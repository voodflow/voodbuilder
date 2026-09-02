/**
 * Undo history hygiene.
 *
 * The editor keeps the canvas tidy behind the author's back: it maintains drop spacers,
 * evicts host-page overlays that bled into the canvas, and refreshes item counters. Every
 * one of those is a component mutation, and the undo manager records component mutations.
 *
 * That is what made undo look broken. The maintenance runs *in response to* the author's
 * own change, so it lands on the stack after it — press undo and you spend a step
 * reverting a spacer the author never knew existed, with nothing visible happening.
 *
 * Anything the author did not do goes through `withoutUndo`.
 */

/**
 * Run a canvas mutation without recording it in the undo history.
 *
 * Reentrant: nested calls collapse into the outermost one, because `UndoManager.skip`
 * restarts tracking on exit and a nested call would resume it too early.
 *
 * @template T
 * @param {object} editor
 * @param {() => T} callback
 * @returns {T}
 */
export function withoutUndo(editor, callback) {
    const undoManager = editor?.UndoManager;

    if (typeof undoManager?.skip !== 'function' || editor.__voodbuilderUndoSkipping) {
        return callback();
    }

    editor.__voodbuilderUndoSkipping = true;

    let result;

    try {
        undoManager.skip(() => {
            result = callback();
        });
    } finally {
        editor.__voodbuilderUndoSkipping = false;
    }

    return result;
}

/**
 * Make the state on screen the oldest thing undo can reach.
 *
 * Loading a page is hundreds of model mutations — parsing markup, resolving component
 * types, locking shell parts, hydrating styles. All of it was landing in the history, so
 * an author who had just opened a page already had a full stack of steps they never took,
 * and undoing far enough would have started dismantling the page load itself.
 *
 * @param {object} editor
 */
export function resetUndoHistory(editor) {
    try {
        editor?.UndoManager?.clear?.();
    } catch (error) {
        console.warn('VoodBuilder: could not reset the undo history.', error);
    }
}

/**
 * How many author actions the history holds.
 *
 * Declared rather than inherited: the GrapesJS default of 500 was never a decision, and
 * an author who has just discovered that undo works needs to know how far back it goes.
 */
export const UNDO_HISTORY_LENGTH = 200;

/**
 * Component attributes that say how the editor may treat a node, not what the page says.
 *
 * Re-parenting a component makes the engine re-resolve its type, which replaces the whole
 * attribute set — so a history entry lists every key regardless of what moved. Only the
 * keys whose *values* actually differ are consulted, and if all of them are in here the
 * entry cannot have changed anything the author can see.
 *
 * Deliberately absent, because they do reach the page: `tagName`, `attributes`, `classes`,
 * `style`, `styles`, `content`, `components`, `script`.
 */
const BOOKKEEPING_KEYS = new Set([
    'type',
    'name',
    'draggable',
    'droppable',
    'removable',
    'copyable',
    'selectable',
    'hoverable',
    'highlightable',
    'layerable',
    'editable',
    'stylable',
    'stylable-require',
    'unstylable',
    'style-signature',
    'resizable',
    'badgable',
    'locked',
    'toolbar',
    'traits',
    'status',
    'state',
    'open',
    'void',
    'icon',
    'dmode',
    'propagate',
    'delegate',
    'script-props',
    'script-export',
    '__symbol',
    '__symbols',
    '__symbol_ovrd',
    '_undo',
    '_undoexc',
]);

/** Never walk the whole history looking for something visible. */
const MAX_BOOKKEEPING_GROUPS = 8;

/**
 * Same value for history purposes?
 *
 * Attribute values range from primitives to Backbone collections, so this stays shallow
 * on purpose: identical references and equal primitives are unchanged, collections are
 * compared by size, and anything else is treated as changed rather than risk swallowing
 * a real edit.
 */
function sameHistoryValue(before, after) {
    if (before === after) {
        return true;
    }

    if (before === null || after === null || before === undefined || after === undefined) {
        return false;
    }

    if (typeof before !== 'object' || typeof after !== 'object') {
        return before === after;
    }

    const beforeSize = before.models?.length ?? before.length;
    const afterSize = after.models?.length ?? after.length;

    if (typeof beforeSize === 'number' && typeof afterSize === 'number') {
        return beforeSize === afterSize;
    }

    return false;
}

/**
 * Does this entry only move bookkeeping?
 *
 * The two sides of a history entry are asymmetric: `before` holds the component's whole
 * previous attribute set, `after` only the keys that changed. Reading both as a symmetric
 * diff makes every untouched key look like it was cleared, which is why an entry that
 * only relabels a layer looked like it rewrote the component.
 */
function isBookkeepingEntry(entry) {
    if (entry?.get?.('type') !== 'change') {
        return false;
    }

    const before = entry.get('before') ?? {};
    const after = entry.get('after') ?? {};
    const changedKeys = Object.keys(after)
        .filter((key) => ! sameHistoryValue(before[key], after[key]));

    if (changedKeys.length === 0) {
        // Nothing moved at all — stepping over it is safe either way.
        return true;
    }

    return changedKeys.every((key) => BOOKKEEPING_KEYS.has(key));
}

/**
 * Step the history until something the author can see changes.
 *
 * One reorder produces two history groups: the move itself, then — about 200ms later,
 * once the engine has re-resolved the moved subtree's component types — a group of pure
 * flag changes. The flag group is the newest, so a single undo spent itself on it and
 * the page did not move. This is what made undo look dead after dragging in Layers.
 *
 * @param {object} editor
 * @param {'undo'|'redo'} direction
 */
export function stepHistory(editor, direction = 'undo') {
    const undoManager = editor?.UndoManager;

    if (! undoManager) {
        return;
    }

    for (let attempt = 0; attempt <= MAX_BOOKKEEPING_GROUPS; attempt += 1) {
        const stack = undoManager.getStack();
        const from = undoManager.getPointer();

        if (direction === 'undo') {
            if (! undoManager.hasUndo()) {
                return;
            }

            undoManager.undo();
        } else {
            if (! undoManager.hasRedo()) {
                return;
            }

            undoManager.redo();
        }

        const to = undoManager.getPointer();

        if (to === from) {
            return;
        }

        const applied = direction === 'undo'
            ? stack.slice(to + 1, from + 1)
            : stack.slice(from + 1, to + 1);

        if (applied.length === 0 || ! applied.every(isBookkeepingEntry)) {
            return;
        }
    }
}

/**
 * Route the toolbar buttons and the keyboard shortcuts through {@link stepHistory}.
 *
 * Overriding the built-in commands rather than the buttons: Ctrl+Z is bound to
 * `core:undo` by the engine's keymaps, and an author who found undo broken with the
 * mouse would have found it broken from the keyboard too.
 *
 * @param {object} editor
 */
export function registerUndoCommands(editor) {
    if (! editor?.Commands?.add) {
        return;
    }

    editor.Commands.add('core:undo', {
        run: (ed) => stepHistory(ed, 'undo'),
    });

    editor.Commands.add('core:redo', {
        run: (ed) => stepHistory(ed, 'redo'),
    });
}

/**
 * @returns {{ maximumStackLength: number, trackSelection: boolean }}
 */
export function undoManagerInitOptions() {
    return {
        maximumStackLength: UNDO_HISTORY_LENGTH,
        /**
         * Selecting a component is not an edit.
         *
         * GrapesJS registers the selection collection with the undo manager, so clicking
         * around the canvas silently fills the history: undo then walks back through past
         * selections instead of undoing the last change.
         */
        trackSelection: false,
    };
}
