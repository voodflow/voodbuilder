/**
 * Link picker dialog matching CTA / Icon link settings (URL · page · menu + target).
 */

import { createSelectField, createTextField } from './editor-form-ui.js';
import { enhanceInspectorSelects } from './inspector-select-ui.js';

let activeDialog = null;

function dismissActiveDialog(result) {
    if (! activeDialog) {
        return;
    }

    const { modal, onKeyDown, resolve } = activeDialog;

    modal.hidden = true;
    modal.remove();
    window.removeEventListener('keydown', onKeyDown, true);
    activeDialog = null;
    resolve(result);
}

/**
 * Default look for inline links in Rich Text / canvas RTE (matches Text link block).
 */
export const RICH_TEXT_LINK_CLASSES = [
    'text-vp-brand-1',
    'underline',
    'underline-offset-2',
    'vb-text-link',
];

/**
 * Ensure an anchor has the standard inline link classes (keep extras).
 *
 * @param {Element|null|undefined} anchor
 */
export function ensureRichTextLinkClasses(anchor) {
    if (! anchor?.classList) {
        return;
    }

    for (const token of RICH_TEXT_LINK_CLASSES) {
        anchor.classList.add(token);
    }
}

/**
 * @param {object|null|undefined} editor
 * @param {string} linkType
 * @param {string} linkRef
 * @param {string} href
 * @returns {string}
 */
