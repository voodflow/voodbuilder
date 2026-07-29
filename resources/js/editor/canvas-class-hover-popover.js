/**
 * Shuffle-style hover popover: tag + class chips for the hovered canvas element.
 */

import { componentClassString } from './clipboard.js';
import { resolveComponentFromElement } from './component-context-menu.js';

const POPOVER_ID = 'voodbuilder-editor-class-hover-popover';
const HIDE_DELAY_MS = 120;
export const CLASS_HOVER_POPOVER_STORAGE_KEY = 'voodbuilder:class-hover-popover-visible';
export const CLASS_HOVER_POPOVER_SHELL_CLASS = 'is-class-hover-popover-visible';

function ensurePopover() {
    let popover = document.getElementById(POPOVER_ID);

    if (popover) {
        return popover;
    }

    popover = document.createElement('div');
    popover.id = POPOVER_ID;
    popover.className = 'voodbuilder-editor-class-hover-popover';
    popover.hidden = true;
    popover.setAttribute('role', 'tooltip');
    document.body.appendChild(popover);

    return popover;
}

function renderPopover(popover, component) {
    const tag = String(component.get?.('tagName') ?? 'div').toLowerCase();
    const classes = component.getClasses?.() ?? [];

    const chips = classes.length > 0
        ? classes.map((name) => `<span class="voodbuilder-editor-class-hover-popover__chip">.${escapeHtml(String(name))}</span>`).join('')
        : `<span class="voodbuilder-editor-class-hover-popover__empty">—</span>`;

    popover.innerHTML = `
        <div class="voodbuilder-editor-class-hover-popover__tag">${escapeHtml(tag)}</div>
        <div class="voodbuilder-editor-class-hover-popover__chips">${chips}</div>
    `;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function positionPopover(popover, clientX, clientY) {
    const pad = 12;
    const rect = popover.getBoundingClientRect();
    let left = clientX + pad;
    let top = clientY + pad;

    if (left + rect.width > window.innerWidth - 8) {
        left = Math.max(8, clientX - rect.width - pad);
    }

    if (top + rect.height > window.innerHeight - 8) {
        top = Math.max(8, clientY - rect.height - pad);
    }

    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
}

function pointerInsideFrame(frame, clientX, clientY) {
    if (! frame) {
        return false;
    }

    const rect = frame.getBoundingClientRect();

    return clientX >= rect.left
        && clientX <= rect.right
        && clientY >= rect.top
        && clientY <= rect.bottom;
}

/**
 * Default ON — only opt-out via toolbar / localStorage.
 *
 * @returns {boolean}
 */
export function readClassHoverPopoverPreference() {
    try {
        const raw = localStorage.getItem(CLASS_HOVER_POPOVER_STORAGE_KEY);

        if (raw === null) {
            return true;
        }

        return raw === '1';
    } catch {
        return true;
    }
}

/**
 * @param {boolean} visible
 */
export function saveClassHoverPopoverPreference(visible) {
    try {
        localStorage.setItem(CLASS_HOVER_POPOVER_STORAGE_KEY, visible ? '1' : '0');
    } catch {
        // Ignore storage errors.
    }
}

/**
 * @param {object} editor
 * @returns {boolean}
 */
export function isClassHoverPopoverEnabled(editor) {
    if (editor?.__voodbuilderClassHoverPopoverEnabled === false) {
        return false;
    }

    if (editor?.__voodbuilderClassHoverPopoverEnabled === true) {
        return true;
    }

    return readClassHoverPopoverPreference();
}

/**
 * @param {object} editor
 * @param {boolean} enabled
 * @param {HTMLElement|null|undefined} shellRoot
 */
export function setClassHoverPopoverEnabled(editor, enabled, shellRoot = null) {
    const next = enabled === true;
    editor.__voodbuilderClassHoverPopoverEnabled = next;
    shellRoot?.classList.toggle(CLASS_HOVER_POPOVER_SHELL_CLASS, next);

    if (! next) {
        editor.__voodbuilderClassHoverPopover?.hide?.();
    }
}

export function registerCanvasClassHoverPopover(editor, options = {}) {
    if (! editor || editor.__voodbuilderClassHoverPopoverRegistered) {
        return;
    }

    editor.__voodbuilderClassHoverPopoverRegistered = true;

    const labels = options.labels ?? {};
    const shellRoot = options.shellRoot ?? null;
    const popover = ensurePopover();
    let hideTimer = null;
    let lastCid = null;
    let raf = 0;
    let pending = null;
    let wiredFrame = null;

    setClassHoverPopoverEnabled(editor, readClassHoverPopoverPreference(), shellRoot);

    const clearPending = () => {
        pending = null;

        if (raf) {
            window.cancelAnimationFrame(raf);
            raf = 0;
        }
    };

    const hide = () => {
        clearPending();
        window.clearTimeout(hideTimer);
        hideTimer = null;
        popover.hidden = true;
        lastCid = null;
    };

    const scheduleHide = () => {
        clearPending();
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(hide, HIDE_DELAY_MS);
    };

    const showFor = (component, clientX, clientY) => {
        if (! isClassHoverPopoverEnabled(editor)) {
            hide();

            return;
        }

        if (! component || component === editor.getWrapper?.()) {
            scheduleHide();

            return;
        }

        window.clearTimeout(hideTimer);
        hideTimer = null;

        if (lastCid !== component.cid) {
            lastCid = component.cid;
            renderPopover(popover, component);
        }

        popover.hidden = false;
        positionPopover(popover, clientX, clientY);
    };

    const flushPending = () => {
        raf = 0;
        const next = pending;
        pending = null;

        if (! next) {
            return;
        }

        if (! isClassHoverPopoverEnabled(editor)) {
            hide();

            return;
        }

        if (editor.__voodbuilderBooting || editor.Canvas?.isDragging?.()) {
            hide();

            return;
        }

        const frame = editor.Canvas?.getFrameEl?.();

        // Pointer already left the canvas (iframe leave is unreliable across docs).
        if (! pointerInsideFrame(frame, next.x, next.y)) {
            hide();

            return;
        }

        const component = resolveComponentFromElement(editor, next.target);

        if (! component) {
            scheduleHide();

            return;
        }

        showFor(component, next.x, next.y);
    };

    const onParentPointerMove = (event) => {
        if (popover.hidden) {
            return;
        }

        const frame = editor.Canvas?.getFrameEl?.();

        if (! pointerInsideFrame(frame, event.clientX, event.clientY)) {
            hide();
        }
    };

    const wireFrame = () => {
        const frame = editor.Canvas?.getFrameEl?.();
        const doc = frame?.contentDocument;

        if (! doc?.body) {
            return;
        }

        // Parent-document leave detection (iframe → sidebar/panels).
        if (! editor.__voodbuilderClassHoverParentWired) {
            editor.__voodbuilderClassHoverParentWired = true;
            document.addEventListener('mousemove', onParentPointerMove, { passive: true });
            document.addEventListener('mouseleave', hide, { passive: true });
            window.addEventListener('blur', hide);
            editor.on('component:selected', hide);
            editor.on('block:drag:start', hide);
        }

        if (wiredFrame === frame && doc.body.dataset.voodbuilderClassHoverWired === '1') {
            return;
        }

        wiredFrame = frame;
        doc.body.dataset.voodbuilderClassHoverWired = '1';

        doc.body.addEventListener('mousemove', (event) => {
            if (! isClassHoverPopoverEnabled(editor)) {
                return;
            }

            const frameRect = frame.getBoundingClientRect();

            pending = {
                target: event.target,
                x: frameRect.left + event.clientX,
                y: frameRect.top + event.clientY,
            };

            if (! raf) {
                raf = window.requestAnimationFrame(flushPending);
            }
        }, { passive: true });

        // mouseleave on iframe body often does not fire when leaving to parent doc.
        doc.body.addEventListener('mouseleave', scheduleHide);
        frame.addEventListener('mouseleave', hide);
        frame.addEventListener('mouseout', (event) => {
            if (! frame.contains(event.relatedTarget)) {
                hide();
            }
        });
    };

    editor.on('canvas:frame:load', () => {
        // Frame remounts — clear sticky hint from previous document.
        hide();
        wireFrame();
    });
    editor.on('load', () => window.setTimeout(wireFrame, 80));
    wireFrame();

    editor.__voodbuilderClassHoverPopover = {
        hide,
        isEnabled: () => isClassHoverPopoverEnabled(editor),
        setEnabled: (enabled) => setClassHoverPopoverEnabled(editor, enabled, shellRoot),
        describe: (component) => componentClassString(component) || (labels.classHoverEmpty ?? 'No classes'),
    };
}
