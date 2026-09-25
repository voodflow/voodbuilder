/**
 * Link picker dialog matching CTA / Icon link settings
 * (URL · page · menu · mail · route + target).
 */

import { createLinkTargetFields } from './link-target-fields.js';
import { enhanceInspectorSelects } from './inspector-select-ui.js';
import {
    MAIL_SUBJECT_ATTR,
    ROUTE_PARAMS_ATTR,
    parseMailtoHref,
    resolveAppRouteHref,
    resolveEditorLinkHref,
    serializeRouteParams,
} from './editor-link-resolve.js';

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

export { resolveEditorLinkHref };

/**
 * Build an HTML anchor opening tag from link picker result.
 *
 * @param {{ href: string, target?: string, linkType?: string, linkRef?: string, mailSubject?: string, routeParams?: Record<string, string> }} link
 * @param {string} [extraAttrs]
 * @returns {string}
 */
export function buildAnchorOpenTag(link, extraAttrs = '') {
    const href = String(link.href ?? '#').trim() || '#';
    const target = String(link.target ?? '').trim();
    const linkType = String(link.linkType ?? 'url').trim() || 'url';
    const linkRef = String(link.linkRef ?? '').trim();
    const mailSubject = String(link.mailSubject ?? '').trim();
    const routeParamsJson = serializeRouteParams(link.routeParams ?? {});
    const classAttr = `class="${escapeAttr(RICH_TEXT_LINK_CLASSES.join(' '))}"`;
    const parts = [
        `href="${escapeAttr(href)}"`,
        classAttr,
        `data-vb-link-type="${escapeAttr(linkType)}"`,
    ];

    if (linkType !== 'url' && linkRef) {
        parts.push(`data-vb-link="${escapeAttr(linkRef)}"`);
    }

    if (linkType === 'mail' && mailSubject) {
        parts.push(`${MAIL_SUBJECT_ATTR}="${escapeAttr(mailSubject)}"`);
    }

    if (linkType === 'route' && routeParamsJson) {
        parts.push(`${ROUTE_PARAMS_ATTR}="${escapeAttr(routeParamsJson)}"`);
    }

    if (target && linkType !== 'mail') {
        parts.push(`target="${escapeAttr(target)}"`);
    }

    if (target === '_blank' && linkType !== 'mail') {
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
 * @param {{ href: string, target?: string, linkType?: string, linkRef?: string, mailSubject?: string, routeParams?: Record<string, string> }} link
 */
export function applyRichTextLinkAttrs(anchor, link) {
    if (! anchor) {
        return;
    }

    const linkType = link.linkType || 'url';

    anchor.setAttribute('href', link.href || '#');
    anchor.setAttribute('data-vb-link-type', linkType);

    if (linkType !== 'url' && link.linkRef) {
        anchor.setAttribute('data-vb-link', link.linkRef);
    } else {
        anchor.removeAttribute('data-vb-link');
    }

    if (linkType === 'mail' && link.mailSubject) {
        anchor.setAttribute(MAIL_SUBJECT_ATTR, link.mailSubject);
    } else {
        anchor.removeAttribute(MAIL_SUBJECT_ATTR);
    }

    const routeParamsJson = serializeRouteParams(link.routeParams ?? {});

    if (linkType === 'route' && routeParamsJson) {
        anchor.setAttribute(ROUTE_PARAMS_ATTR, routeParamsJson);
    } else {
        anchor.removeAttribute(ROUTE_PARAMS_ATTR);
    }

    if (link.target && linkType !== 'mail') {
        anchor.setAttribute('target', link.target);
    } else {
        anchor.removeAttribute('target');
    }

    if (link.target === '_blank' && linkType !== 'mail') {
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
 * @returns {{ linkType: string, href: string, linkRef: string, target: string, mailSubject: string, isLink: boolean }}
 */
export function readAnchorLinkState(anchor) {
    if (! anchor) {
        return {
            linkType: 'url',
            href: '',
            linkRef: '',
            target: '',
            mailSubject: '',
            isLink: false,
        };
    }

    const href = String(anchor.getAttribute('href') ?? '').trim();
    let linkType = String(anchor.getAttribute('data-vb-link-type') ?? 'url').trim() || 'url';
    let linkRef = String(anchor.getAttribute('data-vb-link') ?? '').trim();
    let mailSubject = String(anchor.getAttribute(MAIL_SUBJECT_ATTR) ?? '').trim();

    if (linkType === 'mail' || href.toLowerCase().startsWith('mailto:')) {
        linkType = 'mail';
        const parsed = parseMailtoHref(href);

        if (! linkRef) {
            linkRef = parsed.email;
        }

        if (! mailSubject) {
            mailSubject = parsed.subject;
        }
    }

    return {
        linkType,
        href,
        linkRef,
        target: String(anchor.getAttribute('target') ?? '').trim(),
        mailSubject,
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
 *   defaultMailSubject?: string,
 *   allowRemove?: boolean,
 * }} [options]
 * @returns {Promise<null|{ remove: true }|{ linkType: string, href: string, linkRef: string, target: string, mailSubject?: string, routeParams?: Record<string, string> }>}
 */
export function linkPickerDialog(options = {}) {
    if (activeDialog) {
        dismissActiveDialog(null);
    }

    const labels = options.labels ?? {};
    const editor = options.editor ?? null;
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [], routes: [] };
    const dismissLabel = labels.dialogCancel ?? 'Cancel';
    const okLabel = labels.dialogConfirm ?? 'Confirm';
    const dialogTitle = options.title ?? labels.rteLinkPromptTitle ?? labels.buttonLinkType ?? 'Link';
    const allowRemove = Boolean(options.allowRemove);

    let linkType = String(options.defaultLinkType ?? 'url').trim() || 'url';
    let href = String(options.defaultHref ?? '').trim();
    let linkRef = String(options.defaultLinkRef ?? '').trim();
    let target = String(options.defaultTarget ?? '').trim();
    let mailSubject = String(options.defaultMailSubject ?? '').trim();

    const allowed = new Set(['url', 'page', 'menu', 'mail', 'route']);

    if (! allowed.has(linkType)) {
        if (href.toLowerCase().startsWith('mailto:')) {
            linkType = 'mail';
            const parsed = parseMailtoHref(href);
            linkRef = parsed.email;
            mailSubject = mailSubject || parsed.subject;
        } else {
            linkType = 'url';
        }
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

        const linkFields = createLinkTargetFields({
            labels,
            targets,
            names: {
                type: 'linkPickerType',
                href: 'linkPickerHref',
                page: 'linkPickerPage',
                menu: 'linkPickerMenu',
                mail: 'linkPickerMail',
                subject: 'linkPickerMailSubject',
                route: 'linkPickerRoute',
                target: 'linkPickerTarget',
                routeParamPrefix: 'linkPickerRouteParam_',
            },
            initial: { linkType, linkRef, href, target, mailSubject, routeParams: {} },
            urlValue: href === '#' ? '' : href,
            urlPlaceholder: labels.buttonLinkUrlPlaceholder ?? labels.rteLinkPlaceholder ?? 'https:// or /page',
            emptyHref: '',
            enhanceSelects: enhanceInspectorSelects,
        });
        const { urlInput, mailInput } = linkFields;

        fields.append(...linkFields.fields);
        body.appendChild(fields);
        linkFields.syncVisibility();

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

        const submit = async () => {
            const state = linkFields.read();
            const { routeParams } = state;
            ({ linkType, linkRef, href, target, mailSubject } = state);

            if (linkType === 'url' && href === '') {
                urlInput.focus();

                return;
            }

            if ((linkType === 'page' || linkType === 'menu' || linkType === 'route') && linkRef === '') {
                linkFields.referenceField(linkType)?.querySelector('select')?.focus();

                return;
            }

            if (linkType === 'mail' && linkRef === '') {
                mailInput.focus();

                return;
            }

            let resolved = resolveEditorLinkHref(editor, linkType, linkRef, href, {
                mailSubject,
                routeParams,
            });

            if (linkType === 'route' && linkRef) {
                resolved = await resolveAppRouteHref(editor, linkRef, routeParams) || resolved;
            }

            finish({
                linkType,
                href: resolved,
                linkRef: linkType === 'url' ? '' : linkRef,
                target,
                mailSubject: linkType === 'mail' ? mailSubject : '',
                routeParams: linkType === 'route' ? routeParams : {},
            });
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(null);
            }

            if (event.key === 'Enter' && (event.target === urlInput || event.target === mailInput)) {
                event.preventDefault();
                void submit();
            }
        };

        activeDialog = { modal, onKeyDown, resolve };

        modal.querySelectorAll('[data-voodbuilder-dialog-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        modal.querySelector('[data-voodbuilder-dialog-remove]')?.addEventListener('click', () => {
            finish({ remove: true });
        });

        confirmBtn.addEventListener('click', () => {
            void submit();
        });
        window.addEventListener('keydown', onKeyDown, true);

        window.requestAnimationFrame(() => {
            if (linkType === 'url') {
                urlInput.focus();
                urlInput.select();
            } else if (linkType === 'mail') {
                mailInput.focus();
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
