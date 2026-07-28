/**
 * Footer config sync + preview.
 */

import { encodeVpressConfig } from '../../../voodbuilder-dynamic-config.js';
import { runWithSettingsChangeGuard } from '../../../blocks/settings/ui.js';
import {
    applyChromeLogoSizeClasses,
    applyChromeLogoUrlsPreview,
    chromeLogoFieldDefs,
    CHROME_LOGO_DEFAULT_SIZE,
    CHROME_LOGO_FULL_WIDTH_KEY,
    CHROME_LOGO_FULL_WIDTH_PROP,
    CHROME_LOGO_SIZE_KEY,
    CHROME_LOGO_SIZE_MOBILE_KEY,
    CHROME_LOGO_SIZE_MOBILE_PROP,
    CHROME_LOGO_SIZE_PROP,
    normalizeChromeLogoSize,
} from '../../../editor-form-ui.js';
import { setChromeVisible } from '../../visibility.js';
import { isFooterBlock } from '../../ids.js';

const footerRefreshTimers = new WeakMap();

const FOOTER_LOGO_PROPS = new Set(chromeLogoFieldDefs().map((def) => def.prop));

export const FOOTER_SOCIAL_ALIGN_PROP = 'vpressSocialAlign';
export const FOOTER_SOCIAL_ALIGN_KEY = 'social_align';
export const FOOTER_SOCIAL_ALIGN_DEFAULT = 'center';
export const FOOTER_SOCIAL_ALIGNS = ['left', 'center', 'right'];

/**
 * @param {unknown} value
 * @returns {'left'|'center'|'right'}
 */
export function normalizeFooterSocialAlign(value) {
    const align = typeof value === 'string' ? value.trim().toLowerCase() : '';

    return FOOTER_SOCIAL_ALIGNS.includes(align) ? align : FOOTER_SOCIAL_ALIGN_DEFAULT;
}

export function footerBlockHasColumns(blockId) {
    return blockId === 'site_footer_columns_simple' || blockId === 'site_footer_columns_newsletter';
}

export function footerBlockHasNewsletter(blockId) {
    return blockId === 'site_footer_columns_newsletter';
}

export function footerBlockHasMenu(blockId) {
    return blockId === 'site_footer_social' || blockId === 'site_footer_centered';
}

export function footerColumnLabel(index, editor) {
    const options = editor?.__voodbuilderFooterColumnOptions ?? {};

    return options[String(index)] ?? `Column ${index}`;
}

export function footerSettingLabel(editor, key, fallback) {
    return editor?.__voodbuilderLabels?.[key] ?? fallback;
}

function readFooterColumnVisibilityFromConfig(config = {}) {
    const hasPerColumn = [1, 2, 3, 4].some((index) => Object.prototype.hasOwnProperty.call(config, `show_footer_col_${index}`));

    if (hasPerColumn) {
        return Object.fromEntries(
            [1, 2, 3, 4].map((index) => [index, config[`show_footer_col_${index}`] !== false]),
        );
    }

    const count = Math.max(1, Math.min(4, Number(config.columns) || 4));

    return Object.fromEntries(
        [1, 2, 3, 4].map((index) => [index, index <= count]),
    );
}

function countVisibleFooterColumns(root) {
    return [1, 2, 3, 4].filter((index) => root.get(`vpressShowFooterCol${index}`) === true).length;
}

function resolveShowTaglineFromConfig(config, blockId) {
    if (Object.prototype.hasOwnProperty.call(config, 'show_tagline')) {
        return config.show_tagline !== false;
    }

    if (footerBlockHasColumns(blockId) && Object.prototype.hasOwnProperty.call(config, 'show_footer_menu')) {
        return config.show_footer_menu !== false;
    }

    return true;
}

function setFooterChromeVisible(node, visible) {
    setChromeVisible(node, visible);
}

/**
 * @param {'left'|'center'|'right'} align
 * @returns {string}
 */
export function footerSocialJustifyClass(align) {
    const normalized = normalizeFooterSocialAlign(align);

    if (normalized === 'left') {
        return 'justify-start';
    }

    if (normalized === 'right') {
        return 'justify-end';
    }

    return 'justify-center';
}

