/**
 * Build / boot loading indicators for the GrapesJS editor.
 * Boot splash: brand mark + name + version (Bricks-inspired, our animated logo).
 */

const BUILD_SCOPES = new Map();
let markIdSeq = 0;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function wordmark(brand) {
    const raw = String(brand ?? 'VoodBuilder').trim() || 'VoodBuilder';

    return raw.toLowerCase().replace(/\s+/g, '');
}

/**
 * Inline animated mark (SMIL): packets flow from top nodes toward the center.
 * Must be inline — <img> of a static PNG has no animation.
 */
function animatedMarkMarkup(size = 96) {
    const id = `vb-mark-${++markIdSeq}`;
    const voodGrad = `${id}-vood`;
    const flowGrad = `${id}-flow`;
    const glow = `${id}-glow`;

    return `
        <svg
            class="voodbuilder-gjs-mark"
            width="${size}"
            height="${size}"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            focusable="false"
        >
            <defs>
                <linearGradient id="${voodGrad}" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#a78bfa" />
                    <stop offset="1" stop-color="#7c3aed" />
                </linearGradient>
                <linearGradient id="${flowGrad}" x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#34d399" />
                    <stop offset="1" stop-color="#059669" />
                </linearGradient>
                <filter id="${glow}" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <circle cx="50" cy="50" r="40" fill="#7c3aed" opacity="0.05" />

            <path d="M25 25 Q 25 75 50 85" stroke="#4c1d95" stroke-width="8" stroke-linecap="round" opacity="0.2" />
            <path d="M75 25 Q 75 75 50 85" stroke="#064e3b" stroke-width="8" stroke-linecap="round" opacity="0.2" />

            <path d="M25 25 Q 25 75 50 85" stroke="url(#${voodGrad})" stroke-width="6" stroke-linecap="round" />
            <path d="M75 25 Q 75 75 50 85" stroke="url(#${flowGrad})" stroke-width="6" stroke-linecap="round" />

            <path d="M25 25 Q 25 75 50 85" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-dasharray="4 20" opacity="0.85" fill="none">
                <animate attributeName="stroke-dashoffset" from="24" to="0" dur="1s" repeatCount="indefinite" />
            </path>
            <path d="M75 25 Q 75 75 50 85" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-dasharray="4 20" opacity="0.85" fill="none">
                <animate attributeName="stroke-dashoffset" from="24" to="0" dur="1s" repeatCount="indefinite" />
            </path>

            <circle cx="25" cy="25" r="7" fill="#8b5cf6" stroke="#2e1065" stroke-width="2" />
            <circle cx="75" cy="25" r="7" fill="#10b981" stroke="#2e1065" stroke-width="2" />
            <circle cx="50" cy="85" r="7" fill="#ffffff" stroke="#2e1065" stroke-width="2" filter="url(#${glow})">
                <animate attributeName="r" values="7;8;7" dur="2s" repeatCount="indefinite" />
            </circle>
        </svg>
    `;
}

/** Full-screen boot splash (editor cold start). */
function bootSplashMarkup({ label, brand, version }) {
    const name = wordmark(brand);
    const ver = String(version ?? '').trim();

    return `
        <div class="voodbuilder-gjs-boot-splash" role="status" aria-live="polite" aria-label="${escapeHtml(label)}">
            <div class="voodbuilder-gjs-boot-splash__mark">
                ${animatedMarkMarkup(96)}
            </div>
            <div class="voodbuilder-gjs-boot-splash__brand">
                <span class="voodbuilder-gjs-boot-splash__name">${escapeHtml(name)}</span>
                ${ver !== '' ? `<span class="voodbuilder-gjs-boot-splash__version">${escapeHtml(ver)}</span>` : ''}
            </div>
            <p class="voodbuilder-gjs-boot-splash__hint">${escapeHtml(label)}</p>
        </div>
    `;
}

/** Compact spinner for style compile overlays. */
function spinnerMarkup(label) {
    return `
        <div class="voodbuilder-gjs-status-spinner" role="status" aria-live="polite">
            <div class="voodbuilder-gjs-status-spinner__mark">
                ${animatedMarkMarkup(40)}
            </div>
            <span class="voodbuilder-gjs-status-spinner__label">${escapeHtml(label)}</span>
        </div>
    `;
}

function totalBuildCount() {
    let total = 0;

    for (const count of BUILD_SCOPES.values()) {
        total += count;
    }

    return total;
}

