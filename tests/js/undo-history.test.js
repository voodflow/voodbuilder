/**
 * Undo has to land on the author's last change, not on the editor's bookkeeping.
 *
 * Reordering a section produces two history groups: the move, then — once the engine has
 * re-resolved the moved subtree's component types — a group of pure flag changes. The
 * flag group is newer, so a plain undo spent itself on it and the page did not move.
 */

import { describe, expect, it } from 'vitest';
import { registerUndoCommands, stepHistory, undoManagerInitOptions } from '../../resources/js/editor/editor-undo.js';

/**
 * A history entry shaped like the ones the engine records.
 *
 * `before` carries the component's whole previous attribute set while `after` carries
 * only the keys that changed — the asymmetry is the point, because reading it as a
 * symmetric diff makes every untouched key look cleared.
 */
function changeEntry(before, after) {
    const attributes = { type: 'change', before, after };

    return { get: (key) => attributes[key] };
}

function structuralEntry(type = 'remove') {
    const attributes = { type, before: undefined, after: undefined };

    return { get: (key) => attributes[key] };
}

/**
 * Minimal stand-in for the undo manager: a stack and a pointer, with undo walking
 * backwards over whole fusion groups the way the real one does.
 *
 * @param {Array<{ entries: object[] }>} groups  newest group last
 */
function fakeUndoManager(groups) {
    const stack = groups.flatMap((group) => group.entries);
    const boundaries = [];
    let cursor = -1;

    for (const group of groups) {
        cursor += group.entries.length;
        boundaries.push(cursor);
    }

    let pointer = stack.length - 1;
    const applied = [];

    return {
        applied,
        getStack: () => [...stack],
        getPointer: () => pointer,
        hasUndo: () => pointer >= 0,
        hasRedo: () => pointer < stack.length - 1,
        undo() {
            const groupIndex = boundaries.findIndex((boundary) => boundary === pointer);
            const target = groupIndex <= 0 ? -1 : boundaries[groupIndex - 1];
            applied.push(pointer - target);
            pointer = target;
        },
        redo() {
            const next = boundaries.find((boundary) => boundary > pointer);
            pointer = next ?? stack.length - 1;
        },
    };
}

const RETYPE_BEFORE = {
    name: 'Layout',
    type: 'default',
    tagName: 'div',
    content: '',
    attributes: { class: 'py-10' },
    components: { length: 3 },
};

describe('undo manager configuration', () => {
    it('does not treat selecting a component as an undoable edit', () => {
        // The engine registers the selection collection by default, so clicking around
        // the canvas silently filled the history and undo walked back through it.
        expect(undoManagerInitOptions().trackSelection).toBe(false);
    });

    it('declares how far back the history goes', () => {
        expect(undoManagerInitOptions().maximumStackLength).toBeGreaterThan(0);
    });
});

describe('stepHistory', () => {
    it('steps past a re-typing group to undo the move underneath it', () => {
        const undoManager = fakeUndoManager([
            { entries: [structuralEntry('remove'), structuralEntry('add')] },
            {
                entries: Array.from({ length: 13 }, () => changeEntry(RETYPE_BEFORE, {
                    name: 'List',
                    type: 'list',
                })),
            },
        ]);

        stepHistory({ UndoManager: undoManager }, 'undo');

        expect(undoManager.applied).toEqual([13, 2]);
        expect(undoManager.getPointer()).toBe(-1);
    });

    it('stops on the first group the author can see', () => {
        const undoManager = fakeUndoManager([
            { entries: [structuralEntry('remove')] },
            { entries: [changeEntry({ content: 'Old' }, { content: 'New' })] },
        ]);

        stepHistory({ UndoManager: undoManager }, 'undo');

        expect(undoManager.applied).toEqual([1]);
    });

    it('keeps a change to markup even when bookkeeping travelled with it', () => {
        // A type change that also rewrites attributes is an edit, not bookkeeping.
        const undoManager = fakeUndoManager([
            {
                entries: [changeEntry(RETYPE_BEFORE, {
                    type: 'list',
                    attributes: { class: 'py-20' },
                })],
            },
        ]);

        stepHistory({ UndoManager: undoManager }, 'undo');

        expect(undoManager.applied).toEqual([1]);
    });

    it('does nothing when there is no history', () => {
        const undoManager = fakeUndoManager([]);

        expect(() => stepHistory({ UndoManager: undoManager }, 'undo')).not.toThrow();
        expect(undoManager.applied).toEqual([]);
    });

    it('gives up rather than unwinding the whole history', () => {
        const bookkeepingGroups = Array.from({ length: 40 }, () => ({
            entries: [changeEntry(RETYPE_BEFORE, { name: 'List' })],
        }));

        const undoManager = fakeUndoManager(bookkeepingGroups);

        stepHistory({ UndoManager: undoManager }, 'undo');

        expect(undoManager.applied.length).toBeLessThan(bookkeepingGroups.length);
    });

    it('survives an editor with no undo manager', () => {
        expect(() => stepHistory({}, 'undo')).not.toThrow();
        expect(() => stepHistory(null, 'redo')).not.toThrow();
    });
});

describe('registerUndoCommands', () => {
    it('replaces the built-in commands so the keyboard shortcuts follow', () => {
        const added = {};

        registerUndoCommands({
            Commands: {
                add: (name, definition) => {
                    added[name] = definition;
                },
            },
        });

        expect(Object.keys(added).sort()).toEqual(['core:redo', 'core:undo']);
        expect(typeof added['core:undo'].run).toBe('function');
    });

    it('ignores an editor without a command registry', () => {
        expect(() => registerUndoCommands({})).not.toThrow();
    });
});
