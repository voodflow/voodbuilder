/**
 * Clipboard helpers for GrapesJS editor UI.
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

    try {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        textarea.remove();

        return ok;
    } catch {
        return false;
    }
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
