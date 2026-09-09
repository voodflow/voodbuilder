/**
 * Social share utility — fixed network URLs, specialized inspector settings.
 */

import {
    createCheckboxField,
    createCheckboxGrid,
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';

export const SOCIAL_SHARE_NETWORKS = [
    {
        key: 'facebook',
        label: 'Facebook',
        icon: 'brand-facebook',
        brand: '#1877F2',
        paths: '<path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3"/>',
    },
    {
        key: 'x',
        label: 'X',
        icon: 'brand-x',
        brand: '#000000',
        paths: '<path d="M4 4l11.733 16h4.267l-11.733 -16l-4.267 0"/><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/>',
    },
    {
        key: 'linkedin',
        label: 'LinkedIn',
        icon: 'brand-linkedin',
        brand: '#0A66C2',
        paths: '<path d="M8 11v5"/><path d="M8 8v.01"/><path d="M12 16v-5"/><path d="M16 16v-3a2 2 0 1 0 -4 0"/><path d="M3 7a4 4 0 0 1 4 -4h10a4 4 0 0 1 4 4v10a4 4 0 0 1 -4 4h-10a4 4 0 0 1 -4 -4l0 -10"/>',
    },
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        icon: 'brand-whatsapp',
        brand: '#25D366',
        paths: '<path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"/><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1"/>',
    },
    {
        key: 'email',
        label: 'Email',
        icon: 'mail',
        brand: '#64748b',
        paths: '<path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10"/><path d="M3 7l9 6l9 -6"/>',
    },
    {
        key: 'copy_link',
        label: 'Copy link',
        icon: 'link',
        brand: '#64748b',
        paths: '<path d="M9 15l6 -6"/><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464"/><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463"/>',
    },
];

export const SOCIAL_SHARE_DEFAULT_NETWORKS = SOCIAL_SHARE_NETWORKS.map((item) => item.key);

export const SOCIAL_SHARE_SHAPE_OPTIONS = [
    { value: 'square', label: 'Square' },
    { value: 'round', label: 'Round' },
];

export const SOCIAL_SHARE_COLOR_OPTIONS = [
    { value: 'social', label: 'Social brand colors' },
    { value: 'transparent', label: 'Transparent' },
    { value: 'custom', label: 'Custom color' },
];

export const SOCIAL_SHARE_CONTENT_OPTIONS = [
    { value: 'icon-text', label: 'Icon + text' },
    { value: 'icon', label: 'Icon only' },
    { value: 'text', label: 'Text only' },
];

const ATTR = {
    networks: 'data-vb-share-networks',
    shape: 'data-vb-share-shape',
    color: 'data-vb-share-color',
    customColor: 'data-vb-share-custom-color',
    content: 'data-vb-share-content',
    /** Optional share title → email subject, X/WhatsApp text. Empty = page title. */
    shareTitle: 'data-share-title',
    /** Optional email-only subject override. Empty = share title / page title. */
    emailSubject: 'data-vb-share-email-subject',
};

function runWithSettingsChangeGuard(editor, callback) {
    if (typeof callback !== 'function') {
        return;
    }

    if (! editor) {
        callback();

        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const next = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;

        if (next <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        } else {
            editor.__voodbuilderSettingsChangeDepth = next;
        }
    }
}

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isSocialShareComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-social-share') {
        return true;
    }

    return Object.prototype.hasOwnProperty.call(component.getAttributes?.() ?? {}, 'data-voodbuilder-social-share');
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isSocialShareItemComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-social-share-item') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};

    return attrs['data-network'] != null
        && (attrs['data-voodbuilder-skip-cta'] === 'true' || attrs['data-vb-share-item'] === 'true');
}

/**
 * Walk up from a share button to the social-share root.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findSocialShareHost(component) {
    let current = component;

    while (current) {
        if (isSocialShareComponent(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {string|null|undefined} raw
 * @returns {string[]}
 */
