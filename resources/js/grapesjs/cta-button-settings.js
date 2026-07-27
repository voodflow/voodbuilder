/**
 * Content-panel settings for smart CTA buttons (label + link).
 * UI matches nav/footer settings (createFormSection / editor-form-ui).
 */

import {
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import { extractButtonLabel, persistCtaLabel } from './grapesjs-button-link.js';

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

function resolveHref(editor, linkType, linkRef, href) {
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };

    if (linkType === 'page') {
        return (targets.pages ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    if (linkType === 'menu') {
        return (targets.menuItems ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    return String(href ?? '#').trim() || '#';
}

function applyLinkToComponent(component, editor, { label, linkType, linkRef, href, target }) {
    const resolvedHref = resolveHref(editor, linkType, linkRef, href);

    runWithSettingsChangeGuard(editor, () => {
        component.set({
            ctaLabel: label,
            linkType,
            linkRef: linkType === 'url' ? '' : linkRef,
            href: resolvedHref,
            target: target || '',
        });

        component.addAttributes({
            href: resolvedHref,
            target: target || null,
            rel: target === '_blank' ? 'noopener noreferrer' : null,
            role: 'button',
            'data-voodbuilder-cta': 'true',
            'data-voodbuilder-cta-label': label,
            'data-vb-link-type': linkType,
            'data-vb-link': linkType === 'url' ? null : (linkRef || null),
        });

        persistCtaLabel(component, label);
    });
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

    const targets = editor.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };
    const attrs = component.getAttributes?.() ?? {};
    let linkType = String(component.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url') || 'url';
    let linkRef = String(component.get('linkRef') ?? attrs['data-vb-link'] ?? '');
    let href = String(component.get('href') ?? attrs.href ?? '#');
    let target = String(component.get('target') ?? attrs.target ?? '');
    let label = extractButtonLabel(component);

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
        options: [
            { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
            { value: 'page', label: labels.buttonLinkTypePage ?? 'Site page' },
            { value: 'menu', label: labels.buttonLinkTypeMenu ?? 'Menu item' },
        ],
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

    fields.append(labelField, typeField, urlField, pageField, menuField, targetField);

    const bindKey = String(attrs['data-voodbuilder-bind'] ?? '').trim();
    const bindHref = String(attrs['data-voodbuilder-bind-href'] ?? '').trim();

    if (bindKey || bindHref) {
        const hint = document.createElement('p');
        hint.className = 'voodbuilder-gjs-form-hint';
        hint.textContent = labels.buttonDynamicHint
            ?? 'Dynamic data is active on this button. Text fields bind the label; URL fields bind the link. Clear Dynamic to edit only static values.';
        fields.appendChild(hint);
    }

    mount.appendChild(section);

    const syncVisibility = () => {
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
    };

    const commit = () => {
        label = String(labelInput.value || 'Button').trim() || 'Button';
        href = String(urlInput.value || '#').trim() || '#';
        linkRef = linkType === 'page'
            ? String(pageField.querySelector('select')?.value || '')
            : linkType === 'menu'
                ? String(menuField.querySelector('select')?.value || '')
                : '';
        target = String(targetField.querySelector('select')?.value || '');

        applyLinkToComponent(component, editor, {
            label,
            linkType,
            linkRef,
            href,
            target,
        });
    };

    labelInput.addEventListener('input', commit);
    labelInput.addEventListener('change', commit);
    urlInput.addEventListener('input', commit);
    urlInput.addEventListener('change', commit);

    syncVisibility();

    return true;
}

export { isCtaButtonComponent, componentKey };
