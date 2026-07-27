/**
 * Content-panel settings for Basic elements: Icon, Text link, Divider.
 */

import {
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import { listTablerIconNames, resolveTablerIconName, tablerIconSvg } from './tabler-icons-catalog.js';

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

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

export function isIconComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-icon') {
        return true;
    }

    return component.getAttributes?.()?.['data-voodbuilder-icon'] != null;
}

export function isTextLinkComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-text-link') {
        return true;
    }

    return (component.getClasses?.() ?? []).includes('vb-text-link');
}

export function isDividerComponent(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return attrs['data-voodbuilder-divider'] != null
        || (component.getClasses?.() ?? []).includes('vb-divider')
        || String(component.get('tagName') ?? '').toLowerCase() === 'hr';
}

const ICON_SIZES = [
    { value: 'size-6', label: 'S (24px)' },
    { value: 'size-8', label: 'M (32px)' },
    { value: 'size-10', label: 'L (40px)' },
    { value: 'size-12', label: 'XL (48px)' },
    { value: 'size-16', label: '2XL (64px)' },
];

const DIVIDER_COLORS = [
    { value: 'border-vp-divider', label: 'Default' },
    { value: 'border-vp-text-3', label: 'Muted' },
    { value: 'border-vp-brand-1', label: 'Brand' },
    { value: 'border-slate-300', label: 'Slate' },
    { value: 'border-blue-300', label: 'Blue' },
    { value: 'border-emerald-300', label: 'Green' },
    { value: 'border-rose-300', label: 'Rose' },
    { value: 'border-amber-300', label: 'Amber' },
];

function readIconSize(component) {
    const classes = component.getClasses?.() ?? [];
    const found = ICON_SIZES.find((item) => classes.includes(item.value));

    return found?.value ?? 'size-10';
}

