/**
 * Frontend popup runtime — triggers, frequency caps, overlay shell.
 */

import popupShellCss from '../css/grapesjs/popup-shell.css?inline';

const STORAGE_PREFIX = 'voodbuilder-popup:';
const POPUP_SHELL_CSS = String(popupShellCss ?? '');

let eventsEndpoint = null;

function readConfig() {
    const node = document.querySelector('[data-voodbuilder-popups-config]');

    if (! node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent ?? '{}');
    } catch {
        return null;
    }
}

function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function trackPopupEvent(popupId, event, extra = {}) {
    if (! eventsEndpoint || ! popupId) {
        return;
    }

    const payload = {
        popup_id: popupId,
        event,
        page_path: window.location.pathname || '',
        ...extra,
    };

    try {
        fetch(eventsEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
            keepalive: true,
        }).catch(() => {});
    } catch {
        // Ignore analytics failures.
    }
}

function frequencyKey(popupId, mode) {
    return `${STORAGE_PREFIX}${popupId}:${mode}`;
}

function hasSeen(popup) {
    const mode = popup?.rules?.frequency?.mode ?? 'session';
    const id = popup?.id;

    if (! id || mode === 'always') {
        return false;
    }

    if (mode === 'session') {
        return window.sessionStorage.getItem(frequencyKey(id, 'session')) === '1';
    }

    if (mode === 'once') {
        return window.localStorage.getItem(frequencyKey(id, 'once')) === '1';
    }

    if (mode === 'days') {
        const raw = window.localStorage.getItem(frequencyKey(id, 'days'));

        if (! raw) {
            return false;
        }

        const expires = Number.parseInt(raw, 10);

        return Number.isFinite(expires) && Date.now() < expires;
    }

    return false;
}

function markSeen(popup) {
    const mode = popup?.rules?.frequency?.mode ?? 'session';
    const id = popup?.id;

    if (! id || mode === 'always') {
        return;
    }

    if (mode === 'session') {
        window.sessionStorage.setItem(frequencyKey(id, 'session'), '1');

        return;
    }

    if (mode === 'once') {
        window.localStorage.setItem(frequencyKey(id, 'once'), '1');

        return;
    }

    if (mode === 'days') {
        const days = Number.parseInt(String(popup?.rules?.frequency?.days ?? 7), 10);
        const ttl = (Number.isFinite(days) ? days : 7) * 86_400_000;
        window.localStorage.setItem(frequencyKey(id, 'days'), String(Date.now() + ttl));
    }
}

function widthClass(width) {
    return ({
        sm: 'max-w-sm',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
    })[width] ?? 'max-w-lg';
}

function sanitizePopupHtml(html) {
    const trimmed = String(html ?? '').trim();

    if (trimmed === '') {
        return '';
    }

    if (typeof DOMParser === 'undefined') {
        return trimmed;
    }

    const doc = new DOMParser().parseFromString(trimmed, 'text/html');
    const body = doc.body;

    if (! body) {
        return trimmed;
    }

    return body.innerHTML.trim();
}

function closeMountedPopups() {
    document.querySelectorAll('.voodbuilder-popup-root').forEach((element) => {
        element.remove();
    });
    document.documentElement.classList.remove('voodbuilder-popup-open');
}