/**
 * @param {ParentNode} el
 * @param {'left'|'center'|'right'} align
 */
function applyFooterSocialAlignPreview(el, align) {
    const justify = footerSocialJustifyClass(align);
    const tokens = ['justify-start', 'justify-center', 'justify-end', 'sm:justify-start', 'sm:justify-center', 'sm:justify-end'];

    el.querySelectorAll('[data-voodbuilder-footer-social], [data-voodbuilder-social-align]').forEach((node) => {
        tokens.forEach((cls) => node.classList.remove(cls));
        node.classList.add(justify);
        node.setAttribute('data-voodbuilder-social-align', normalizeFooterSocialAlign(align));
    });

    el.querySelectorAll('[data-voodbuilder-social-links]').forEach((node) => {
        tokens.forEach((cls) => node.classList.remove(cls));
        node.classList.add(justify);
    });
}

function applySiteFooterMenuColumnsLayout(root) {
    const el = root.getEl?.();

    if (! el) {
        return;
    }

    const redistribute = root.get('vpressFooterColumnsRedistribute') === true;
    const visibleCount = countVisibleFooterColumns(root);

    el.querySelectorAll('[data-voodbuilder-footer-menu-cols]').forEach((node) => {
        node.classList.toggle('flex', redistribute);
        node.classList.toggle('flex-wrap', redistribute);
        node.classList.toggle('md:flex-nowrap', redistribute);
        node.classList.toggle('grid', ! redistribute);
        node.classList.toggle('grid-cols-1', ! redistribute);
        node.classList.toggle('md:grid-cols-4', ! redistribute);
        node.classList.toggle('md:justify-end', redistribute && visibleCount === 1);
        node.setAttribute('data-voodbuilder-footer-columns-redistribute', redistribute ? '1' : '0');
    });

    el.querySelectorAll('[data-voodbuilder-footer-col]').forEach((col) => {
        const index = Number(col.getAttribute('data-voodbuilder-footer-col'));
        const visible = root.get(`vpressShowFooterCol${index}`) === true;

        col.classList.toggle('basis-full', redistribute);
        col.classList.toggle('md:mb-0', redistribute);
        col.classList.toggle('md:basis-0', redistribute && visible);
        col.classList.toggle('md:flex-1', redistribute && visible);

        for (let columnIndex = 1; columnIndex <= 4; columnIndex++) {
            col.classList.toggle(`md:col-start-${columnIndex}`, ! redistribute && visible && index === columnIndex);
        }
    });
}

