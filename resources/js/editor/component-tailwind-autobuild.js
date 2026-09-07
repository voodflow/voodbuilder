/**
 * Recompiles scoped Tailwind utilities for pasted components in the canvas iframe.
 * Triggered by library drops / class changes — Style Manager inline styles do not need compile-css.
 * Uses the same compile-css API as code import (Tailwind v4 via compile-component-tailwind.mjs).
 */
import { editorApiHeaders } from './editor-api.js';
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';
import { shouldDeferCssRebuild } from './editor-lifecycle.js';
import { componentHasRenderableView, safeFindComponents } from './tailwind-visual-style.js';
import { pageCssCoversClass } from './page-tailwind-autobuild.js';
import { componentClassList } from './style-tailwind-class-groups.js';
import { STYLE_ANIMATION_BUNDLED_UTILITIES, VISIBLE_MARKER_CLASS } from './style-animation-safelist.js';

const LIVE_STYLE_ID = 'voodbuilder-component-live-css';
const DEBOUNCE_MS = 450;
const BUILD_SCOPE = 'component-css';
const INITIAL_BUILD_DELAY_MS = 120;
const AFTER_DROP_DELAY_MS = 200;

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
    }

    // Keep live JIT last so dark:/responsive utilities win over canvas theme sheets.
    if (styleEl.parentNode !== doc.head || styleEl !== doc.head.lastElementChild) {
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

    if (component.getAttributes?.()?.['data-voodbuilder-component']) {
        return true;
    }

    try {
        return Boolean(
            component.closest?.('.voodbuilder-pasted-component')
            || component.closest?.('[data-voodbuilder-component]'),
        );
    } catch {
        return false;
    }
}

/**
 * Animation-sector tokens only — Style Manager animation toggles must not flash JIT.
 * Do NOT treat general Style-panel / spacing safelist tokens as "skip compile":
 * library component drops often use those and still need scoped compile-css.
 */
function isAnimationCatalogUtility(className) {
    const token = String(className ?? '').trim().replace(/^!/, '');

    if (token === '' || token === VISIBLE_MARKER_CLASS) {
        return true;
    }

    const base = token.replace(/^(?:hover|active):/, '');

    if (STYLE_ANIMATION_BUNDLED_UTILITIES.has(token) || STYLE_ANIMATION_BUNDLED_UTILITIES.has(base)) {
        return true;
    }

    return /^(?:animate-(?:spin|ping|pulse|bounce|wiggle|wiggle-more|rotate-[xy]|jump(?:-in|-out)?|shake|fade(?:-(?:up|down|left|right))?|flip-(?:up|down)|infinite|once|twice|thrice|duration-\d+|delay-(?:none|\d+)|ease(?:-linear|-in|-out|-in-out)?|normal|reverse|alternate(?:-reverse)?|fill-(?:none|forwards|backwards|both))|transition(?:-all|-colors|-opacity|-shadow|-transform|-none)?|duration-\d+|ease-(?:linear|in|out|in-out)|delay-\d+)$/.test(base);
}

function classFingerprintFromHtml(html) {
    return [...String(html ?? '').matchAll(/\bclass="([^"]*)"/g)]
        .flatMap((match) => String(match[1] ?? '').split(/\s+/))
        .filter(Boolean)
        .sort()
        .join(' ');
}

