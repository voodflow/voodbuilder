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
import { placeCanvasLiveStyle } from './canvas-live-style.js';
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild, resetEditorBuildStatus } from './editor-build-status.js';
import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { shouldDeferCssRebuild } from './editor-lifecycle.js';
import { extractChromeShellPageHtml } from './editor-chrome-shell.js';
import { extractGrapesComposerCss, mergeAuthorCssChunks } from './editor/payload.js';
import { STYLE_SPACING_SAFELIST } from './style-spacing-safelist.js';
import { STYLE_COLOR_SAFELIST } from './style-color-safelist.js';
import { STYLE_UTILITY_GROUPS, componentClassList } from './style-tailwind-class-groups.js';
import { STYLE_ANIMATION_BUNDLED_UTILITIES } from './style-animation-safelist.js';

const LIVE_STYLE_ID = 'voodbuilder-page-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'page-css';
const INITIAL_BUILD_DELAY_MS = 200;
const SETTINGS_RETRY_MS = 100;
const MAX_SETTINGS_RETRIES = 40;
/** Cap tight retries after compile failures (esp. 429) so we do not starve /blocks. */
const MAX_COMPILE_FAILURES = 6;
const RATE_LIMIT_DEFAULT_BACKOFF_MS = 15_000;
const RATE_LIMIT_MAX_BACKOFF_MS = 60_000;

/** Utilities already shipped in canvas section-utilities.css (Style panel catalogs). */
const PALETTE_SHADE_UTILITY_RE = /^(?:bg|text|border|from|via|to|shadow)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}$/;
const NAMED_COLOR_UTILITY_RE = /^(?:bg|text|border|from|via|to|shadow)-(?:black|white|transparent|current|inherit)$/;

const CANVAS_BUNDLED_UTILITIES = (() => {
    const set = new Set(
        `${STYLE_SPACING_SAFELIST ?? ''}\n${STYLE_COLOR_SAFELIST ?? ''}`
            .split(/\s+/)
            .map((token) => token.trim())
            .filter(Boolean),
    );

    // Add Style panel literals (font-bold, bg-cover, drop-shadow-lg, …).
    // Skip palette/named color tokens unless they are already in the safelist
    // files — template-built options (e.g. shadow-red-500) are invisible to
    // Tailwind @source; claiming them as "bundled" skipped JIT so the color
    // only appeared after Save.
    for (const group of STYLE_UTILITY_GROUPS ?? []) {
        for (const opt of group.options ?? []) {
            const value = String(opt?.value ?? '').trim();

            if (value === '') {
                continue;
            }

            const isPaletteColor = PALETTE_SHADE_UTILITY_RE.test(value)
                || NAMED_COLOR_UTILITY_RE.test(value);

            if (isPaletteColor && ! set.has(value)) {
                continue;
            }

            set.add(value);
        }
    }

    // Animation sector catalog is checked live in pageCssCoversClass (theme.css /
    // section-utilities.css already ship those tokens). Do not copy at init —
    // a circular import can leave the Set empty during this IIFE.

    return set;
})();

function isGrapesPrivateClassName(className) {
    return /^c\d+[a-z0-9]*$/i.test(String(className ?? '').trim());
}

function isCanvasBundledUtility(className) {
    const token = String(className ?? '').trim();

    if (token === '') {
        return true;
    }

    if (CANVAS_BUNDLED_UTILITIES.has(token)) {
        return true;
    }

    // Live check — Animation safelist must not depend on IIFE copy timing.
    return STYLE_ANIMATION_BUNDLED_UTILITIES.has(token);
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
            '[data-voodbuilder-editor-site-header]',
            '[data-voodbuilder-editor-site-footer]',
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

    // Before Theme Studio palette — see canvas-live-style.js.
    placeCanvasLiveStyle(doc, styleEl);
    styleEl.textContent = String(css ?? '').trim();

    // Palette must stay last after live inject (do not toggle .dark here — JIT is hot).
    const palette = doc.getElementById('voodbuilder-canvas-theme-palette');

    if (palette?.parentNode === doc.head && doc.head.lastElementChild !== palette) {
        doc.head.appendChild(palette);
    }
}