function mountPopup(popup, { preview = false, onClose = null } = {}) {
    if (! preview && hasSeen(popup)) {
        return null;
    }

    if (document.querySelector(`[data-voodbuilder-popup-id="${popup.id}"]`)) {
        return null;
    }

    if (! preview) {
        closeMountedPopups();
    }

    const display = popup.rules?.display ?? {};
    const overlayEnabled = display.overlay !== false;
    const root = document.createElement('div');
    root.className = 'voodbuilder-popup-root fixed inset-0 z-[120] flex items-center justify-center p-4';
    root.dataset.voodbuilderPopupId = popup.id;
    root.hidden = true;

    const overlay = document.createElement('button');
    overlay.type = 'button';
    overlay.className = 'absolute inset-0 bg-black/50';
    overlay.setAttribute('aria-label', 'Close popup');
    overlay.tabIndex = -1;

    const panel = document.createElement('div');
    panel.className = `relative z-10 w-full ${widthClass(display.width)} overflow-hidden rounded-xl bg-vp-bg shadow-2xl`;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'voodbuilder-popup-close';
    closeButton.dataset.voodbuilderPopupClose = '';
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Close');

    const body = document.createElement('div');
    body.className = 'voodbuilder-popup-body';
    body.innerHTML = sanitizePopupHtml(popup.html);

    if (popup.css) {
        const style = document.createElement('style');
        style.textContent = popup.css;
        root.appendChild(style);
    }

    const shellStyleId = 'voodbuilder-popup-shell-css';

    if (! document.getElementById(shellStyleId)) {
        const shellLink = document.createElement('style');
        shellLink.id = shellStyleId;
        shellLink.textContent = POPUP_SHELL_CSS;
        document.head.appendChild(shellLink);
    }

    panel.append(closeButton, body);

    if (overlayEnabled) {
        root.append(overlay, panel);
    } else {
        root.append(panel);
    }

    let closed = false;
    let trackedCta = false;

    const close = (reason = 'button') => {
        if (closed) {
            return;
        }

        closed = true;
        root.remove();
        document.documentElement.classList.remove('voodbuilder-popup-open');

        if (! preview) {
            markSeen(popup);
            trackPopupEvent(popup.id, 'closed', { close_reason: reason });
        }

        if (typeof onClose === 'function') {
            onClose();
        }
    };

    const bindClose = (element, reason) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            close(reason);
        });
    };

    if (overlayEnabled && display.close_on_overlay !== false) {
        bindClose(overlay, 'overlay');
    }

    bindClose(closeButton, 'button');

    body.querySelectorAll('[data-voodbuilder-popup-close]').forEach((element) => {
        bindClose(element, 'content');
    });

    if (! preview) {
        body.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('a[href], button, [data-voodbuilder-popup-cta], [role="button"]')
                : null;

            if (! target || trackedCta) {
                return;
            }

            if (target.matches('[data-voodbuilder-popup-close]') || target.closest('[data-voodbuilder-popup-close]')) {
                return;
            }

            if (target === closeButton || closeButton.contains(target)) {
                return;
            }

            trackedCta = true;
            trackPopupEvent(popup.id, 'cta_click');
        });
    }

    if (display.close_on_escape !== false) {
        const onKeydown = (event) => {
            if (event.key === 'Escape') {
                close('escape');
                window.removeEventListener('keydown', onKeydown);
            }
        };

        window.addEventListener('keydown', onKeydown);
    }

    if (popup.js) {
        try {
            const runner = new Function(popup.js);
            runner();
        } catch (error) {
            console.error('VoodBuilder popup script failed', popup.id, error);
        }
    }

    document.body.appendChild(root);
    document.documentElement.classList.add('voodbuilder-popup-open');
    root.hidden = false;

    if (! preview) {
        trackPopupEvent(popup.id, 'shown');
    }

    return { root, close };
}

function scheduleTrigger(popup, show) {
    const trigger = popup.rules?.trigger ?? {};
    const type = trigger.type ?? 'delay';

    if (type === 'load') {
        show();

        return;
    }

    if (type === 'delay') {
        const seconds = Number.parseInt(String(trigger.delay_seconds ?? 3), 10);
        window.setTimeout(show, (Number.isFinite(seconds) ? seconds : 3) * 1000);

        return;
    }

    if (type === 'scroll') {
        const target = Number.parseInt(String(trigger.scroll_percent ?? 50), 10);
        const threshold = Number.isFinite(target) ? target : 50;

        const onScroll = () => {
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;

            if (scrollHeight <= 0) {
                show();
                window.removeEventListener('scroll', onScroll);

                return;
            }

            const progress = (window.scrollY / scrollHeight) * 100;

            if (progress >= threshold) {
                show();
                window.removeEventListener('scroll', onScroll);
            }
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        return;
    }

    if (type === 'exit_intent') {
        const onMouseOut = (event) => {
            if (event.clientY <= 0) {
                show();
                document.removeEventListener('mouseout', onMouseOut);
            }
        };

        document.addEventListener('mouseout', onMouseOut);

        return;
    }

    if (type === 'click') {
        const selector = String(trigger.click_selector ?? '').trim();

        if (selector === '') {
            return;
        }

        document.querySelectorAll(selector).forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                show();
            });
        });
    }
}

export function previewPopup(popup, options = {}) {
    closeMountedPopups();

    return mountPopup(popup, { preview: true, onClose: options.onClose ?? null });
}

async function bootPopups() {
    if (document.documentElement.classList.contains('voodbuilder-popup-editor')) {
        return;
    }

    if (document.body.classList.contains('voodbuilder-grapesjs-editing')) {
        return;
    }

    const config = readConfig();

    if (! config?.endpoint) {
        return;
    }

    eventsEndpoint = config.eventsEndpoint ?? null;

    let payload;

    try {
        const response = await fetch(config.endpoint, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            return;
        }

        payload = await response.json();
    } catch {
        return;
    }

    const popups = payload?.popups ?? [];

    for (const popup of popups) {
        scheduleTrigger(popup, () => {
            if (document.querySelector('.voodbuilder-popup-root')) {
                return;
            }

            mountPopup(popup);
        });
    }
}

let popupsBootPromise = null;

export function initPopups() {
    if (! popupsBootPromise) {
        popupsBootPromise = bootPopups();
    }
}
