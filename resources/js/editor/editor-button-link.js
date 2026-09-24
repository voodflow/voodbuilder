/**
 * Link traits for CTA buttons in section blocks (URL + same/new tab).
 *
 * Labels are edited via traits (not inline RTE). Editor `editable: true` +
 * `change:content` can wipe child textnodes (updateContent sets innerHTML='')
 * and then getHtml/toHTML serializes empty <a data-voodbuilder-cta>…</a>.
 *
 * Persistence: keep label in `ctaLabel` prop, `data-voodbuilder-cta-label`
 * (survives data-gjs strip), and a single textnode child for HTML export.
 */

import { isInsideChromeShellPartComponent } from './chrome-content-slot-utils.js';
import { resolveEditorLinkHref as resolveSharedLinkHref } from './editor-link-resolve.js';

export const CTA_LABEL_ATTR = 'data-voodbuilder-cta-label';

/** Grapes forms / CTA defaults — never treat these as author copy on empty/icon buttons. */
const INVENTED_BUTTON_LABELS = new Set(['button', 'send']);

function componentChildModels(component) {
    const children = component?.components?.();

    return [...(children?.models ?? children ?? [])];
}

function isTextishChild(child) {
    const type = child?.get?.('type');

    return child?.is?.('textnode') || type === 'textnode' || type === 'text';
}

function buttonHasStructuralChildren(component) {
    return componentChildModels(component).some((child) => ! isTextishChild(child));
}

function readAuthorTextNodes(component) {
    return componentChildModels(component)
        .filter((child) => isTextishChild(child))
        .map((child) => String(child.get?.('content') ?? '').trim())
        .filter(Boolean)
        .join(' ')
        .trim();
}

/**
 * True for empty color swatches / icon-only controls that must not become CTAs.
 * Text + decorative SVG (pricing "Submit" + arrow) must still promote.
 */
