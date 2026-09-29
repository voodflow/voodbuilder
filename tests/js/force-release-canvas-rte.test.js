import { describe, expect, it, vi } from 'vitest';
import { forceReleaseCanvasRte } from '../../resources/js/editor/text-elements.js';

describe('forceReleaseCanvasRte', () => {
    it('disables Grapes editing and strips orphan contenteditable nodes', () => {
        const disableEditing = vi.fn();
        const rteDisable = vi.fn();
        const keepNode = {
            getAttribute: vi.fn(() => null),
            removeAttribute: vi.fn(),
            contains: () => false,
        };
        const orphan = {
            getAttribute: vi.fn(() => 'true'),
            removeAttribute: vi.fn(),
            contains: () => false,
        };

        const editor = {
            getEditing: () => ({
                view: { disableEditing },
                getEl: () => keepNode,
            }),
            RichTextEditor: { disable: rteDisable },
            Canvas: {
                getDocument: () => ({
                    querySelectorAll: () => [keepNode, orphan],
                }),
            },
        };

        forceReleaseCanvasRte(editor, keepNode);

        expect(disableEditing).toHaveBeenCalled();
        expect(rteDisable).toHaveBeenCalled();
        expect(orphan.removeAttribute).toHaveBeenCalledWith('contenteditable');
        expect(keepNode.removeAttribute).not.toHaveBeenCalledWith('contenteditable');
    });

    it('is re-entrancy safe while disableEditing fires nested release', () => {
        const editor = {
            getEditing: vi.fn(() => null),
            RichTextEditor: {
                disable: vi.fn(() => {
                    forceReleaseCanvasRte(editor);
                }),
            },
            Canvas: {
                getDocument: () => ({ querySelectorAll: () => [] }),
            },
        };

        expect(() => forceReleaseCanvasRte(editor)).not.toThrow();
        expect(editor.RichTextEditor.disable).toHaveBeenCalledTimes(1);
    });
});
