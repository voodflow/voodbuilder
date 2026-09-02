/**
 * @vitest-environment node
 */

import { beforeEach, describe, expect, it, vi } from 'vitest';

import registerEditorTailwindPlugin from '../../resources/js/editor/editor-tailwind-plugin.js';

// The build-status module the plugin reports through drives window timers, including a
// long "stuck build" failsafe. Unref the handles: a pending real timer would keep the
// Node event loop alive and hang the run instead of failing.
globalThis.window = globalThis.window ?? {
    setTimeout: (...args) => setTimeout(...args).unref?.(),
    clearTimeout: (...args) => clearTimeout(...args),
    requestAnimationFrame: (fn) => setTimeout(fn, 0).unref?.(),
    addEventListener() {},
};

/**
 * Minimal stand-in for the GrapesJS editor surface the plugin touches.
 */
function makeEditor() {
    const commands = new Map();
    const listeners = new Map();

    const editor = {
        Commands: {
            add: (id, definition) => commands.set(id, definition),
            get: (id) => commands.get(id),
        },
        on: (event, handler) => {
            listeners.set(event, [...(listeners.get(event) ?? []), handler]);
        },
        trigger: (event, ...args) => {
            for (const handler of listeners.get(event) ?? []) {
                handler(...args);
            }
        },
    };

    // GrapesJS passes the editor as the first argument to a command's run().
    editor.runCommand = (id, options) => commands.get(id)?.run?.(editor, { id }, options);

    return editor;
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('editor Tailwind plugin loading', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('registers build-tailwind without fetching the compiler', () => {
        const editor = makeEditor();
        const loadPlugin = vi.fn(() => Promise.resolve(() => {}));

        registerEditorTailwindPlugin(editor, { autobuild: false, buildButton: false, loadPlugin });

        // The command must exist so callers and the UI keep working…
        expect(typeof editor.Commands.get('build-tailwind')?.run).toBe('function');
        // …but the 371 KiB compiler chunk must not be requested at boot.
        expect(loadPlugin).not.toHaveBeenCalled();
    });

    it('loads the compiler on first build and delegates to the upstream command', async () => {
        const editor = makeEditor();
        let appliedOptions = null;
        const upstreamRun = vi.fn();

        const loadPlugin = vi.fn(() => Promise.resolve((editorInstance, options) => {
            appliedOptions = options;
            // Upstream replaces our placeholder when it is applied.
            editorInstance.Commands.add('build-tailwind', { run: upstreamRun });
        }));

        registerEditorTailwindPlugin(editor, { autobuild: false, buildButton: false, loadPlugin });
        editor.runCommand('build-tailwind');
        await flush();

        expect(loadPlugin).toHaveBeenCalledTimes(1);
        expect(appliedOptions).not.toBeNull();
        expect(appliedOptions.autobuild).toBe(false);
        expect(upstreamRun).toHaveBeenCalledTimes(1);
    });

    it('loads eagerly when autobuild is requested', async () => {
        const editor = makeEditor();
        const loadPlugin = vi.fn(() => Promise.resolve(() => {}));

        // autobuild has to observe the canvas from boot, so it cannot be deferred.
        registerEditorTailwindPlugin(editor, { autobuild: true, loadPlugin });
        await flush();

        expect(loadPlugin).toHaveBeenCalledTimes(1);
    });

    it('loads eagerly when the toolbar button is enabled', async () => {
        const editor = makeEditor();
        const loadPlugin = vi.fn(() => Promise.resolve(() => {}));

        registerEditorTailwindPlugin(editor, { autobuild: false, buildButton: true, loadPlugin });
        await flush();

        expect(loadPlugin).toHaveBeenCalledTimes(1);
    });

    it('contains a failed load instead of breaking editor boot', async () => {
        const editor = makeEditor();
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

        expect(() => registerEditorTailwindPlugin(editor, {
            autobuild: true,
            loadPlugin: () => Promise.reject(new Error('offline')),
        })).not.toThrow();

        await flush();

        expect(consoleError).toHaveBeenCalled();
    });

    it('fetches the compiler only once across repeated builds', async () => {
        const editor = makeEditor();
        const upstreamRun = vi.fn();
        const loadPlugin = vi.fn(() => Promise.resolve((editorInstance) => {
            editorInstance.Commands.add('build-tailwind', { run: upstreamRun });
        }));

        registerEditorTailwindPlugin(editor, { autobuild: false, buildButton: false, loadPlugin });

        editor.runCommand('build-tailwind');
        await flush();
        editor.runCommand('build-tailwind');
        await flush();

        expect(loadPlugin).toHaveBeenCalledTimes(1);
        expect(upstreamRun).toHaveBeenCalledTimes(2);
    });

    it('does not recurse when the compiler registers no command', async () => {
        const editor = makeEditor();
        const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {});
        // A no-op upstream leaves our placeholder in place; delegating to it again would
        // re-enter the same handler forever.
        const loadPlugin = vi.fn(() => Promise.resolve(() => {}));

        registerEditorTailwindPlugin(editor, { autobuild: false, buildButton: false, loadPlugin });
        editor.runCommand('build-tailwind');

        // A recursion would never settle; this resolving at all is the assertion.
        await flush();
        await flush();

        expect(loadPlugin).toHaveBeenCalledTimes(1);
        expect(consoleWarn).toHaveBeenCalled();
    });
});
