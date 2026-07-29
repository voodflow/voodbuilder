/**
 * Navbar config sync + preview — extracted from plugin for chrome domain layer.
 */

import { encodeVpressConfig } from '../../../voodbuilder-dynamic-config.js';
import { resolveSettings } from '../../../blocks/settings/index.js';
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
import { isNavBlock } from '../../ids.js';
import { migrateNavId } from './preview.js';

/** Logo URL changes need a server re-render to inject new <img> variants. */
const SITE_NAV_SERVER_REFRESH_PROPS = new Set(
    chromeLogoFieldDefs().map((def) => def.prop),
);

const siteNavRefreshTimers = new WeakMap();

export function navSettingLabel(editor, key, fallback) {
    return editor?.__voodbuilderLabels?.[key] ?? fallback;
}

function siteHeaderTraitOptions(editor = null) {
    const label = (key, fallback) => navSettingLabel(editor, key, fallback);

    return [
        {
            type: 'checkbox',
            label: label('navShowLogo', 'Show logo'),
            name: 'vpressShowLogo',
            changeProp: true,
        },
        {
            type: 'checkbox',
            label: label('navShowSiteName', 'Show site name'),
            name: 'vpressShowSiteName',
            changeProp: true,
        },
        {
            type: 'select',
            label: label('navMenuPosition', 'Menu position'),
            name: 'vpressMainNavAlign',
            changeProp: true,
            options: [
                { value: 'start', id: 'start', name: label('navMenuLeft', 'Left (next to logo)') },
                { value: 'center', id: 'center', name: label('navMenuCenter', 'Center') },
            ],
        },
        {
            type: 'select',
            label: label('navSticky', 'Sticky'),
            name: 'vpressStickyNav',
            changeProp: true,
            options: [
                { value: 'inherit', id: 'inherit', name: label('navStickyInherit', 'Site default') },
                { value: 'sticky', id: 'sticky', name: label('navStickyOn', 'Sticky') },
                { value: 'static', id: 'static', name: label('navStickyOff', 'Scrolls with page') },
            ],
        },
        {
            type: 'checkbox',
            label: label('navShowSearch', 'Show search'),
            name: 'vpressShowSearch',
            changeProp: true,
        },
        {
            type: 'checkbox',
            label: label('navShowNotifications', 'Show notifications'),
            name: 'vpressShowNotifications',
            changeProp: true,
        },
        {
            type: 'checkbox',
            label: label('navShowProfile', 'Show account menu'),
            name: 'vpressShowProfileMenu',
            changeProp: true,
        },
    ];
}

function resolveSiteNavStickyState(stickyMode, editor) {
    const siteDefaultSticky = editor?.__voodbuilderSiteNavDefaults?.stickyNav === true;

    if (stickyMode === 'static') {
        return { pinned: false, spacer: false };
    }

    if (stickyMode === 'sticky') {
        return { pinned: true, spacer: true };
    }

    return { pinned: siteDefaultSticky, spacer: siteDefaultSticky };
}

function setNavChromeVisible(node, visible) {
    setChromeVisible(node, visible);
}

/**
 * Blade renders different DOM for start vs center align — CSS class toggles alone
 * cannot move the menu. Reshape the live canvas DOM so the settings preview updates instantly.
 *
 * @param {ParentNode} scope
 * @param {boolean} alignCenter
 */
function reshapeSiteNavAlignDom(scope, alignCenter) {
    const header = scope.querySelector?.('header[role="banner"], header');
    const row = header?.querySelector?.('.voodbuilder-nav__row') ?? scope.querySelector?.('.voodbuilder-nav__row');

    if (! row) {
        return;
    }

    const desktopNav = row.querySelector('[data-voodbuilder-desktop-nav]');
    const actions = row.querySelector('.ml-auto');

    if (! desktopNav || ! actions) {
        return;
    }

    const brand = row.querySelector('[data-voodbuilder-brand], [data-voodbuilder-chrome="brand"], a[href].flex, a[href].inline-flex')
        ?? desktopNav.previousElementSibling;

    // Prefer the brand wrapper that sits beside the desktop nav in start layout.
    let brandEl = null;
    const startGroup = desktopNav.parentElement !== row ? desktopNav.parentElement : null;

    if (startGroup && startGroup !== row && startGroup.contains(desktopNav)) {
        brandEl = [...startGroup.children].find((child) => child !== desktopNav) ?? null;
    } else {
        brandEl = [...row.children].find((child) => (
            child !== desktopNav
            && child !== actions
            && ! child.hasAttribute?.('data-voodbuilder-desktop-nav')
            && ! child.classList?.contains('ml-auto')
        )) ?? brand;
    }

    if (! brandEl || brandEl === desktopNav || brandEl === actions) {
        return;
    }

    if (alignCenter) {
        row.classList.add('justify-between');
        desktopNav.classList.add('flex-1', 'justify-center', 'min-w-0');
        desktopNav.classList.remove('shrink-0');

        if (startGroup && startGroup !== row) {
            row.insertBefore(brandEl, actions);
            row.insertBefore(desktopNav, actions);

            if (startGroup.childNodes.length === 0) {
                startGroup.remove();
            }
        } else if (brandEl.parentElement === row) {
            row.insertBefore(brandEl, actions);
            row.insertBefore(desktopNav, actions);
        }
    } else {
        row.classList.remove('justify-between');
        desktopNav.classList.remove('flex-1', 'justify-center');

        if (desktopNav.parentElement === row && brandEl.parentElement === row) {
            const group = document.createElement('div');
            group.className = 'flex min-w-0 shrink-0 items-center gap-3 md:gap-4';
            row.insertBefore(group, actions);
            group.appendChild(brandEl);
            group.appendChild(desktopNav);
        }
    }
}