function isIconOrEmptyButton(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const classes = `${attrs.class ?? ''} ${(component?.getClasses?.() ?? []).join(' ')}`.toLowerCase();

    // Tailwind swatches: rounded-full + fixed box, no horizontal CTA padding.
    if (
        /\brounded-full\b/.test(classes)
        && /\bw-(?:\d+|\[)/.test(classes)
        && /\bh-(?:\d+|\[)/.test(classes)
        && ! /\bpx-(?:\d+|\[)/.test(classes)
    ) {
        return true;
    }

    const authorText = readAuthorTextNodes(component);

    // Prefer live DOM text when Grapes nests label + svg (textnode may be missing).
    const domText = String(component?.getView?.()?.el?.textContent ?? '')
        .replace(/\s+/g, ' ')
        .trim();
    const label = authorText || domText;

    // Empty markup, or only structural children (svg/img) with no author text.
    return label === '';
}

/** Billing toggles / slider chevrons — keep native <button>, not link CTAs. */
function isNonCtaControlLabel(label) {
    const normalized = String(label ?? '').replace(/\s+/g, ' ').trim().toLowerCase();

    if (normalized === '') {
        return false;
    }

    if (['monthly', 'annually', 'yearly'].includes(normalized)) {
        return true;
    }

    // Single-glyph slider / carousel controls.
    return /^[‹›«»←→<>]$/u.test(normalized);
}

function shouldPromoteNativeButtonToCta(component) {
    const attrs = component?.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-cta'] === 'true') {
        return true;
    }

    if (
        attrs['data-voodbuilder-skip-cta'] === 'true'
        || attrs['data-code-copy'] != null
        || Object.prototype.hasOwnProperty.call(attrs, 'data-code-copy')
    ) {
        return false;
    }

    if (component?.closest?.('[data-code-block]') || component?.closest?.('.vp-code-block')) {
        return false;
    }

    if (isIconOrEmptyButton(component)) {
        return false;
    }

    const authorText = readAuthorTextNodes(component);
    const domText = String(component?.getView?.()?.el?.textContent ?? '')
        .replace(/\s+/g, ' ')
        .trim();
    const label = authorText || domText;

    if (label === '' || isNonCtaControlLabel(label)) {
        return false;
    }

    return true;
}

/**
 * Strip CTA/"Button" leftovers from icon-only or empty swatch buttons.
 */
function neutralizeNonCtaButton(component) {
    if (! component) {
        return;
    }

    const attrs = component.getAttributes?.() ?? {};
    const authorText = readAuthorTextNodes(component);
    const inventedOnly = authorText !== '' && INVENTED_BUTTON_LABELS.has(authorText.toLowerCase());
    const structural = buttonHasStructuralChildren(component);

    if (! isIconOrEmptyButton(component) && ! (structural && inventedOnly)) {
        return;
    }

    if (attrs['data-voodbuilder-cta'] === 'true' || attrs[CTA_LABEL_ATTR]) {
        const next = { ...attrs };
        delete next['data-voodbuilder-cta'];
        delete next[CTA_LABEL_ATTR];
        delete next['data-vb-link-type'];
        delete next['data-vb-link'];
        component.setAttributes(next);
    }

    if (component.get?.('type') === 'voodbuilder-cta-button') {
        component.set('type', 'button', { silent: true });
    }

    if (structural && inventedOnly) {
        componentChildModels(component)
            .filter((child) => isTextishChild(child))
            .forEach((child) => {
                if (INVENTED_BUTTON_LABELS.has(String(child.get?.('content') ?? '').trim().toLowerCase())) {
                    child.remove?.();
                }
            });
    } else if (! structural && inventedOnly) {
        component.components?.('');
    }

    if (component.get?.('ctaLabel')) {
        component.set('ctaLabel', '', { silent: true });
    }

    if (component.get?.('text')) {
        component.set('text', structural ? '' : '', { silent: true });
    }
}

export function extractButtonLabel(component) {
    const attrs = component.getAttributes?.() ?? {};
    const fromAttr = String(attrs[CTA_LABEL_ATTR] ?? '').trim();

    if (fromAttr !== '') {
        return fromAttr;
    }

    const ctaLabel = String(component.get?.('ctaLabel') ?? '').trim();

    if (ctaLabel !== '') {
        return ctaLabel;
    }

    const children = component.components?.();
    const models = [...(children?.models ?? children ?? [])];

    if (models.length) {
        let fromChildren = '';

        for (const child of models) {
            if (child?.get?.('type') !== 'textnode') {
                continue;
            }

            fromChildren = String(child.get('content') ?? '').trim();

            if (fromChildren !== '') {
                return fromChildren;
            }
        }

        const hasStructuralChildren = models.some((child) => {
            const type = child?.get?.('type');

            return type !== 'textnode' && type !== 'text';
        });

        // Card-style links (img + title blocks): never scrape nested copy into a sibling label.
        if (hasStructuralChildren) {
            return '';
        }
    }

    const element = component.getView?.()?.el;

    if (element?.textContent?.trim()) {
        return element.textContent.trim();
    }

    if (component.get('text')) {
        return String(component.get('text')).trim();
    }

    if (component.get('content')) {
        return String(component.get('content')).trim();
    }

    // Never invent "Button" for empty / icon markup — only palette defaults do that.
    return '';
}

/**
 * Patch the live canvas DOM label without renderChildren() (which clears
 * innerHTML first and causes visible Button flicker / editor freezes).
 */
function patchCtaDomLabel(component, text) {
    const el = component?.getEl?.();
    const label = String(text ?? '').trim();

    if (! el || label === '') {
        return;
    }

    if (el.childNodes.length === 1 && el.firstChild?.nodeType === Node.TEXT_NODE) {
        if (el.firstChild.textContent !== label) {
            el.firstChild.textContent = label;
        }

        return;
    }

    if (el.querySelector?.('svg, img, i, span, strong, em, b')) {
        const textNode = [...el.childNodes].find((node) => node.nodeType === Node.TEXT_NODE);

        if (textNode) {
            if (textNode.textContent !== label) {
                textNode.textContent = label;
            }

            return;
        }

        // Icon-only buttons: never append a default label next to the icon.
        return;
    }

    if (String(el.textContent ?? '') !== label) {
        el.textContent = label;
    }
}

function ctaDomLabelMismatches(component, text) {
    const el = component?.getEl?.();

    if (! el || text === '') {
        return false;
    }

    return String(el.textContent ?? '').replace(/\s+/g, ' ').trim() !== text;
}

/**
 * Sync label onto the model (attr + textnode). Prefer DOM patch over view re-render.
 */
function syncCtaLabelModel(component, label = null) {
    if (! component?.components) {
        return { changed: false };
    }

    const text = String(label ?? extractButtonLabel(component) ?? '').trim();
    let changed = false;

    if (text === '') {
        return { changed: false };
    }

    if (component.get('ctaLabel') !== text) {
        component.set('ctaLabel', text, { silent: true });
        changed = true;
    }

    const currentAttr = String(component.getAttributes?.()?.[CTA_LABEL_ATTR] ?? '');

    if (currentAttr !== text) {
        component.addAttributes({ [CTA_LABEL_ATTR]: text }, { silent: true });
        changed = true;
    }

    const children = component.components();
    const models = [...(children?.models ?? children ?? [])];
    const onlyTextNodes = models.length > 0 && models.every((child) => {
        const type = child?.get?.('type');

        return type === 'textnode' || type === 'text';
    });
    const hasStructuralChildren = models.some((child) => {
        const type = child?.get?.('type');

        return type !== 'textnode' && type !== 'text';
    });

    if (models.length === 1 && models[0]?.get?.('type') === 'textnode') {
        if (String(models[0].get('content') ?? '') !== text) {
            models[0].set('content', text, { silent: true });
            changed = true;
        }
    } else if (models.length === 0) {
        component.append({ type: 'textnode', content: text });
        changed = true;
    } else if (onlyTextNodes) {
        const currentJoined = models
            .map((child) => String(child?.get?.('content') ?? ''))
            .join('');

        // Only rebuild when the joined label is wrong — never for "normalize to 1 node".
        if (currentJoined !== text) {
            component.components(text);
            changed = true;
        }
    } else if (hasStructuralChildren) {
        // Keep existing textnode labels (icon + text CTA). Never invent a new one
        // from nested card copy — that duplicated titles next to the real markup.
        const textNode = models.find((child) => child?.get?.('type') === 'textnode') ?? null;

        if (textNode && String(textNode.get('content') ?? '') !== text && label != null) {
            textNode.set('content', text, { silent: true });
            changed = true;
        }
    } else {
        const textNode = models.find((child) => child?.get?.('type') === 'textnode') ?? null;

        if (textNode) {
            if (String(textNode.get('content') ?? '') !== text) {
                textNode.set('content', text, { silent: true });
                changed = true;
            }
        } else {
            component.append({ type: 'textnode', content: text });
            changed = true;
        }
    }

    return { changed };
}

/**
 * Force a serializable label on the CTA model (attr + textnode).
 * Call after trait changes / morph — not from toHTML or CSS compile scans.
 */
export function persistCtaLabel(component, label = null) {
    if (! component?.components) {
        return '';
    }

    if (component.__vbPersistingCtaLabel) {
        return extractButtonLabel(component);
    }

    component.__vbPersistingCtaLabel = true;

    try {
        const { changed } = syncCtaLabelModel(component, label);
        const text = extractButtonLabel(component);

        if (changed || ctaDomLabelMismatches(component, text)) {
            patchCtaDomLabel(component, text);
        }

        return text;
    } finally {
        component.__vbPersistingCtaLabel = false;
    }
}

/** @deprecated use persistCtaLabel */
export function ensureTextLabel(component, label) {
    persistCtaLabel(component, label);
}

function buttonLinkTraitSchema(labels = {}, editor = null) {
    const linkTargets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };
    const linkType = 'url';

    return [
        {
            type: 'text',
            name: 'ctaLabel',
            label: labels.buttonLinkLabel ?? 'Button label',
            changeProp: true,
        },
        {
            type: 'select',
            name: 'linkType',
            label: labels.buttonLinkType ?? 'Link type',
            options: [
                { id: 'url', name: labels.buttonLinkTypeUrl ?? 'URL' },
                { id: 'page', name: labels.buttonLinkTypePage ?? 'Site page' },
                { id: 'menu', name: labels.buttonLinkTypeMenu ?? 'Menu item' },
                { id: 'mail', name: labels.buttonLinkTypeMail ?? 'Email' },
                { id: 'route', name: labels.buttonLinkTypeRoute ?? 'App route' },
            ],
            changeProp: true,
        },
        {
            type: 'text',
            name: 'href',
            label: labels.buttonLinkUrl ?? 'Link URL',
            placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
            changeProp: true,
        },
        {
            type: 'select',
            name: 'linkRef',
            label: labels.buttonLinkPage ?? 'Page',
            options: [
                { id: '', name: '—' },
                ...(linkTargets.pages ?? []).map((item) => ({ id: item.id, name: item.label })),
            ],
            changeProp: true,
        },
        {
            type: 'select',
            name: 'target',
            label: labels.buttonLinkTarget ?? 'Open in',
            options: [
                { id: '', name: labels.buttonLinkSameTab ?? 'Same tab' },
                { id: '_blank', name: labels.buttonLinkNewTab ?? 'New tab' },
            ],
            changeProp: true,
        },
    ];
}

function linkTraitsFor(editor, component = null) {
    const labels = editor?.__voodbuilderLabels ?? {};
    const linkTargets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [], routes: [] };
    const linkType = String(component?.get?.('linkType') ?? component?.getAttributes?.()?.['data-vb-link-type'] ?? 'url');
    const traits = [
        {
            type: 'text',
            name: 'ctaLabel',
            label: labels.buttonLinkLabel ?? 'Button label',
            changeProp: true,
        },
        {
            type: 'select',
            name: 'linkType',
            label: labels.buttonLinkType ?? 'Link type',
            options: [
                { id: 'url', name: labels.buttonLinkTypeUrl ?? 'URL' },
                { id: 'page', name: labels.buttonLinkTypePage ?? 'Site page' },
                { id: 'menu', name: labels.buttonLinkTypeMenu ?? 'Menu item' },
                { id: 'mail', name: labels.buttonLinkTypeMail ?? 'Email' },
                { id: 'route', name: labels.buttonLinkTypeRoute ?? 'App route' },
            ],
            changeProp: true,
        },
    ];

    if (linkType === 'page') {
        traits.push({
            type: 'select',
            name: 'linkRef',
            label: labels.buttonLinkPage ?? 'Page',
            options: [
                { id: '', name: '—' },
                ...(linkTargets.pages ?? []).map((item) => ({ id: String(item.id), name: item.label })),
            ],
            changeProp: true,
        });
    } else if (linkType === 'menu') {
        traits.push({
            type: 'select',
            name: 'linkRef',
            label: labels.buttonLinkMenu ?? 'Menu item',
            options: [
                { id: '', name: '—' },
                ...(linkTargets.menuItems ?? []).map((item) => ({ id: String(item.id), name: item.label })),
            ],
            changeProp: true,
        });
    } else if (linkType === 'route') {
        traits.push({
            type: 'select',
            name: 'linkRef',
            label: labels.buttonLinkRoute ?? 'App route',
            options: [
                { id: '', name: '—' },
                ...(linkTargets.routes ?? []).map((item) => ({ id: String(item.id), name: item.label })),
            ],
            changeProp: true,
        });
    } else if (linkType === 'mail') {
        traits.push(
            {
                type: 'text',
                name: 'linkRef',
                label: labels.buttonLinkMail ?? 'Email address',
                placeholder: labels.buttonLinkMailPlaceholder ?? 'name@example.com',
                changeProp: true,
            },
            {
                type: 'text',
                name: 'mailSubject',
                label: labels.buttonLinkMailSubject ?? 'Subject',
                placeholder: labels.buttonLinkMailSubjectPlaceholder ?? 'Optional subject',
                changeProp: true,
            },
        );
    } else {
        traits.push({
            type: 'text',
            name: 'href',
            label: labels.buttonLinkUrl ?? 'Link URL',
            placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
            changeProp: true,
        });
    }

    if (linkType !== 'mail') {
        traits.push({
            type: 'select',
            name: 'target',
            label: labels.buttonLinkTarget ?? 'Open in',
            options: [
                { id: '', name: labels.buttonLinkSameTab ?? 'Same tab' },
                { id: '_blank', name: labels.buttonLinkNewTab ?? 'New tab' },
            ],
            changeProp: true,
        });
    }

    return traits;
}

