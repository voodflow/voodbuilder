/**
 * Footer config sync + preview.
 */

import { encodeBlockConfig } from '../../../voodbuilder-dynamic-config.js';
import { runWithSettingsChangeGuard } from '../../../blocks/settings/ui.js';
import {
    applyChromeLogoSizeClasses,
    applyChromeLogoUrlsPreview,
    chromeLogoFieldDefs,
    CHROME_LOGO_DEFAULT_SIZE,
    CHROME_LOGO_DEFAULT_SHAPE,
    CHROME_LOGO_FULL_WIDTH_KEY,
    CHROME_LOGO_FULL_WIDTH_PROP,
    CHROME_LOGO_SHAPE_KEY,
    CHROME_LOGO_SHAPE_PROP,
    CHROME_LOGO_SIZE_KEY,
    CHROME_LOGO_SIZE_MOBILE_KEY,
    CHROME_LOGO_SIZE_MOBILE_PROP,
    CHROME_LOGO_SIZE_PROP,
    normalizeChromeLogoShape,
    normalizeChromeLogoSize,
} from '../../../editor-form-ui.js';
import { retagCurrentYear, replaceGlobalTextTags, globalTextTagValues } from '../../../global-text-tags.js';
import { setChromeVisible } from '../../visibility.js';
import { isFooterBlock } from '../../ids.js';

const footerRefreshTimers = new WeakMap();

const FOOTER_LOGO_PROPS = new Set(chromeLogoFieldDefs().map((def) => def.prop));

export const FOOTER_TAGLINE_PROP = 'voodbuilderTagline';
export const FOOTER_COPYRIGHT_PROP = 'voodbuilderCopyright';
export const FOOTER_DEFAULT_COPYRIGHT = '© {current_year} {brand_name}';

export const FOOTER_SOCIAL_ALIGN_PROP = 'voodbuilderSocialAlign';
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
    return [1, 2, 3, 4].filter((index) => root.get(`voodbuilderShowFooterCol${index}`) === true).length;
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

    const redistribute = root.get('voodbuilderFooterColumnsRedistribute') === true;
    const visibleCount = countVisibleFooterColumns(root);

    el.querySelectorAll('[data-voodbuilder-footer-menu-cols]').forEach((node) => {
        node.classList.toggle('flex', redistribute);
        node.classList.toggle('flex-wrap', redistribute);
        node.classList.toggle('lg:flex-nowrap', redistribute);
        node.classList.toggle('grid', ! redistribute);
        node.classList.toggle('grid-cols-1', ! redistribute);
        node.classList.toggle('lg:grid-cols-4', ! redistribute);
        node.classList.toggle('lg:justify-end', redistribute && visibleCount === 1);
        node.setAttribute('data-voodbuilder-footer-columns-redistribute', redistribute ? '1' : '0');
    });

    el.querySelectorAll('[data-voodbuilder-footer-col]').forEach((col) => {
        const index = Number(col.getAttribute('data-voodbuilder-footer-col'));
        const visible = root.get(`voodbuilderShowFooterCol${index}`) === true;

        col.classList.toggle('basis-full', redistribute);
        col.classList.toggle('lg:mb-0', redistribute);
        col.classList.toggle('lg:basis-0', redistribute && visible);
        col.classList.toggle('lg:flex-1', redistribute && visible);

        for (let columnIndex = 1; columnIndex <= 4; columnIndex++) {
            col.classList.toggle(`lg:col-start-${columnIndex}`, ! redistribute && visible && index === columnIndex);
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

    const showNewsletter = root.get('voodbuilderShowNewsletter') !== false;
    const showSocial = root.get('voodbuilderShowSocial') !== false;
    const showMenu = root.get('voodbuilderShowFooterMenu') === true;
    const showTagline = root.get('voodbuilderShowTagline') !== false;
    const showCopyright = root.get('voodbuilderShowCopyright') !== false;
    const showLogoDesktop = root.get('voodbuilderShowLogoDesktop') !== false;
    const showLogoMobile = root.get('voodbuilderShowLogoMobile') !== false;
    const showNameDesktop = root.get('voodbuilderShowSiteNameDesktop') !== false;
    const showNameMobile = root.get('voodbuilderShowSiteNameMobile') !== false;
    const showBrand = showLogoDesktop || showLogoMobile;
    const showSiteName = showNameDesktop || showNameMobile;
    const logoSize = normalizeChromeLogoSize(root.get(CHROME_LOGO_SIZE_PROP));
    const logoSizeMobile = normalizeChromeLogoSize(
        root.get(CHROME_LOGO_SIZE_MOBILE_PROP) ?? logoSize,
    );
    const logoFullWidth = root.get(CHROME_LOGO_FULL_WIDTH_PROP) === true;
    const logoShape = normalizeChromeLogoShape(root.get(CHROME_LOGO_SHAPE_PROP));
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
            setFooterChromeVisible(node, root.get(`voodbuilderShowFooterCol${index}`) === true);
        }
    });

    applyFooterBrandPartsPreview(el, {
        showBrand,
        showSiteName,
        showLogoDesktop,
        showLogoMobile,
        showNameDesktop,
        showNameMobile,
        logoSize,
        logoSizeMobile,
        logoFullWidth,
        logoShape,
    });
    applyChromeLogoUrlsPreview(el, root);
    applyFooterCopyPreview(el, root, editor);
    applyFooterSocialAlignPreview(el, socialAlign);

    if (footerBlockHasColumns(blockId)) {
        applySiteFooterMenuColumnsLayout(root);
    }

    if (options.invalidateCss && editor?.__voodbuilderChromeLayoutMode) {
        editor.trigger?.('voodbuilder:page-css-invalidate');
    }
}

