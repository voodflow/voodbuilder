/**
 * Shuffle-style hover popover: tag + class chips for the hovered canvas element.
 */

import { componentClassString } from './clipboard.js';

const POPOVER_ID = 'voodbuilder-gjs-class-hover-popover';
const HIDE_DELAY_MS = 160;

function resolveComponentFromElement(editor, el) {
    if (! el || el.nodeType !== 1) {
        return null;
    }

    const body = editor.Canvas?.getBody?.();
    let current = el;

    while (current && current !== body) {
        const id = current.getAttribute?.('id');

        if (id) {
            const byId = editor.Components?.getById?.(id)
                ?? editor.DomComponents?.getById?.(id);

            if (byId) {
                return byId;
            }

            try {
                const matches = editor.getWrapper?.()?.find?.(`#${CSS.escape(id)}`) ?? [];
                if (matches[0]) {
                    return matches[0];
                }
            } catch {
                // Invalid selector — skip.
            }
        }

        current = current.parentElement;
    }

    return null;
}

function ensurePopover() {
    let popover = document.getElementById(POPOVER_ID);

    if (popover) {
        return popover;
    }

    popover = document.createElement('div');
    popover.id = POPOVER_ID;
    popover.className = 'voodbuilder-gjs-class-hover-popover';
    popover.hidden = true;
    popover.setAttribute('role', 'tooltip');
    document.body.appendChild(popover);

    return popover;
}

function renderPopover(popover, component) {
    const tag = String(component.get?.('tagName') ?? 'div').toLowerCase();
    const classes = component.getClasses?.() ?? [];

    const chips = classes.length > 0
        ? classes.map((name) => `<span class="voodbuilder-gjs-class-hover-popover__chip">.${escapeHtml(String(name))}</span>`).join('')
        : `<span class="voodbuilder-gjs-class-hover-popover__empty">—</span>`;

    popover.innerHTML = `
        <div class="voodbuilder-gjs-class-hover-popover__tag">${escapeHtml(tag)}</div>
        <div class="voodbuilder-gjs-class-hover-popover__chips">${chips}</div>
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

export function registerCanvasClassHoverPopover(editor, options = {}) {
    if (! editor || editor.__voodbuilderClassHoverPopoverRegistered) {
        return;
    }

    editor.__voodbuilderClassHoverPopoverRegistered = true;

    const labels = options.labels ?? {};
    const popover = ensurePopover();
    let hideTimer = null;
    let lastCid = null;

    const hide = () => {
        popover.hidden = true;
        lastCid = null;
    };

    const scheduleHide = () => {
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(hide, HIDE_DELAY_MS);
    };

    const showFor = (component, clientX, clientY) => {
        if (! component || component === editor.getWrapper?.()) {
            scheduleHide();

            return;
        }

        window.clearTimeout(hideTimer);

        if (lastCid !== component.cid) {
            lastCid = component.cid;
            renderPopover(popover, component);
        }

        popover.hidden = false;
        positionPopover(popover, clientX, clientY);
    };

    const wireFrame = () => {
        const frame = editor.Canvas?.getFrameEl?.();
        const doc = frame?.contentDocument;

        if (! doc?.body || doc.body.dataset.voodbuilderClassHoverWired === '1') {
            return;
        }

        doc.body.dataset.voodbuilderClassHoverWired = '1';

        doc.body.addEventListener('mousemove', (event) => {
            if (editor.__voodbuilderBooting || editor.Canvas?.isDragging?.()) {
                hide();

                return;
            }

            const component = resolveComponentFromElement(editor, event.target);

            if (! component) {
                scheduleHide();

                return;
            }

            const frameRect = frame.getBoundingClientRect();
            showFor(
                component,
                frameRect.left + event.clientX,
                frameRect.top + event.clientY,
            );
        });

        doc.body.addEventListener('mouseleave', scheduleHide);
    };

    editor.on('canvas:frame:load', wireFrame);
    editor.on('load', () => window.setTimeout(wireFrame, 80));
    wireFrame();

    // Expose for toolbar “copy classes” feedback reuse.
    editor.__voodbuilderClassHoverPopover = {
        hide,
        describe: (component) => componentClassString(component) || (labels.classHoverEmpty ?? 'No classes'),
    };
}