function resolveLinkHref(editor, linkType, linkRef, href) {
    return resolveSharedLinkHref(editor, linkType, linkRef, href);
}

function syncLinkableButtonTraits(component, editor, { forceSelect = false } = {}) {
    // Silent trait schema refresh — never remount TraitManager unless selecting.
    component.set('traits', linkTraitsFor(editor, component), { silent: true });

    if (! forceSelect || editor?.getSelected?.() !== component) {
        return;
    }

    // Content panel owns CTA/link settings. Hiding its mount + TraitManager.select
    // blanked the right column for ~1–2s on linkType changes (and fought every select).
    if (editor.__voodbuilderSettingsChange) {
        return;
    }

    const activeTab = editor.__voodbuilderInspectorActiveTab ?? 'content';

    if (activeTab === 'content') {
        return;
    }

    const traitsMount = editor.getContainer?.()
        ?.closest?.('.voodbuilder-editor-shell')
        ?.querySelector?.('.voodbuilder-editor-traits-mount')
        ?? document.querySelector('.voodbuilder-editor-traits-mount');
    const settingsMount = traitsMount
        ?.closest?.('[data-voodbuilder-inspector="content"]')
        ?.querySelector?.('.voodbuilder-editor-site-chrome-settings-mount');

    settingsMount && (settingsMount.hidden = true);
    traitsMount?.classList.remove('hidden');

    if (editor.TraitManager?.select) {
        editor.TraitManager.select(component);
    }
}