export function applySiteFooterSettingsPreview(root, editor = null, options = {}) {
    // Page editor chrome shell: server HTML already has the correct utilities.
    // Mutating grid/flex classes here without a chrome CSS rebuild leaves the footer unstyled.
    if (editor?.__voodbuilderChromeShellMode && ! editor?.__voodbuilderChromeLayoutMode) {
        return;
    }

    void editor;

    const blockId = root.getAttributes()['data-voodbuilder-block'];

    const el = root.getEl?.();

    if (! el) {
        return;
    }

    const showNewsletter = root.get('vpressShowNewsletter') !== false;
    const showSocial = root.get('vpressShowSocial') !== false;
    const showMenu = root.get('vpressShowFooterMenu') === true;
    const showTagline = root.get('vpressShowTagline') !== false;
    const showCopyright = root.get('vpressShowCopyright') !== false;
    // Match nav: treat unset as on so toggles stay independent and visible immediately.
    const showBrand = root.get('vpressShowBrand') !== false;
    const showSiteName = root.get('vpressShowSiteName') !== false;
    const logoSize = normalizeChromeLogoSize(root.get(CHROME_LOGO_SIZE_PROP));
    const logoSizeMobile = normalizeChromeLogoSize(
        root.get(CHROME_LOGO_SIZE_MOBILE_PROP) ?? logoSize,
    );
    const logoFullWidth = root.get(CHROME_LOGO_FULL_WIDTH_PROP) === true;
    const socialAlign = normalizeFooterSocialAlign(root.get(FOOTER_SOCIAL_ALIGN_PROP));

    el.querySelectorAll('[data-voodbuilder-chrome]').forEach((node) => {
        const kind = node.getAttribute('data-voodbuilder-chrome');

        if (kind === 'newsletter') {
            setFooterChromeVisible(node, showNewsletter);
        } else if (kind === 'social') {
            setFooterChromeVisible(node, showSocial);
        } else if (kind === 'footer-menu') {
            setFooterChromeVisible(node, showMenu);
        } else if (kind === 'footer-tagline') {
            setFooterChromeVisible(node, showTagline);
        } else if (kind === 'copyright') {
            setFooterChromeVisible(node, showCopyright);
        } else if (kind === 'brand') {
            setFooterChromeVisible(node, showBrand || showSiteName);
        } else if (kind === 'tagline') {
            setFooterChromeVisible(node, showTagline);
        } else if (kind === 'brand-column') {
            setFooterChromeVisible(node, showBrand || showSiteName || showCopyright || showSocial || showTagline);
        } else if (kind?.startsWith('footer-col-')) {
            const index = Number(kind.replace('footer-col-', ''));
            setFooterChromeVisible(node, root.get(`vpressShowFooterCol${index}`) === true);
        }
    });

    applyFooterBrandPartsPreview(el, showBrand, showSiteName, logoSize, logoSizeMobile, logoFullWidth);
    applyChromeLogoUrlsPreview(el, root);
    applyFooterSocialAlignPreview(el, socialAlign);

    if (footerBlockHasColumns(blockId)) {
        applySiteFooterMenuColumnsLayout(root);
    }

    if (options.invalidateCss && editor?.__voodbuilderChromeLayoutMode) {
        editor.trigger?.('voodbuilder:page-css-invalidate');
    }
}

/**
 * Toggle logo vs site name independently inside the brand chrome (no full remount).
 *
 * @param {HTMLElement} el
 * @param {boolean} showBrand
 * @param {boolean} showSiteName
 * @param {string} [logoSize]
 * @param {string} [logoSizeMobile]
 * @param {boolean} [logoFullWidth]
 */
function applyFooterBrandPartsPreview(
    el,
    showBrand,
    showSiteName,
    logoSize = CHROME_LOGO_DEFAULT_SIZE,
    logoSizeMobile = logoSize,
    logoFullWidth = false,
) {
    const logoOnly = showBrand && ! showSiteName;
    const size = normalizeChromeLogoSize(logoSize);
    const sizeMobile = normalizeChromeLogoSize(logoSizeMobile);
    const wide = logoOnly || logoFullWidth;

    el.querySelectorAll('[data-voodbuilder-footer-brand-link]').forEach((link) => {
        link.classList.toggle('w-full', wide);
        link.classList.toggle('min-w-0', wide);
        link.setAttribute('data-voodbuilder-brand-logo-only', logoOnly ? '1' : '0');
        link.setAttribute('data-voodbuilder-brand-logo-full', logoFullWidth ? '1' : '0');
        link.setAttribute('data-voodbuilder-logo-size', size);
        link.setAttribute('data-voodbuilder-logo-size-mobile', sizeMobile);
    });

    el.querySelectorAll('[data-voodbuilder-chrome-part="logo"]').forEach((node) => {
        node.classList.toggle('hidden', ! showBrand);
        node.classList.toggle('contents', showBrand);
        node.classList.toggle('w-full', wide);
        if (showBrand) {
            node.removeAttribute('data-voodbuilder-chrome-hidden');
            node.removeAttribute('hidden');
        } else {
            node.setAttribute('data-voodbuilder-chrome-hidden', '');
        }
    });

    applyChromeLogoSizeClasses(el, size, {
        footerAvatar: true,
        sizeMobile,
        fullWidth: logoFullWidth,
    });

    el.querySelectorAll('[data-voodbuilder-chrome-part="site-name"]').forEach((node) => {
        node.classList.toggle('hidden', ! showSiteName);
        node.classList.toggle('ml-3', showSiteName);
        node.classList.toggle('text-xl', showSiteName);
    });
}

