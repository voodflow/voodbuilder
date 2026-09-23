/**
 * Shared link-type resolution for CTA / text link / icon / RTE picker.
 * Types: url | page | menu | mail | route (+ optional none for icons).
 */

export const MAIL_SUBJECT_ATTR = 'data-vb-mail-subject';
export const ROUTE_PARAMS_ATTR = 'data-vb-route-params';

/**
 * @param {Record<string, string>} [labels]
 * @param {{ includeNone?: boolean }} [options]
 * @returns {list<{ value: string, label: string }>}
 */
export function linkTypeSelectOptions(labels = {}, options = {}) {
    const items = [];

    if (options.includeNone) {
        items.push({ value: 'none', label: labels.iconLinkNone ?? 'No link' });
    }

    items.push(
        { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
        { value: 'page', label: labels.buttonLinkTypePage ?? 'Site page' },
        { value: 'menu', label: labels.buttonLinkTypeMenu ?? 'Menu item' },
        { value: 'mail', label: labels.buttonLinkTypeMail ?? 'Email' },
        { value: 'route', label: labels.buttonLinkTypeRoute ?? 'App route' },
    );

    return items;
}

/**
 * @param {string|null|undefined} href
 * @returns {{ email: string, subject: string }}
 */
export function parseMailtoHref(href) {
    const raw = String(href ?? '').trim();

    if (! raw.toLowerCase().startsWith('mailto:')) {
        return { email: '', subject: '' };
    }

    const withoutScheme = raw.slice('mailto:'.length);
    const qIndex = withoutScheme.indexOf('?');
    const email = decodeURIComponent((qIndex >= 0 ? withoutScheme.slice(0, qIndex) : withoutScheme).trim());
    let subject = '';

    if (qIndex >= 0) {
        const params = new URLSearchParams(withoutScheme.slice(qIndex + 1));
        subject = String(params.get('subject') ?? '').trim();
    }

    return { email, subject };
}

/**
 * @param {string} email
 * @param {string} [subject]
 * @returns {string}
 */
export function buildMailtoHref(email, subject = '') {
    const address = String(email ?? '').trim().replace(/^mailto:/i, '');

    if (address === '') {
        return '#';
    }

    const encoded = encodeURIComponent(address).replace(/%40/g, '@');
    const sub = String(subject ?? '').trim();

    if (sub === '') {
        return `mailto:${encoded}`;
    }

    return `mailto:${encoded}?subject=${encodeURIComponent(sub)}`;
}

/**
 * @param {object|null|undefined} editor
 * @param {string} linkType
 * @param {string} linkRef
 * @param {string} href
 * @param {{ mailSubject?: string, routeParams?: Record<string, string> }} [extra]
 * @returns {string}
 */
export function resolveEditorLinkHref(editor, linkType, linkRef, href, extra = {}) {
    const type = String(linkType ?? 'url').trim() || 'url';
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [], routes: [] };

    if (type === 'none') {
        return '#';
    }

    if (type === 'page') {
        return (targets.pages ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    if (type === 'menu') {
        return (targets.menuItems ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    if (type === 'mail') {
        const parsed = parseMailtoHref(href);
        const email = String(linkRef ?? '').trim() || parsed.email;
        const subject = String(extra.mailSubject ?? '').trim() || parsed.subject;

        return buildMailtoHref(email, subject);
    }

    if (type === 'route') {
        const entry = (targets.routes ?? []).find((item) => String(item.id) === String(linkRef));

        if (! entry) {
            return '#';
        }

        const required = Array.isArray(entry.requiredParams) ? entry.requiredParams : [];
        const params = extra.routeParams && typeof extra.routeParams === 'object'
            ? extra.routeParams
            : {};

        if (required.length === 0) {
            return String(entry.url ?? '#').trim() || '#';
        }

        const missing = required.some((name) => String(params[name] ?? '').trim() === '');

        if (missing) {
            return '#';
        }

        // Prefer cached template url only when no params; otherwise build from resolve cache.
        const cacheKey = `${entry.id}?${required.map((name) => `${name}=${params[name]}`).join('&')}`;
        const cached = editor?.__voodbuilderResolvedRoutes?.[cacheKey];

        if (cached) {
            return cached;
        }

        return '#';
    }

    return String(href ?? '#').trim() || '#';
}

/**
 * Resolve a named app route (with params) via the link-targets endpoint and cache the URL.
 *
 * @param {object} editor
 * @param {string} routeName
 * @param {Record<string, string>} [params]
 * @returns {Promise<string>}
 */
export async function resolveAppRouteHref(editor, routeName, params = {}) {
    const name = String(routeName ?? '').trim();

    if (! name) {
        return '#';
    }

    const required = (editor?.__voodbuilderLinkTargets?.routes ?? [])
        .find((item) => String(item.id) === name)
        ?.requiredParams ?? [];

    if (required.length === 0) {
        const entry = (editor?.__voodbuilderLinkTargets?.routes ?? [])
            .find((item) => String(item.id) === name);

        return String(entry?.url ?? '#').trim() || '#';
    }

    const cacheKey = `${name}?${required.map((key) => `${key}=${params[key] ?? ''}`).join('&')}`;

    if (editor.__voodbuilderResolvedRoutes?.[cacheKey]) {
        return editor.__voodbuilderResolvedRoutes[cacheKey];
    }

    const baseUrl = String(editor.__voodbuilderLinkTargetsUrl ?? '').trim();

    if (! baseUrl) {
        return '#';
    }

    const query = new URLSearchParams({ route: name });

    for (const key of required) {
        const value = String(params[key] ?? '').trim();

        if (value !== '') {
            query.set(key, value);
        }
    }

    try {
        const separator = baseUrl.includes('?') ? '&' : '?';
        const response = await fetch(`${baseUrl}${separator}${query.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            return '#';
        }

        const payload = await response.json();
        const url = String(payload?.url ?? '#').trim() || '#';

        editor.__voodbuilderResolvedRoutes = {
            ...(editor.__voodbuilderResolvedRoutes ?? {}),
            [cacheKey]: url,
        };

        return url;
    } catch {
        return '#';
    }
}

/**
 * @param {object|null|undefined} attrs
 * @returns {Record<string, string>}
 */
export function readRouteParamsFromAttrs(attrs = {}) {
    const raw = String(attrs?.[ROUTE_PARAMS_ATTR] ?? '').trim();

    if (raw === '') {
        return {};
    }

    try {
        const parsed = JSON.parse(raw);

        if (! parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            return {};
        }

        const out = {};

        for (const [key, value] of Object.entries(parsed)) {
            out[String(key)] = String(value ?? '');
        }

        return out;
    } catch {
        return {};
    }
}

/**
 * @param {Record<string, string>} params
 * @returns {string|null}
 */
export function serializeRouteParams(params) {
    const cleaned = {};

    for (const [key, value] of Object.entries(params ?? {})) {
        const trimmed = String(value ?? '').trim();

        if (trimmed !== '') {
            cleaned[key] = trimmed;
        }
    }

    return Object.keys(cleaned).length > 0 ? JSON.stringify(cleaned) : null;
}