function syncClassesOverlay(editor) {
    const overlay = editor?.__voodbuilderClassesBuildOverlay;

    if (overlay) {
        const busy = totalBuildCount() > 0;
        overlay.hidden = ! busy;
        overlay.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    syncCanvasCompileOverlay(editor);
}

function syncCanvasCompileOverlay(editor) {
    const overlay = editor?.__voodbuilderCanvasBuildOverlay;

    if (! overlay) {
        return;
    }

    const busy = (BUILD_SCOPES.get('page-css') ?? 0) > 0
        || (BUILD_SCOPES.get('component-css') ?? 0) > 0;

    overlay.hidden = ! busy;
    overlay.setAttribute('aria-busy', busy ? 'true' : 'false');
}

function syncBootOverlay(editor) {
    const overlay = editor?.__voodbuilderBootOverlay;

    if (! overlay) {
        return;
    }

    const booting = editor?.__voodbuilderBooting === true;
    overlay.hidden = ! booting;
    overlay.setAttribute('aria-busy', booting ? 'true' : 'false');
}

export function beginEditorBuild(editor, scope = 'default') {
    if (! editor) {
        return;
    }

    BUILD_SCOPES.set(scope, (BUILD_SCOPES.get(scope) ?? 0) + 1);
    syncClassesOverlay(editor);
}

export function endEditorBuild(editor, scope = 'default') {
    if (! editor) {
        return;
    }

    const next = Math.max(0, (BUILD_SCOPES.get(scope) ?? 0) - 1);

    if (next === 0) {
        BUILD_SCOPES.delete(scope);
    } else {
        BUILD_SCOPES.set(scope, next);
    }

    syncClassesOverlay(editor);
}

/** Clear stuck compile overlays (e.g. after a failed/aborted build storm). */
export function resetEditorBuildStatus(editor) {
    BUILD_SCOPES.clear();
    syncClassesOverlay(editor);
}

export function registerEditorBuildStatus(editor, shell, labels = {}, meta = {}) {
    const selectorsMount = shell?.mounts?.selectors;
    const compilingLabel = labels.compilingStyles ?? 'Compiling styles…';
    const loadingLabel = labels.loadingEditor ?? 'Loading editor…';
    const brand = meta.brand ?? labels.builderBrand ?? 'VoodBuilder';
    const version = meta.version ?? labels.packageVersion ?? '';

    if (selectorsMount && ! selectorsMount.querySelector('[data-voodbuilder-classes-build-overlay]')) {
        selectorsMount.classList.add('voodbuilder-gjs-selectors-mount--overlay-host');
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-gjs-build-overlay';
        overlay.dataset.voodbuilderClassesBuildOverlay = '';
        overlay.hidden = true;
        overlay.innerHTML = spinnerMarkup(compilingLabel);
        selectorsMount.appendChild(overlay);
        editor.__voodbuilderClassesBuildOverlay = overlay;
    }

    const canvasHost = shell?.mounts?.canvas
        ?? shell?.shell?.querySelector?.('.voodbuilder-gjs-shell__center')
        ?? null;

    if (canvasHost && ! canvasHost.querySelector('[data-voodbuilder-canvas-build-overlay]')) {
        canvasHost.classList.add('voodbuilder-gjs-canvas-compile-host');
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-gjs-build-overlay voodbuilder-gjs-build-overlay--canvas';
        overlay.dataset.voodbuilderCanvasBuildOverlay = '';
        overlay.hidden = true;
        overlay.innerHTML = spinnerMarkup(compilingLabel);
        canvasHost.appendChild(overlay);
        editor.__voodbuilderCanvasBuildOverlay = overlay;
    }

    const bootHost = shell?.shell ?? shell?.mounts?.canvas?.closest('.voodbuilder-gjs-shell');

    if (bootHost && ! bootHost.querySelector('[data-voodbuilder-boot-overlay]')) {
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-gjs-boot-overlay';
        overlay.dataset.voodbuilderBootOverlay = '';
        overlay.hidden = true;
        overlay.innerHTML = bootSplashMarkup({
            label: loadingLabel,
            brand,
            version,
        });
        bootHost.appendChild(overlay);
        editor.__voodbuilderBootOverlay = overlay;
    }

    editor.__voodbuilderBuildStatus = {
        begin: (scope) => beginEditorBuild(editor, scope),
        end: (scope) => endEditorBuild(editor, scope),
    };
}

export function startEditorBoot(editor) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderBooting = true;
    syncBootOverlay(editor);
}

export function finishEditorBoot(editor) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderBooting = false;
    syncBootOverlay(editor);
}

export async function waitForEditorBootTasks(editor, tasks = []) {
    const timeoutMs = 4_000;

    await Promise.race([
        Promise.all(tasks.filter(Boolean)),
        new Promise((resolve) => {
            window.setTimeout(resolve, timeoutMs);
        }),
    ]);

    await new Promise((resolve) => {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(resolve);
        });
    });
}
