/**
 * Recompiles scoped Tailwind utilities for pasted components in the canvas iframe.
 * Uses the same compile-css API as code import (Tailwind v4 via compile-component-tailwind.mjs).
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';
import { componentHasRenderableView, safeFindComponents } from './tailwind-visual-style.js';

const LIVE_STYLE_ID = 'voodbuilder-component-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'component-css';
const INITIAL_BUILD_DELAY_MS = 120;

function collectPastedComponentsHtml(editor) {
    const parts = [];
    const wrapper = editor.getWrapper?.();

    if (! wrapper || ! componentHasRenderableView(wrapper)) {
        return '';
    }

    for (const component of safeFindComponents(wrapper, '.voodbuilder-pasted-component')) {
        const html = String(component.toHTML?.() ?? '').trim();

        if (html !== '') {
            parts.push(html);
        }
    }

    return parts.join('\n');
}

function injectLiveComponentCss(editor, css) {
    const frame = editor.Canvas?.getFrameEl?.();

    if (! frame) {
        return;
    }

    const doc = frame.contentDocument ?? frame.contentWindow?.document;

    if (! doc) {
        return;
    }

    let styleEl = doc.getElementById(LIVE_STYLE_ID);

    if (! styleEl) {
        styleEl = doc.createElement('style');
        styleEl.id = LIVE_STYLE_ID;
        doc.head.appendChild(styleEl);
    }

    styleEl.textContent = String(css ?? '').trim();
}

function isInsidePastedComponent(component) {
    if (! component) {
        return false;
    }

    if (component.getClasses?.()?.includes('voodbuilder-pasted-component')) {
        return true;
    }

    try {
        return Boolean(component.closest?.('.voodbuilder-pasted-component'));
    } catch {
        return false;
    }
}

export function registerComponentTailwindAutobuild(editor, options = {}) {
    const componentsUrl = String(options.componentsUrl ?? '').replace(/\/$/, '');
    const csrf = options.csrf ?? '';

    if (! componentsUrl) {
        editor.__voodbuilderInitialComponentBuild = Promise.resolve();

        return;
    }

    let timer = null;
    let requestId = 0;
    let lastHtml = '';
    let lastCss = '';
    let frameReady = false;
    let editorLoaded = false;
    let initialBuildResolve = null;
    let initialBuildAttempts = 0;
    const initialBuildDone = new Promise((resolve) => {
        initialBuildResolve = resolve;
    });

    editor.__voodbuilderInitialComponentBuild = initialBuildDone;

    const finishInitialBuild = () => {
        if (initialBuildResolve) {
            initialBuildResolve();
            initialBuildResolve = null;
        }
    };

    window.setTimeout(finishInitialBuild, 5_000);

    const schedule = (delay = DEBOUNCE_MS) => {
        if (! frameReady) {
            return;
        }

        clearTimeout(timer);
        timer = setTimeout(() => {
            void rebuild();
        }, delay);
    };

    const rebuild = async () => {
        const html = collectPastedComponentsHtml(editor);
        initialBuildAttempts += 1;

        if (html === '') {
            injectLiveComponentCss(editor, '');

            if (editorLoaded && initialBuildAttempts >= 2) {
                finishInitialBuild();
            } else if (editorLoaded) {
                schedule(INITIAL_BUILD_DELAY_MS);
            }

            return;
        }

        if (html === lastHtml) {
            injectLiveComponentCss(editor, lastCss);
            finishInitialBuild();

            return;
        }

        const currentRequest = ++requestId;

        beginEditorBuild(editor, BUILD_SCOPE);

        const controller = new AbortController();
        const fetchTimeout = window.setTimeout(() => controller.abort(), 8_000);

        try {
            const response = await fetch(`${componentsUrl}/compile-css`, {
                method: 'POST',
                headers: editorApiHeaders(csrf, { json: true }),
                credentials: 'same-origin',
                mode: 'same-origin',
                signal: controller.signal,
                body: JSON.stringify({ html, scope: 'component' }),
            });

            if (! response.ok || currentRequest !== requestId) {
                return;
            }

            const payload = await response.json();
            const css = String(payload?.css ?? '').trim();

            if (currentRequest !== requestId) {
                return;
            }

            lastHtml = html;
            lastCss = css;
            injectLiveComponentCss(editor, css);
        } catch {
            // Ignore transient network errors; next edit will retry.
        } finally {
            window.clearTimeout(fetchTimeout);
            endEditorBuild(editor, BUILD_SCOPE);
            finishInitialBuild();
        }
    };

    editor.on('component:update', (component) => {
        if (isInsidePastedComponent(component)) {
            schedule();
        }
    });

    editor.on('component:remove', (component) => {
        if (isInsidePastedComponent(component)) {
            schedule();
        }
    });

    editor.on('style:change', () => schedule());

    editor.on('load', () => {
        editorLoaded = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    editor.on('canvas:frame:load', () => {
        frameReady = true;
        injectLiveComponentCss(editor, lastCss);
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    if (editor.Canvas?.getFrameEl?.()) {
        frameReady = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    }
}