function applyIconToComponent(component, { name, sizeClass, href, linkType }) {
    const iconName = resolveTablerIconName(name);
    const size = ICON_SIZES.some((item) => item.value === sizeClass) ? sizeClass : 'size-10';
    const attrs = component.getAttributes?.() ?? {};

    if (
        attrs['data-vb-icon'] === iconName
        && attrs['data-vb-icon-size'] === size
        && String(attrs['data-vb-link-type'] ?? 'none') === String(linkType || 'none')
        && component.__vbIconSynced
    ) {
        return;
    }

    const classes = [...(component.getClasses?.() ?? [])]
        .filter((token) => ! String(token).startsWith('size-'));

    if (! classes.includes('inline-flex')) {
        classes.push('inline-flex', 'items-center', 'justify-center', 'vb-icon-link');
    }

    classes.push(size);
    component.setClass(classes);
    component.addAttributes({
        'data-voodbuilder-icon': '',
        'data-vb-icon': iconName,
        'data-vb-icon-size': size,
        'data-vb-link-type': linkType || 'none',
        href: linkType === 'none' ? null : (String(href ?? '').trim() || '#'),
    });
    component.components(tablerIconSvg(iconName, { sizeClass: size }));
    component.__vbIconSynced = true;
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

function readTextLinkLabel(component) {
    return String(component.get('content') ?? '')
        || [...(component.components?.() ?? [])].map((child) => String(child.get?.('content') ?? '')).join('')
        || 'Text link';
}

function applyTextLinkToComponent(component, editor, { label, linkType, linkRef, href, target }) {
    const resolvedHref = resolveHref(editor, linkType, linkRef, href);
    const nextLabel = String(label ?? '').trim() || 'Text link';

    runWithSettingsChangeGuard(editor, () => {
        component.set({
            href: resolvedHref,
            target: target || '',
            'data-vb-link-type': linkType,
            linkType,
            linkRef: linkType === 'url' ? '' : linkRef,
        });

        component.addAttributes({
            href: resolvedHref,
            target: target || null,
            rel: target === '_blank' ? 'noopener noreferrer' : null,
            'data-vb-link-type': linkType,
            'data-vb-link': linkType === 'url' ? null : (linkRef || null),
        });

        const children = [...(component.components?.() ?? [])];
        const textNode = children.find((child) => child?.get?.('type') === 'textnode');

        if (textNode) {
            textNode.set('content', nextLabel);
        } else {
            component.components(nextLabel);
        }
    });
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderIconSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-icon-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    let iconName = resolveTablerIconName(attrs['data-vb-icon'] ?? 'star');
    let sizeClass = attrs['data-vb-icon-size'] || readIconSize(component);
    let linkType = String(attrs['data-vb-link-type'] ?? 'none') || 'none';
    let href = String(attrs.href ?? component.get('href') ?? '');

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.iconSettingsTitle ?? 'Icon settings');
    section.setAttribute('data-voodbuilder-icon-settings', '');
    section.setAttribute('data-component-key', key);

    const iconField = createSelectField({
        label: labels.iconName ?? 'Icon (Tabler)',
        name: 'iconName',
        value: iconName,
        options: listTablerIconNames().map((name) => ({
            value: name,
            label: name.replace(/-/g, ' '),
        })),
        onChange: (value) => {
            iconName = value;
            commit();
        },
    });

    const preview = document.createElement('div');
    preview.className = 'voodbuilder-gjs-icon-preview';
    preview.innerHTML = tablerIconSvg(iconName, { sizeClass: 'size-8' });

    const sizeField = createSelectField({
        label: labels.iconSize ?? 'Size',
        name: 'iconSize',
        value: sizeClass,
        options: ICON_SIZES,
        onChange: (value) => {
            sizeClass = value;
            commit();
        },
    });

    const typeField = createSelectField({
        label: labels.iconLinkType ?? 'Link',
        name: 'iconLinkType',
        value: linkType,
        options: [
            { value: 'none', label: labels.iconLinkNone ?? 'No link' },
            { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
        ],
        onChange: (value) => {
            linkType = value;
            urlField.hidden = linkType !== 'url';
            commit();
        },
    });

    const { field: urlField, input: urlInput } = createTextField({
        label: labels.buttonLinkUrl ?? 'Link URL',
        name: 'iconHref',
        value: href,
        placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });
    urlField.hidden = linkType !== 'url';

    const commit = () => {
        runWithSettingsChangeGuard(editor, () => {
            component.__vbIconSynced = false;
            applyIconToComponent(component, {
                name: iconName,
                sizeClass,
                href: urlInput.value,
                linkType,
            });
            preview.innerHTML = tablerIconSvg(iconName, { sizeClass: 'size-8' });
        });
    };

    urlInput.addEventListener('change', commit);
    urlInput.addEventListener('blur', commit);

    fields.append(iconField, preview, sizeField, typeField, urlField);
    mount.appendChild(section);
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

/**
 * Text link Content settings — same link controls as Button (type / URL / page / menu / target).
 *
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderTextLinkSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-text-link-settings]');

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
    let label = readTextLinkLabel(component);

    if (linkType === 'none') {
        linkType = 'url';
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.textLinkSettingsTitle ?? 'Text link');
    section.setAttribute('data-voodbuilder-text-link-settings', '');
    section.setAttribute('data-component-key', key);

    const { field: labelField, input: labelInput } = createTextField({
        label: labels.textLinkLabel ?? 'Label',
        name: 'textLinkLabel',
        value: label,
    });

    const typeField = createSelectField({
        label: labels.buttonLinkType ?? 'Link type',
        name: 'textLinkType',
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
        name: 'textLinkHref',
        value: href === '#' ? '' : href,
        placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });

    const pageField = createSelectField({
        label: labels.buttonLinkPage ?? 'Page',
        name: 'textLinkRefPage',
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
        name: 'textLinkRefMenu',
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
        name: 'textLinkTarget',
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
    mount.appendChild(section);

    const syncVisibility = () => {
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
    };

    const commit = () => {
        label = String(labelInput.value || 'Text link').trim() || 'Text link';
        href = String(urlInput.value || '#').trim() || '#';
        linkRef = linkType === 'page'
            ? String(pageField.querySelector('select')?.value || '')
            : linkType === 'menu'
                ? String(menuField.querySelector('select')?.value || '')
                : '';
        target = String(targetField.querySelector('select')?.value || '');

        applyTextLinkToComponent(component, editor, {
            label,
            linkType,
            linkRef,
            href,
            target,
        });
    };

    labelInput.addEventListener('change', commit);
    labelInput.addEventListener('blur', commit);
    urlInput.addEventListener('change', commit);
    urlInput.addEventListener('blur', commit);

    syncVisibility();
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderDividerSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-divider-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const classes = component.getClasses?.() ?? [];
    let color = DIVIDER_COLORS.find((item) => classes.includes(item.value))?.value ?? 'border-vp-divider';

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.dividerSettingsTitle ?? 'Divider');
    section.setAttribute('data-voodbuilder-divider-settings', '');
    section.setAttribute('data-component-key', key);

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-gjs-form-hint';
    hint.textContent = labels.dividerColorHint
        ?? 'Use border-* classes (not divide-*). Divide utilities style gaps between children, not the line itself.';

    const colorField = createSelectField({
        label: labels.dividerColor ?? 'Color',
        name: 'dividerColor',
        value: color,
        options: DIVIDER_COLORS,
        onChange: (value) => {
            color = value;
            runWithSettingsChangeGuard(editor, () => {
                const next = [...(component.getClasses?.() ?? [])]
                    .filter((token) => ! String(token).startsWith('border-') || token === 'border-0' || token === 'border-t');
                const base = ['vb-divider', 'my-6', 'w-full', 'border-0', 'border-t'];

                for (const token of base) {
                    if (! next.includes(token)) {
                        next.push(token);
                    }
                }

                next.push(color);
                component.setClass(next);
                component.addAttributes({ 'data-voodbuilder-divider': '', 'data-vb-divider-color': color });
            });
        },
    });

    fields.append(hint, colorField);
    mount.appendChild(section);
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

export { applyIconToComponent, ICON_SIZES };
