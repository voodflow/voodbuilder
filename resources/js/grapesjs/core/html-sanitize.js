/**
 * Strip invalid DOM attribute names from HTML before GrapesJS parses it.
 * Defends against uncompiled Blade directives leaking into editor HTML.
 */

const INVALID_ATTR_PATTERN = /\s+(?:@\w+(?:\([^)]*\))?(?:=(?:"[^"]*"|'[^']*'|[^\s>]*))?|\([^)]*\)(?:=(?:"[^"]*"|'[^']*'|[^\s>]*))?)/gi;

/**
 * @param {string} name
 * @returns {boolean}
 */
export function isValidDomAttributeName(name) {
    if (typeof name !== 'string' || name === '') {
        return false;
    }

    if (name.startsWith('@') || name.startsWith('(') || name.includes('(')) {
        return false;
    }

    return /^[a-zA-Z_][\w:.-]*$/.test(name);
}

/**
 * @param {ParentNode|null|undefined} root
 */
export function stripInvalidDomAttributes(root) {
    if (! root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    const elements = root instanceof Element ? [root, ...root.querySelectorAll('*')] : [...root.querySelectorAll('*')];

    for (const element of elements) {
        if (! element?.attributes) {
            continue;
        }

        const toRemove = [];

        for (const attribute of element.attributes) {
            if (! isValidDomAttributeName(attribute.name)) {
                toRemove.push(attribute.name);
            }
        }

        for (const name of toRemove) {
            element.removeAttribute(name);
        }
    }
}

/**
 * @param {string} html
 * @returns {string}
 */
export function stripInvalidDomAttributesFromHtml(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    if (! html.includes('@') && ! html.includes('(@')) {
        return html;
    }

    const cleaned = html.replace(INVALID_ATTR_PATTERN, '');

    if (typeof document === 'undefined') {
        return cleaned;
    }

    const temp = document.createElement('div');

    temp.innerHTML = cleaned;
    stripInvalidDomAttributes(temp);

    return temp.innerHTML;
}
