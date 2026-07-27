/**
 * Recompiles page-level Tailwind utilities in the canvas iframe when classes change.
 * Style Manager paints are inline — they must NOT trigger compile-css.
 * Never compile during block/sorter drag (selector:add storms on drag-start).
 * After a successful drop, compile once only if new utilities are missing from live CSS.
 * Saves / explicit invalidate always recompile.
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild, resetEditorBuildStatus } from './editor-build-status.js';
import { extractChromeShellPageHtml } from './editor-chrome-shell.js';

const LIVE_STYLE_ID = 'voodbuilder-page-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'page-css';
const INITIAL_BUILD_DELAY_MS = 200;
const SETTINGS_RETRY_MS = 100;
const MAX_SETTINGS_RETRIES = 40;

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
    }

    // Keep live JIT last so dark:/responsive utilities win over canvas theme sheets.
    doc.head.appendChild(styleEl);
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
    let dragLockDepth = 0;
    let pendingAfterDrag = false;
    let endDragTimer = null;

    const isDragLocked = () => (
        dragLockDepth > 0
        || editor.__voodbuilderActiveBlockDrag
        || editor.__voodbuilderCssRebuildDragLock === true
    );

    const schedule = (delay = DEBOUNCE_MS) => {
        if (
            ! frameReady
            || editor.__voodbuilderBulkStructureUpdate
            || (editor.__voodbuilderCssRebuildSuspendDepth ?? 0) > 0
        ) {
            return;
        }

        // GrapesJS fires selector:add as soon as a block drag starts — never compile mid-drag.
        if (isDragLocked()) {
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

    const beginDragLock = () => {
        dragLockDepth = 1;
        editor.__voodbuilderCssRebuildDragLock = true;
        window.clearTimeout(endDragTimer);
        clearTimeout(timer);
        requestId += 1;
        building = false;
        queuedWhileBuilding = false;
    };

    const endDragLock = ({ flush = false } = {}) => {
        if (flush) {
            pendingAfterDrag = true;
        }

        // Coalesce block:drag:stop + sorter:drag:end (both fire on library drops).
        window.clearTimeout(endDragTimer);
        endDragTimer = window.setTimeout(() => {
            dragLockDepth = 0;
            editor.__voodbuilderCssRebuildDragLock = false;

            if (pendingAfterDrag) {
                pendingAfterDrag = false;
                schedule(DEBOUNCE_MS);
            }
        }, 80);
    };

    const componentNeedsLiveCss = (component) => {
        if (! component) {
            return false;
        }

        let needs = false;
        const visit = (node) => {
            if (needs || ! node) {
                return;
            }

            for (const className of (node.getClasses?.() ?? [])) {
                const token = String(className ?? '').trim();

                if (token === '' || token.startsWith('gjs-') || token.startsWith('vb-')) {
                    continue;
                }

                if (! pageCssCoversClass(editor, token)) {
                    needs = true;

                    return;
                }
            }

            node.components?.()?.forEach?.((child) => visit(child));
        };

        visit(component);

        return needs;
    };

    const rebuild = async () => {
        if (building) {
            queuedWhileBuilding = true;

            return;
        }

        if (
            (editor.__voodbuilderCssRebuildSuspendDepth ?? 0) > 0
            || isDragLocked()
        ) {
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
            // Skip page-css-compiled on cache hits — listeners remorph CTAs / replay
            // animations and that freezes or flickers the canvas.

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
    editor.__voodbuilderSetCssRebuildSuspended = (suspended) => {
        const depth = Number(editor.__voodbuilderCssRebuildSuspendDepth ?? 0);

        if (suspended) {
            editor.__voodbuilderCssRebuildSuspendDepth = depth + 1;
            clearTimeout(timer);
            requestId += 1;
            building = false;
            queuedWhileBuilding = false;
            (editor.__voodbuilderCssRebuildCancelHooks ?? []).forEach((hook) => {
                try {
                    hook();
                } catch {
                    // Ignore cancel-hook failures.
                }
            });

            return;
        }

        const nextDepth = Math.max(0, depth - 1);
        editor.__voodbuilderCssRebuildSuspendDepth = nextDepth;

        if (nextDepth === 0 && editor.__voodbuilderFlushCssRebuildOnResume) {
            editor.__voodbuilderFlushCssRebuildOnResume = false;
            schedule(200);
        }
    };
    editor.__voodbuilderApplyPageLiveCss = (css) => {
        lastHtml = collectPageLevelHtml(editor);
        applyPageLiveCss(editor, css);
    };
    editor.__voodbuilderInvalidatePageCss = () => {
        lastHtml = '';
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Live compile when classes are added/renamed — not when removed from canvas,
    // and not while dragging (block library drag creates temp components + selectors).
    editor.on('component:update:classes', () => schedule());
    editor.on('selector:add', () => schedule());
    editor.on('selector:update', () => schedule());

    editor.on('block:drag:start', beginDragLock);
    editor.on('sorter:drag:start', beginDragLock);
    editor.on('block:drag:stop', (component) => {
        // Only flush after a real insert when live CSS is missing utilities.
        endDragLock({ flush: componentNeedsLiveCss(component) });
    });
    editor.on('sorter:drag:end', () => {
        // Reorder/move: release lock, do not force a rebuild by itself.
        endDragLock({ flush: false });
    });

    const invalidate = () => {
        lastHtml = '';
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Explicit rebuilds (save, code import, templates) — not routine canvas deletes.
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
