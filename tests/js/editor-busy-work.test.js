import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    beginEditorBuild,
    endEditorBuild,
    isEditorBuildBusy,
    resetEditorBuildStatus,
    runEditorBusyWork,
} from '../../resources/js/editor/editor-build-status.js';

function stubFrames() {
    vi.stubGlobal('window', globalThis);
    vi.stubGlobal('requestAnimationFrame', (callback) => {
        callback(0);

        return 1;
    });
}

describe('runEditorBusyWork', () => {
    beforeEach(() => {
        stubFrames();
    });

    afterEach(() => {
        resetEditorBuildStatus({
            __voodbuilderClassesBuildOverlay: null,
            __voodbuilderCanvasBuildOverlay: null,
        });
        vi.unstubAllGlobals();
    });

    it('shows a busy overlay before the work runs and clears it after', async () => {
        const order = [];
        const editor = {};

        await runEditorBusyWork(editor, {
            scope: 'revision-restore',
            label: 'Restoring revision…',
            work: () => {
                expect(isEditorBuildBusy()).toBe(true);
                order.push('work');
            },
        });

        expect(order).toEqual(['work']);
        expect(isEditorBuildBusy()).toBe(false);
    });

    it('clears the overlay when work throws', async () => {
        const editor = {};

        await expect(runEditorBusyWork(editor, {
            scope: 'payload-apply',
            work: () => {
                throw new Error('swap failed');
            },
        })).rejects.toThrow('swap failed');

        expect(isEditorBuildBusy()).toBe(false);
    });

    it('keeps nested scopes busy until the outer work finishes', async () => {
        const editor = {};

        beginEditorBuild(editor, 'revision-restore');

        await runEditorBusyWork(editor, {
            scope: 'payload-apply',
            work: () => {
                expect(isEditorBuildBusy()).toBe(true);
            },
        });

        expect(isEditorBuildBusy()).toBe(true);
        endEditorBuild(editor, 'revision-restore');
        expect(isEditorBuildBusy()).toBe(false);
    });
});