/** Undo CSS selector escapes so `.hover\:bg-red-500` indexes as `hover:bg-red-500`. */
function unescapeCssClassSelector(raw) {
    return String(raw ?? '').replace(/\\(.)/g, '$1');
}

function cssDefinesUtility(css, className) {
    if (! className || ! css) {
        return false;
    }

    if (cssUtilityClassIndex(css).has(className)) {
        return true;
    }

    // Arbitrary values (`bg-[#fff]`) may not match the fast index regex — fall back once.
    const escaped = String(className)
        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        .replace(/:/g, '\\:');

    return new RegExp(`\\.${escaped}(?:[\\s{:,>+~\\[]|$)`).test(css);
}

/** @type {string|null} */
let cssUtilityIndexSource = null;

/** @type {Set<string>|null} */
let cssUtilityIndex = null;

/**
 * Index utility selectors once per CSS blob — regex-per-class on large saved sheets
 * blocked the main thread for tens of seconds at editor open.
 *
 * @param {string} css
 * @returns {Set<string>}
 */
function cssUtilityClassIndex(css) {
    const normalized = String(css ?? '');

    if (cssUtilityIndexSource === normalized && cssUtilityIndex) {
        return cssUtilityIndex;
    }

    const index = new Set();
    // Tailwind v4 emits escaped selectors: .hover\:bg-red-500, .md\:flex, .w-1\/2
    const re = /\.((?:\\.|[-\w])+)/g;
    let match;

    while ((match = re.exec(normalized)) !== null) {
        const className = unescapeCssClassSelector(match[1]);

        if (className !== '') {
            index.add(className);
        }
    }

    cssUtilityIndexSource = normalized;
    cssUtilityIndex = index;

    return index;
}

function resetCssUtilityClassIndex() {
    cssUtilityIndexSource = null;
    cssUtilityIndex = null;
}

function isIgnorableClassToken(className) {
    const token = String(className ?? '').trim();

    return token === ''
        || token.startsWith('gjs-')
        || token.startsWith('vb-')
        || token.startsWith('voodbuilder-')
        || isGrapesPrivateClassName(token);
}

function isPastedComponentInstance(component) {
    return Boolean(component?.getAttributes?.()?.['data-voodbuilder-component']);
}

