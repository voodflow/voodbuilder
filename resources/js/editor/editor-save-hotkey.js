/**
 * Ctrl+S / Cmd+S page save — Windows, Linux, and macOS.
 *
 * Native capture on `window` (not GrapesJS Keymaps): keymaster skips INPUT/TEXTAREA,
 * and authors expect Save to work while editing inspector fields. Canvas keydowns are
 * re-dispatched onto the parent iframe element by GrapesJS, so the same listener covers
 * both the shell UI and the canvas.
 */

/**
 * @param {KeyboardEvent} event
 * @returns {boolean}
 */
export function isEditorSaveHotkey(event) {
    if (! event || event.altKey || event.shiftKey) {
        return false;
    }

    if (! (event.metaKey || event.ctrlKey)) {
        return false;
    }

    const key = typeof event.key === 'string' ? event.key.toLowerCase() : '';

    // Prefer `key` when the browser provides it; fall back for GrapesJS iframe
    // re-dispatch where `key` can be empty but keyCode/which still carry 'S'.
    if (key) {
        return key === 's';
    }

    return event.code === 'KeyS' || event.keyCode === 83 || event.which === 83;
}

/**
 * @param {(event: KeyboardEvent) => void} onSave
 * @returns {() => void} unsubscribe
 */
export function registerEditorSaveHotkey(onSave) {
    const onKeyDown = (event) => {
        if (! isEditorSaveHotkey(event)) {
            return;
        }

        event.preventDefault();
        // GrapesJS stashes the original canvas event on `_parentEvent`.
        event._parentEvent?.preventDefault?.();

        onSave(event);
    };

    window.addEventListener('keydown', onKeyDown, true);

    return () => {
        window.removeEventListener('keydown', onKeyDown, true);
    };
}
