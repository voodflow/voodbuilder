/**
 * Build / boot loading indicators for the GrapesJS editor.
 */

const BUILD_SCOPES = new Map();

function spinnerMarkup(label) {
    return `
        <div class="voodbuilder-gjs-status-spinner" role="status" aria-live="polite">
            <span class="voodbuilder-gjs-status-spinner__ring" aria-hidden="true"></span>
            <span class="voodbuilder-gjs-status-spinner__label">${label}</span>
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

export function registerEditorBuildStatus(editor, shell, labels = {}) {
    const selectorsMount = shell?.mounts?.selectors;
    const compilingLabel = labels.compilingStyles ?? 'Compiling styles…';
    const loadingLabel = labels.loadingEditor ?? 'Loading editor…';

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
        overlay.innerHTML = spinnerMarkup(loadingLabel);
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
