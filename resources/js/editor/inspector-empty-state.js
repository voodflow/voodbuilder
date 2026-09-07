/**
 * Shared inspector empty / notice states (Content, Style, Dynamic, Conditions).
 */

export const INSPECTOR_EMPTY_STATE_CLASS = 'voodbuilder-editor-inspector-empty-state';
export const INSPECTOR_EMPTY_STATE_ATTR = 'data-voodbuilder-inspector-empty-state';

/**
 * @param {object|null|undefined} labels
 * @returns {string}
 */
export function inspectorSelectElementMessage(labels = {}) {
    return labels.selectComponent ?? 'Select an element on the canvas first.';
}

/**
 * @param {HTMLElement} parent
 * @param {string|null|undefined} linkUrl
 * @param {string|null|undefined} linkLabel
 * @returns {void}
 */
function appendMarketingLink(parent, linkUrl, linkLabel) {
    if (! linkUrl) {
        return;
    }

    const link = document.createElement('a');
    link.className = `${INSPECTOR_EMPTY_STATE_CLASS}__link`;
    link.href = String(linkUrl);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = linkLabel ?? 'Learn more';
    parent.appendChild(link);
}

/**
 * @param {object} [options]
 * @param {string} [options.message]
 * @param {string} [options.title]
 * @param {object|null|undefined} [options.labels]
 * @param {'p'|'div'} [options.tag]
 * @param {string} [options.classNameExtra]
 * @param {string|null|undefined} [options.linkUrl]
 * @param {string|null|undefined} [options.linkLabel]
 * @returns {HTMLElement}
 */
export function createInspectorEmptyState(options = {}) {
    const {
        message = null,
        title = null,
        labels = {},
        tag = 'p',
        classNameExtra = '',
        linkUrl = null,
        linkLabel = null,
    } = options;

    const resolvedLinkUrl = linkUrl ?? null;
    const resolvedLinkLabel = linkLabel ?? labels.learnMore ?? 'Learn more';

    if (title) {
        const wrap = document.createElement('div');
        wrap.className = [INSPECTOR_EMPTY_STATE_CLASS, classNameExtra].filter(Boolean).join(' ');
        wrap.setAttribute(INSPECTOR_EMPTY_STATE_ATTR, '1');

        const heading = document.createElement('p');
        heading.className = `${INSPECTOR_EMPTY_STATE_CLASS}__title`;
        heading.textContent = title;
        wrap.appendChild(heading);

        const body = document.createElement('p');
        body.className = `${INSPECTOR_EMPTY_STATE_CLASS}__body`;
        body.textContent = message ?? inspectorSelectElementMessage(labels);
        wrap.appendChild(body);

        appendMarketingLink(wrap, resolvedLinkUrl, resolvedLinkLabel);

        return wrap;
    }

    const el = document.createElement(tag === 'div' ? 'div' : 'p');
    el.className = [INSPECTOR_EMPTY_STATE_CLASS, classNameExtra].filter(Boolean).join(' ');
    el.setAttribute(INSPECTOR_EMPTY_STATE_ATTR, '1');
    el.textContent = message ?? inspectorSelectElementMessage(labels);

    appendMarketingLink(el, resolvedLinkUrl, resolvedLinkLabel);

    return el;
}

/**
 * Title + body (+ optional marketing link) as an HTML string for string-based mounts.
 *
 * @param {object} [options]
 * @param {string} [options.title]
 * @param {string} [options.message]
 * @param {string|null|undefined} [options.linkUrl]
 * @param {string|null|undefined} [options.linkLabel]
 * @param {string} [options.classNameExtra]
 * @returns {string}
 */
export function inspectorEmptyStateUpsellHtml(options = {}) {
    const {
        title = '',
        message = '',
        linkUrl = null,
        linkLabel = 'Learn more',
        classNameExtra = '',
    } = options;

    const className = [INSPECTOR_EMPTY_STATE_CLASS, classNameExtra].filter(Boolean).join(' ');
    const link = linkUrl
        ? `<a class="${INSPECTOR_EMPTY_STATE_CLASS}__link" href="${escapeHtml(linkUrl)}" target="_blank" rel="noopener noreferrer">${escapeHtml(linkLabel)}</a>`
        : '';

    return `<div class="${className}" ${INSPECTOR_EMPTY_STATE_ATTR}="1">
            <p class="${INSPECTOR_EMPTY_STATE_CLASS}__title">${escapeHtml(title)}</p>
            <p class="${INSPECTOR_EMPTY_STATE_CLASS}__body">${escapeHtml(message)}</p>
            ${link}
        </div>`;
}

/**
 * @param {string|null|undefined} message
 * @param {object|null|undefined} [labels]
 * @returns {string}
 */
export function inspectorEmptyStateHtml(message = null, labels = {}) {
    const text = escapeHtml(message ?? inspectorSelectElementMessage(labels));

    return `<p class="${INSPECTOR_EMPTY_STATE_CLASS}" ${INSPECTOR_EMPTY_STATE_ATTR}="1">${text}</p>`;
}

/**
 * @param {ParentNode|null|undefined} mount
 * @param {object} [options]
 * @param {string} [options.message]
 * @param {object|null|undefined} [options.labels]
 * @returns {HTMLElement|null}
 */
export function renderInspectorEmptyState(mount, options = {}) {
    if (! mount) {
        return null;
    }

    mount.replaceChildren();
    const el = createInspectorEmptyState(options);
    mount.appendChild(el);

    return el;
}

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
