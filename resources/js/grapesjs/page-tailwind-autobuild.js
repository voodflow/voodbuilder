/**
 * Recompiles page-level Tailwind utilities in the canvas iframe when needed.
 *
 * Compile only when:
 * - new element brings utilities missing from live CSS
 * - Save / explicit invalidate
 * - new classes not already present in the compiled CSS
 *
 * Do NOT compile on reorder / move of existing page elements (HTML order changes
 * alone must not hit compile-css).
 *
 * Style Manager paints are inline — they must NOT trigger compile-css.
 * Never compile during block/sorter drag (selector:add storms on drag-start).
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

function isIgnorableClassToken(className) {
    const token = String(className ?? '').trim();

    return token === ''
        || token.startsWith('gjs-')
        || token.startsWith('vb-')
        || token.startsWith('voodbuilder-');
}

function collectComponentClassSet(component, into = new Set()) {
    if (! component) {
        return into;
    }

    for (const className of (component.getClasses?.() ?? [])) {
        const token = String(className ?? '').trim();

        if (! isIgnorableClassToken(token)) {
            into.add(token);
        }
    }

    component.components?.()?.forEach?.((child) => collectComponentClassSet(child, into));

    return into;
}

function sameClassSet(left, right) {
    if (left.size !== right.size) {
        return false;
    }

    for (const token of left) {
        if (! right.has(token)) {
            return false;
        }
    }

    return true;
}

export function pageCssCoversClass(editor, className) {
    const normalized = String(className ?? '').trim();

    if (normalized === '' || isIgnorableClassToken(normalized)) {
        return true;
    }

    // Theme / section utility tokens ship outside page live CSS.
    if (normalized.includes('-vp-') || /(?:^|:)vp-/.test(normalized)) {
        return true;
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
    let lastClassSet = new Set();
    let frameReady = false;
    let editorLoaded = false;
    let pendingInvalidate = false;
    let building = false;
    let queuedWhileBuilding = false;
    let settingsRetries = 0;
    let dragLockDepth = 0;
    let pendingAfterDrag = false;
    let pendingAfterDragComponent = null;
    let endDragTimer = null;

    const isDragLocked = () => (
        dragLockDepth > 0
        || editor.__voodbuilderActiveBlockDrag
        || editor.__voodbuilderCssRebuildDragLock === true
    );

    const currentPageClassSet = () => collectComponentClassSet(editor.getWrapper?.());

    const classSetNeedsCompile = (classSet) => {
        for (const token of classSet) {
            if (! lastClassSet.has(token) && ! pageCssCoversClass(editor, token)) {
                return true;
            }
        }

        return false;
    };

    const componentNeedsLiveCss = (component) => {
        if (! component) {
            return false;
        }

        const classSet = collectComponentClassSet(component);

        for (const token of classSet) {
            if (! pageCssCoversClass(editor, token)) {
                return true;
            }
        }

        return false;
    };

    const selectorNeedsLiveCss = (selector) => {
        const name = String(
            selector?.getLabel?.()
            ?? selector?.get?.('name')
            ?? selector?.id
            ?? '',
        ).trim().replace(/^\./, '');

        if (name === '' || lastClassSet.has(name) || pageCssCoversClass(editor, name)) {
            return false;
        }

        return true;
    };

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

    /** Schedule only when new utilities are missing (reorder/move must no-op). */
    const scheduleIfMissingUtilities = (delay = DEBOUNCE_MS) => {
        if (pendingInvalidate) {
            schedule(delay);

            return;
        }

        if (classSetNeedsCompile(currentPageClassSet())) {
            schedule(delay);
        }
    };

    const beginDragLock = () => {
        dragLockDepth = 1;
        editor.__voodbuilderCssRebuildDragLock = true;
        window.clearTimeout(endDragTimer);
        clearTimeout(timer);
        requestId += 1;
        building = false;
        queuedWhileBuilding = false;
        pendingAfterDrag = false;
        pendingAfterDragComponent = null;
    };

    const endDragLock = ({ flush = false, component = null } = {}) => {
        if (flush) {
            pendingAfterDrag = true;
            pendingAfterDragComponent = component;
        }

        // Coalesce block:drag:stop + sorter:drag:end (both fire on library drops).
        window.clearTimeout(endDragTimer);
        endDragTimer = window.setTimeout(() => {
            dragLockDepth = 0;
            editor.__voodbuilderCssRebuildDragLock = false;

            if (pendingAfterDrag) {
                pendingAfterDrag = false;
                const dropped = pendingAfterDragComponent;
                pendingAfterDragComponent = null;

                if (componentNeedsLiveCss(dropped) || classSetNeedsCompile(currentPageClassSet())) {
                    schedule(DEBOUNCE_MS);
                }
            }
        }, 80);
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

        const classSet = currentPageClassSet();

        if (html === '') {
            applyPageLiveCss(editor, '');
            lastHtml = '';
            lastClassSet = new Set();
            editor.trigger('voodbuilder:page-css-compiled', { css: '', html: '' });

            return;
        }

        // Same class tokens as last compile → structure-only change (reorder/move).
        if (! pendingInvalidate && sameClassSet(classSet, lastClassSet)) {
            lastHtml = html;

            return;
        }

        // New tokens only: compile if any are missing from live CSS.
        if (! pendingInvalidate && ! classSetNeedsCompile(classSet)) {
            lastHtml = html;
            lastClassSet = classSet;

            return;
        }

        if (html === lastHtml && ! pendingInvalidate) {
            applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');

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
            lastClassSet = classSet;
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
                scheduleIfMissingUtilities(DEBOUNCE_MS);
            }
        }
    };

    editor.__voodbuilderSchedulePageCssRebuild = scheduleIfMissingUtilities;
    editor.__voodbuilderForcePageCssRebuild = schedule;
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
            scheduleIfMissingUtilities(200);
        }
    };
    editor.__voodbuilderApplyPageLiveCss = (css) => {
        lastHtml = collectPageLevelHtml(editor);
        lastClassSet = currentPageClassSet();
        applyPageLiveCss(editor, css);
    };
    editor.__voodbuilderInvalidatePageCss = () => {
        lastHtml = '';
        lastClassSet = new Set();
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Live compile only when classes/selectors introduce utilities missing from live CSS.
    editor.on('component:update:classes', (component) => {
        if (classSetNeedsCompile(collectComponentClassSet(component))) {
            schedule();
        }
    });
    editor.on('selector:add', (selector) => {
        if (selectorNeedsLiveCss(selector)) {
            schedule();
        }
    });
    editor.on('selector:update', (selector) => {
        if (selectorNeedsLiveCss(selector)) {
            schedule();
        }
    });

    editor.on('block:drag:start', beginDragLock);
    editor.on('sorter:drag:start', beginDragLock);
    editor.on('component:drag:start', beginDragLock);
    editor.on('block:drag:stop', (component) => {
        // Only flush after a real insert when live CSS is missing utilities.
        endDragLock({ flush: componentNeedsLiveCss(component), component });
    });
    editor.on('sorter:drag:end', () => {
        // Reorder/move: release lock, do not force a rebuild by itself.
        endDragLock({ flush: false });
    });
    editor.on('component:drag:end', () => {
        endDragLock({ flush: false });
    });

    const invalidate = () => {
        lastHtml = '';
        lastClassSet = new Set();
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Explicit rebuilds (save, code import, templates) — not routine canvas deletes.
    editor.on('voodbuilder:chrome-layout-ready', invalidate);
    editor.on('voodbuilder:page-css-invalidate', invalidate);

    editor.on('load', () => {
        editorLoaded = true;
        resetEditorBuildStatus(editor);
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    editor.on('canvas:frame:load', () => {
        frameReady = true;
        applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    });

    if (editor.Canvas?.getFrameEl?.()) {
        frameReady = true;
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    }

    if (editorLoaded) {
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    }
}
