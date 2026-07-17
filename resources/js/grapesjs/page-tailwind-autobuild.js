/**
 * Recompiles page-level Tailwind utilities in the canvas iframe when classes change.
 * Component library instances keep their own scoped compile (component-tailwind-autobuild.js).
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';

const LIVE_STYLE_ID = 'voodbuilder-page-live-css';
const DEBOUNCE_MS = 280;
const BUILD_SCOPE = 'page-css';
const INITIAL_BUILD_DELAY_MS = 80;
const DROP_BUILD_DELAY_MS = 60;

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

    // Chrome shell preview includes locked nav/footer — those are styled by layout CSS
    // + theme sheets, not by page-level live compile. Including them bloated JIT CSS and
    // could fight chrome rules after save.
    if (editor.__voodbuilderChromeShellMode) {
        doc.querySelectorAll([
            '[data-voodbuilder-chrome-shell-part]',
            '[data-voodbuilder-gjs-site-header]',
            '[data-voodbuilder-gjs-site-footer]',
        ].join(',')).forEach((node) => {
            node.remove();
        });
    }

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

    // Layout editor needs the same live compile: dropped footer/nav blocks otherwise
    // stay unstyled until Save replaces CssComposer with server-compiled CSS.
    if (! componentsUrl) {
        return;
    }

    let timer = null;
    let requestId = 0;
    let lastHtml = '';
    let frameReady = false;
    let editorLoaded = false;
    let pendingInvalidate = false;

    const schedule = (delay = DEBOUNCE_MS) => {
        if (! frameReady) {
            return;
        }

        // Footer/nav settings batches set this flag; retry shortly so live CSS still builds.
        if (editor.__voodbuilderSettingsChange) {
            clearTimeout(timer);
            timer = setTimeout(() => schedule(delay), 80);

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
            editor.trigger('voodbuilder:page-css-compiled', { css: '', html: '' });

            return;
        }

        if (html === lastHtml && ! pendingInvalidate) {
            applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');
            editor.trigger('voodbuilder:page-css-compiled', {
                css: editor.__voodbuilderPageLiveCss ?? '',
                html,
                cached: true,
            });

            return;
        }

        pendingInvalidate = false;
        const currentRequest = ++requestId;

        beginEditorBuild(editor, BUILD_SCOPE);

        const controller = new AbortController();
        const fetchTimeout = window.setTimeout(() => controller.abort(), 12_000);

        try {
            const response = await fetch(`${componentsUrl}/compile-css`, {
                method: 'POST',
                headers: editorApiHeaders(csrf, { json: true }),
                credentials: 'same-origin',
                mode: 'same-origin',
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
    editor.__voodbuilderInvalidatePageCss = () => {
        lastHtml = '';
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Prefer narrow events — blanket component:update floods compile + console noise.
    editor.on('component:add', () => schedule(DROP_BUILD_DELAY_MS));
    editor.on('component:remove', () => schedule());
    editor.on('component:styleUpdate', () => schedule());
    editor.on('component:update:classes', () => schedule());
    editor.on('component:update:attributes', () => schedule());
    editor.on('selector:add', () => schedule());
    editor.on('selector:remove', () => schedule());
    editor.on('selector:update', () => schedule());

    const invalidate = () => {
        lastHtml = '';
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // One invalidate after drop settle — avoid 0/400/900 storm.
    editor.on('block:drag:stop', () => {
        schedule(DROP_BUILD_DELAY_MS);
        window.setTimeout(invalidate, 180);
    });
    editor.on('voodbuilder:chrome-layout-ready', invalidate);
    editor.on('voodbuilder:page-css-invalidate', invalidate);

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
