/**
 * Site-wide curly tags (mirror of PHP GlobalTextTags).
 * Used for editor preview / copyright persistence helpers.
 */

/**
 * @param {object} [editor]
 * @returns {Record<string, string>}
 */
export function globalTextTagValues(editor = null) {
    const fromEditor = editor?.__voodbuilderGlobalTextTags;

    if (fromEditor && typeof fromEditor === 'object') {
        return { ...fromEditor };
    }

    return {
        current_year: String(new Date().getFullYear()),
        brand_name: 'VoodBuilder',
        site_name: 'VoodBuilder',
        site_url: '',
    };
}

/**
 * @param {string} text
 * @param {Record<string, string>} [values]
 * @returns {string}
 */
export function replaceGlobalTextTags(text, values = {}) {
    let out = String(text ?? '');

    if (out === '' || ! out.includes('{')) {
        return out;
    }

    for (const [key, value] of Object.entries(values)) {
        out = out.split(`{${key}}`).join(String(value ?? ''));
    }

    return out;
}

/**
 * Keep copyright year dynamic when the canvas shows a resolved © YYYY.
 *
 * @param {string} text
 * @param {number|string} [year]
 * @returns {string}
 */
export function retagCurrentYear(text, year = new Date().getFullYear()) {
    const y = String(year);

    return String(text ?? '').replace(new RegExp(`©\\s*${y}\\b`, 'g'), '© {current_year}');
}
