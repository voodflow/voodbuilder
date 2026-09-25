/**
 * Shared link target controls (type · URL · page · menu · mail · route + params · target)
 * for the Button and Text link settings panels and the link picker dialog.
 *
 * Text inputs are returned so each caller keeps its own commit timing
 * (live `input` for Button, `change`/`blur` for Text link, submit for the dialog).
 */

import { createSelectField, createTextField } from './editor-form-ui.js';
import { buildMailtoHref, linkTypeSelectOptions } from './editor-link-resolve.js';

/**
 * @typedef {{ linkType: string, linkRef: string, href: string, target: string, mailSubject: string, routeParams: Record<string, string> }} LinkTargetState
 */

/**
 * @param {{
 *   labels?: Record<string, string>,
 *   targets?: { pages?: Array<{ id: unknown, label: string }>, menuItems?: Array<{ id: unknown, label: string }>, routes?: Array<{ id: unknown, label: string, requiredParams?: string[] }> },
 *   names: { type: string, href: string, page: string, menu: string, mail: string, subject: string, route: string, target: string, routeParamPrefix: string },
 *   initial: LinkTargetState,
 *   urlValue?: string,
 *   urlPlaceholder?: string,
 *   emptyHref?: string,
 *   includeNone?: boolean,
 *   typeLabel?: string,
 *   onChange?: () => void,
 *   enhanceSelects?: (root: HTMLElement) => void,
 * }} options
 */
export function createLinkTargetFields({
    labels = {},
    targets = {},
    names,
    initial,
    urlValue,
    urlPlaceholder,
    emptyHref = '#',
    includeNone = false,
    typeLabel,
    onChange = () => {},
    enhanceSelects = () => {},
}) {
    let linkType = initial.linkType;
    let linkRef = initial.linkRef;
    let routeParams = { ...(initial.routeParams ?? {}) };

    const refOptions = (items) => [
        { value: '', label: '—' },
        ...(items ?? []).map((item) => ({
            value: String(item.id),
            label: item.label,
        })),
    ];

    const typeField = createSelectField({
        label: typeLabel ?? labels.buttonLinkType ?? 'Link type',
        name: names.type,
        value: linkType,
        options: linkTypeSelectOptions(labels, { includeNone }),
        onChange: (value) => {
            linkType = value;
            syncVisibility();
            onChange();
        },
    });

    const { field: urlField, input: urlInput } = createTextField({
        label: labels.buttonLinkUrl ?? 'Link URL',
        name: names.href,
        value: urlValue ?? initial.href,
        placeholder: urlPlaceholder ?? labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });

    const pageField = createSelectField({
        label: labels.buttonLinkPage ?? 'Page',
        name: names.page,
        value: linkRef,
        options: refOptions(targets.pages),
        onChange: (value) => {
            linkRef = value;
            onChange();
        },
    });

    const menuField = createSelectField({
        label: labels.buttonLinkMenu ?? 'Menu item',
        name: names.menu,
        value: linkRef,
        options: refOptions(targets.menuItems),
        onChange: (value) => {
            linkRef = value;
            onChange();
        },
    });

    const { field: mailField, input: mailInput } = createTextField({
        label: labels.buttonLinkMail ?? 'Email address',
        name: names.mail,
        value: linkType === 'mail' ? linkRef : '',
        placeholder: labels.buttonLinkMailPlaceholder ?? 'name@example.com',
    });

    const { field: subjectField, input: subjectInput } = createTextField({
        label: labels.buttonLinkMailSubject ?? 'Subject',
        name: names.subject,
        value: initial.mailSubject,
        placeholder: labels.buttonLinkMailSubjectPlaceholder ?? 'Optional subject',
    });

    const routeField = createSelectField({
        label: labels.buttonLinkRoute ?? 'App route',
        name: names.route,
        value: linkType === 'route' ? linkRef : '',
        options: refOptions(targets.routes),
        onChange: (value) => {
            linkRef = value;
            routeParams = {};
            rebuildRouteParamFields();
            onChange();
        },
    });

    const routeParamsMount = document.createElement('div');
    routeParamsMount.className = 'voodbuilder-editor-form-route-params';
    routeParamsMount.setAttribute('data-voodbuilder-route-params', '');

    const targetField = createSelectField({
        label: labels.buttonLinkTarget ?? 'Open in',
        name: names.target,
        value: initial.target,
        options: [
            { value: '', label: labels.buttonLinkSameTab ?? 'Same tab' },
            { value: '_blank', label: labels.buttonLinkNewTab ?? 'New tab' },
        ],
        onChange: () => {
            onChange();
        },
    });

    function rebuildRouteParamFields() {
        routeParamsMount.replaceChildren();

        if (linkType !== 'route' || ! linkRef) {
            return;
        }

        const entry = (targets.routes ?? []).find((item) => String(item.id) === String(linkRef));

        for (const paramName of entry?.requiredParams ?? []) {
            const { field, input } = createTextField({
                label: paramName,
                name: `${names.routeParamPrefix}${paramName}`,
                value: String(routeParams[paramName] ?? ''),
                placeholder: paramName,
            });

            const update = () => {
                routeParams = { ...routeParams, [paramName]: input.value };
                onChange();
            };

            input.addEventListener('input', update);
            input.addEventListener('change', update);
            routeParamsMount.appendChild(field);
        }

        enhanceSelects(routeParamsMount);
    }

    function syncVisibility() {
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
        mailField.hidden = linkType !== 'mail';
        subjectField.hidden = linkType !== 'mail';
        routeField.hidden = linkType !== 'route';
        routeParamsMount.hidden = linkType !== 'route';
        targetField.hidden = linkType === 'mail' || linkType === 'none';
        rebuildRouteParamFields();
    }

    const selectValue = (field) => String(field.querySelector('select')?.value || '');

    /**
     * @returns {LinkTargetState}
     */
    function read() {
        const mailSubject = String(subjectInput.value || '').trim();
        let href = String(urlInput.value || '').trim() || emptyHref;

        if (linkType === 'page') {
            linkRef = selectValue(pageField);
        } else if (linkType === 'menu') {
            linkRef = selectValue(menuField);
        } else if (linkType === 'mail') {
            linkRef = String(mailInput.value || '').trim();
            href = buildMailtoHref(linkRef, mailSubject);
        } else if (linkType === 'route') {
            linkRef = selectValue(routeField);
        } else {
            linkRef = '';
        }

        return {
            linkType,
            linkRef,
            href,
            target: linkType === 'mail' || linkType === 'none' ? '' : selectValue(targetField),
            mailSubject,
            routeParams,
        };
    }

    /**
     * Field holding the reference for page / menu / route types (focus on validation).
     *
     * @param {string} type
     * @returns {HTMLElement|null}
     */
    function referenceField(type) {
        return { page: pageField, menu: menuField, route: routeField }[type] ?? null;
    }

    return {
        fields: [typeField, urlField, pageField, menuField, mailField, subjectField, routeField, routeParamsMount, targetField],
        urlInput,
        mailInput,
        subjectInput,
        syncVisibility,
        read,
        referenceField,
        linkType: () => linkType,
    };
}