export function parseSocialShareNetworks(raw) {
    const parts = String(raw ?? '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
    const known = new Set(SOCIAL_SHARE_DEFAULT_NETWORKS);
    const unique = [];

    for (const key of parts) {
        if (known.has(key) && ! unique.includes(key)) {
            unique.push(key);
        }
    }

    return unique;
}

/**
 * @param {object} component
 * @returns {string[]}
 */
export function readSocialShareNetworks(component) {
    const attrs = component.getAttributes?.() ?? {};
    const fromAttr = parseSocialShareNetworks(attrs[ATTR.networks]);

    if (fromAttr.length > 0) {
        return fromAttr;
    }

    const fromChildren = [];

    for (const child of component.components?.()?.models ?? component.components?.() ?? []) {
        const network = child?.getAttributes?.()?.['data-network'];

        if (typeof network === 'string' && SOCIAL_SHARE_DEFAULT_NETWORKS.includes(network) && ! fromChildren.includes(network)) {
            fromChildren.push(network);
        }
    }

    return fromChildren.length > 0 ? fromChildren : [...SOCIAL_SHARE_DEFAULT_NETWORKS];
}

/**
 * @param {object} component
 * @returns {{ networks: string[], shape: string, color: string, customColor: string, content: string, shareTitle: string, emailSubject: string }}
 */
export function readSocialShareOptions(component) {
    const attrs = component.getAttributes?.() ?? {};
    let shape = String(attrs[ATTR.shape] || 'square');
    let color = String(attrs[ATTR.color] || 'social');
    let content = String(attrs[ATTR.content] || 'icon-text');
    let customColor = String(attrs[ATTR.customColor] || '#1877F2').trim() || '#1877F2';
    const shareTitle = String(attrs[ATTR.shareTitle] ?? attrs['data-share-title'] ?? '').trim();
    const emailSubject = String(attrs[ATTR.emailSubject] ?? '').trim();

    if (! SOCIAL_SHARE_SHAPE_OPTIONS.some((item) => item.value === shape)) {
        shape = 'square';
    }

    if (! SOCIAL_SHARE_COLOR_OPTIONS.some((item) => item.value === color)) {
        color = 'social';
    }

    if (! SOCIAL_SHARE_CONTENT_OPTIONS.some((item) => item.value === content)) {
        content = 'icon-text';
    }

    if (! /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(customColor) && ! customColor.startsWith('var(')) {
        customColor = '#1877F2';
    }

    return {
        networks: readSocialShareNetworks(component),
        shape,
        color,
        customColor,
        content,
        shareTitle,
        emailSubject,
    };
}

/**
 * @param {string} paths
 * @param {string} name
 * @returns {string}
 */
function iconSvgMarkup(paths, name) {
    return (
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"`
        + ` stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"`
        + ` class="vb-social-share__glyph" data-vb-icon-glyph="${name}">${paths}</svg>`
    );
}

/**
 * @param {{ key: string, label: string, icon: string, paths: string }} network
 * @returns {object}
 */
export function buildSocialShareItemDefinition(network) {
    const isCopy = network.key === 'copy_link';
    // Always <a>: GrapesJS `button` defaults inject the literal text "Button".
    const iconHtml = (
        `<span class="vb-social-share__icon" aria-hidden="true">${iconSvgMarkup(network.paths, network.icon)}</span>`
        + `<span class="vb-social-share__label" data-vb-share-label>${network.label}</span>`
    );

    return {
        type: 'voodbuilder-social-share-item',
        tagName: 'a',
        name: network.label,
        classes: ['vb-social-share__btn', 'inline-flex', 'items-center', 'justify-center', 'gap-2', 'text-sm', 'font-medium', 'transition'],
        droppable: false,
        editable: false,
        selectable: false,
        hoverable: false,
        highlightable: false,
        layerable: false,
        draggable: false,
        copyable: false,
        removable: false,
        attributes: {
            'data-network': network.key,
            'data-vb-share-item': 'true',
            'data-voodbuilder-skip-cta': 'true',
            'aria-label': network.label,
            href: '#',
            ...(isCopy
                ? { role: 'button', 'data-copy-url': '', 'data-copied-label': 'Copied' }
                : { target: '_blank', rel: 'noopener noreferrer' }),
        },
        components: iconHtml,
    };
}

/**
 * @param {string[]} networks
 * @returns {object[]}
 */
export function buildSocialShareItemDefinitions(networks = SOCIAL_SHARE_DEFAULT_NETWORKS) {
    const enabled = new Set(networks.length > 0 ? networks : SOCIAL_SHARE_DEFAULT_NETWORKS);

    return SOCIAL_SHARE_NETWORKS
        .filter((network) => enabled.has(network.key))
        .map((network) => buildSocialShareItemDefinition(network));
}

/**
 * Persist options on the root and rebuild child buttons.
 *
 * @param {object} component
 * @param {Partial<{ networks: string[], shape: string, color: string, customColor: string, content: string, shareTitle: string, emailSubject: string }>} [patch]
 */
export function applySocialShareOptions(component, patch = {}) {
    if (! isSocialShareComponent(component)) {
        return;
    }

    const current = readSocialShareOptions(component);
    const next = {
        networks: patch.networks ?? current.networks,
        shape: patch.shape ?? current.shape,
        color: patch.color ?? current.color,
        customColor: patch.customColor ?? current.customColor,
        content: patch.content ?? current.content,
        shareTitle: patch.shareTitle !== undefined ? String(patch.shareTitle ?? '').trim() : current.shareTitle,
        emailSubject: patch.emailSubject !== undefined ? String(patch.emailSubject ?? '').trim() : current.emailSubject,
    };

    if (next.networks.length === 0) {
        next.networks = [...SOCIAL_SHARE_DEFAULT_NETWORKS];
    }

    const attrs = {
        [ATTR.networks]: next.networks.join(','),
        [ATTR.shape]: next.shape,
        [ATTR.color]: next.color,
        [ATTR.customColor]: next.customColor,
        [ATTR.content]: next.content,
        [ATTR.shareTitle]: next.shareTitle,
        [ATTR.emailSubject]: next.emailSubject,
    };

    if (next.color === 'custom') {
        attrs.style = `--vb-share-custom-color: ${next.customColor}`;
    }

    component.addAttributes(attrs);

    if (next.color !== 'custom') {
        const el = component.getEl?.();

        if (el?.style) {
            el.style.removeProperty('--vb-share-custom-color');
        }

        const currentStyle = String(component.getAttributes?.()?.style ?? '');

        if (currentStyle.includes('--vb-share-custom-color')) {
            const cleaned = currentStyle
                .split(';')
                .map((part) => part.trim())
                .filter((part) => part && ! part.startsWith('--vb-share-custom-color'))
                .join('; ');

            if (cleaned) {
                component.addAttributes({ style: cleaned });
            } else {
                component.removeAttributes?.('style');
            }
        }
    }

    const networksChanged = patch.networks !== undefined
        && patch.networks.join(',') !== current.networks.join(',');

    if (networksChanged) {
        rebuildSocialShareItems(component, next.networks);
    }
}

/**
 * @param {object} component
 * @param {string[]} [networks]
 */
export function rebuildSocialShareItems(component, networks) {
    if (! component?.components) {
        return;
    }

    const enabled = networks ?? readSocialShareNetworks(component);
    const definitions = buildSocialShareItemDefinitions(enabled);

    component.components().reset(definitions);
}

/**
 * Ensure defaults exist (migration for older blocks).
 *
 * @param {object} component
 */
export function ensureSocialShareDefaults(component) {
    if (! isSocialShareComponent(component)) {
        return;
    }

    const attrs = component.getAttributes?.() ?? {};
    const options = readSocialShareOptions(component);
    const needsAttrWrite = ! attrs[ATTR.networks]
        || ! attrs[ATTR.shape]
        || ! attrs[ATTR.color]
        || ! attrs[ATTR.content];

    if (needsAttrWrite) {
        component.addAttributes({
            [ATTR.networks]: options.networks.join(','),
            [ATTR.shape]: options.shape,
            [ATTR.color]: options.color,
            [ATTR.customColor]: options.customColor,
            [ATTR.content]: options.content,
        });
    }

    const children = component.components?.() ?? [];
    const childModels = children.models ?? children;
    const needsRebuild = childModels.length === 0
        || [...childModels].some((child) => {
            const childAttrs = child?.getAttributes?.() ?? {};
            const type = child?.get?.('type');
            const tag = String(child?.get?.('tagName') ?? '').toLowerCase();

            return type === 'link'
                || type === 'button'
                || tag === 'button'
                || childAttrs['data-voodbuilder-skip-cta'] !== 'true'
                || childAttrs['data-vb-share-item'] !== 'true';
        });

    if (needsRebuild) {
        rebuildSocialShareItems(component, options.networks);
    }

    if (options.color === 'custom') {
        component.addAttributes({
            style: `--vb-share-custom-color: ${options.customColor}`,
        });
    }
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderSocialShareSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const host = findSocialShareHost(component) ?? (isSocialShareComponent(component) ? component : null);

    if (! host) {
        return false;
    }

    const key = componentKey(host);
    const existing = mount.querySelector('[data-voodbuilder-social-share-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    let options = readSocialShareOptions(host);

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.socialShareSettingsTitle ?? 'Social share');
    section.setAttribute('data-voodbuilder-social-share-settings', '');
    section.setAttribute('data-component-key', key);

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.textContent = labels.socialShareSettingsHint
        ?? 'Share URLs are fixed per network (current page). Enable networks and choose appearance — buttons are not editable links.';

    const networkFields = SOCIAL_SHARE_NETWORKS.map((network) => createCheckboxField({
        label: network.label,
        name: `shareNetwork_${network.key}`,
        checked: options.networks.includes(network.key),
        onChange: (checked) => {
            const set = new Set(options.networks);

            if (checked) {
                set.add(network.key);
            } else {
                set.delete(network.key);
            }

            // Keep catalog order
            options = {
                ...options,
                networks: SOCIAL_SHARE_DEFAULT_NETWORKS.filter((keyName) => set.has(keyName)),
            };

            if (options.networks.length === 0) {
                options.networks = [...SOCIAL_SHARE_DEFAULT_NETWORKS];
            }

            runWithSettingsChangeGuard(editor, () => {
                applySocialShareOptions(host, { networks: options.networks });
            });

            // Refresh checkboxes if we re-enabled all after empty
            section.querySelectorAll('input[type="checkbox"][name^="shareNetwork_"]').forEach((input) => {
                const keyName = String(input.name || '').replace('shareNetwork_', '');
                input.checked = options.networks.includes(keyName);
            });
        },
    }));

    const networksLabel = document.createElement('div');
    networksLabel.className = 'voodbuilder-editor-form-label';
    networksLabel.textContent = labels.socialShareNetworks ?? 'Networks';

    const shapeField = createSelectField({
        label: labels.socialShareShape ?? 'Shape',
        name: 'shareShape',
        value: options.shape,
        options: SOCIAL_SHARE_SHAPE_OPTIONS,
        onChange: (value) => {
            options = { ...options, shape: value };
            runWithSettingsChangeGuard(editor, () => {
                applySocialShareOptions(host, { shape: value });
            });
        },
    });

    const colorField = createSelectField({
        label: labels.socialShareColor ?? 'Color',
        name: 'shareColor',
        value: options.color,
        options: SOCIAL_SHARE_COLOR_OPTIONS,
        onChange: (value) => {
            options = { ...options, color: value };
            runWithSettingsChangeGuard(editor, () => {
                applySocialShareOptions(host, { color: value });
            });
            customColorField.hidden = value !== 'custom';
        },
    });

    const { field: customColorField, input: customInput } = createTextField({
        label: labels.socialShareCustomColor ?? 'Custom color',
        name: 'shareCustomColor',
        value: options.customColor,
        type: 'color',
    });
    customColorField.hidden = options.color !== 'custom';

    customInput?.addEventListener('input', () => {
        const value = String(customInput.value || '').trim() || '#1877F2';
        options = { ...options, customColor: value };
        runWithSettingsChangeGuard(editor, () => {
            applySocialShareOptions(host, { customColor: value, color: 'custom' });
        });
    });

    const contentField = createSelectField({
        label: labels.socialShareContent ?? 'Content',
        name: 'shareContent',
        value: options.content,
        options: SOCIAL_SHARE_CONTENT_OPTIONS,
        onChange: (value) => {
            options = { ...options, content: value };
            runWithSettingsChangeGuard(editor, () => {
                applySocialShareOptions(host, { content: value });
            });
        },
    });

    const { field: shareTitleField, input: shareTitleInput } = createTextField({
        label: labels.socialShareTitle ?? 'Share title',
        name: 'shareTitle',
        value: options.shareTitle,
        placeholder: labels.socialShareTitlePlaceholder ?? 'Defaults to page title',
    });

    shareTitleInput?.addEventListener('change', () => {
        const value = String(shareTitleInput.value || '').trim();
        options = { ...options, shareTitle: value };
        runWithSettingsChangeGuard(editor, () => {
            applySocialShareOptions(host, { shareTitle: value });
        });
    });

    const { field: emailSubjectField, input: emailSubjectInput } = createTextField({
        label: labels.socialShareEmailSubject ?? 'Email subject',
        name: 'shareEmailSubject',
        value: options.emailSubject,
        placeholder: labels.socialShareEmailSubjectPlaceholder ?? 'Defaults to share title / page title',
    });

    emailSubjectInput?.addEventListener('change', () => {
        const value = String(emailSubjectInput.value || '').trim();
        options = { ...options, emailSubject: value };
        runWithSettingsChangeGuard(editor, () => {
            applySocialShareOptions(host, { emailSubject: value });
        });
    });

    fields.append(
        hint,
        networksLabel,
        createCheckboxGrid(networkFields),
        shareTitleField,
        emailSubjectField,
        shapeField,
        colorField,
        customColorField,
        contentField,
    );
    mount.appendChild(section);
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);
    ensureSocialShareDefaults(host);

    return true;
}
