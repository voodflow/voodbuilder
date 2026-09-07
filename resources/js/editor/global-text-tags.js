/**
 * Site-wide curly tags (mirror of PHP GlobalTextTags).
 * Used for editor preview / copyright persistence helpers.
 */

/** Stable order matching PHP GlobalTextTags::values(). */
export const GLOBAL_TEXT_TAG_KEYS = [
    'current_year',
    'brand_name',
    'site_name',
    'site_url',
    'logged_username',
];

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
        logged_username: '',
    };
}

/**
 * @param {string} key
 * @param {object} [labels]
 * @returns {string}
 */
function tagLabelForKey(key, labels = {}) {
    const map = {
        current_year: labels.globalTextTagCurrentYear ?? 'Current year',
        brand_name: labels.globalTextTagBrandName ?? 'Brand name',
        site_name: labels.globalTextTagSiteName ?? 'Site name',
        site_url: labels.globalTextTagSiteUrl ?? 'Site URL',
        logged_username: labels.globalTextTagLoggedUsername ?? 'Logged-in user name (empty for guests)',
    };

    return map[key] ?? key;
}

/**
 * Catalog for the topbar tags modal.
 *
 * @param {object} [editor]
 * @param {object} [labels]
 * @returns {Array<{ key: string, token: string, label: string, preview: string }>}
 */
export function globalTextTagCatalog(editor = null, labels = {}) {
    const values = globalTextTagValues(editor);
    const keys = GLOBAL_TEXT_TAG_KEYS.filter((key) => Object.prototype.hasOwnProperty.call(values, key));

    for (const key of Object.keys(values)) {
        if (! keys.includes(key)) {
            keys.push(key);
        }
    }

    return keys.map((key) => ({
        key,
        token: `{${key}}`,
        label: tagLabelForKey(key, labels),
        preview: String(values[key] ?? ''),
    }));
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
