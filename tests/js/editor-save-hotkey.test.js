import { afterEach, describe, expect, it, vi } from 'vitest';
import { isEditorSaveHotkey, registerEditorSaveHotkey } from '../../resources/js/editor/editor-save-hotkey.js';

function keyEvent(overrides = {}) {
    return {
        key: 's',
        code: 'KeyS',
        keyCode: 83,
        which: 83,
        ctrlKey: false,
        metaKey: false,
        altKey: false,
        shiftKey: false,
        preventDefault: vi.fn(),
        ...overrides,
    };
}

describe('isEditorSaveHotkey', () => {
    it('matches Ctrl+S (Windows / Linux)', () => {
        expect(isEditorSaveHotkey(keyEvent({ ctrlKey: true }))).toBe(true);
    });

    it('matches Cmd+S (macOS)', () => {
        expect(isEditorSaveHotkey(keyEvent({ metaKey: true }))).toBe(true);
    });

    it('ignores bare S and non-save modifier combinations', () => {
        expect(isEditorSaveHotkey(keyEvent())).toBe(false);
        expect(isEditorSaveHotkey(keyEvent({ ctrlKey: true, shiftKey: true }))).toBe(false);
        expect(isEditorSaveHotkey(keyEvent({ ctrlKey: true, altKey: true }))).toBe(false);
        expect(isEditorSaveHotkey(keyEvent({ ctrlKey: true, key: 'a', code: 'KeyA', keyCode: 65 }))).toBe(false);
    });

    it('still matches when only keyCode is present (GrapesJS iframe delegation)', () => {
        expect(isEditorSaveHotkey(keyEvent({
            ctrlKey: true,
            key: '',
            code: '',
            keyCode: 83,
        }))).toBe(true);
    });
});

describe('registerEditorSaveHotkey', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('prevents the browser Save dialog and invokes the handler', () => {
        const listeners = new Map();
        const fakeWindow = {
            addEventListener: (type, handler, options) => {
                listeners.set(type, { handler, options });
            },
            removeEventListener: (type, handler) => {
                const current = listeners.get(type);
                if (current?.handler === handler) {
                    listeners.delete(type);
                }
            },
        };
        vi.stubGlobal('window', fakeWindow);

        const onSave = vi.fn();
        const unsubscribe = registerEditorSaveHotkey(onSave);
        const binding = listeners.get('keydown');

        expect(binding?.options).toBe(true);

        const parentEvent = { preventDefault: vi.fn() };
        const event = keyEvent({
            ctrlKey: true,
            _parentEvent: parentEvent,
        });

        binding.handler(event);

        expect(onSave).toHaveBeenCalledTimes(1);
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(parentEvent.preventDefault).toHaveBeenCalledTimes(1);

        unsubscribe();
        expect(listeners.has('keydown')).toBe(false);
    });
});
