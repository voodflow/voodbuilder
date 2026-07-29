/**
 * Shared helpers for visual editor API calls.
 */

export function resolveCsrfToken(fallback = '') {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? fallback;
}

export function editorApiHeaders(csrf, options = {}) {
    const { json = false, extra = {} } = options;

    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': resolveCsrfToken(csrf),
        ...extra,
    };

    if (json) {
        headers['Content-Type'] = 'application/json';
    }

    return headers;
}

export async function resolveApiErrorMessage(response, fallback, labels = {}) {
    if (response.status === 419 || response.status === 401) {
        return labels.sessionExpired ?? fallback;
    }

    if (response.status === 403) {
        return labels.forbidden ?? fallback;
    }

    try {
        const payload = await response.clone().json();

        if (typeof payload?.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }
    } catch {
        // ignore non-json bodies
    }

    return fallback;
}