export function resolveEditorLinkHref(editor, linkType, linkRef, href) {
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };

    if (linkType === 'page') {
        return (targets.pages ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    if (linkType === 'menu') {
        return (targets.menuItems ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    return String(href ?? '#').trim() || '#';
}

/**
 * Build an HTML anchor opening tag from link picker result.
 *
 * @param {{ href: string, target?: string, linkType?: string, linkRef?: string }} link
 * @param {string} [extraAttrs]
 * @returns {string}
 */
export function buildAnchorOpenTag(link, extraAttrs = '') {
    const href = String(link.href ?? '#').trim() || '#';
    const target = String(link.target ?? '').trim();
    const linkType = String(link.linkType ?? 'url').trim() || 'url';
    const linkRef = String(link.linkRef ?? '').trim();
    const classAttr = `class="${escapeAttr(RICH_TEXT_LINK_CLASSES.join(' '))}"`;
    const parts = [
        `href="${escapeAttr(href)}"`,
        classAttr,
        `data-vb-link-type="${escapeAttr(linkType)}"`,
    ];

    if (linkType !== 'url' && linkRef) {
        parts.push(`data-vb-link="${escapeAttr(linkRef)}"`);
    }

    if (target) {
        parts.push(`target="${escapeAttr(target)}"`);
    }

    if (target === '_blank') {
        parts.push('rel="noopener noreferrer"');
    }

    if (extraAttrs) {
        parts.push(extraAttrs);
    }

    return `<a ${parts.join(' ')}>`;
}

/**
 * Apply href/target/type + standard visual classes on an existing <a>.
 *
 * @param {Element|null|undefined} anchor
 * @param {{ href: string, target?: string, linkType?: string, linkRef?: string }} link
 */
export function applyRichTextLinkAttrs(anchor, link) {
    if (! anchor) {
        return;
    }

    anchor.setAttribute('href', link.href || '#');
    anchor.setAttribute('data-vb-link-type', link.linkType || 'url');

    if (link.linkType !== 'url' && link.linkRef) {
        anchor.setAttribute('data-vb-link', link.linkRef);
    } else {
        anchor.removeAttribute('data-vb-link');
    }

    if (link.target) {
        anchor.setAttribute('target', link.target);
    } else {
        anchor.removeAttribute('target');
    }

    if (link.target === '_blank') {
        anchor.setAttribute('rel', 'noopener noreferrer');
    } else {
        anchor.removeAttribute('rel');
    }

    ensureRichTextLinkClasses(anchor);
}

function escapeAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

/**
 * Read link fields from an existing <a> (or null).
 *
 * @param {Element|null|undefined} anchor
 * @returns {{ linkType: string, href: string, linkRef: string, target: string, isLink: boolean }}
 */
export function readAnchorLinkState(anchor) {
    if (! anchor) {
        return {
            linkType: 'url',
            href: '',
            linkRef: '',
            target: '',
            isLink: false,
        };
    }

    return {
        linkType: String(anchor.getAttribute('data-vb-link-type') ?? 'url').trim() || 'url',
        href: String(anchor.getAttribute('href') ?? '').trim(),
        linkRef: String(anchor.getAttribute('data-vb-link') ?? '').trim(),
        target: String(anchor.getAttribute('target') ?? '').trim(),
        isLink: true,
    };
}

/**
 * @param {{
 *   editor?: object|null,
 *   labels?: Record<string, string>,
 *   title?: string,
 *   message?: string,
 *   defaultLinkType?: string,
 *   defaultHref?: string,
 *   defaultLinkRef?: string,
 *   defaultTarget?: string,
 *   allowRemove?: boolean,
 * }} [options]
 * @returns {Promise<null|{ remove: true }|{ linkType: string, href: string, linkRef: string, target: string }>}
 */
export function linkPickerDialog(options = {}) {
    if (activeDialog) {
        dismissActiveDialog(null);
    }

    const labels = options.labels ?? {};
    const editor = options.editor ?? null;
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };
    const dismissLabel = labels.dialogCancel ?? 'Cancel';
    const okLabel = labels.dialogConfirm ?? 'Confirm';
    const dialogTitle = options.title ?? labels.rteLinkPromptTitle ?? labels.buttonLinkType ?? 'Link';
    const allowRemove = Boolean(options.allowRemove);

    let linkType = String(options.defaultLinkType ?? 'url').trim() || 'url';
    let href = String(options.defaultHref ?? '').trim();
    let linkRef = String(options.defaultLinkRef ?? '').trim();
    let target = String(options.defaultTarget ?? '').trim();

    if (linkType !== 'url' && linkType !== 'page' && linkType !== 'menu') {
        linkType = 'url';
    }

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-editor-modal voodbuilder-editor-dialog voodbuilder-editor-link-picker-dialog';
        modal.setAttribute('role', 'presentation');

        const panel = document.createElement('div');
        panel.className = 'voodbuilder-editor-modal__panel voodbuilder-editor-dialog__panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');

        const backdrop = document.createElement('div');
        backdrop.className = 'voodbuilder-editor-modal__backdrop';
        backdrop.dataset.voodbuilderDialogCancel = '';

        const head = document.createElement('header');
        head.className = 'voodbuilder-editor-modal__head';
        head.innerHTML = `
            <h2 class="voodbuilder-editor-modal__title">${escapeHtml(dialogTitle)}</h2>
            <button type="button" class="voodbuilder-editor-modal__close" data-voodbuilder-dialog-cancel aria-label="${escapeAttr(dismissLabel)}">×</button>
        `;

        const body = document.createElement('div');
        body.className = 'voodbuilder-editor-modal__body voodbuilder-editor-dialog__body voodbuilder-editor-form';

        if (options.message) {
            const message = document.createElement('p');
            message.className = 'voodbuilder-editor-dialog__message';
            message.textContent = options.message;
            body.appendChild(message);
        }

        const fields = document.createElement('div');
        fields.className = 'voodbuilder-editor-form__fields';

        const typeField = createSelectField({
            label: labels.buttonLinkType ?? 'Link type',
            name: 'linkPickerType',
            value: linkType,
            options: [
                { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
                { value: 'page', label: labels.buttonLinkTypePage ?? 'Site page' },
                { value: 'menu', label: labels.buttonLinkTypeMenu ?? 'Menu item' },
            ],
            onChange: (value) => {
                linkType = value;
                syncVisibility();
            },
        });

        const { field: urlField, input: urlInput } = createTextField({
            label: labels.buttonLinkUrl ?? 'Link URL',
            name: 'linkPickerHref',
            value: href === '#' ? '' : href,
            placeholder: labels.buttonLinkUrlPlaceholder ?? labels.rteLinkPlaceholder ?? 'https:// or /page',
        });

        const pageField = createSelectField({
            label: labels.buttonLinkPage ?? 'Page',
            name: 'linkPickerPage',
            value: linkRef,
            options: [
                { value: '', label: '—' },
                ...(targets.pages ?? []).map((item) => ({
                    value: String(item.id),
                    label: item.label,
                })),
            ],
            onChange: (value) => {
                linkRef = value;
            },
        });

        const menuField = createSelectField({
            label: labels.buttonLinkMenu ?? 'Menu item',
            name: 'linkPickerMenu',
            value: linkRef,
            options: [
                { value: '', label: '—' },
                ...(targets.menuItems ?? []).map((item) => ({
                    value: String(item.id),
                    label: item.label,
                })),
            ],
            onChange: (value) => {
                linkRef = value;
            },
        });

        const targetField = createSelectField({
            label: labels.buttonLinkTarget ?? 'Open in',
            name: 'linkPickerTarget',
            value: target,
            options: [
                { value: '', label: labels.buttonLinkSameTab ?? 'Same tab' },
                { value: '_blank', label: labels.buttonLinkNewTab ?? 'New tab' },
            ],
            onChange: (value) => {
                target = value;
            },
        });

        const syncVisibility = () => {
            urlField.hidden = linkType !== 'url';
            pageField.hidden = linkType !== 'page';
            menuField.hidden = linkType !== 'menu';
        };

        fields.append(typeField, urlField, pageField, menuField, targetField);
        body.appendChild(fields);
        syncVisibility();

        const footer = document.createElement('footer');
        footer.className = 'voodbuilder-editor-dialog__footer';

        if (allowRemove) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--ghost';
            removeBtn.textContent = labels.rteLinkRemove ?? 'Remove link';
            removeBtn.dataset.voodbuilderDialogRemove = '';
            footer.appendChild(removeBtn);
        }

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--ghost';
        cancelBtn.dataset.voodbuilderDialogCancel = '';
        cancelBtn.textContent = dismissLabel;

        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--primary';
        confirmBtn.dataset.voodbuilderDialogConfirm = '';
        confirmBtn.textContent = okLabel;

        footer.append(cancelBtn, confirmBtn);
        panel.append(head, body, footer);
        modal.append(backdrop, panel);
        document.body.appendChild(modal);
        modal.hidden = false;
        enhanceInspectorSelects(modal);

        const finish = (result) => {
            if (! activeDialog) {
                return;
            }

            dismissActiveDialog(result);
        };

        const submit = () => {
            href = String(urlInput.value || '').trim();
            linkRef = linkType === 'page'
                ? String(pageField.querySelector('select')?.value || '')
                : linkType === 'menu'
                    ? String(menuField.querySelector('select')?.value || '')
                    : '';
            target = String(targetField.querySelector('select')?.value || '');

            if (linkType === 'url' && href === '') {
                urlInput.focus();

                return;
            }

            if ((linkType === 'page' || linkType === 'menu') && linkRef === '') {
                const select = (linkType === 'page' ? pageField : menuField).querySelector('select');
                select?.focus();

                return;
            }

            const resolved = resolveEditorLinkHref(editor, linkType, linkRef, href);

            finish({
                linkType,
                href: resolved,
                linkRef: linkType === 'url' ? '' : linkRef,
                target,
            });
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(null);
            }

            if (event.key === 'Enter' && event.target === urlInput) {
                event.preventDefault();
                submit();
            }
        };

        activeDialog = { modal, onKeyDown, resolve };

        modal.querySelectorAll('[data-voodbuilder-dialog-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        modal.querySelector('[data-voodbuilder-dialog-remove]')?.addEventListener('click', () => {
            finish({ remove: true });
        });

        confirmBtn.addEventListener('click', submit);
        window.addEventListener('keydown', onKeyDown, true);

        window.requestAnimationFrame(() => {
            if (linkType === 'url') {
                urlInput.focus();
                urlInput.select();
            } else {
                confirmBtn.focus();
            }
        });
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