export function registerComponentTailwindAutobuild(editor, options = {}) {
    const componentsUrl = String(options.componentsUrl ?? '').replace(/\/$/, '');
    const compileCssUrl = String(options.compileCssUrl ?? '').replace(/\/$/, '')
        || (componentsUrl !== '' ? `${componentsUrl}/compile-css` : '');
    const csrf = options.csrf ?? '';

    if (! compileCssUrl) {
        editor.__voodbuilderInitialComponentBuild = Promise.resolve();

        return;
    }

    if (editor.__voodbuilderComponentTailwindAutobuildRegistered) {
        return;
    }

    editor.__voodbuilderComponentTailwindAutobuildRegistered = true;

    let timer = null;
    let requestId = 0;
    let lastHtml = '';
    let lastCss = '';
    let frameReady = false;
    let editorLoaded = false;
    let pendingAfterDrag = false;
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
        if (
            ! frameReady
            || shouldDeferCssRebuild(editor)
            || editor.__voodbuilderCssRebuildDragLock
            || editor.__voodbuilderActiveBlockDrag
        ) {
            pendingAfterDrag = true;

            return;
        }

        clearTimeout(timer);
        timer = setTimeout(() => {
            void rebuild();
        }, delay);
    };

    /** Force a rebuild after drag lock releases (library / block drops). */
    const scheduleAfterDrag = (delay = AFTER_DROP_DELAY_MS, attempts = 0) => {
        pendingAfterDrag = true;

        window.setTimeout(() => {
            if (
                (editor.__voodbuilderCssRebuildDragLock || editor.__voodbuilderActiveBlockDrag)
                && attempts < 20
            ) {
                scheduleAfterDrag(delay, attempts + 1);

                return;
            }

            if (! pendingAfterDrag) {
                return;
            }

            pendingAfterDrag = false;
            schedule(delay);
        }, delay);
    };

    editor.__voodbuilderScheduleComponentCssRebuild = (delay = DEBOUNCE_MS) => {
        if (
            editor.__voodbuilderCssRebuildDragLock
            || editor.__voodbuilderActiveBlockDrag
        ) {
            scheduleAfterDrag(typeof delay === 'number' ? delay : AFTER_DROP_DELAY_MS);

            return;
        }

        schedule(typeof delay === 'number' ? delay : DEBOUNCE_MS);
    };
    editor.__voodbuilderForceComponentCssRebuild = (delay = DEBOUNCE_MS) => {
        pendingAfterDrag = false;
        schedule(typeof delay === 'number' ? delay : DEBOUNCE_MS);
    };

    editor.__voodbuilderCssRebuildCancelHooks = editor.__voodbuilderCssRebuildCancelHooks ?? [];
    editor.__voodbuilderCssRebuildCancelHooks.push(() => {
        clearTimeout(timer);
        requestId += 1;
    });

    const rebuild = async () => {
        if (
            shouldDeferCssRebuild(editor)
            || editor.__voodbuilderCssRebuildDragLock
            || editor.__voodbuilderActiveBlockDrag
        ) {
            pendingAfterDrag = true;

            return;
        }

        const html = collectPastedComponentsHtml(editor);
        initialBuildAttempts += 1;

        if (html === '') {
            injectLiveComponentCss(editor, '');
            lastHtml = '';
            lastCss = '';
            editor.__voodbuilderComponentCssClassFingerprint = '';

            if (editorLoaded && initialBuildAttempts >= 2) {
                finishInitialBuild();
            } else if (editorLoaded) {
                schedule(INITIAL_BUILD_DELAY_MS);
            }

            return;
        }

        // Reorder within page changes HTML order but not utility needs — skip.
        if (html === lastHtml) {
            injectLiveComponentCss(editor, lastCss);
            finishInitialBuild();

            return;
        }

        // Same class tokens in different order: treat as structure-only.
        const classFingerprint = classFingerprintFromHtml(html);
        const previousFingerprint = editor.__voodbuilderComponentCssClassFingerprint ?? '';

        if (classFingerprint === previousFingerprint && lastCss !== '') {
            lastHtml = html;
            finishInitialBuild();

            return;
        }

        const htmlTokens = [...html.matchAll(/\bclass="([^"]*)"/g)]
            .flatMap((match) => String(match[1] ?? '').split(/\s+/))
            .map((token) => token.trim())
            .filter(Boolean);
        const previousTokenSet = new Set(
            String(previousFingerprint ?? '').split(/\s+/).filter(Boolean),
        );
        const newTokens = htmlTokens.filter((token) => ! previousTokenSet.has(token));
        const newlyUncovered = newTokens.filter(
            (token) => ! pageCssCoversClass(editor, token),
        );

        // Skip JIT only when the author solely toggled Animation-sector utilities
        // (theme.css / animated plugin already ships them). Library drops and Style
        // utilities that need scoped compile-css must still hit the endpoint.
        const onlyAnimationGains = newTokens.length > 0
            && newlyUncovered.length === 0
            && newTokens.every((token) => isAnimationCatalogUtility(token));

        if (onlyAnimationGains && lastCss !== '') {
            lastHtml = html;
            editor.__voodbuilderComponentCssClassFingerprint = classFingerprint;
            finishInitialBuild();

            return;
        }

        const currentRequest = ++requestId;

        beginEditorBuild(editor, BUILD_SCOPE);

        const controller = new AbortController();
        const fetchTimeout = window.setTimeout(() => controller.abort(), 8_000);

        try {
            const response = await fetch(compileCssUrl, {
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
            editor.__voodbuilderComponentCssClassFingerprint = classFingerprint;
            injectLiveComponentCss(editor, css);
        } catch {
            // Ignore transient network errors; next edit will retry.
        } finally {
            window.clearTimeout(fetchTimeout);
            endEditorBuild(editor, BUILD_SCOPE);
            finishInitialBuild();
        }
    };

    // Class edits inside a pasted tree — skip when every token is already theme-backed
    // (Animation sector + Style catalogs). Uncovered utilities still need JIT.
    editor.on('component:update:classes', (component) => {
        if (editor.__voodbuilderCssRebuildDragLock || editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        if (! isInsidePastedComponent(component)) {
            return;
        }

        const uncovered = componentClassList(component).some(
            (token) => ! pageCssCoversClass(editor, token),
        );

        if (! uncovered) {
            return;
        }

        schedule();
    });

    // Library / Elements drop: hydrate finishes after component:add — compile realtime.
    editor.on('component:add', (component) => {
        if (! isInsidePastedComponent(component)) {
            return;
        }

        if (editor.__voodbuilderCssRebuildDragLock || editor.__voodbuilderActiveBlockDrag) {
            scheduleAfterDrag();

            return;
        }

        schedule(AFTER_DROP_DELAY_MS);
    });

    editor.on('block:drag:start', () => {
        clearTimeout(timer);
        pendingAfterDrag = false;
    });
    editor.on('sorter:drag:start', () => {
        clearTimeout(timer);
    });

    editor.on('block:drag:stop', () => {
        scheduleAfterDrag();
    });
    editor.on('sorter:drag:end', () => {
        if (pendingAfterDrag) {
            scheduleAfterDrag();
        }
    });

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
