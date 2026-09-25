/**
 * Editor hooks intentionally swallow many errors (frame not ready, view detached, …).
 * While diagnosing, set `localStorage.voodbuilderDebug = '1'` (or
 * `window.__VOODBUILDER_DEBUG__ = true`) to log them instead of losing them.
 *
 * @param {unknown} error
 */
export function debugSwallowed(error) {
    if (! isDebugEnabled()) {
        return;
    }

    console.debug('[voodbuilder] swallowed error', error);
}

function isDebugEnabled() {
    try {
        return globalThis.__VOODBUILDER_DEBUG__ === true
            || globalThis.localStorage?.getItem?.('voodbuilderDebug') === '1';
    } catch {
        return false;
    }
}