/**
 * Toggle logo vs site name independently inside the brand chrome (no full remount).
 *
 * @param {ParentNode} scope
 * @param {boolean} showLogo
 * @param {boolean} showSiteName
 */
function applyNavBrandPartsPreview(scope, showLogo, showSiteName) {
    scope.querySelectorAll('[data-voodbuilder-chrome="brand"]').forEach((brand) => {
        brand.querySelectorAll('[data-voodbuilder-chrome-part="logo"]').forEach((node) => {
            node.classList.toggle('hidden', ! showLogo);
            node.classList.toggle('contents', showLogo);
            if (showLogo) {
                node.removeAttribute('data-voodbuilder-chrome-hidden');
                node.removeAttribute('hidden');
            } else {
                node.setAttribute('data-voodbuilder-chrome-hidden', '');
            }
        });

        brand.querySelectorAll('[data-voodbuilder-chrome-part="site-name"]').forEach((node) => {
            node.classList.toggle('hidden', ! showSiteName && ! showLogo);
            node.classList.toggle('sr-only', ! showSiteName && showLogo);
            node.classList.toggle('whitespace-nowrap', showSiteName);
        });
    });
}

export function applySiteNavSettingsPreview(root, editor = null, options = {}) {
    // Same as footer: do not mutate chrome-shell nav classes in the page editor.
    if (editor?.__voodbuilderChromeShellMode && ! editor?.__voodbuilderChromeLayoutMode) {
        return;
    }

    const el = root.getEl?.();

    if (! el) {
        return;
    }

    const showSearch = root.get('vpressShowSearch') === true;
    const showNotifications = root.get('vpressShowNotifications') === true;
    const showProfile = root.get('vpressShowProfileMenu') === true;
    const showLogo = root.get('vpressShowLogo') !== false;
    const showSiteName = root.get('vpressShowSiteName') !== false;
    const alignCenter = root.get('vpressMainNavAlign') === 'center';
    const stickyMode = root.get('vpressStickyNav') ?? 'inherit';
    const logoSize = normalizeChromeLogoSize(root.get(CHROME_LOGO_SIZE_PROP));
    const logoSizeMobile = normalizeChromeLogoSize(
        root.get(CHROME_LOGO_SIZE_MOBILE_PROP) ?? logoSize,
    );
    const logoFullWidth = root.get(CHROME_LOGO_FULL_WIDTH_PROP) === true;
    const { pinned, spacer } = resolveSiteNavStickyState(stickyMode, editor);

    const scope = el.querySelector('[data-voodbuilder-editor-site-header]') ?? el;

    const siteHeader = scope.matches?.('[data-voodbuilder-editor-site-header]')
        ? scope
        : scope.querySelector('[data-voodbuilder-editor-site-header]');

    if (siteHeader) {
        siteHeader.classList.toggle('voodbuilder-site-header-spacer', spacer);
    }

    const header = scope.querySelector('header[role="banner"], header');

    if (header) {
        header.classList.remove('fixed', 'sticky', 'self-start', 'relative');
        header.classList.toggle('voodbuilder-nav-align-center', alignCenter);
        header.classList.toggle('voodbuilder-nav-align-start', ! alignCenter);

        if (pinned) {
            header.classList.add('fixed');
        } else {
            header.classList.add('relative');
        }
    }

    reshapeSiteNavAlignDom(scope, alignCenter);

    scope.querySelectorAll('[data-voodbuilder-chrome]').forEach((node) => {
        const kind = node.getAttribute('data-voodbuilder-chrome');

        if (kind === 'search') {
            setNavChromeVisible(node, showSearch);
        } else if (kind === 'notifications') {
            setNavChromeVisible(node, showNotifications);
        } else if (kind === 'profile') {
            setNavChromeVisible(node, showProfile);
        } else if (kind === 'brand') {
            setNavChromeVisible(node, showLogo || showSiteName);
        }
    });

    applyNavBrandPartsPreview(scope, showLogo, showSiteName);
    applyChromeLogoUrlsPreview(scope, root);
    applyChromeLogoSizeClasses(scope, logoSize, {
        sizeMobile: logoSizeMobile,
        fullWidth: logoFullWidth,
    });

    if (options.invalidateCss && editor?.__voodbuilderChromeLayoutMode) {
        editor.trigger?.('voodbuilder:page-css-invalidate');
    }
}