/**
 * Resolve tagline / copyright templates into the canvas (keeps {tags} in config).
 *
 * @param {HTMLElement} el
 * @param {object} root
 * @param {object|null} editor
 */
export function applyFooterCopyPreview(el, root, editor = null) {
    const values = globalTextTagValues(editor);
    const defaultTagline = editor?.__voodbuilderLabels?.footerDefaultTagline
        ?? 'A Visual CMS for Laravel & Filament';
    const taglineTemplate = String(root.get(FOOTER_TAGLINE_PROP) ?? '').trim() || defaultTagline;
    const copyrightTemplate = String(root.get(FOOTER_COPYRIGHT_PROP) ?? '').trim() || FOOTER_DEFAULT_COPYRIGHT;
    const taglineText = replaceGlobalTextTags(taglineTemplate, values);
    const copyrightText = replaceGlobalTextTags(copyrightTemplate, values);

    el.querySelectorAll('[data-voodbuilder-footer-tagline]').forEach((node) => {
        node.textContent = taglineText;
    });

    el.querySelectorAll('[data-voodbuilder-footer-copyright]').forEach((node) => {
        node.textContent = copyrightText;
    });

    // GrapesJS serializes the component tree, not the live DOM — keep model text
    // in sync so save/publish does not revert to the Blade default slogan.
    syncFooterCopyComponentText(root, 'data-voodbuilder-footer-tagline', taglineText);
    syncFooterCopyComponentText(root, 'data-voodbuilder-footer-copyright', copyrightText);
}

/**
 * @param {object} root
 * @param {string} attr
 * @param {string} text
 */
function syncFooterCopyComponentText(root, attr, text) {
    if (! root || typeof root.find !== 'function') {
        return;
    }

    let matches = [];

    try {
        matches = root.find(`[${attr}]`) ?? [];
    } catch {
        return;
    }

    const list = typeof matches.forEach === 'function'
        ? matches
        : (Array.isArray(matches) ? matches : []);

    list.forEach((component) => {
        if (! component || typeof component.set !== 'function') {
            return;
        }

        const children = typeof component.components === 'function'
            ? component.components()
            : null;

        if (children && typeof children.forEach === 'function' && children.length > 0) {
            let updated = false;

            children.forEach((child) => {
                if (! child || typeof child.set !== 'function') {
                    return;
                }

                const childType = child.get?.('type');
                const nested = typeof child.components === 'function' ? child.components() : null;
                const isLeaf = ! nested || nested.length === 0;

                if (childType === 'textnode' || isLeaf) {
                    child.set('content', text, { silent: true });
                    updated = true;
                }
            });

            if (updated) {
                return;
            }
        }

        component.set('content', text, { silent: true });
    });
}