function collectComponentClassSet(component, into = new Set()) {
    if (! component || isPastedComponentInstance(component)) {
        return into;
    }

    for (const token of componentClassList(component)) {
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
    let normalized = String(className ?? '').trim();

    if (normalized === '' || isIgnorableClassToken(normalized)) {
        return true;
    }

    // Important modifier — coverage follows the base utility.
    if (normalized.startsWith('!')) {
        normalized = normalized.slice(1);
    }

    // Theme / section utility tokens ship outside page live CSS.
    if (normalized.includes('-vp-') || /(?:^|:)vp-/.test(normalized)) {
        return true;
    }

    // Style panel catalogs (section-utilities) + Animation sector (theme.css).
    if (isCanvasBundledUtility(normalized)) {
        return true;
    }

    // Defensive: Animation catalog patterns even if the Set failed to hydrate.
    if (isAnimationCatalogUtility(normalized)) {
        return true;
    }

    return cssDefinesUtility(editor?.__voodbuilderPageLiveCss ?? '', normalized);
}

function isAnimationCatalogUtility(className) {
    const token = String(className ?? '').trim().replace(/^!/, '');

    if (token === 'vb-animate-on-visible') {
        return true;
    }

    // Strip interaction variants used by the Animation sector.
    const base = token.replace(/^(?:hover|active):/, '');

    if (STYLE_ANIMATION_BUNDLED_UTILITIES.has(token) || STYLE_ANIMATION_BUNDLED_UTILITIES.has(base)) {
        return true;
    }

    return /^(?:animate-(?:spin|ping|pulse|bounce|wiggle|wiggle-more|rotate-[xy]|jump(?:-in|-out)?|shake|fade(?:-(?:up|down|left|right))?|flip-(?:up|down)|infinite|once|twice|thrice|duration-\d+|delay-(?:none|\d+)|ease(?:-linear|-in|-out|-in-out)?|normal|reverse|alternate(?:-reverse)?|fill-(?:none|forwards|backwards|both))|transition(?:-all|-colors|-opacity|-shadow|-transform|-none)?|duration-\d+|ease-(?:linear|in|out|in-out)|delay-\d+)$/.test(base);
}

export function applyPageLiveCss(editor, css) {
    const normalized = String(css ?? '').trim();

    resetCssUtilityClassIndex();
    editor.__voodbuilderPageLiveCss = normalized;
    injectLivePageCss(editor, normalized);
}

/**
 * JIT compile returns utilities only. Keep author `#id` Style Manager paints and
 * custom class / @keyframes rules from CssComposer (Library embeds) so motion
 * and paints survive rebuilds.
 *
 * @param {object} editor
 * @param {string} compiledCss
 * @returns {string}
 */
export function mergeCompiledPageCssWithAuthorIdRules(editor, compiledCss) {
    const compiled = String(compiledCss ?? '').trim();
    const previousAuthor = extractGrapesComposerCss(editor?.__voodbuilderPageLiveCss ?? '');
    let composerAuthor = '';

    try {
        composerAuthor = extractGrapesComposerCss(
            typeof editor?.getCss === 'function'
                ? (editor.getCss({ keepUnusedStyles: true }) ?? '')
                : '',
        );
    } catch {
        composerAuthor = '';
    }

    return mergeAuthorCssChunks([compiled, previousAuthor, composerAuthor]);
}

export function registerPageTailwindAutobuild(editor, options = {}) {
    const componentsUrl = String(options.componentsUrl ?? '').replace(/\/$/, '');
    const compileCssUrl = String(options.compileCssUrl ?? '').replace(/\/$/, '')
        || (componentsUrl !== '' ? `${componentsUrl}/compile-css` : '');
    const csrf = options.csrf ?? '';

    // Layout editor needs the same live compile: dropped footer/nav blocks otherwise
    // stay unstyled until Save replaces CssComposer with server-compiled CSS.
    if (! compileCssUrl) {
        return;
    }

    if (editor.__voodbuilderPageTailwindAutobuildRegistered) {
        return;
    }

    editor.__voodbuilderPageTailwindAutobuildRegistered = true;

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
    let consecutiveFailures = 0;
    let rateLimitedUntil = 0;
    let rateLimitWarned = false;

    const hasSeededPageLiveCss = () => (editor.__voodbuilderPageLiveCss ?? '').trim() !== '';

    const syncBootTracking = () => {
        try {
            const html = collectPageLevelHtml(editor);

            if (html !== '') {
                lastHtml = html;
            }

            lastClassSet = currentPageClassSet();
        } catch {
            // Canvas/frame may not be ready yet.
        }
    };

    let bootCompileTimer = null;
    let bootCompileScheduled = false;

    const scheduleBootCompile = () => {
        if (bootCompileScheduled && editor.__voodbuilderPageCssSeededFromServer === true) {
            return;
        }

        bootCompileScheduled = true;
        window.clearTimeout(bootCompileTimer);
        bootCompileTimer = window.setTimeout(() => {
            bootCompileTimer = null;

            // Saved page CSS from the server already matches the published page — no JIT at open.
            if (editor.__voodbuilderPageCssSeededFromServer === true) {
                syncBootTracking();

                return;
            }

            syncBootTracking();

            const classSet = currentPageClassSet();

            if (hasSeededPageLiveCss() && ! classSetNeedsCompile(classSet)) {
                lastClassSet = classSet;

                return;
            }

            if (hasSeededPageLiveCss()) {
                scheduleIfMissingUtilities(INITIAL_BUILD_DELAY_MS);
            } else {
                pendingInvalidate = true;
                schedule(INITIAL_BUILD_DELAY_MS);
            }
        }, INITIAL_BUILD_DELAY_MS);
    };

    const deferBootCompile = () => {
        if (editor.__voodbuilderPageCssSeededFromServer === true) {
            syncBootTracking();

            return;
        }

        const run = () => scheduleBootCompile();

        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(run, { timeout: 2500 });
        } else {
            window.setTimeout(run, 0);
        }
    };

    /** One compile check after boot chrome/layer sync finishes (layout setClass storms). */
    const schedulePostBootCssCheck = () => {
        let attempts = 0;

        const run = () => {
            attempts += 1;

            if (shouldDeferCssRebuild(editor)) {
                if (attempts < 40) {
                    window.setTimeout(run, 120);
                }

                return;
            }

            syncBootTracking();

            if (classSetNeedsCompile(currentPageClassSet())) {
                scheduleIfMissingUtilities(INITIAL_BUILD_DELAY_MS);
            }
        };

        window.setTimeout(run, 300);
    };

    const isUserCanvasDrag = () => (
        editor.__voodbuilderActiveBlockDrag
        || editor.__voodbuilderCssRebuildUserDrag === true
    );

    const isDragLocked = () => (
        dragLockDepth > 0
        || editor.__voodbuilderActiveBlockDrag
        || editor.__voodbuilderCssRebuildDragLock === true
    );

    /**
     * Class tokens that page JIT must satisfy — must mirror `collectPageLevelHtml()`.
     * Chrome-shell page editor: only the page-content slot (nav/footer use theme CSS).
     */
    const currentPageClassSet = () => {
        if (editor.__voodbuilderChromeShellMode) {
            const slot = findPageContentSlotInEditor(editor);

            if (slot) {
                return collectComponentClassSet(slot);
            }
        }

        return collectComponentClassSet(editor.getWrapper?.());
    };

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

    const schedule = (delay = DEBOUNCE_MS) => {
        if (
            ! frameReady
            || shouldDeferCssRebuild(editor)
        ) {
            return;
        }

        // Editor fires selector:add as soon as a block drag starts — never compile mid-drag.
        // Queue a rebuild so drop/invalidate still compiles once the lock clears.
        if (isDragLocked()) {
            pendingInvalidate = true;
            pendingAfterDrag = true;

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
        if (shouldDeferCssRebuild(editor)) {
            return;
        }

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
            editor.__voodbuilderCssRebuildUserDrag = false;

            if (pendingAfterDrag) {
                pendingAfterDrag = false;
                const dropped = pendingAfterDragComponent;
                pendingAfterDragComponent = null;

                // Library drops / forced invalidate must compile even when coverage
                // heuristics think catalog utilities already cover the tree.
                if (
                    pendingInvalidate
                    || componentNeedsLiveCss(dropped)
                    || classSetNeedsCompile(currentPageClassSet())
                ) {
                    pendingInvalidate = true;
                    schedule(DEBOUNCE_MS);
                }
            } else if (pendingInvalidate || classSetNeedsCompile(currentPageClassSet())) {
                schedule(DEBOUNCE_MS);
            }
        }, 80);
    };

    // Chrome shell / spacer / layout bootstrap fire sorter:drag:start without a user
    // drag — locking compile there left CssRebuildDragLock stuck and blocked JIT forever.
    const beginUserDragLock = () => {
        editor.__voodbuilderCssRebuildUserDrag = true;
        beginDragLock();
    };

    const rebuild = async () => {
        if (building) {
            queuedWhileBuilding = true;

            return;
        }

        if (
            shouldDeferCssRebuild(editor)
            || isDragLocked()
        ) {
            if (isDragLocked()) {
                pendingInvalidate = true;
                pendingAfterDrag = true;
            }

            return;
        }

        const now = Date.now();

        if (now < rateLimitedUntil) {
            pendingInvalidate = true;
            schedule(rateLimitedUntil - now);

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
            // Boot races (chrome shell not ready) used to wipe a seeded live sheet here.
            if ((editor.__voodbuilderPageLiveCss ?? '').trim() !== '') {
                return;
            }

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

        // Only NEW uncovered utilities justify compile-css + "Compiling styles…".
        // Pre-existing custom/BEM classes on the page must not force a rebuild when the
        // author only toggles Animation/Style catalog utilities (already in theme).
        // Explicit invalidate (drop / save seed / ForcePageCssRebuild) always compiles.
        const newlyUncovered = [...classSet].filter(
            (token) => ! lastClassSet.has(token) && ! pageCssCoversClass(editor, token),
        );

        if (newlyUncovered.length === 0 && ! pendingInvalidate) {
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
            const response = await fetch(compileCssUrl, {
                method: 'POST',
                headers: editorApiHeaders(csrf, { json: true }),
                credentials: 'same-origin',
                mode: 'same-origin',
                signal: controller.signal,
                body: JSON.stringify({ html, scope: 'page' }),
            });

            if (! response.ok) {
                consecutiveFailures += 1;

                if (response.status === 429) {
                    const retryAfterHeader = Number(response.headers.get('Retry-After'));
                    const backoffMs = Number.isFinite(retryAfterHeader) && retryAfterHeader > 0
                        ? Math.min(RATE_LIMIT_MAX_BACKOFF_MS, retryAfterHeader * 1000)
                        : Math.min(
                            RATE_LIMIT_MAX_BACKOFF_MS,
                            RATE_LIMIT_DEFAULT_BACKOFF_MS * Math.max(1, consecutiveFailures),
                        );
                    rateLimitedUntil = Date.now() + backoffMs;
                    pendingInvalidate = true;

                    if (! rateLimitWarned) {
                        rateLimitWarned = true;
                        console.warn(
                            'VoodBuilder page CSS: compile-css rate-limited (429). Cooling down before retry; block library uses a separate quota.',
                            { backoffMs },
                        );
                    }
                } else if (consecutiveFailures >= MAX_COMPILE_FAILURES) {
                    pendingInvalidate = false;
                    console.warn(
                        'VoodBuilder page CSS: compile-css failed repeatedly; pausing auto-rebuild until the next edit.',
                        response.status,
                    );
                } else {
                    pendingInvalidate = true;
                    console.warn('VoodBuilder page CSS: compile-css failed', response.status);
                }

                return;
            }

            if (currentRequest !== requestId) {
                return;
            }

            const payload = await response.json();
            const css = String(payload?.css ?? '').trim();

            if (currentRequest !== requestId) {
                return;
            }

            consecutiveFailures = 0;
            rateLimitedUntil = 0;
            rateLimitWarned = false;
            lastHtml = html;
            lastClassSet = classSet;
            applyPageLiveCss(editor, mergeCompiledPageCssWithAuthorIdRules(editor, css));
            editor.trigger('voodbuilder:page-css-compiled', { css: editor.__voodbuilderPageLiveCss ?? css, html });
            // Do not editor.refresh() here — it re-registers every class selector and
            // retriggers inspector MutationObservers (compile overlay stuck + main-thread storms).
        } catch (error) {
            consecutiveFailures += 1;

            if (consecutiveFailures >= MAX_COMPILE_FAILURES) {
                pendingInvalidate = false;
                console.warn(
                    'VoodBuilder page CSS: compile-css error; pausing auto-rebuild until the next edit.',
                    error,
                );
            } else {
                pendingInvalidate = true;
                console.warn('VoodBuilder page CSS: compile-css error', error);
            }
        } finally {
            window.clearTimeout(fetchTimeout);
            endEditorBuild(editor, BUILD_SCOPE);
            building = false;

            // A force/invalidate during this request must not fall back to
            // scheduleIfMissingUtilities — that can no-op after a partial early compile
            // (e.g. Library HTML still parsing when the first collect ran).
            if (queuedWhileBuilding || pendingInvalidate) {
                queuedWhileBuilding = false;
                let delay = DEBOUNCE_MS;

                if (Date.now() < rateLimitedUntil) {
                    delay = Math.max(DEBOUNCE_MS, rateLimitedUntil - Date.now());
                } else if (consecutiveFailures > 0 && pendingInvalidate) {
                    delay = Math.min(
                        RATE_LIMIT_MAX_BACKOFF_MS,
                        Math.max(DEBOUNCE_MS, 1000 * (2 ** (consecutiveFailures - 1))),
                    );
                } else if (pendingInvalidate) {
                    delay = INITIAL_BUILD_DELAY_MS;
                }

                schedule(delay);
            }
        }
    };

    editor.__voodbuilderSchedulePageCssRebuild = scheduleIfMissingUtilities;
    editor.__voodbuilderForcePageCssRebuild = (delay = DEBOUNCE_MS) => {
        pendingInvalidate = true;
        schedule(delay);
    };
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
            pendingInvalidate = true;
            schedule(200);
        }
    };
    editor.__voodbuilderApplyPageLiveCss = (css) => {
        const normalized = String(css ?? '').trim();
        applyPageLiveCss(editor, normalized);

        if (normalized !== '') {
            editor.__voodbuilderPageCssSeededFromServer = true;
            syncBootTracking();
        }
    };
    editor.__voodbuilderSyncPageCssBootTracking = syncBootTracking;
    editor.__voodbuilderInvalidatePageCss = () => {
        editor.__voodbuilderPageCssSeededFromServer = false;
        bootCompileScheduled = false;
        lastHtml = '';
        lastClassSet = new Set();
        pendingInvalidate = true;
        schedule(INITIAL_BUILD_DELAY_MS);
    };

    // Live compile only when a class change introduces utilities missing from theme/live CSS.
    // Do not JIT because the same node also has older custom/BEM classes.
    editor.on('component:update:classes', (component) => {
        if (shouldDeferCssRebuild(editor) || isDragLocked()) {
            return;
        }

        const tokens = collectComponentClassSet(component);
        let needsCompile = false;

        for (const token of tokens) {
            if (lastClassSet.has(token)) {
                continue;
            }

            if (pageCssCoversClass(editor, token)) {
                lastClassSet.add(token);

                continue;
            }

            needsCompile = true;

            break;
        }

        if (needsCompile) {
            schedule();
        }
    });

    // Dropped blocks / templates may land without a classes event (Grapes reuses tokens).
    editor.on('component:add', (component) => {
        if (shouldDeferCssRebuild(editor)) {
            return;
        }

        if (
            ! component
            || component.getAttributes?.()?.['data-voodbuilder-top-drop-spacer']
            || component.getAttributes?.()?.['data-voodbuilder-bottom-drop-spacer']
            || component.getAttributes?.()?.['data-voodbuilder-inner-drop']
            || isDragLocked()
        ) {
            return;
        }

        if (componentNeedsLiveCss(component)) {
            scheduleIfMissingUtilities();
        }
    });

    editor.on('block:drag:start', beginUserDragLock);
    editor.on('component:drag:start', () => {
        if (editor.__voodbuilderLayerTreeSorting) {
            return;
        }

        beginUserDragLock();
    });
    editor.on('sorter:drag:start', () => {
        // Ignore programmatic sorter sessions (chrome shell, spacer, layout boot).
        if (! isUserCanvasDrag()) {
            return;
        }

        beginDragLock();
    });
    editor.on('block:drag:stop', (component) => {
        // Always invalidate after a library drop so section HTML (hero gradients,
        // etc.) compiles even when coverage heuristics no-op.
        pendingInvalidate = true;
        endDragLock({
            flush: true,
            component: component ?? null,
        });
    });
    editor.on('sorter:drag:end', () => {
        // Reorder/move: release lock, do not force a rebuild by itself.
        endDragLock({ flush: false });
    });
    editor.on('component:drag:end', () => {
        endDragLock({ flush: false });
    });

    // Safety: clear a stuck drag lock left by a missed drag:end (blocks all JIT).
    window.clearInterval(editor.__voodbuilderPageCssDragLockWatchdog);
    editor.__voodbuilderPageCssDragLockWatchdog = window.setInterval(() => {
        if (! editor.__voodbuilderCssRebuildDragLock) {
            return;
        }

        if (isUserCanvasDrag() || editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        dragLockDepth = 0;
        editor.__voodbuilderCssRebuildDragLock = false;
        editor.__voodbuilderCssRebuildUserDrag = false;

        if (pendingInvalidate || classSetNeedsCompile(currentPageClassSet())) {
            scheduleIfMissingUtilities(DEBOUNCE_MS);
        }
    }, 1500);

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

        if (editor.__voodbuilderPageCssSeededFromServer === true) {
            syncBootTracking();
        } else {
            deferBootCompile();
        }

        schedulePostBootCssCheck();
    });

    editor.on('canvas:frame:load', () => {
        frameReady = true;
        applyPageLiveCss(editor, editor.__voodbuilderPageLiveCss ?? '');
        deferBootCompile();
    });

    if (editor.Canvas?.getFrameEl?.()) {
        frameReady = true;
        deferBootCompile();
    }

    if (editorLoaded) {
        deferBootCompile();
        schedulePostBootCssCheck();
    }
}
