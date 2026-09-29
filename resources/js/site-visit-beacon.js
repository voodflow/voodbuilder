/**
 * Public site-visit beacon — notifies Voodflow Site Visit triggers on land / navigate.
 *
 * Gated on vcookiebar marketing consent when the consent shell is present.
 */

const VISITOR_COOKIE_FALLBACK = 'vpopups_vid';

function readVisitConfig() {
    const node = document.querySelector('[data-voodbuilder-site-visit-config]');

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

function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : '';
}

function isUsableVisitorKey(value) {
    const raw = String(value ?? '').trim();

    if (raw === '' || raw === VISITOR_COOKIE_FALLBACK) {
        return false;
    }

    // Laravel encrypted cookies — not a visitor id.
    if (raw.startsWith('eyJ') || raw.length > 128) {
        return false;
    }

    return true;
}

function ensureVisitorKey(cookieName) {
    const existing = readCookie(cookieName);

    if (isUsableVisitorKey(existing)) {
        return existing;
    }

    const key = (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function')
        ? crypto.randomUUID()
        : `vid-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

    document.cookie = `${cookieName}=${encodeURIComponent(key)}; path=/; max-age=31536000; SameSite=Lax`;

    return key;
}

/**
 * When vcookiebar is on the page, site-visit + visitor cookie need marketing opt-in.
 * No consent shell → keep previous behaviour (hosts without the package).
 */
function marketingConsentAllows() {
    const consentActive = Boolean(document.querySelector('[data-vcookiebar-shell]'))
        || Boolean(window.__vcookiebar);

    if (! consentActive) {
        return true;
    }

    return Boolean(window.__vcookiebar?.has?.('marketing'));
}

function whenMarketingAllowed(run) {
    if (marketingConsentAllows()) {
        run();

        return;
    }

    window.addEventListener('vcookiebar:consent', function onConsent(event) {
        if (event?.detail?.preferences?.marketing === true) {
            window.removeEventListener('vcookiebar:consent', onConsent);
            run();
        }
    });
}

let lastBeaconPath = null;
let lastBeaconAt = 0;

function sendBeacon() {
    const config = readVisitConfig();

    if (! config?.endpoint) {
        return;
    }

    const path = window.location.pathname || '/';
    const now = Date.now();

    // Skip duplicate beacons for the same path within a short window
    // (DOMContentLoaded + livewire:navigated on first paint).
    if (lastBeaconPath === path && now - lastBeaconAt < 1500) {
        return;
    }

    lastBeaconPath = path;
    lastBeaconAt = now;

    const cookieName = config.visitorCookie || VISITOR_COOKIE_FALLBACK;
    const visitorKey = ensureVisitorKey(cookieName);

    const payload = {
        path,
        url: window.location.href,
        page_id: config.pageId || null,
        visitor_key: visitorKey,
        locale: config.locale || document.documentElement.lang || null,
    };

    try {
        fetch(config.endpoint, {
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
        // Ignore beacon failures — workflows are best-effort.
    }
}

export function bootSiteVisitBeacon() {
    whenMarketingAllowed(sendBeacon);
}
