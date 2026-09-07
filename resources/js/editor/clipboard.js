/**
 * Clipboard helpers for visual editor UI.
 */

export async function copyTextToClipboard(text) {
    const value = String(text ?? '');

    if (value === '') {
        return false;
    }

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);

            return true;
        }
    } catch {
        // Fall through to execCommand.
    }

    // Avoid deprecated document.execCommand('copy') (browser deprecation warnings).
    return false;
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
