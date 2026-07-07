/**
 * Recompiles page-level Tailwind utilities in the canvas iframe when classes change.
 * Component library instances keep their own scoped compile (component-tailwind-autobuild.js).
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';

const LIVE_STYLE_ID = 'voodbuilder-page-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'page-css';
const INITIAL_BUILD_DELAY_MS = 120;

function collectPageLevelHtml(editor) {
    const raw = String(editor.getHtml?.({
        cleanId: false,
        withProps: true,
        keepInlineStyle: true,
    }) ?? '').trim();

    if (raw === '') {
        return raw;
    }

    const doc = new DOMParser().parseFromString(`<body>${raw}</body>`, 'text/html');

    doc.querySelectorAll('[data-voodbuilder-component]').forEach((node) => {
        node.remove();
    });

    return doc.body.innerHTML.trim();
}

function injectLivePageCss(editor, css) {
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

function cssDefinesUtility(css, className) {
    if (! className || ! css) {
        return false;
    }

    const escaped = className.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`\\.${escaped}(?:\\b|[\\[:])`).test(css);
}

export function pageCssCoversClass(editor, className) {
    const normalized = String(className ?? '').trim();

    if (normalized === '') {
        return false;
    }

    return cssDefinesUtility(editor?.__voodbuilderPageLiveCss ?? '', normalized);
}

export function applyPageLiveCss(editor, css) {
    const normalized = String(css ?? '').trim();

    editor.__voodbuilderPageLiveCss = normalized;
    injectLivePageCss(editor, normalized);
}

export function registerPageTailwindAutobuild(editor, options = {}) {
    const componentsUrl = String(options.componentsUrl ?? '').replace(/\/$/, '');
    const csrf = options.csrf ?? '';

    if (! componentsUrl) {
        return;
    }

    let timer = null;
    let requestId = 0;
    let lastHtml = '';
    let frameReady = false;
    let editorLoaded = false;

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
        const html = collectPageLevelHtml(editor);

        if (html === '') {
            applyPageLiveCss(editor, '');

            return;
        }

        if (html === lastHtml) {
            applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');

            return;
        }

        const currentRequest = ++requestId;

        beginEditorBuild(editor, BUILD_SCOPE);

        const controller = new AbortController();
        const fetchTimeout = window.setTimeout(() => controller.abort(), 12_000);

        try {
            const response = await fetch(`${componentsUrl}/compile-css`, {
                method: 'POST',
                headers: editorApiHeaders(csrf, { 'Content-Type': 'application/json' }),
                credentials: 'same-origin',
                signal: controller.signal,
                body: JSON.stringify({ html, scope: 'page' }),
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
            applyPageLiveCss(editor, css);
            editor.trigger('voodbuilder:page-css-compiled', { css, html });
        } catch {
            // Transient network errors — next edit will retry.
        } finally {
            window.clearTimeout(fetchTimeout);
            endEditorBuild(editor, BUILD_SCOPE);
        }
    };

    editor.__voodbuilderSchedulePageCssRebuild = schedule;
    editor.__voodbuilderApplyPageLiveCss = (css) => {
        lastHtml = collectPageLevelHtml(editor);
        applyPageLiveCss(editor, css);
    };

    editor.on('component:update', () => schedule());
    editor.on('component:add', () => schedule());
    editor.on('component:remove', () => schedule());
    editor.on('component:styleUpdate', () => schedule());
    editor.on('selector:add', () => schedule());
    editor.on('selector:remove', () => schedule());
    editor.on('selector:update', () => schedule());

    editor.on('load', () => {
        editorLoaded = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    editor.on('canvas:frame:load', () => {
        frameReady = true;
        applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    if (editor.Canvas?.getFrameEl?.()) {
        frameReady = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    }

    if (editorLoaded) {
        schedule(INITIAL_BUILD_DELAY_MS);
    }
}