function scheduleSiteNavBlockRefresh(editor, root) {
    const existing = siteNavRefreshTimers.get(root);

    if (existing) {
        window.clearTimeout(existing);
    }

    siteNavRefreshTimers.set(root, window.setTimeout(() => {
        siteNavRefreshTimers.delete(root);
        delete root.__voodbuilderLastDynamicRenderFingerprint;
        delete root.__voodbuilderLastDynamicRenderHtml;
        editor.trigger('voodbuilder:refresh-dynamic-block', root);
    }, 120));
}

export function syncSiteHeaderConfig(component) {
    const align = component.get('vpressMainNavAlign') === 'center' ? 'center' : 'start';
    const stickyNav = ['inherit', 'sticky', 'static'].includes(component.get('vpressStickyNav'))
        ? component.get('vpressStickyNav')
        : 'inherit';
    const config = {
        ...(component.get('vpressConfig') ?? {}),
        variant: 'simple',
        main_nav_align: align,
        sticky_nav: stickyNav,
        show_search: component.get('vpressShowSearch') === true,
        show_notifications: component.get('vpressShowNotifications') === true,
        show_profile_menu: component.get('vpressShowProfileMenu') === true,
        show_logo: component.get('vpressShowLogo') !== false,
        show_site_name: component.get('vpressShowSiteName') !== false,
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

    component.set('vpressConfig', config, { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeVpressConfig(config),
    }, { silent: true });
}

export function applySiteNavSettingChange(editor, root, name, value) {
    runWithSettingsChangeGuard(editor, () => {
        if (typeof value === 'boolean') {
            root.set(name, value, { silent: true });
        } else if (typeof value === 'string') {
            root.set(name, value, { silent: true });
        }

        syncSiteHeaderConfig(root);
        applySiteNavSettingsPreview(root, editor, {
            invalidateCss: name === CHROME_LOGO_SIZE_PROP
                || name === CHROME_LOGO_SIZE_MOBILE_PROP
                || name === CHROME_LOGO_FULL_WIDTH_PROP,
        });

        // Logo URL changes need a server re-render; visibility / size are DOM-only.
        if (SITE_NAV_SERVER_REFRESH_PROPS.has(name)) {
            scheduleSiteNavBlockRefresh(editor, root);
        }
    });
}

export function configureSiteNavTraits(component, editor) {
    migrateNavId(component);

    if (! isNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
        return;
    }

    component.set('stylable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });
    component.set('badgable', Boolean(editor?.__voodbuilderChromeLayoutMode), { silent: true });

    const config = component.get('vpressConfig') ?? {};

    component.set('vpressMainNavAlign', config.main_nav_align === 'center' ? 'center' : 'start', { silent: true });
    component.set('vpressStickyNav', config.sticky_nav ?? 'inherit', { silent: true });
    component.set('vpressShowSearch', config.show_search === true, { silent: true });
    component.set('vpressShowNotifications', config.show_notifications === true, { silent: true });
    component.set('vpressShowProfileMenu', config.show_profile_menu === true, { silent: true });
    component.set('vpressShowLogo', config.show_logo !== false, { silent: true });
    component.set('vpressShowSiteName', config.show_site_name !== false, { silent: true });
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
        component.setTraits(siteHeaderTraitOptions(editor));
    } else {
        component.set('traits', siteHeaderTraitOptions(editor));
        component.getTraits?.();
    }

    applySiteNavSettingsPreview(component, editor);

    if (
        editor?.TraitManager
        && ! editor.__voodbuilderChromeLayoutMode
        && editor.getSelected?.() === component
        && ! resolveSettings(component, editor).descriptor
    ) {
        editor.TraitManager.select(component);
    }
}

/** @deprecated */
export const syncNavConfig = syncSiteHeaderConfig;
