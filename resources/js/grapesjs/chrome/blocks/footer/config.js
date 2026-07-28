/**
 * Footer config sync + preview.
 */

import { encodeVpressConfig } from '../../../voodbuilder-dynamic-config.js';
import { runWithSettingsChangeGuard } from '../../../blocks/settings/ui.js';
import { chromeLogoFieldDefs } from '../../../editor-form-ui.js';
import { setChromeVisible } from '../../visibility.js';
import { isFooterBlock } from '../../ids.js';

const footerRefreshTimers = new WeakMap();

const FOOTER_LOGO_PROPS = new Set(chromeLogoFieldDefs().map((def) => def.prop));

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

export function applySiteFooterSettingsPreview(root, editor = null) {
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

    const showNewsletter = root.get('vpressShowNewsletter') === true;
    const showSocial = root.get('vpressShowSocial') === true;
    const showMenu = root.get('vpressShowFooterMenu') === true;
    const showTagline = root.get('vpressShowTagline') === true;
    const showCopyright = root.get('vpressShowCopyright') === true;
    const showBrand = root.get('vpressShowBrand') === true;
    const showSiteName = root.get('vpressShowSiteName') !== false;

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

    applyFooterBrandPartsPreview(el, showBrand, showSiteName);

    if (footerBlockHasColumns(blockId)) {
        applySiteFooterMenuColumnsLayout(root);
    }

    if (editor?.__voodbuilderChromeLayoutMode) {
        editor.trigger?.('voodbuilder:page-css-invalidate');
    }
}

/**
 * Toggle logo vs site name independently inside the brand chrome (no full remount).
 *
 * @param {HTMLElement} el
 * @param {boolean} showBrand
 * @param {boolean} showSiteName
 */
function applyFooterBrandPartsPreview(el, showBrand, showSiteName) {
    const logoOnly = showBrand && ! showSiteName;

    el.querySelectorAll('[data-voodbuilder-footer-brand-link]').forEach((link) => {
        link.classList.toggle('w-full', logoOnly);
        link.classList.toggle('min-w-0', logoOnly);
        link.setAttribute('data-voodbuilder-brand-logo-only', logoOnly ? '1' : '0');
    });

    el.querySelectorAll('[data-voodbuilder-chrome-part="logo"]').forEach((node) => {
        node.classList.toggle('hidden', ! showBrand);

        node.querySelectorAll('img.vb-brand-logo').forEach((img) => {
            const isDesktop = img.classList.contains('vb-brand-logo--desktop');
            img.classList.toggle('h-12', logoOnly && isDesktop);
            img.classList.toggle('h-10', ! (logoOnly && isDesktop) || ! isDesktop);
            img.classList.toggle('w-auto', logoOnly);
            img.classList.toggle('max-w-full', logoOnly);
            img.classList.toggle('object-contain', logoOnly);
            img.classList.toggle('object-left', logoOnly);
            img.classList.toggle('w-10', ! logoOnly);
            img.classList.toggle('rounded-full', ! logoOnly);
            img.classList.toggle('object-cover', ! logoOnly);
        });
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
        show_newsletter: component.get('vpressShowNewsletter') === true,
        show_social: component.get('vpressShowSocial') === true,
        show_footer_menu: component.get('vpressShowFooterMenu') === true,
        show_tagline: component.get('vpressShowTagline') === true,
        show_copyright: component.get('vpressShowCopyright') === true,
        show_brand: component.get('vpressShowBrand') === true,
        show_site_name: component.get('vpressShowSiteName') !== false,
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
        applySiteFooterSettingsPreview(root, editor);

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