export function syncSiteFooterConfig(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];
    const config = {
        ...(component.get('vpressConfig') ?? {}),
        show_newsletter: component.get('vpressShowNewsletter') !== false,
        show_social: component.get('vpressShowSocial') !== false,
        show_footer_menu: component.get('vpressShowFooterMenu') === true,
        show_tagline: component.get('vpressShowTagline') !== false,
        show_copyright: component.get('vpressShowCopyright') !== false,
        show_brand: component.get('vpressShowBrand') !== false,
        show_site_name: component.get('vpressShowSiteName') !== false,
        [FOOTER_SOCIAL_ALIGN_KEY]: normalizeFooterSocialAlign(
            component.get(FOOTER_SOCIAL_ALIGN_PROP) ?? component.get('vpressConfig')?.[FOOTER_SOCIAL_ALIGN_KEY],
        ),
        [CHROME_LOGO_SIZE_KEY]: normalizeChromeLogoSize(
            component.get(CHROME_LOGO_SIZE_PROP) ?? component.get('vpressConfig')?.[CHROME_LOGO_SIZE_KEY],
        ),
        [CHROME_LOGO_SIZE_MOBILE_KEY]: normalizeChromeLogoSize(
            component.get(CHROME_LOGO_SIZE_MOBILE_PROP)
                ?? component.get('vpressConfig')?.[CHROME_LOGO_SIZE_MOBILE_KEY]
                ?? component.get(CHROME_LOGO_SIZE_PROP)
                ?? component.get('vpressConfig')?.[CHROME_LOGO_SIZE_KEY],
        ),
        [CHROME_LOGO_FULL_WIDTH_KEY]: component.get(CHROME_LOGO_FULL_WIDTH_PROP) === true
            || component.get('vpressConfig')?.[CHROME_LOGO_FULL_WIDTH_KEY] === true,
    };

    for (const def of chromeLogoFieldDefs()) {
        const value = String(component.get(def.prop) ?? '').trim();
        config[def.key] = value !== '' ? value : null;
    }

    if (footerBlockHasColumns(blockId)) {
        config.footer_columns_redistribute = component.get('vpressFooterColumnsRedistribute') === true;

        for (let index = 1; index <= 4; index++) {
            config[`show_footer_col_${index}`] = component.get(`vpressShowFooterCol${index}`) === true;
        }

        config.columns = Math.max(1, countVisibleFooterColumns(component));
    }

    component.set('vpressConfig', config, { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeVpressConfig(config),
    }, { silent: true });
}

function scheduleSiteFooterBlockRefresh(editor, root) {
    const existing = footerRefreshTimers.get(root);

    if (existing) {
        window.clearTimeout(existing);
    }

    footerRefreshTimers.set(root, window.setTimeout(() => {
        footerRefreshTimers.delete(root);
        delete root.__voodbuilderLastDynamicRenderFingerprint;
        delete root.__voodbuilderLastDynamicRenderHtml;
        editor.trigger('voodbuilder:refresh-dynamic-block', root);
    }, 120));
}

export function applySiteFooterSettingChange(editor, root, name, value) {
    runWithSettingsChangeGuard(editor, () => {
        if (typeof value === 'boolean') {
            root.set(name, value, { silent: true });
        } else if (typeof value === 'string') {
            root.set(name, value, { silent: true });
        }

        syncSiteFooterConfig(root);
        applySiteFooterSettingsPreview(root, editor, {
            invalidateCss: name === CHROME_LOGO_SIZE_PROP
                || name === CHROME_LOGO_SIZE_MOBILE_PROP
                || name === CHROME_LOGO_FULL_WIDTH_PROP
                || name === FOOTER_SOCIAL_ALIGN_PROP,
        });

        // Logo URL changes need a server re-render; visibility toggles are DOM-only
        // so Brand tab state and logo/name independence stay intact.
        if (FOOTER_LOGO_PROPS.has(name)) {
            scheduleSiteFooterBlockRefresh(editor, root);
        }
    });
}

