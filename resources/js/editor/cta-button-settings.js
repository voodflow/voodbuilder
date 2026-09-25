/**
 * Content-panel settings for smart CTA buttons (label + link).
 * UI matches nav/footer settings (createFormSection / editor-form-ui).
 */

import { createFormSection, createTextField } from './editor-form-ui.js';
import { createLinkTargetFields } from './link-target-fields.js';
import { extractButtonLabel, persistCtaLabel } from './editor-button-link.js';
import {
    MAIL_SUBJECT_ATTR,
    ROUTE_PARAMS_ATTR,
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
    const linkType = String(component.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url') || 'url';
    let linkRef = String(component.get('linkRef') ?? attrs['data-vb-link'] ?? '');
    const href = String(component.get('href') ?? attrs.href ?? '#');
    const target = String(component.get('target') ?? attrs.target ?? '');
    let label = extractButtonLabel(component);
    let mailSubject = String(component.get('mailSubject') ?? attrs[MAIL_SUBJECT_ATTR] ?? '');
    const routeParams = readRouteParamsFromAttrs(attrs);

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

    const linkFields = createLinkTargetFields({
        labels,
        targets,
        names: {
            type: 'linkType',
            href: 'href',
            page: 'linkRefPage',
            menu: 'linkRefMenu',
            mail: 'linkMail',
            subject: 'linkMailSubject',
            route: 'linkRefRoute',
            target: 'target',
            routeParamPrefix: 'routeParam_',
        },
        initial: { linkType, linkRef, href, target, mailSubject, routeParams },
        emptyHref: '#',
        onChange: () => commit(),
        enhanceSelects: (root) => editor.__voodbuilderEnhanceInspectorSelects?.(root),
    });
    const { urlInput, mailInput, subjectInput, syncVisibility } = linkFields;

    fields.append(labelField, ...linkFields.fields);

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

    const commit = () => {
        label = String(labelInput.value || 'Button').trim() || 'Button';

        applyLinkToComponent(component, editor, {
            label,
            ...linkFields.read(),
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