/**
 * Toggle logo vs site name independently inside the brand chrome (no full remount).
 *
 * @param {HTMLElement} el
 * @param {{
 *   showBrand: boolean,
 *   showSiteName: boolean,
 *   showLogoDesktop: boolean,
 *   showLogoMobile: boolean,
 *   showNameDesktop: boolean,
 *   showNameMobile: boolean,
 *   logoSize?: string,
 *   logoSizeMobile?: string,
 *   logoFullWidth?: boolean,
 *   logoShape?: string,
 * }} opts
 */
function applyFooterBrandPartsPreview(el, opts) {
    const showBrand = opts.showBrand !== false;
    const showSiteName = opts.showSiteName !== false;
    const showLogoDesktop = opts.showLogoDesktop !== false;
    const showLogoMobile = opts.showLogoMobile !== false;
    const showNameDesktop = opts.showNameDesktop !== false;
    const showNameMobile = opts.showNameMobile !== false;
    const logoSize = normalizeChromeLogoSize(opts.logoSize);
    const logoSizeMobile = normalizeChromeLogoSize(opts.logoSizeMobile ?? opts.logoSize);
    const logoFullWidth = opts.logoFullWidth === true;
    const logoShape = normalizeChromeLogoShape(opts.logoShape);
    const logoOnly = showBrand && ! showSiteName;
    const wide = logoOnly || logoFullWidth;

    el.querySelectorAll('[data-voodbuilder-footer-brand-link]').forEach((link) => {
        link.classList.toggle('w-full', wide);
        link.classList.toggle('min-w-0', wide);
        link.setAttribute('data-voodbuilder-brand-logo-only', logoOnly ? '1' : '0');
        link.setAttribute('data-voodbuilder-brand-logo-full', logoFullWidth ? '1' : '0');
        link.setAttribute('data-voodbuilder-logo-size', logoSize);
        link.setAttribute('data-voodbuilder-logo-size-mobile', logoSizeMobile);
        link.setAttribute('data-voodbuilder-logo-shape', logoShape);
        link.setAttribute('data-vb-show-logo-desktop', showLogoDesktop ? '1' : '0');
        link.setAttribute('data-vb-show-logo-mobile', showLogoMobile ? '1' : '0');
        link.setAttribute('data-vb-show-name-desktop', showNameDesktop ? '1' : '0');
        link.setAttribute('data-vb-show-name-mobile', showNameMobile ? '1' : '0');
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

    applyChromeLogoSizeClasses(el, logoSize, {
        sizeMobile: logoSizeMobile,
        fullWidth: logoFullWidth,
        shape: logoShape,
    });

    el.querySelectorAll('[data-voodbuilder-chrome-part="site-name"]').forEach((node) => {
        node.classList.toggle('hidden', ! showSiteName);
        node.classList.toggle('ml-3', showSiteName);
        node.classList.toggle('text-xl', showSiteName);
    });
}

export function syncSiteFooterConfig(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];
    const el = component.getEl?.();
    const showLogoDesktop = component.get('voodbuilderShowLogoDesktop') !== false;
    const showLogoMobile = component.get('voodbuilderShowLogoMobile') !== false;
    const showNameDesktop = component.get('voodbuilderShowSiteNameDesktop') !== false;
    const showNameMobile = component.get('voodbuilderShowSiteNameMobile') !== false;
    const config = {
        ...(component.get('voodbuilderConfig') ?? {}),
        show_newsletter: component.get('voodbuilderShowNewsletter') !== false,
        show_social: component.get('voodbuilderShowSocial') !== false,
        show_footer_menu: component.get('voodbuilderShowFooterMenu') === true,
        show_tagline: component.get('voodbuilderShowTagline') !== false,
        show_copyright: component.get('voodbuilderShowCopyright') !== false,
        show_logo_desktop: showLogoDesktop,
        show_logo_mobile: showLogoMobile,
        show_site_name_desktop: showNameDesktop,
        show_site_name_mobile: showNameMobile,
        show_brand: showLogoDesktop || showLogoMobile,
        show_site_name: showNameDesktop || showNameMobile,
        [FOOTER_SOCIAL_ALIGN_KEY]: normalizeFooterSocialAlign(
            component.get(FOOTER_SOCIAL_ALIGN_PROP) ?? component.get('voodbuilderConfig')?.[FOOTER_SOCIAL_ALIGN_KEY],
        ),
        [CHROME_LOGO_SIZE_KEY]: normalizeChromeLogoSize(
            component.get(CHROME_LOGO_SIZE_PROP) ?? component.get('voodbuilderConfig')?.[CHROME_LOGO_SIZE_KEY],
        ),
        [CHROME_LOGO_SIZE_MOBILE_KEY]: normalizeChromeLogoSize(
            component.get(CHROME_LOGO_SIZE_MOBILE_PROP)
                ?? component.get('voodbuilderConfig')?.[CHROME_LOGO_SIZE_MOBILE_KEY]
                ?? component.get(CHROME_LOGO_SIZE_PROP)
                ?? component.get('voodbuilderConfig')?.[CHROME_LOGO_SIZE_KEY],
        ),
        [CHROME_LOGO_FULL_WIDTH_KEY]: component.get(CHROME_LOGO_FULL_WIDTH_PROP) === true
            || component.get('voodbuilderConfig')?.[CHROME_LOGO_FULL_WIDTH_KEY] === true,
        [CHROME_LOGO_SHAPE_KEY]: normalizeChromeLogoShape(
            component.get(CHROME_LOGO_SHAPE_PROP) ?? component.get('voodbuilderConfig')?.[CHROME_LOGO_SHAPE_KEY],
        ),
    };

    for (const def of chromeLogoFieldDefs()) {
        const value = String(component.get(def.prop) ?? '').trim();
        config[def.key] = value !== '' ? value : null;
    }

    // Settings props own templates (may include {current_year}). Empty → PHP defaults.
    const taglineProp = component.get(FOOTER_TAGLINE_PROP);
    const copyrightProp = component.get(FOOTER_COPYRIGHT_PROP);

    if (typeof taglineProp === 'string') {
        const trimmed = taglineProp.trim();
        config.tagline = trimmed !== '' ? trimmed : null;
    } else {
        const taglineFromDom = String(el?.querySelector?.('[data-voodbuilder-footer-tagline]')?.textContent ?? '').trim();
        config.tagline = taglineFromDom !== '' ? taglineFromDom : (config.tagline ?? null);
    }

    if (typeof copyrightProp === 'string') {
        const trimmed = copyrightProp.trim();
        config.copyright = trimmed !== '' ? retagCurrentYear(trimmed) : null;
    } else {
        const copyrightFromDom = String(el?.querySelector?.('[data-voodbuilder-footer-copyright]')?.textContent ?? '').trim();
        config.copyright = copyrightFromDom !== ''
            ? retagCurrentYear(copyrightFromDom)
            : (config.copyright ?? null);
    }

    if (footerBlockHasColumns(blockId)) {
        config.footer_columns_redistribute = component.get('voodbuilderFooterColumnsRedistribute') === true;

        for (let index = 1; index <= 4; index++) {
            config[`show_footer_col_${index}`] = component.get(`voodbuilderShowFooterCol${index}`) === true;
        }

        config.columns = Math.max(1, countVisibleFooterColumns(component));
    }

    component.set('voodbuilderConfig', config, { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeBlockConfig(config),
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
                || name === CHROME_LOGO_SHAPE_PROP
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
    tagline.textContent = editor?.__voodbuilderLabels?.footerDefaultTagline
        ?? 'A Visual CMS for Laravel & Filament';

    brand.parentNode.insertBefore(tagline, brand.nextSibling);
}

export function configureSiteFooterTraits(component, editor = null) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (! isFooterBlock(blockId)) {
        return;
    }

    component.set('stylable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });
    component.set('badgable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });

    const config = component.get('voodbuilderConfig') ?? {};
    const columnVisibility = readFooterColumnVisibilityFromConfig(config);

    for (let index = 1; index <= 4; index++) {
        component.set(`voodbuilderShowFooterCol${index}`, columnVisibility[index] === true, { silent: true });
    }

    const showBrand = config.show_brand !== false;
    const showSiteName = config.show_site_name !== false;
    const showLogoDesktop = Object.prototype.hasOwnProperty.call(config, 'show_logo_desktop')
        ? config.show_logo_desktop !== false
        : showBrand;
    const showLogoMobile = Object.prototype.hasOwnProperty.call(config, 'show_logo_mobile')
        ? config.show_logo_mobile !== false
        : showBrand;
    const showNameDesktop = Object.prototype.hasOwnProperty.call(config, 'show_site_name_desktop')
        ? config.show_site_name_desktop !== false
        : showSiteName;
    const showNameMobile = Object.prototype.hasOwnProperty.call(config, 'show_site_name_mobile')
        ? config.show_site_name_mobile !== false
        : showSiteName;

    component.set('voodbuilderShowNewsletter', config.show_newsletter !== false, { silent: true });
    component.set('voodbuilderShowSocial', config.show_social !== false, { silent: true });
    component.set('voodbuilderShowFooterMenu', config.show_footer_menu !== false, { silent: true });
    component.set('voodbuilderShowTagline', resolveShowTaglineFromConfig(config, blockId), { silent: true });
    component.set('voodbuilderShowCopyright', config.show_copyright !== false, { silent: true });
    component.set('voodbuilderShowLogoDesktop', showLogoDesktop, { silent: true });
    component.set('voodbuilderShowLogoMobile', showLogoMobile, { silent: true });
    component.set('voodbuilderShowSiteNameDesktop', showNameDesktop, { silent: true });
    component.set('voodbuilderShowSiteNameMobile', showNameMobile, { silent: true });
    component.set('voodbuilderShowBrand', showLogoDesktop || showLogoMobile, { silent: true });
    component.set('voodbuilderShowSiteName', showNameDesktop || showNameMobile, { silent: true });
    component.set('voodbuilderFooterColumnsRedistribute', config.footer_columns_redistribute === true, { silent: true });
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
    component.set(
        CHROME_LOGO_SHAPE_PROP,
        normalizeChromeLogoShape(config[CHROME_LOGO_SHAPE_KEY] ?? CHROME_LOGO_DEFAULT_SHAPE),
        { silent: true },
    );

    for (const def of chromeLogoFieldDefs()) {
        component.set(def.prop, config[def.key] ?? '', { silent: true });
    }

    component.set(FOOTER_TAGLINE_PROP, typeof config.tagline === 'string' ? config.tagline : '', { silent: true });
    component.set(
        FOOTER_COPYRIGHT_PROP,
        typeof config.copyright === 'string' ? config.copyright : '',
        { silent: true },
    );

    if (typeof component.setTraits === 'function') {
        component.setTraits([]);
    } else {
        component.set('traits', []);
        component.getTraits?.();
    }

    ensureSiteFooterTaglineSlot(component, editor);
    applySiteFooterSettingsPreview(component, editor);
    registerFooterTextSync(editor);
}

/**
 * Persist tagline/copyright edits into voodbuilderConfig when leaving the RTE.
 *
 * @param {object|null} editor
 */
export function registerFooterTextSync(editor) {
    if (! editor || editor.__voodbuilderFooterTextSyncRegistered) {
        return;
    }

    editor.__voodbuilderFooterTextSyncRegistered = true;

    editor.on('rte:disable', (payload) => {
        const component = payload?.model ?? payload;
        let node = component;

        while (node) {
            const blockId = node.getAttributes?.()?.['data-voodbuilder-block'];

            if (isFooterBlock(blockId)) {
                const taglineEl = node.getEl?.()?.querySelector?.('[data-voodbuilder-footer-tagline]');
                const copyrightEl = node.getEl?.()?.querySelector?.('[data-voodbuilder-footer-copyright]');
                const taglineText = String(taglineEl?.textContent ?? '').trim();
                const copyrightText = String(copyrightEl?.textContent ?? '').trim();
                const currentTagline = String(node.get(FOOTER_TAGLINE_PROP) ?? '');
                const currentCopyright = String(node.get(FOOTER_COPYRIGHT_PROP) ?? '');

                // Do not overwrite templates that still contain {tags} with resolved canvas text.
                if (taglineText !== '' && ! currentTagline.includes('{')) {
                    node.set(FOOTER_TAGLINE_PROP, taglineText, { silent: true });
                }

                if (copyrightText !== '' && ! currentCopyright.includes('{')) {
                    node.set(FOOTER_COPYRIGHT_PROP, retagCurrentYear(copyrightText), { silent: true });
                }

                syncSiteFooterConfig(node);

                return;
            }

            node = typeof node.parent === 'function' ? node.parent() : null;
        }
    });
}

export function applySiteFooterColumns(component, columns) {
    const visibility = readFooterColumnVisibilityFromConfig({ columns });

    for (let index = 1; index <= 4; index++) {
        component.set(`voodbuilderShowFooterCol${index}`, visibility[index] === true, { silent: true });
    }

    syncSiteFooterConfig(component);
    applySiteFooterSettingsPreview(component);
}
