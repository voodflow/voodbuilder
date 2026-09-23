/**
 * Content-panel settings for smart CTA buttons (label + link).
 * UI matches nav/footer settings (createFormSection / editor-form-ui).
 */

import {
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import { extractButtonLabel, persistCtaLabel } from './editor-button-link.js';
import {
    MAIL_SUBJECT_ATTR,
    ROUTE_PARAMS_ATTR,
    buildMailtoHref,
    linkTypeSelectOptions,
    parseMailtoHref,
    readRouteParamsFromAttrs,
    resolveAppRouteHref,
    resolveEditorLinkHref,
    serializeRouteParams,
} from './editor-link-resolve.js';

function runWithSettingsChangeGuard(editor, callback) {
    if (! editor || typeof callback !== 'function') {
        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

function isCtaButtonComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-cta-button') {
        return true;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};

    return tag === 'a' && attrs['data-voodbuilder-cta'] === 'true';
}

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

function applyLinkToComponent(component, editor, {
    label,
    linkType,
    linkRef,
    href,
    target,
    mailSubject = '',
    routeParams = {},
}) {
    const resolvedHref = resolveEditorLinkHref(editor, linkType, linkRef, href, {
        mailSubject,
        routeParams,
    });

    runWithSettingsChangeGuard(editor, () => {
        component.set({
            ctaLabel: label,
            linkType,
            linkRef: linkType === 'url' ? '' : linkRef,
            href: resolvedHref,
            target: linkType === 'mail' ? '' : (target || ''),
            mailSubject: linkType === 'mail' ? mailSubject : '',
        });

        const nextAttrs = {
            href: resolvedHref,
            target: linkType === 'mail' || ! target ? null : target,
            rel: linkType !== 'mail' && target === '_blank' ? 'noopener noreferrer' : null,
            role: 'button',
            'data-voodbuilder-cta': 'true',
            'data-voodbuilder-cta-label': label,
            'data-vb-link-type': linkType,
            'data-vb-link': linkType === 'url' ? null : (linkRef || null),
            [MAIL_SUBJECT_ATTR]: linkType === 'mail' && mailSubject ? mailSubject : null,
            [ROUTE_PARAMS_ATTR]: linkType === 'route' ? serializeRouteParams(routeParams) : null,
        };

        if (linkType === 'mail') {
            nextAttrs['data-vb-link'] = linkRef || null;
        }

        component.addAttributes(nextAttrs);
        persistCtaLabel(component, label);
    });

    if (linkType === 'route' && linkRef) {
        void resolveAppRouteHref(editor, linkRef, routeParams).then((url) => {
            if (! url || url === '#' || component.isRemoved?.()) {
                return;
            }

            runWithSettingsChangeGuard(editor, () => {
                component.set({ href: url });
                component.addAttributes({ href: url });
            });
        });
    }
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderCtaButtonSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-cta-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const targets = editor.__voodbuilderLinkTargets ?? { pages: [], menuItems: [], routes: [] };
    const attrs = component.getAttributes?.() ?? {};
    let linkType = String(component.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url') || 'url';
    let linkRef = String(component.get('linkRef') ?? attrs['data-vb-link'] ?? '');
    let href = String(component.get('href') ?? attrs.href ?? '#');
    let target = String(component.get('target') ?? attrs.target ?? '');
    let label = extractButtonLabel(component);
    let mailSubject = String(component.get('mailSubject') ?? attrs[MAIL_SUBJECT_ATTR] ?? '');
    let routeParams = readRouteParamsFromAttrs(attrs);

    if (linkType === 'mail') {
        const parsed = parseMailtoHref(href);

        if (! linkRef) {
            linkRef = parsed.email;
        }

        if (! mailSubject) {
            mailSubject = parsed.subject;
        }
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.buttonSettingsTitle ?? 'Button settings');
    section.setAttribute('data-voodbuilder-cta-settings', '');
    section.setAttribute('data-component-key', key);

    const { field: labelField, input: labelInput } = createTextField({
        label: labels.buttonLinkLabel ?? 'Button text',
        name: 'ctaLabel',
        value: label,
        placeholder: labels.buttonLinkLabel ?? 'Button',
    });

    const typeField = createSelectField({
        label: labels.buttonLinkType ?? 'Link type',
        name: 'linkType',
        value: linkType,
        options: linkTypeSelectOptions(labels),
        onChange: (value) => {
            linkType = value;
            syncVisibility();
            commit();
        },
    });

    const { field: urlField, input: urlInput } = createTextField({
        label: labels.buttonLinkUrl ?? 'Link URL',
        name: 'href',
        value: href,
        placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });

    const pageField = createSelectField({
        label: labels.buttonLinkPage ?? 'Page',
        name: 'linkRefPage',
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
            commit();
        },
    });

    const menuField = createSelectField({
        label: labels.buttonLinkMenu ?? 'Menu item',
        name: 'linkRefMenu',
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
            commit();
        },
    });

    const { field: mailField, input: mailInput } = createTextField({
        label: labels.buttonLinkMail ?? 'Email address',
        name: 'linkMail',
        value: linkType === 'mail' ? linkRef : '',
        placeholder: labels.buttonLinkMailPlaceholder ?? 'name@example.com',
    });

    const { field: subjectField, input: subjectInput } = createTextField({
        label: labels.buttonLinkMailSubject ?? 'Subject',
        name: 'linkMailSubject',
        value: mailSubject,
        placeholder: labels.buttonLinkMailSubjectPlaceholder ?? 'Optional subject',
    });

    const routeField = createSelectField({
        label: labels.buttonLinkRoute ?? 'App route',
        name: 'linkRefRoute',
        value: linkType === 'route' ? linkRef : '',
        options: [
            { value: '', label: '—' },
            ...(targets.routes ?? []).map((item) => ({
                value: String(item.id),
                label: item.label,
            })),
        ],
        onChange: (value) => {
            linkRef = value;
            routeParams = {};
            rebuildRouteParamFields();
            commit();
        },
    });

    const routeParamsMount = document.createElement('div');
    routeParamsMount.className = 'voodbuilder-editor-form-route-params';
    routeParamsMount.setAttribute('data-voodbuilder-route-params', '');

    const targetField = createSelectField({
        label: labels.buttonLinkTarget ?? 'Open in',
        name: 'target',
        value: target,
        options: [
            { value: '', label: labels.buttonLinkSameTab ?? 'Same tab' },
            { value: '_blank', label: labels.buttonLinkNewTab ?? 'New tab' },
        ],
        onChange: (value) => {
            target = value;
            commit();
        },
    });

    fields.append(
        labelField,
        typeField,
        urlField,
        pageField,
        menuField,
        mailField,
        subjectField,
        routeField,
        routeParamsMount,
        targetField,
    );

    const bindKey = String(attrs['data-voodbuilder-bind'] ?? '').trim();
    const bindHref = String(attrs['data-voodbuilder-bind-href'] ?? '').trim();

    if (bindKey || bindHref) {
        const hint = document.createElement('p');
        hint.className = 'voodbuilder-editor-form-hint';
        hint.textContent = labels.buttonDynamicHint
            ?? 'Dynamic data is active on this button. Text fields bind the label; URL fields bind the link. Clear Dynamic to edit only static values.';
        fields.appendChild(hint);
    }

    mount.appendChild(section);

    const rebuildRouteParamFields = () => {
        routeParamsMount.replaceChildren();

        if (linkType !== 'route' || ! linkRef) {
            return;
        }

        const entry = (targets.routes ?? []).find((item) => String(item.id) === String(linkRef));
        const required = entry?.requiredParams ?? [];

        for (const paramName of required) {
            const { field, input } = createTextField({
                label: paramName,
                name: `routeParam_${paramName}`,
                value: String(routeParams[paramName] ?? ''),
                placeholder: paramName,
            });

            input.addEventListener('input', () => {
                routeParams = { ...routeParams, [paramName]: input.value };
                commit();
            });
            input.addEventListener('change', () => {
                routeParams = { ...routeParams, [paramName]: input.value };
                commit();
            });

            routeParamsMount.appendChild(field);
        }

        editor.__voodbuilderEnhanceInspectorSelects?.(routeParamsMount);
    };

    const syncVisibility = () => {
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
        mailField.hidden = linkType !== 'mail';
        subjectField.hidden = linkType !== 'mail';
        routeField.hidden = linkType !== 'route';
        routeParamsMount.hidden = linkType !== 'route';
        targetField.hidden = linkType === 'mail';
        rebuildRouteParamFields();
    };

    const commit = () => {
        label = String(labelInput.value || 'Button').trim() || 'Button';
        href = String(urlInput.value || '#').trim() || '#';
        mailSubject = String(subjectInput.value || '').trim();

        if (linkType === 'page') {
            linkRef = String(pageField.querySelector('select')?.value || '');
        } else if (linkType === 'menu') {
            linkRef = String(menuField.querySelector('select')?.value || '');
        } else if (linkType === 'mail') {
            linkRef = String(mailInput.value || '').trim();
            href = buildMailtoHref(linkRef, mailSubject);
        } else if (linkType === 'route') {
            linkRef = String(routeField.querySelector('select')?.value || '');
        } else {
            linkRef = '';
        }

        target = linkType === 'mail'
            ? ''
            : String(targetField.querySelector('select')?.value || '');

        applyLinkToComponent(component, editor, {
            label,
            linkType,
            linkRef,
            href,
            target,
            mailSubject,
            routeParams,
        });
    };

    labelInput.addEventListener('input', commit);
    labelInput.addEventListener('change', commit);
    urlInput.addEventListener('input', commit);
    urlInput.addEventListener('change', commit);
    mailInput.addEventListener('input', commit);
    mailInput.addEventListener('change', commit);
    subjectInput.addEventListener('input', commit);
    subjectInput.addEventListener('change', commit);

    syncVisibility();
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

export { isCtaButtonComponent, componentKey };
