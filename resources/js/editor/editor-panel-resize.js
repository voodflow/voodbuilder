/**
 * Drag-resize left (library) and right (inspector) editor columns.
 * Widths persist in localStorage.
 */

const STORAGE_LEFT = 'voodbuilder:gjs:panel-left-width';
const STORAGE_RIGHT = 'voodbuilder:gjs:panel-right-width';

const LEFT_DEFAULT = 15.5;
const RIGHT_DEFAULT = 23.5;
// Minimum width = default: panels can only grow, never shrink below the design default.
const LEFT_MIN = LEFT_DEFAULT;
const LEFT_MAX = 28;
const RIGHT_MIN = RIGHT_DEFAULT;
const RIGHT_MAX = 40;

function readRem(key, fallback) {
    try {
        const raw = window.localStorage.getItem(key);

        if (raw == null || raw === '') {
            return fallback;
        }

        const value = Number.parseFloat(raw);

        return Number.isFinite(value) ? value : fallback;
    } catch {
        return fallback;
    }
}

function writeRem(key, value) {
    try {
        window.localStorage.setItem(key, String(value));
    } catch {
        // ignore quota / private mode
    }
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function pxToRem(px) {
    const root = Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

    return px / root;
}

/**
 * @param {{ shell?: HTMLElement|null }|HTMLElement|null} shellOrApi
 * @param {Record<string, string>} [labels]
 */
export function registerEditorPanelResize(shellOrApi, labels = {}) {
    // configureEditorChrome passes the shell API object ({ shell, mounts, … });
    // accept a raw HTMLElement too for safety.
    const rootShell = shellOrApi?.shell instanceof Element
        ? shellOrApi.shell
        : (shellOrApi instanceof Element ? shellOrApi : null);

    if (! rootShell || rootShell.__voodbuilderPanelResizeRegistered) {
        return;
    }

    rootShell.__voodbuilderPanelResizeRegistered = true;

    const left = rootShell.querySelector('.voodbuilder-editor-shell__left');
    const right = rootShell.querySelector('.voodbuilder-editor-shell__right');

    if (! left || ! right) {
        return;
    }

    let leftRem = clamp(readRem(STORAGE_LEFT, LEFT_DEFAULT), LEFT_MIN, LEFT_MAX);
    let rightRem = clamp(readRem(STORAGE_RIGHT, RIGHT_DEFAULT), RIGHT_MIN, RIGHT_MAX);

    const apply = () => {
        left.style.width = `${leftRem}rem`;
        right.style.setProperty('--voodbuilder-editor-inspector-width', `${rightRem}rem`);
        right.style.width = `${rightRem}rem`;
    };

    apply();

    const makeHandle = (side) => {
        const handle = document.createElement('div');
        handle.className = `voodbuilder-editor-panel-resize voodbuilder-editor-panel-resize--${side}`;
        handle.setAttribute('role', 'separator');
        handle.setAttribute('aria-orientation', 'vertical');
        handle.tabIndex = 0;
        handle.title = side === 'left'
            ? (labels.resizeLibraryPanel ?? 'Drag to resize library')
            : (labels.resizeInspectorPanel ?? 'Drag to resize inspector');
        handle.setAttribute(
            'aria-label',
            side === 'left'
                ? (labels.resizeLibraryPanel ?? 'Resize library panel')
                : (labels.resizeInspectorPanel ?? 'Resize inspector panel'),
        );

        let dragging = false;
        let startX = 0;
        let startRem = 0;

        const onMove = (event) => {
            if (! dragging) {
                return;
            }

            const deltaPx = event.clientX - startX;

            if (side === 'left') {
                leftRem = clamp(startRem + pxToRem(deltaPx), LEFT_MIN, LEFT_MAX);
            } else {
                // Dragging the left edge of the right panel: move left → wider.
                rightRem = clamp(startRem - pxToRem(deltaPx), RIGHT_MIN, RIGHT_MAX);
            }

            apply();
        };

        const onUp = () => {
            if (! dragging) {
                return;
            }

            dragging = false;
            document.body.classList.remove('voodbuilder-editor-is-resizing-panel');
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            writeRem(STORAGE_LEFT, leftRem);
            writeRem(STORAGE_RIGHT, rightRem);
        };

        handle.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }

            event.preventDefault();
            dragging = true;
            startX = event.clientX;
            startRem = side === 'left' ? leftRem : rightRem;
            document.body.classList.add('voodbuilder-editor-is-resizing-panel');
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp);
        });

        handle.addEventListener('keydown', (event) => {
            const step = event.shiftKey ? 1 : 0.5;

            if (event.key === 'ArrowLeft') {
                event.preventDefault();

                if (side === 'left') {
                    leftRem = clamp(leftRem - step, LEFT_MIN, LEFT_MAX);
                } else {
                    rightRem = clamp(rightRem + step, RIGHT_MIN, RIGHT_MAX);
                }

                apply();
                writeRem(STORAGE_LEFT, leftRem);
                writeRem(STORAGE_RIGHT, rightRem);
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();

                if (side === 'left') {
                    leftRem = clamp(leftRem + step, LEFT_MIN, LEFT_MAX);
                } else {
                    rightRem = clamp(rightRem - step, RIGHT_MIN, RIGHT_MAX);
                }

                apply();
                writeRem(STORAGE_LEFT, leftRem);
                writeRem(STORAGE_RIGHT, rightRem);
            }
        });

        return handle;
    };

    left.appendChild(makeHandle('left'));
    right.appendChild(makeHandle('right'));
}