function ensureSiteFooterTaglineSlot(root, editor = null) {
    const el = root.getEl?.();

    if (! el || el.querySelector('[data-voodbuilder-footer-tagline]')) {
        return;
    }

    const brand = el.querySelector('[data-voodbuilder-chrome="brand"]');

    if (! brand?.parentNode) {
        return;
    }

    const blockId = root.getAttributes()['data-voodbuilder-block'];
    const tagline = document.createElement('p');
    tagline.className = blockId === 'site_footer_social'
        ? 'mt-1 max-w-xs text-center text-xs text-vp-text-2 sm:text-left'
        : 'mt-4 text-sm text-vp-text-2';
    tagline.setAttribute('data-voodbuilder-footer-tagline', '');
    tagline.setAttribute('data-voodbuilder-chrome', 'tagline');
    tagline.textContent = editor?.__voodbuilderLabels?.footerDefaultTagline ?? 'Short description for your brand.';

    brand.parentNode.insertBefore(tagline, brand.nextSibling);
}

export function configureSiteFooterTraits(component, editor = null) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (! isFooterBlock(blockId)) {
        return;
    }

    component.set('stylable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });
    component.set('badgable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });

    const config = component.get('vpressConfig') ?? {};
    const columnVisibility = readFooterColumnVisibilityFromConfig(config);

    for (let index = 1; index <= 4; index++) {
        component.set(`vpressShowFooterCol${index}`, columnVisibility[index] === true, { silent: true });
    }

    component.set('vpressShowNewsletter', config.show_newsletter !== false, { silent: true });
    component.set('vpressShowSocial', config.show_social !== false, { silent: true });
    component.set('vpressShowFooterMenu', config.show_footer_menu !== false, { silent: true });
    component.set('vpressShowTagline', resolveShowTaglineFromConfig(config, blockId), { silent: true });
    component.set('vpressShowCopyright', config.show_copyright !== false, { silent: true });
    component.set('vpressShowBrand', config.show_brand !== false, { silent: true });
    component.set('vpressShowSiteName', config.show_site_name !== false, { silent: true });
    component.set('vpressFooterColumnsRedistribute', config.footer_columns_redistribute === true, { silent: true });
    component.set(
        FOOTER_SOCIAL_ALIGN_PROP,
        normalizeFooterSocialAlign(config[FOOTER_SOCIAL_ALIGN_KEY] ?? FOOTER_SOCIAL_ALIGN_DEFAULT),
        { silent: true },
    );
    component.set(
        CHROME_LOGO_SIZE_PROP,
        normalizeChromeLogoSize(config[CHROME_LOGO_SIZE_KEY] ?? CHROME_LOGO_DEFAULT_SIZE),
        { silent: true },
    );
    component.set(
        CHROME_LOGO_SIZE_MOBILE_PROP,
        normalizeChromeLogoSize(
            config[CHROME_LOGO_SIZE_MOBILE_KEY] ?? config[CHROME_LOGO_SIZE_KEY] ?? CHROME_LOGO_DEFAULT_SIZE,
        ),
        { silent: true },
    );
    component.set(CHROME_LOGO_FULL_WIDTH_PROP, config[CHROME_LOGO_FULL_WIDTH_KEY] === true, { silent: true });

    for (const def of chromeLogoFieldDefs()) {
        component.set(def.prop, config[def.key] ?? '', { silent: true });
    }

    if (typeof component.setTraits === 'function') {
        component.setTraits([]);
    } else {
        component.set('traits', []);
        component.getTraits?.();
    }

    ensureSiteFooterTaglineSlot(component, editor);
    applySiteFooterSettingsPreview(component, editor);
}

export function applySiteFooterColumns(component, columns) {
    const visibility = readFooterColumnVisibilityFromConfig({ columns });

    for (let index = 1; index <= 4; index++) {
        component.set(`vpressShowFooterCol${index}`, visibility[index] === true, { silent: true });
    }

    syncSiteFooterConfig(component);
    applySiteFooterSettingsPreview(component);
}
