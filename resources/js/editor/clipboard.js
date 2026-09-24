/**
 * Clipboard helpers for visual editor UI.
 */

/**
 * Last successful class/style copy (system clipboard may be blocked on HTTP).
 * Paste handlers can fall back to this when `clipboardData` is empty.
 *
 * @type {string}
 */
let lastCopiedEditorText = '';

/**
 * @returns {string}
 */
export function peekEditorClipboardText() {
    return lastCopiedEditorText;
}

/**
 * Fallback for insecure contexts (http://host ≠ localhost) and lost user-activation
 * after async menu handlers. Prefer the async Clipboard API when it works.
 *
 * @param {string} value
 * @returns {boolean}
 */
function copyTextViaExecCommand(value) {
    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', '');
    textarea.setAttribute('aria-hidden', 'true');
    textarea.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;padding:0;border:0;opacity:0;pointer-events:none';
    document.body.appendChild(textarea);

    const selection = document.getSelection?.();
    const previousRange = selection && selection.rangeCount > 0
        ? selection.getRangeAt(0)
        : null;

    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, value.length);

    let ok = false;

    try {
        ok = document.execCommand('copy');
    } catch {
        ok = false;
    }

    document.body.removeChild(textarea);

    if (previousRange && selection) {
        selection.removeAllRanges();
        selection.addRange(previousRange);
    }

    return ok;
}

export async function copyTextToClipboard(text) {
    const value = String(text ?? '');

    if (value === '') {
        return false;
    }

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
            lastCopiedEditorText = value;

            return true;
        }
    } catch {
        // Fall through — common on http://non-localhost and after async UI.
    }

    const ok = copyTextViaExecCommand(value);

    if (ok) {
        lastCopiedEditorText = value;
    }

    return ok;
}

/**
 * Split a class string / pasted blob into Tailwind-ish tokens.
 * Handles space-separated lists and newlines/commas from design tools.
 */
export function splitClassTokens(raw) {
    return String(raw ?? '')
        .replace(/,/g, ' ')
        .split(/\s+/)
        .map((token) => token.trim())
        .filter((token) => token !== '' && token !== '.');
}

export function componentClassString(component) {
    return (component?.getClasses?.() ?? [])
        .map((name) => String(name ?? '').trim())
        .filter(Boolean)
        .join(' ');
}
