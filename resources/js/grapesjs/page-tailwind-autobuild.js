/**
 * Recompiles page-level Tailwind utilities in the canvas iframe when classes change.
 * Style Manager paints are inline — they must NOT trigger compile-css.
 * Rebuild when: Classes panel, Global Classes applied to a component, structure drops.
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild, resetEditorBuildStatus } from './editor-build-status.js';
import { extractChromeShellPageHtml } from './editor-chrome-shell.js';

const LIVE_STYLE_ID = 'voodbuilder-page-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'page-css';
const INITIAL_BUILD_DELAY_MS = 200;
const DROP_BUILD_DELAY_MS = 160;
const SETTINGS_RETRY_MS = 100;
const MAX_SETTINGS_RETRIES = 40;

/** Attribute noise that must not retrigger live CSS compile. */
const IGNORED_ATTR_KEYS = new Set([
    'style', // Style Manager → inline styles (avoidInlineStyle:false)
    'id',
]);

const IGNORED_ATTR_PREFIXES = [
    'data-gjs-',
    'data-voodbuilder-cta-label',
    'data-voodbuilder-cta',
    'data-highlightable',
];

function shouldIgnoreAttributeUpdate(component, event) {
    const changed = event?.attributes
        ?? event?.changed
        ?? component?.changed
        ?? null;

    if (! changed || typeof changed !== 'object') {
        return false;
    }

    const keys = Object.keys(changed);

    if (keys.length === 0) {
        return false;
    }

    return keys.every((key) => {
        if (IGNORED_ATTR_KEYS.has(key)) {
            return true;
        }

        return IGNORED_ATTR_PREFIXES.some((prefix) => key.startsWith(prefix) || key === prefix);
    });
}

function collectPageLevelHtml(editor) {
    // Chrome-shell page editor: compile only the page content slot — full getHtml()
    // includes locked nav/footer and is slower / noisier for attribute churn.
    if (editor.__voodbuilderChromeShellMode) {
        const slotHtml = String(extractChromeShellPageHtml(editor) ?? '').trim();

        if (slotHtml !== '') {
            return stripComponentInstances(slotHtml);
        }
    }

    const raw = String(editor.getHtml?.({
        cleanId: false,
        withProps: false,
        keepInlineStyle: true,
    }) ?? '').trim();

    if (raw === '') {
        return raw;
    }

    return stripChromeAndComponents(editor, raw);
}

function stripComponentInstances(html) {
    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');

    doc.querySelectorAll('[data-voodbuilder-component]').forEach((node) => {
        node.remove();
    });

    return doc.body.innerHTML.trim();
}

function stripChromeAndComponents(editor, raw) {
    const doc = new DOMParser().parseFromString(`<body>${raw}</body>`, 'text/html');

    doc.querySelectorAll('[data-voodbuilder-component]').forEach((node) => {
        node.remove();
    });

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
    let building = false;
    let queuedWhileBuilding = false;
    let settingsRetries = 0;

    const schedule = (delay = DEBOUNCE_MS) => {
        if (! frameReady) {
            return;
        }

        // Footer/nav settings batches set this flag; retry a bounded number of times.
        if (editor.__voodbuilderSettingsChange) {
            if (settingsRetries >= MAX_SETTINGS_RETRIES) {
                settingsRetries = 0;

                return;
            }

            settingsRetries += 1;
            clearTimeout(timer);
            timer = setTimeout(() => schedule(delay), SETTINGS_RETRY_MS);

            return;
        }

        settingsRetries = 0;
        clearTimeout(timer);
        timer = setTimeout(() => {
            void rebuild();
        }, delay);
    };

    const rebuild = async () => {
        if (building) {
            queuedWhileBuilding = true;

            return;
        }

        let html = '';

        try {
            html = collectPageLevelHtml(editor);
        } catch (error) {
            console.warn('VoodBuilder page CSS: collect HTML failed', error);

            return;
        }

        if (html === '') {
            applyPageLiveCss(editor, '');
            lastHtml = '';
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
        building = true;
        queuedWhileBuilding = false;

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
            building = false;

            if (queuedWhileBuilding) {
                queuedWhileBuilding = false;
                schedule(DEBOUNCE_MS);
            }
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

    // Tailwind compile only when utility classes enter/leave the HTML.
    // Style Manager (Styles tab) writes inline styles — never schedule from those.
    editor.on('component:add', () => schedule(DROP_BUILD_DELAY_MS));
    editor.on('component:remove', () => schedule());
    editor.on('component:update:classes', () => schedule());
    editor.on('component:update:attributes', (component, event) => {
        // Ignore Style Manager → style="" churn; still compile if class/other attrs change.
        if (shouldIgnoreAttributeUpdate(component, event)) {
            return;
        }

        schedule();
    });
    // Selector Manager = Classes panel (add/rename/remove class selectors).
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
        resetEditorBuildStatus(editor);
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
