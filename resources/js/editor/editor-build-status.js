/**
 * Build / boot loading indicators for the visual editor.
 * Boot splash: brand mark + name + version (animated VoodBuilder logo).
 */

const BUILD_SCOPES = new Map();
let markIdSeq = 0;
/** Failsafe when begin/end pairs desync after aborted compiles or observer storms. */
const STUCK_BUILD_RESET_MS = 20_000;
let stuckBuildTimer = null;

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
export function animatedMarkMarkup(size = 96) {
    const id = `vb-mark-${++markIdSeq}`;
    const voodGrad = `${id}-vood`;
    const flowGrad = `${id}-flow`;
    const glow = `${id}-glow`;

    return `
        <svg
            class="voodbuilder-editor-mark"
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

/**
 * Boot phases, in the order the editor actually reaches them.
 *
 * Each id maps to a milestone we already wait on, so the bar tracks work instead of
 * animating on a timer: `shell` once the panels exist, `canvas` when the frame and its
 * stylesheets have settled and the document is revealed, `content` when the dynamic block
 * refresh has resolved, `ready` when the editor accepts input.
 */
export const EDITOR_BOOT_PHASES = ['shell', 'canvas', 'content', 'ready'];

const BOOT_PHASE_FALLBACK_LABELS = {
    shell: 'Preparing the workspace…',
    canvas: 'Rendering your page…',
    content: 'Loading blocks and elements…',
    ready: 'Finishing up…',
};

/** How long a single phase may run before we admit it is slow. */
const BOOT_PHASE_SLOW_MS = 4_000;

/** Full-screen boot splash (editor cold start). */
function bootSplashMarkup({ label, brand, version }) {
    const name = wordmark(brand);
    const ver = String(version ?? '').trim();

    return `
        <div class="voodbuilder-editor-boot-splash" role="status" aria-live="polite" aria-label="${escapeHtml(label)}">
            <div class="voodbuilder-editor-boot-splash__mark">
                ${animatedMarkMarkup(96)}
            </div>
            <div class="voodbuilder-editor-boot-splash__brand">
                <span class="voodbuilder-editor-boot-splash__name">${escapeHtml(name)}</span>
                ${ver !== '' ? `<span class="voodbuilder-editor-boot-splash__version">${escapeHtml(ver)}</span>` : ''}
            </div>
            <p class="voodbuilder-editor-boot-splash__hint" data-voodbuilder-boot-phase-label>${escapeHtml(label)}</p>
            <div
                class="voodbuilder-editor-boot-splash__progress"
                data-voodbuilder-boot-progress
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="${EDITOR_BOOT_PHASES.length}"
                aria-valuenow="0"
            >
                <span class="voodbuilder-editor-boot-splash__progress-bar" data-voodbuilder-boot-progress-bar></span>
            </div>
            <p class="voodbuilder-editor-boot-splash__slow" data-voodbuilder-boot-slow hidden></p>
        </div>
    `;
}

function bootPhaseLabel(editor, phaseId) {
    return editor?.__voodbuilderBootPhaseLabels?.[phaseId]
        ?? BOOT_PHASE_FALLBACK_LABELS[phaseId]
        ?? '';
}

function renderBootPhase(editor) {
    const overlay = editor?.__voodbuilderBootOverlay;
    const phaseId = editor?.__voodbuilderBootPhase;

    if (! overlay || ! phaseId) {
        return;
    }

    const index = EDITOR_BOOT_PHASES.indexOf(phaseId);
    const total = EDITOR_BOOT_PHASES.length;
    // Report the phase as entered, not completed: a bar that only moves on completion sits
    // at zero through the longest step.
    const done = index < 0 ? 0 : index + 1;

    const label = overlay.querySelector('[data-voodbuilder-boot-phase-label]');
    const progress = overlay.querySelector('[data-voodbuilder-boot-progress]');
    const bar = overlay.querySelector('[data-voodbuilder-boot-progress-bar]');
    const text = bootPhaseLabel(editor, phaseId);

    if (label) {
        label.textContent = text;
    }

    if (progress) {
        progress.setAttribute('aria-valuenow', String(done));
        progress.setAttribute('aria-valuetext', `${done}/${total} — ${text}`);
    }

    if (bar) {
        bar.style.width = `${Math.round((done / total) * 100)}%`;
    }

    const splash = overlay.querySelector('.voodbuilder-editor-boot-splash');

    if (splash) {
        splash.setAttribute('aria-label', text);
    }
}

function armBootPhaseSlowHint(editor) {
    window.clearTimeout(editor.__voodbuilderBootSlowTimer);

    editor.__voodbuilderBootSlowTimer = window.setTimeout(() => {
        const overlay = editor.__voodbuilderBootOverlay;
        const slow = overlay?.querySelector('[data-voodbuilder-boot-slow]');

        if (! slow || editor.__voodbuilderBooting !== true) {
            return;
        }

        // Naming the stuck step is the whole point: an unqualified splash makes a slow
        // load indistinguishable from a hung editor.
        slow.textContent = editor.__voodbuilderBootLabels?.slow
            ?? 'This is taking longer than usual — still working.';
        slow.hidden = false;
    }, BOOT_PHASE_SLOW_MS);
}

/**
 * Advance the splash to a boot phase. Never moves backwards, so a late event from an
 * earlier phase cannot make the bar retreat.
 */
export function setEditorBootPhase(editor, phaseId) {
    if (! editor || ! EDITOR_BOOT_PHASES.includes(phaseId)) {
        return;
    }

    const current = EDITOR_BOOT_PHASES.indexOf(editor.__voodbuilderBootPhase ?? '');

    if (EDITOR_BOOT_PHASES.indexOf(phaseId) <= current) {
        return;
    }

    editor.__voodbuilderBootPhase = phaseId;

    const overlay = editor.__voodbuilderBootOverlay;
    const slow = overlay?.querySelector('[data-voodbuilder-boot-slow]');

    if (slow) {
        slow.hidden = true;
    }

    renderBootPhase(editor);
    armBootPhaseSlowHint(editor);
}

/** Compact spinner for style compile overlays. */
function spinnerMarkup(label) {
    return `
        <div class="voodbuilder-editor-status-spinner" role="status" aria-live="polite">
            <div class="voodbuilder-editor-status-spinner__mark">
                ${animatedMarkMarkup(40)}
            </div>
            <span class="voodbuilder-editor-status-spinner__label">${escapeHtml(label)}</span>
        </div>
    `;
}

function armStuckBuildFailsafe(editor) {
    window.clearTimeout(stuckBuildTimer);
    stuckBuildTimer = window.setTimeout(() => {
        if (totalBuildCount() === 0) {
            return;
        }

        console.warn('VoodBuilder Editor: compile overlay stuck — resetting build counters.');
        BUILD_SCOPES.clear();
        syncClassesOverlay(editor);
    }, STUCK_BUILD_RESET_MS);
}

function disarmStuckBuildFailsafe() {
    if (totalBuildCount() === 0) {
        window.clearTimeout(stuckBuildTimer);
        stuckBuildTimer = null;
    }
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

    // Any in-flight build (template apply, page JIT, component JIT) — not only CSS scopes.
    const busy = ! editor?.__voodbuilderBooting && totalBuildCount() > 0;

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
    armStuckBuildFailsafe(editor);
    syncClassesOverlay(editor);
}

/**
 * Update the visible label on canvas / classes compile overlays (multi-step apply).
 *
 * @param {object|null|undefined} editor
 * @param {string} label
 */
export function setEditorBuildLabel(editor, label) {
    const text = String(label ?? '').trim();

    if (text === '') {
        return;
    }

    for (const overlay of [
        editor?.__voodbuilderCanvasBuildOverlay,
        editor?.__voodbuilderClassesBuildOverlay,
    ]) {
        const labelEl = overlay?.querySelector?.('.voodbuilder-editor-status-spinner__label');

        if (labelEl) {
            labelEl.textContent = text;
        }
    }
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

    disarmStuckBuildFailsafe();
    syncClassesOverlay(editor);
}

/** Clear stuck compile overlays (e.g. after a failed/aborted build storm). */
export function resetEditorBuildStatus(editor) {
    BUILD_SCOPES.clear();
    disarmStuckBuildFailsafe();
    syncClassesOverlay(editor);
}

export function registerEditorBuildStatus(editor, shell, labels = {}, meta = {}) {
    const selectorsMount = shell?.mounts?.selectors;
    const compilingLabel = labels.compilingStyles ?? 'Compiling styles…';
    const loadingLabel = labels.loadingEditor ?? 'Loading editor…';
    const brand = meta.brand ?? labels.builderBrand ?? 'VoodBuilder';
    const version = meta.version ?? labels.packageVersion ?? '';

    if (selectorsMount && ! selectorsMount.querySelector('[data-voodbuilder-classes-build-overlay]')) {
        selectorsMount.classList.add('voodbuilder-editor-selectors-mount--overlay-host');
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-editor-build-overlay';
        overlay.dataset.voodbuilderClassesBuildOverlay = '';
        overlay.hidden = true;
        overlay.innerHTML = spinnerMarkup(compilingLabel);
        selectorsMount.appendChild(overlay);
        editor.__voodbuilderClassesBuildOverlay = overlay;
    }

    const canvasHost = shell?.mounts?.canvas
        ?? shell?.shell?.querySelector?.('.voodbuilder-editor-shell__center')
        ?? null;

    if (canvasHost && ! canvasHost.querySelector('[data-voodbuilder-canvas-build-overlay]')) {
        canvasHost.classList.add('voodbuilder-editor-canvas-compile-host');
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-editor-build-overlay voodbuilder-editor-build-overlay--canvas';
        overlay.dataset.voodbuilderCanvasBuildOverlay = '';
        overlay.hidden = true;
        overlay.innerHTML = spinnerMarkup(compilingLabel);
        canvasHost.appendChild(overlay);
        editor.__voodbuilderCanvasBuildOverlay = overlay;
    }

    editor.__voodbuilderBootPhaseLabels = {
        shell: labels.bootPhaseShell ?? BOOT_PHASE_FALLBACK_LABELS.shell,
        canvas: labels.bootPhaseCanvas ?? BOOT_PHASE_FALLBACK_LABELS.canvas,
        content: labels.bootPhaseContent ?? BOOT_PHASE_FALLBACK_LABELS.content,
        ready: labels.bootPhaseReady ?? BOOT_PHASE_FALLBACK_LABELS.ready,
    };
    editor.__voodbuilderBootLabels = {
        slow: labels.bootSlow ?? 'This is taking longer than usual — still working.',
    };

    const bootHost = shell?.shell ?? shell?.mounts?.canvas?.closest('.voodbuilder-editor-shell');

    if (bootHost && ! bootHost.querySelector('[data-voodbuilder-boot-overlay]')) {
        const overlay = document.createElement('div');
        overlay.className = 'voodbuilder-editor-boot-overlay';
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

/**
 * Upper bound on the splash, after which we hand the editor over regardless.
 *
 * The old bound was 3 500 ms, which is shorter than a normal cold boot: the splash
 * routinely vanished before the canvas document was even revealed, so the author faced an
 * editor that looked ready and ignored input for seconds. The cap is now a genuine
 * emergency exit rather than the usual path — the phase milestones dismiss the splash.
 */
const BOOT_FAILSAFE_MS = 20_000;

export function startEditorBoot(editor) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderBooting = true;
    editor.__voodbuilderBootPhase = null;
    syncBootOverlay(editor);
    setEditorBootPhase(editor, 'shell');

    window.clearTimeout(editor.__voodbuilderBootFailsafeTimer);
    editor.__voodbuilderBootFailsafeTimer = window.setTimeout(() => {
        if (editor.__voodbuilderBooting === true) {
            console.warn(
                'VoodBuilder Editor: boot failsafe — forcing editor unlock at phase',
                editor.__voodbuilderBootPhase,
            );
            finishEditorBoot(editor);
            document.querySelector('.voodbuilder-editor-root--booting')
                ?.classList.remove('voodbuilder-editor-root--booting');
            resetEditorBuildStatus(editor);
        }
    }, BOOT_FAILSAFE_MS);
}

export function finishEditorBoot(editor) {
    if (! editor) {
        return;
    }

    window.clearTimeout(editor.__voodbuilderBootFailsafeTimer);
    editor.__voodbuilderBootFailsafeTimer = null;
    window.clearTimeout(editor.__voodbuilderBootSlowTimer);
    editor.__voodbuilderBootSlowTimer = null;
    setEditorBootPhase(editor, 'ready');
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