function readLinkProps(component) {
    const attrs = component.getAttributes?.() ?? {};

    return {
        href: String(component.get('href') ?? attrs.href ?? '').trim(),
        target: String(component.get('target') ?? attrs.target ?? '').trim(),
        linkType: String(component.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url').trim() || 'url',
        linkRef: String(component.get('linkRef') ?? attrs['data-vb-link'] ?? '').trim(),
    };
}

function hydrateLinkPropsFromAttributes(component) {
    const { href, target, linkType, linkRef } = readLinkProps(component);
    const updates = {};

    if (href !== '' && component.get('href') !== href) {
        updates.href = href;
    }

    if (target !== component.get('target')) {
        updates.target = target;
    }

    if (component.get('linkType') !== linkType) {
        updates.linkType = linkType;
    }

    if (component.get('linkRef') !== linkRef) {
        updates.linkRef = linkRef;
    }

    const label = extractButtonLabel(component);

    if (label !== '' && component.get('ctaLabel') !== label) {
        updates.ctaLabel = label;
    }

    if (Object.keys(updates).length > 0) {
        component.set(updates, { silent: true });
    }

    if (label !== '' && String(component.getAttributes?.()?.[CTA_LABEL_ATTR] ?? '') !== label) {
        component.addAttributes({ [CTA_LABEL_ATTR]: label }, { silent: true });
    }
}

function isExcludedLinkableButton(component) {
    const type = component.get('type');

    if (type === 'voodbuilder-nav-menu-button' || type === 'voodbuilder-chrome-button') {
        return true;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();

    if (tag !== 'button' && tag !== 'a') {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};
    const classes = component.getClasses?.() ?? [];

    if (classes.includes('voodbuilder-header-icon-btn')) {
        return true;
    }

    if (
        attrs['data-voodbuilder-search-open'] != null
        || attrs['data-voodbuilder-notification-bell-preview'] != null
        || attrs['data-voodbuilder-profile-menu-toggle'] != null
    ) {
        return true;
    }

    if (classes.includes('voodbuilder-mobile-nav__cookie-link') || classes.includes('cc-revoke') || classes.includes('cc-btn')) {
        return true;
    }

    if (
        attrs['data-voodbuilder-nav-dropdown-toggle']
        || attrs['data-voodbuilder-nav-mobile-toggle']
        || attrs['data-cookie-preferences']
        || attrs['data-mobile-nav-close']
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs['data-cc']
        || attrs['aria-label'] === 'vcookiebar'
    ) {
        return true;
    }

    if (
        attrs['data-voodbuilder-chrome'] === 'cookie'
        || (attrs.role === 'button' && classes.some((name) => String(name).startsWith('cc-')))
    ) {
        return true;
    }

    const label = String(attrs['aria-label'] ?? component.get?.('ctaLabel') ?? '').trim().toLowerCase();

    if (label === 'cookie settings' || label === 'impostazioni cookie') {
        return true;
    }

    if (attrs['data-carousel-prev'] || attrs['data-carousel-next']) {
        return true;
    }

    if (
        attrs['data-code-copy'] != null
        || Object.prototype.hasOwnProperty.call(attrs, 'data-code-copy')
        || classes.includes('vp-code-block__copy')
        || component.closest?.('[data-code-block]')
        || component.closest?.('.vp-code-block')
    ) {
        return true;
    }

    if (attrs['data-voodbuilder-bind']) {
        return true;
    }

    if (tag !== 'button') {
        return false;
    }

    const form = component.closest?.('form');

    if (! form) {
        return false;
    }

    if (attrs.type === 'submit') {
        return true;
    }

    const formAttrs = form.getAttributes?.() ?? {};
    const formClasses = form.getClasses?.() ?? [];

    if (formAttrs['data-voodbuilder-form'] || formClasses.includes('vb-gjs-form')) {
        return true;
    }

    return false;
}

function escapeHtmlText(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function applyCtaButtonLink(component, editor = null) {
    const ed = editor ?? component?.em ?? null;
    const { href, target, linkType, linkRef } = readLinkProps(component);
    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const label = extractButtonLabel(component);
    const resolvedHref = resolveLinkHref(ed, linkType, linkRef, href);

    if (tag !== 'button' && ! (tag === 'a' && component.getAttributes()?.['data-voodbuilder-cta'] === 'true')) {
        return;
    }

    // Avoid change:tagName → full view reset when already an anchor CTA.
    const nextProps = {
        type: 'voodbuilder-cta-button',
        editable: false,
        highlightable: true,
        selectable: true,
        layerable: true,
        href: resolvedHref,
        target,
        linkType,
        linkRef,
        ctaLabel: label,
    };

    if (tag !== 'a') {
        nextProps.tagName = 'a';
    }

    const propUpdates = {};

    for (const [key, value] of Object.entries(nextProps)) {
        if (component.get(key) !== value) {
            propUpdates[key] = value;
        }
    }

    if (Object.keys(propUpdates).length > 0) {
        component.set(propUpdates);
    }

    const attrs = component.getAttributes?.() ?? {};
    const nextAttrs = {
        href: resolvedHref,
        target: target || null,
        rel: target === '_blank' ? 'noopener noreferrer' : null,
        role: 'button',
        'data-voodbuilder-cta': 'true',
        [CTA_LABEL_ATTR]: label,
        'data-vb-link-type': linkType || 'url',
        'data-vb-link': linkType === 'url' ? null : (linkRef || null),
    };
    const attrsChanged = Object.entries(nextAttrs).some(([key, value]) => {
        const current = attrs[key] ?? null;

        return String(current ?? '') !== String(value ?? '');
    });

    if (attrsChanged) {
        component.setAttributes({
            ...attrs,
            ...nextAttrs,
        });
        component.removeAttributes(['type', 'onclick']);
    }

    persistCtaLabel(component, label);
}

function assignLinkableButtonType(component) {
    if (component.get('type') === 'voodbuilder-cta-button') {
        return;
    }

    component.set('type', 'voodbuilder-cta-button');
}

function isLinkableCtaComponent(component) {
    if (isExcludedLinkableButton(component) || isInsideChromeShellPartComponent(component)) {
        return false;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};

    if (tag === 'button') {
        if (attrs['data-voodbuilder-cta'] === 'true') {
            return true;
        }

        // Plain empty / icon buttons stay native <button> — no CTA morph / "Button" label.
        return shouldPromoteNativeButtonToCta(component);
    }

    return tag === 'a' && attrs['data-voodbuilder-cta'] === 'true';
}

/**
 * Label stored on the model for export (attr / prop / textnode). No "Button" fallback —
 * that hid missing persistence and let empty CTAs look fine until reload.
 *
 * @param {object} component
 * @returns {string}
 */
function readPersistedCtaLabel(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const fromAttr = String(attrs[CTA_LABEL_ATTR] ?? '').trim();

    if (fromAttr !== '') {
        return fromAttr;
    }

    const ctaLabel = String(component?.get?.('ctaLabel') ?? '').trim();

    if (ctaLabel !== '') {
        return ctaLabel;
    }

    const children = component?.components?.();
    const models = [...(children?.models ?? children ?? [])];

    for (const child of models) {
        if (child?.get?.('type') !== 'textnode') {
            continue;
        }

        const text = String(child.get('content') ?? '').trim();

        if (text !== '') {
            return text;
        }
    }

    return '';
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function ctaModelHasLabelChild(component) {
    const children = component?.components?.();
    const models = [...(children?.models ?? children ?? [])];

    return models.some((child) => {
        if (child?.get?.('type') !== 'textnode') {
            return false;
        }

        return String(child.get('content') ?? '').trim() !== '';
    });
}

function isStableCtaButton(component) {
    if (! component || component.get?.('type') !== 'voodbuilder-cta-button') {
        return false;
    }

    if (! component.__vbLinkMorphApplied) {
        return false;
    }

    if (component.getAttributes?.()?.['data-voodbuilder-cta'] !== 'true') {
        return false;
    }

    // Require a real textnode — DOM-only patches do not survive getHtml / reload.
    return readPersistedCtaLabel(component) !== '' && ctaModelHasLabelChild(component);
}

function upgradeLinkableButton(component, editor) {
    if (! component || ! editor) {
        return;
    }

    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    if (tag === 'button' && ! shouldPromoteNativeButtonToCta(component)) {
        neutralizeNonCtaButton(component);

        return;
    }

    if (! isLinkableCtaComponent(component)) {
        return;
    }

    // Already a healthy CTA with persisted label — avoid remorph flicker.
    if (isStableCtaButton(component)) {
        const label = extractButtonLabel(component);

        if (ctaDomLabelMismatches(component, label)) {
            patchCtaDomLabel(component, label);
        }

        return;
    }

    assignLinkableButtonType(component);
    hydrateLinkPropsFromAttributes(component);

    if (component.getAttributes?.()?.['data-voodbuilder-cta'] !== 'true') {
        component.addAttributes({ 'data-voodbuilder-cta': 'true' }, { silent: true });
    }

    const label = readPersistedCtaLabel(component) || extractButtonLabel(component);

    // Text CTAs without author copy stay without an invented "Button" label.
    if (label === '') {
        return;
    }

    if (component.get('type') === 'voodbuilder-cta-button' && component.__vbLinkMorphApplied) {
        persistCtaLabel(component, label);

        return;
    }

    applyCtaButtonLink(component, editor);
    component.__vbLinkMorphApplied = true;
    persistCtaLabel(component, label);
    syncLinkableButtonTraits(component, editor);
}

function registerLinkableButtonType(editor) {
    if (editor.__voodbuilderLinkableButtonTypeRegistered) {
        return;
    }

    editor.__voodbuilderLinkableButtonTypeRegistered = true;

    const labels = editor.__voodbuilderLabels ?? {};
    const BaseComponent = editor.DomComponents.getType('default')?.model
        ?? editor.DomComponents.getType('link')?.model
        ?? null;

    editor.DomComponents.addType('voodbuilder-cta-button', {
        isComponent: (element) => {
            if (! element?.getAttribute) {
                return false;
            }

            if (
                element.hasAttribute('data-cookie-preferences')
                || element.hasAttribute('data-cc')
                || element.classList?.contains('cc-revoke')
                || element.classList?.contains('cc-btn')
                || element.classList?.contains('voodbuilder-mobile-nav__cookie-link')
                || element.closest?.('[data-voodbuilder-chrome-shell-part], [data-mobile-nav], .voodbuilder-mobile-nav__legal')
            ) {
                return false;
            }

            if (element.getAttribute('data-voodbuilder-cta') === 'true') {
                return { type: 'voodbuilder-cta-button' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'a',
                attributes: {
                    href: '#',
                    role: 'button',
                    'data-voodbuilder-cta': 'true',
                    'data-voodbuilder-cta-label': 'Button',
                    'data-vb-link-type': 'url',
                },
                traits: buttonLinkTraitSchema(labels, editor),
                href: '#',
                target: '',
                linkType: 'url',
                linkRef: '',
                ctaLabel: 'Button',
                components: 'Button',
                editable: false,
                droppable: false,
                layerable: true,
                name: 'Button',
            },
            init() {
                hydrateLinkPropsFromAttributes(this);
                persistCtaLabel(this, extractButtonLabel(this) || 'Button');
                syncLinkableButtonTraits(this, editor);
                this.__vbLinkMorphApplied = true;

                this.on('change:ctaLabel', () => {
                    if (this.__vbPersistingCtaLabel) {
                        return;
                    }

                    persistCtaLabel(this, this.get('ctaLabel') || 'Button');
                });

                // Do NOT listen to change:content — Editor updateContent + our
                // restore fought each other and made "Button" flicker on every
                // getHtml/toHTML during page CSS compile.

                this.on('change:href change:target change:linkRef', () => {
                    // Content panel already applied attrs — skip a second morph pass.
                    if (editor.__voodbuilderSettingsChange) {
                        return;
                    }

                    this.__vbLinkMorphApplied = true;
                    applyCtaButtonLink(this, editor);
                });

                this.on('change:linkType', () => {
                    this.__vbLinkMorphApplied = true;

                    // Content panel commit already wrote linkType/linkRef/href and keeps
                    // the form mounted. Clearing linkRef + forceSelect blanked the panel.
                    if (editor.__voodbuilderSettingsChange) {
                        syncLinkableButtonTraits(this, editor);

                        return;
                    }

                    this.set('linkRef', '', { silent: true });
                    syncLinkableButtonTraits(this, editor, { forceSelect: true });
                    applyCtaButtonLink(this, editor);
                });
            },
            toHTML(opts) {
                // Never mutate during serialization (CSS compile / getHtml loops).
                const tag = this.get('tagName') || 'a';
                const attrs = this.getAttrToHTML?.(opts) ?? this.getAttributes?.() ?? {};
                const attrString = Object.entries(attrs)
                    .filter(([, value]) => value != null && value !== false)
                    .map(([key, value]) => (value === true ? key : `${key}="${String(value).replace(/"/g, '&quot;')}"`))
                    .join(' ');
                const label = extractButtonLabel(this);

                return `<${tag}${attrString ? ` ${attrString}` : ''}>${escapeHtmlText(label)}</${tag}>`;
            },
            getAttrToHTML(opts) {
                const attrs = BaseComponent?.prototype?.getAttrToHTML
                    ? BaseComponent.prototype.getAttrToHTML.call(this, opts)
                    : { ...(this.getAttributes?.() ?? {}) };

                const label = extractButtonLabel(this);
                const linkType = String(this.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url');
                const linkRef = String(this.get('linkRef') ?? attrs['data-vb-link'] ?? '');

                attrs['data-voodbuilder-cta'] = 'true';
                attrs[CTA_LABEL_ATTR] = label;
                attrs.role = attrs.role || 'button';
                attrs['data-vb-link-type'] = linkType;
                attrs['data-vb-link'] = linkType === 'url' ? null : (linkRef || null);
                attrs.href = this.get('href') || attrs.href || '#';

                return attrs;
            },
        },
        view: {
            /**
             * Editor wires change:content → updateContent, which sets
             * innerHTML='' when child components exist (without re-rendering them).
             * That empties CTA labels until something calls renderChildren — the
             * Button text flicker on editor refresh. Keep updateContent for
             * renderChildren (needs the clear), but detach the change:content wipe.
             */
            init() {
                this.stopListening(this.model, 'change:content', this.updateContent);
            },
            onRender() {
                const model = this.model;

                if (! model) {
                    return;
                }

                // Deselect / remorph / clone can leave an empty <a> (collapsed blue sliver).
                // Re-assert label from ctaLabel / data attribute into model + live DOM.
                const label = extractButtonLabel(model) || 'Button';
                persistCtaLabel(model, label);

                // Drop/placement can remount the view after the first patch — heal again.
                window.requestAnimationFrame(() => {
                    if (model.isRemoved?.()) {
                        return;
                    }

                    persistCtaLabel(model, extractButtonLabel(model) || 'Button');
                });
            },
        },
    });
}

function registerCtaLinkPreview(editor) {
    if (editor.__voodbuilderCtaLinkPreviewRegistered) {
        return;
    }

    editor.__voodbuilderCtaLinkPreviewRegistered = true;

    editor.on('load', () => {
        const frame = editor.Canvas?.getFrameEl?.();

        if (! frame?.contentDocument) {
            return;
        }

        frame.contentDocument.addEventListener('click', (event) => {
            const anchor = event.target?.closest?.('a[data-voodbuilder-cta][href]');

            if (! anchor) {
                return;
            }

            if (! (event.metaKey || event.ctrlKey || event.altKey)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const target = anchor.getAttribute('target') || '_self';

            if (target === '_blank') {
                window.open(anchor.href, '_blank', 'noopener,noreferrer');
            } else {
                window.open(anchor.href, '_self');
            }
        }, true);
    });
}

export function registerLinkableButtonTypes(editor) {
    registerLinkableButtonType(editor);
}

export function scanLinkableButtons(editor, root = editor.getWrapper?.()) {
    if (! root) {
        return;
    }

    const visit = (component) => {
        // Promote Tailblocks/template CTAs that lack data-voodbuilder-cta yet.
        promoteButtonLikeAnchor(component);

        const tag = String(component.get?.('tagName') ?? '').toLowerCase();

        if (tag === 'button' && ! shouldPromoteNativeButtonToCta(component)) {
            neutralizeNonCtaButton(component);
        } else if (isLinkableCtaComponent(component) || component.get?.('type') === 'voodbuilder-cta-button') {
            upgradeLinkableButton(component, editor);
        }

        component.components?.().forEach((child) => visit(child));
    };

    visit(root);
}

/** Upgrade one selected component to a smart CTA when applicable. */
export function ensureSmartCtaButton(component, editor) {
    if (! component || ! editor) {
        return false;
    }

    promoteButtonLikeAnchor(component);

    if (! isLinkableCtaComponent(component) && component.get?.('type') !== 'voodbuilder-cta-button') {
        return false;
    }

    upgradeLinkableButton(component, editor);

    return component.get?.('type') === 'voodbuilder-cta-button'
        || component.getAttributes?.()?.['data-voodbuilder-cta'] === 'true';
}

function classesLookLikeCtaButton(classes) {
    const c = String(classes ?? '').toLowerCase();

    if (c === '') {
        return false;
    }

    if (/\bbtn(?:-|\s|$)/.test(c)) {
        return true;
    }

    const hasHorizontalPad = c.includes('px-') || /(?:^|\s)p-\d/.test(c);
    const hasVerticalPad = c.includes('py-')
        || /(?:^|\s)p-\d/.test(c)
        || /(?:^|\s)h-(?:\d+|\[)/.test(c)
        || /(?:^|\s)min-h-(?:\d+|\[)/.test(c);
    const hasPad = hasHorizontalPad && hasVerticalPad;
    const hasRounded = c.includes('rounded');

    if (! hasPad || ! hasRounded) {
        return false;
    }

    const hasFill = /bg-(indigo|vp-brand|blue|gray-8|black|green|teal|emerald)/.test(c);
    const hasOutline = c.includes('border') && ! c.includes('border-0');
    const hasInlineFlex = c.includes('inline-flex');

    return hasFill || hasOutline || hasInlineFlex;
}

function promoteButtonLikeAnchor(component) {
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const type = String(component.get?.('type') ?? '');

    if (tag !== 'a' && tag !== 'button' && type !== 'link') {
        return;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (
        attrs['data-voodbuilder-skip-cta'] === 'true'
        || attrs['data-code-copy'] != null
        || Object.prototype.hasOwnProperty.call(attrs, 'data-code-copy')
        || attrs['data-vb-share-item'] === 'true'
        || attrs['data-vx-gallery-index'] != null
        || attrs['data-vx-gallery-close'] != null
        || attrs['data-vx-gallery-prev'] != null
        || attrs['data-vx-gallery-next'] != null
        || component.closest?.('[data-vx-gallery]')
        || component.closest?.('[data-voodbuilder-social-share]')
        || component.closest?.('[data-code-block]')
        || component.closest?.('.vp-code-block')
    ) {
        return;
    }

    if (attrs['data-voodbuilder-cta'] === 'true') {
        return;
    }

    if (tag === 'button') {
        const buttonType = String(attrs.type ?? '').toLowerCase();

        if (buttonType === 'submit' || buttonType === 'reset') {
            return;
        }

        if (! shouldPromoteNativeButtonToCta(component)) {
            neutralizeNonCtaButton(component);

            return;
        }

        component.addAttributes({ 'data-voodbuilder-cta': 'true' });

        return;
    }

    const classes = `${attrs.class ?? ''} ${(component.getClasses?.() ?? []).join(' ')}`;

    if (classesLookLikeCtaButton(classes) || attrs.role === 'button') {
        const label = String(attrs[CTA_LABEL_ATTR] ?? '').trim()
            || String(component.get?.('ctaLabel') ?? '').trim()
            || extractButtonLabel(component)
            || 'Button';

        component.addAttributes({
            'data-voodbuilder-cta': 'true',
            role: 'button',
            [CTA_LABEL_ATTR]: label,
            'data-vb-link-type': attrs['data-vb-link-type'] || 'url',
        });
    }
}

/**
 * Before getHtml / chrome-shell extract: guarantee every real CTA has attr + textnode label.
 * Do not invent "Button" on empty/icon-only native buttons.
 */
export function ensureCtaButtonsForExport(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const visit = (component) => {
        const tag = String(component.get?.('tagName') ?? '').toLowerCase();

        if (tag === 'button' && ! shouldPromoteNativeButtonToCta(component)) {
            neutralizeNonCtaButton(component);
        } else if (isLinkableCtaComponent(component) || component.get?.('type') === 'voodbuilder-cta-button') {
            upgradeLinkableButton(component, editor);
            const label = extractButtonLabel(component);

            if (label !== '') {
                persistCtaLabel(component, label);
            }
        }

        component.components?.()?.forEach?.((child) => visit(child));
    };

    visit(wrapper);
}

export function configureLinkableButtons(editor) {
    registerLinkableButtonType(editor);
    registerCtaLinkPreview(editor);

    if (editor.__voodbuilderLinkableButtonsConfigured) {
        return;
    }

    editor.__voodbuilderLinkableButtonsConfigured = true;

    editor.on('load', () => {
        scanLinkableButtons(editor);

        // Heal CTAs that loaded with only the model default (no textnode in HTML).
        window.requestAnimationFrame(() => {
            ensureCtaButtonsForExport(editor);
        });
    });

    const pendingLinkableRoots = new Set();
    let linkableScanFrame = null;

    const flushLinkableScan = () => {
        linkableScanFrame = null;
        const roots = [...pendingLinkableRoots];
        pendingLinkableRoots.clear();

        // Full promote+upgrade (not upgrade-only): Tailblocks <button> CTAs and
        // annotated <a data-voodbuilder-cta> must become smart buttons on insert,
        // not only after Save → ensureCtaButtonsForExport.
        for (const root of roots) {
            if (! root || root.isRemoved?.()) {
                continue;
            }

            scanLinkableButtons(editor, root);
        }
    };

    editor.on('component:add', (component) => {
        if (! component || editor.__voodbuilderBulkStructureUpdate) {
            return;
        }

        // Ignore textnode churn from label sync — that used to re-upgrade parents.
        if (component.get?.('type') === 'textnode' || component.get?.('type') === 'text') {
            return;
        }

        pendingLinkableRoots.add(component);

        if (linkableScanFrame == null) {
            linkableScanFrame = window.requestAnimationFrame(flushLinkableScan);
        }

        // Grapes finishes nested parse a frame later — second pass catches Submit+svg.
        window.setTimeout(() => {
            if (! component || component.isRemoved?.()) {
                return;
            }

            scanLinkableButtons(editor, component);
        }, 120);

        // New Basic Button drops often keep the label only as a Grapes default prop.
        // Persist into attr + textnode (and heal DOM after placement settles).
        if (
            component.get?.('type') === 'voodbuilder-cta-button'
            || component.getAttributes?.()?.['data-voodbuilder-cta'] === 'true'
        ) {
            const heal = () => {
                if (! component || component.isRemoved?.()) {
                    return;
                }

                persistCtaLabel(component, extractButtonLabel(component) || 'Button');
            };

            heal();
            window.requestAnimationFrame(() => {
                heal();
                window.requestAnimationFrame(heal);
            });
        }
    });

    editor.on('component:selected', (component) => {
        if (! isLinkableCtaComponent(component) && component?.get?.('type') !== 'voodbuilder-cta-button') {
            return;
        }

        upgradeLinkableButton(component, editor);
        syncLinkableButtonTraits(component, editor, { forceSelect: true });
    });

    editor.on('component:deselected', (component) => {
        if (! component || component.get?.('type') !== 'voodbuilder-cta-button') {
            return;
        }

        const label = extractButtonLabel(component);

        if (label !== '') {
            persistCtaLabel(component, label);
        }
    });

    // Do NOT rescan on page-css-compiled — compile fires often (including cache
    // hits) and CTA remorph/re-render loops froze the canvas.

    scanLinkableButtons(editor);
}

/**
 * After Library / BlockManager HTML insert: promote CTAs once the tree has views.
 * Call alongside schedulePageCssAfterInsert so authors do not need Save first.
 *
 * @param {object} editor
 * @param {object|null} [root]
 */
export function hydrateCtasAfterHtmlInsert(editor, root = null) {
    if (! editor) {
        return;
    }

    const run = () => {
        try {
            if (root && ! root.isRemoved?.()) {
                scanLinkableButtons(editor, root);
            } else {
                ensureCtaButtonsForExport(editor);
            }
        } catch {
            // ignore
        }
    };

    window.requestAnimationFrame(() => {
        run();
        window.setTimeout(run, 200);
        window.setTimeout(run, 700);
    });
}
