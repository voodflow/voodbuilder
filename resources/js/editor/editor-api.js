/**
 * Shared helpers for visual editor API calls.
 */

import { debugSwallowed } from './debug-swallowed.js';

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
            return truncateDialogMessage(payload.message);
        }

        if (payload?.errors && typeof payload.errors === 'object') {
            const joined = Object.values(payload.errors).flat().filter((part) => typeof part === 'string').join(' ');

            if (joined.trim() !== '') {
                return truncateDialogMessage(joined);
            }
        }
    } catch (error) {
        // ignore non-json bodies
        debugSwallowed(error);
    }

    return fallback;
}

/**
 * Keep editor alert dialogs readable — SQL dumps / CSS bodies can be megabytes.
 *
 * @param {string} message
 * @param {number} [maxChars]
 * @returns {string}
 */
export function truncateDialogMessage(message, maxChars = 480) {
    const text = String(message ?? '').trim();

    if (text.length <= maxChars) {
        return text;
    }

    // Prefer a short SQLSTATE / validation cue when the body is a QueryException dump.
    const sqlState = text.match(/SQLSTATE\[[^\]]+\]:\s*[^\n(]+/i)?.[0];

    if (sqlState) {
        return `${sqlState.trim()}…`;
    }

    return `${text.slice(0, maxChars).trimEnd()}…`;
}
