/**
 * Voodbuilder GrapesJS plugin — uses the public GrapesJS plugin API only.
 * @see https://grapesjs.com/docs/modules/Plugins.html
 */

import {
    clearBackgroundCssRules,
    pruneRedundantSpacingZeros,
    resolveVisualStyleTarget,
    safeFindComponents,
    safeGetClasses,
    walkComponentTree,
} from '../tailwind-visual-style.js';
import { registerBoundComponentType } from '../bindings-ui.js';
import { registerTopDropSpacerType } from '../canvas-block-drag.js';
import { resolveBlockLayerLabel } from '../layer-display-name.js';
import { registerComponentInstanceType } from '../component-instance-type.js';
import { encodeVpressConfig, parseVpressConfig } from '../voodbuilder-dynamic-config.js';
import { isComponentCategoryId } from '../component-block-utils.js';
import { resolveCategoryOrder, normalizeCategoryLabel } from '../section-block-meta.js';
import { createCheckboxField, createFormSection, createSelectField } from '../editor-form-ui.js';
import { registerBlockSettings, resolveSettings } from '../blocks/settings/index.js';
import {
    CHROME_DROP_ZONE_ATTR,
    findPrimaryBlockInChromeDropZone,
} from '../chrome-content-slot-utils.js';
import { isFooterBlock, isHeaderBlock, isNavBlock, resolveNavId } from '../chrome/ids.js';
import { lockChromePreview } from '../chrome/blocks/preview.js';
import { registerNavSettings } from '../chrome/blocks/nav/settings.js';
import { registerFooterSettings } from '../chrome/blocks/footer/settings.js';
import {
    applySiteFooterColumns,
    applySiteFooterSettingsPreview,
    configureSiteFooterTraits,
    syncSiteFooterConfig,
} from '../chrome/blocks/footer/config.js';
import { setChromeVisible } from '../chrome/visibility.js';
import { runWithSettingsChangeGuard } from '../blocks/settings/ui.js';
import { registerLinkableButtonTypes } from '../grapesjs-button-link.js';
import { registerMediaSectionTypes } from '../media-section-types.js';
import { registerDropzoneTypes } from '../dropzone-types.js';
import { registerInnerDropSlots } from '../inner-drop-slots.js';
import { stripInvalidDomAttributesFromHtml } from '../core/html-sanitize.js';
import {
    isClearedBackground,
    isClearedBackgroundImage,
    restoreBackgroundClasses,
    stripBackgroundClasses,
    stripBorderColorClasses,
    stripRoundedClasses,
    enforceStyleManagerColorOverUtilities,
} from '../theme-tokens.js';

function isSiteFooterBlock(blockId) {
    return isFooterBlock(blockId);
}

const SECTION_PADDING_CLASSES = ['py-0', 'py-8', 'py-12', 'py-16', 'py-20', 'py-24'];

const TAILWIND_SPACING_CLASS = /^(?:md:)?(?:[pm][xytblr]?|gap(?:-[xy])?)-/;

function stripTailwindSpacingClasses(component) {
    const classes = safeGetClasses(component)
        .filter((className) => ! TAILWIND_SPACING_CLASS.test(className) && ! SECTION_PADDING_CLASSES.includes(className));

    component.setClass(classes);
}

function isSpacingStyleProperty(property) {
    return typeof property === 'string' && /^(padding|margin)(-(top|right|bottom|left))?$/.test(property);
}

function registerSpacingStyleSync(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (! isSpacingStyleProperty(property)) {
            return;
        }

        stripTailwindSpacingClasses(component);
        window.requestAnimationFrame(() => {
            pruneRedundantSpacingZeros(component);

            for (const target of [component, resolveVisualStyleTarget(component)]) {
                if (target && target !== component) {
                    pruneRedundantSpacingZeros(target);
                }
            }
        });
    });
}

function isBorderRadiusProperty(property) {
    return typeof property === 'string' && /border-radius|border-top-left-radius|border-top-right-radius|border-bottom-left-radius|border-bottom-right-radius/.test(property);
}

function isBorderPaintProperty(property) {
    return typeof property === 'string' && /^border(-color|-width|-style)?$/.test(property);
}

function registerTailwindStyleSync(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (editor.__voodbuilderPurgingBackground) {
            return;
        }

        if (! property || ! component) {
            return;
        }

        const target = resolveVisualStyleTarget(component);
        const wrapperStyle = component.getStyle?.() ?? {};
        const targetStyle = target?.getStyle?.() ?? {};

        if (property === 'background'
            || property === 'background-color'
            || property === 'background-image') {
            const background = wrapperStyle[property]
                ?? wrapperStyle['background-color']
                ?? wrapperStyle['background-image']
                ?? wrapperStyle.background
                ?? targetStyle[property]
                ?? targetStyle['background-color']
                ?? targetStyle['background-image']
                ?? targetStyle.background;

            const cleared = background == null
                || background === ''
                || isClearedBackground(background)
                || isClearedBackgroundImage(background);

            if (cleared) {
                restoreBackgroundClasses(target);
                clearBackgroundCssRules(editor, component);
            } else {
                stripBackgroundClasses(target);
            }
        }

        if (property === 'color') {
            enforceStyleManagerColorOverUtilities(target);
        }

        if (isBorderRadiusProperty(property)) {
            stripRoundedClasses(target);
        }

        if (isBorderPaintProperty(property)) {
            stripBorderColorClasses(target);
        }

        target.view?.updateStyles?.();
    });
}

function fixGrapesJsSrcUri(value) {
    let fixed = value.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');

    if (fixed.startsWith('data:image/svg+xml,')) {
        fixed = fixed.replace(/ /g, '%20');
    }

    return fixed;
}

const NEUTRAL_PLACEHOLDER_SRC = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Image placeholder</text>'
    + '</svg>',
);

function normalizePlaceholderHtml(html) {
    if (typeof html !== 'string' || html === '' || ! html.includes('://')) {
        return html;
    }

    return html
        .replace(/\bsrc=(["'])(https?:\/\/(?:dummyimage|placehold|placekitten|placeimg|picsum)\.[^"']+)\1/gi, `src=$1${NEUTRAL_PLACEHOLDER_SRC}$1`)
        .replace(/\bbackground-image\s*:\s*url\((["']?)(https?:\/\/(?:dummyimage|placehold|placekitten|placeimg|picsum)\.[^"')]+)\1\)\s*;?/gi, `background-image: url(${NEUTRAL_PLACEHOLDER_SRC});`);
}

function sanitizeBlockHtml(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    const normalized = normalizePlaceholderHtml(html);

    return stripInvalidDomAttributesFromHtml(
        normalized.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`),
    );
}

function sectionCatalogBlockId(section) {
    return String(section?.getAttributes?.()?.['data-voodbuilder-section-block'] ?? '');
}

function isHeroCatalogSection(section) {
    return sectionCatalogBlockId(section).startsWith('vb-hero-');
}

function registerLayoutSectionType(editor) {
    const sectionTypeDefinition = {
        isComponent: (element) => {
            if (element?.tagName !== 'SECTION') {
                return false;
            }

            return Boolean(element.querySelector?.('.container'))
                || element.classList.contains('body-font');
        },
        extend: 'default',
        model: {
            defaults: {
                name: 'Section',
                traits: [],
                droppable: (srcComponent) => {
                    if (! srcComponent?.get) {
                        return true;
                    }

                    if (String(srcComponent.get('tagName') ?? '').toLowerCase() === 'section') {
                        return false;
                    }

                    return safeFindComponents(srcComponent, 'section[data-voodbuilder-section-block]').length === 0;
                },
            },
            init() {
                if (isHeroCatalogSection(this)) {
                    this.set('traits', []);
                }
            },
        },
    };

    editor.DomComponents.addType('voodbuilder-section', sectionTypeDefinition);

    editor.DomComponents.addType('voodbuilder-container', {
        isComponent: (element) => {
            if (element?.tagName !== 'DIV') {
                return false;
            }

            return element.classList.contains('container') || element.classList.contains('voodbuilder-gjs-container');
        },
        extend: 'default',
        model: {
            defaults: {
                name: 'Container',
                traits: [],
            },
            init() {
                const parentSection = this.parent();

                if (parentSection && isHeroCatalogSection(parentSection)) {
                    this.set('traits', []);
                }
            },
        },
    });
}

function syncVpressDynamicAttributes(component) {
    if (component.__voodbuilderSyncingAttributes) {
        return;
    }

    component.__voodbuilderSyncingAttributes = true;

    try {
        const attributes = component.getAttributes();
        const blockId = attributes['data-voodbuilder-block'] ?? '';
        const config = parseVpressConfig(attributes['data-voodbuilder-config']);
        const encodedConfig = encodeVpressConfig(config);

        component.set('vpressConfig', config, { silent: true });
        component.set('name', blockId ? resolveBlockLayerLabel(blockId) : 'Block', { silent: true });

        const updates = {};

        if (blockId && attributes['data-voodbuilder-block'] !== blockId) {
            updates['data-voodbuilder-block'] = blockId;
        }

        if (attributes['data-voodbuilder-config'] !== encodedConfig) {
            updates['data-voodbuilder-config'] = encodedConfig;
        }

        if (Object.keys(updates).length > 0) {
            component.addAttributes(updates, { silent: true });
        }
    } finally {
        component.__voodbuilderSyncingAttributes = false;
    }
}

function lockComponentTree(component) {
    const attributes = component.getAttributes?.() ?? {};
    const protectedSlot = attributes['data-voodbuilder-menu'] || attributes['data-voodbuilder-brand'];

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: ! protectedSlot,
        hoverable: ! protectedSlot,
        layerable: false,
        editable: false,
        stylable: ! protectedSlot,
    });

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function suppressChromeBlockDescendants(component) {
    component.components().forEach((child) => {
        child.set({
            removable: false,
            draggable: false,
            copyable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
            layerable: false,
            editable: false,
            stylable: false,
        }, { silent: true });
        suppressChromeBlockDescendants(child);
    });
}

function lockDynamicPreviewContent(component, editor = null) {
    const resolvedEditor = editor ?? component?.em ?? null;
    const locked = lockChromePreview(component, resolvedEditor, {
        resolveBlockLayerLabel,
        normalizeMenuButtons: normalizeSiteNavMenuButtons,
        normalizeChromeButtons: normalizeSiteNavChromeButtons,
    });

    if (locked) {
        return;
    }

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function isSiteNavInteractiveComponent(component) {
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};

    if (tag === 'a' || tag === 'p') {
        return true;
    }

    if (tag === 'span' && ! attrs['data-voodbuilder-nav-mobile-chevron']) {
        return true;
    }

    if (tag === 'button' && (attrs['data-voodbuilder-nav-dropdown-toggle'] || attrs['data-voodbuilder-nav-mobile-toggle'])) {
        return true;
    }

    if (tag === 'svg') {
        return true;
    }

    return false;
}

function lockSiteNavPreviewTree(component) {
    const interactive = isSiteNavInteractiveComponent(component);
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: interactive,
        hoverable: interactive,
        layerable: interactive,
        editable: interactive && (tag === 'span' || tag === 'a' || tag === 'p'),
        stylable: interactive,
        highlightable: interactive,
    }, { silent: true });

    component.components().forEach((child) => {
        lockSiteNavPreviewTree(child);
    });
}

function isSiteNavBlock(blockId) {
    return isNavBlock(blockId);
}

function isSiteHeaderBlock(blockId) {
    return isHeaderBlock(blockId);
}

function resolveSiteNavBlockId(blockId) {
    return resolveNavId(blockId);
}

function migrateSiteNavBlockComponent(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (! isSiteHeaderBlock(blockId)) {
        return;
    }

    const resolvedId = resolveSiteNavBlockId(blockId);

    if (resolvedId !== blockId) {
        component.addAttributes({ 'data-voodbuilder-block': resolvedId });
    }
}

function findChromeDropZoneAncestor(component) {
    let current = component?.parent?.();

    while (current) {
        if (current.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR]) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

function findSiteNavRootComponent(component) {
    if (! component) {
        return null;
    }

    const dropZone = component.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR];

    if (dropZone === 'nav') {
        const primary = findPrimaryBlockInChromeDropZone(component);

        if (primary) {
            return findSiteNavRootComponent(primary) ?? primary;
        }
    }

    let current = component;

    while (current) {
        const blockId = current.getAttributes?.()?.['data-voodbuilder-block'];

        if (isSiteHeaderBlock(blockId)) {
            migrateSiteNavBlockComponent(current);

            return current;
        }

        if (current.getAttributes?.()?.['data-voodbuilder-gjs-site-header']) {
            const dynamicRoot = findVpressDynamicAncestor(current);

            if (dynamicRoot && isSiteHeaderBlock(dynamicRoot.getAttributes?.()?.['data-voodbuilder-block'])) {
                migrateSiteNavBlockComponent(dynamicRoot);

                return dynamicRoot;
            }

            let parent = current.parent?.();

            while (parent) {
                const parentBlockId = parent.getAttributes?.()?.['data-voodbuilder-block'];

                if (isSiteHeaderBlock(parentBlockId)) {
                    migrateSiteNavBlockComponent(parent);

                    return parent;
                }

                parent = parent.parent?.();
            }

            const zone = findChromeDropZoneAncestor(current);

            if (zone) {
                const primary = findPrimaryBlockInChromeDropZone(zone);

                if (primary) {
                    return findSiteNavRootComponent(primary) ?? primary;
                }
            }

            return dynamicRoot ?? current;
        }

        current = current.parent();
    }

    return null;
}

function normalizeSiteNavMenuButtons(root) {
    safeFindComponents(root, 'button[data-voodbuilder-nav-dropdown-toggle], button[data-voodbuilder-nav-mobile-toggle]').forEach((button) => {
        const type = button.get('type');

        if (type === 'button' || type === 'voodbuilder-chrome-button') {
            button.set('type', 'default');
        }

        const textOnly = String(button.get('text') ?? '').trim();
        const hasStructure = button.components().length > 0;

        if (textOnly === 'Send') {
            if (hasStructure) {
                button.set('text', '');
            } else {
                button.set('content', '');
            }
        }
    });
}

function registerSiteNavMenuButtonType(editor) {
    if (editor.__voodbuilderSiteNavMenuButtonRegistered) {
        return;
    }

    editor.__voodbuilderSiteNavMenuButtonRegistered = true;

    editor.DomComponents.addType('voodbuilder-nav-menu-button', {
        isComponent: (element) => {
            if (element?.tagName !== 'BUTTON') {
                return false;
            }

            if (element.hasAttribute('data-voodbuilder-nav-dropdown-toggle')
                || element.hasAttribute('data-voodbuilder-nav-mobile-toggle')) {
                return { type: 'voodbuilder-nav-menu-button' };
            }

            return false;
        },
        extend: 'default',
        model: {
            defaults: {
                tagName: 'button',
                draggable: false,
                droppable: false,
                copyable: false,
                removable: false,
                text: '',
            },
            init() {
                const text = String(this.get('text') ?? '').trim();

                if (text === 'Send') {
                    this.set('text', '', { silent: true });
                }
            },
        },
    });
}

function isChromeIconButtonElement(element) {
    if (! element || element.tagName !== 'BUTTON') {
        return false;
    }

    return Boolean(
        element.classList?.contains('voodbuilder-header-icon-btn')
        || element.hasAttribute('data-mobile-nav-toggle')
        || element.hasAttribute('data-mobile-nav-close')
        || element.hasAttribute('data-theme-toggle')
        || element.hasAttribute('data-voodbuilder-search-open')
        || element.hasAttribute('data-voodbuilder-notification-bell-preview')
        || element.hasAttribute('data-voodbuilder-profile-menu-toggle'),
    );
}

const CHROME_ICON_PLACEHOLDER_TEXT = new Set(['Send', 'Button', 'Notifications']);

const CHROME_ICON_SVG = {
    search: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
    bell: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
    user: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    menu: '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>',
};

function chromeIconSvgForButton(button) {
    const attrs = button.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-search-open'] != null) {
        return CHROME_ICON_SVG.search;
    }

    if (attrs['data-voodbuilder-notification-bell-preview'] != null) {
        return CHROME_ICON_SVG.bell;
    }

    if (attrs['data-voodbuilder-profile-menu-toggle'] != null) {
        return CHROME_ICON_SVG.user;
    }

    if (attrs['data-mobile-nav-toggle'] != null || attrs['data-mobile-nav-close'] != null) {
        return CHROME_ICON_SVG.menu;
    }

    return CHROME_ICON_SVG.user;
}

function restoreChromeIconButtonContent(button) {
    if (! button?.get) {
        return;
    }

    const hasSvg = safeFindComponents(button, 'svg').length > 0;
    const text = String(button.get('text') ?? '').trim();
    const content = String(button.get('content') ?? '').trim();
    const domText = String(button.getEl?.()?.textContent ?? '').replace(/\s+/g, '');
    const placeholder = CHROME_ICON_PLACEHOLDER_TEXT.has(text)
        || CHROME_ICON_PLACEHOLDER_TEXT.has(content)
        || /^(?:Button|Notifications|Send)+$/.test(domText)
        || domText === 'ButtonButton';

    if (hasSvg && ! placeholder && text === '' && content === '' && ! /Button|Notifications|Send/.test(domText)) {
        return;
    }

    button.set({
        text: '',
        content: '',
    }, { silent: true });
    button.components(chromeIconSvgForButton(button));
}

function normalizeSiteNavChromeButtons(root) {
    safeFindComponents(root, 'button[data-mobile-nav-toggle], button[data-mobile-nav-close], button[data-theme-toggle], button.voodbuilder-header-icon-btn, button[data-voodbuilder-search-open], button[data-voodbuilder-notification-bell-preview], button[data-voodbuilder-profile-menu-toggle]').forEach((button) => {
        if (! button?.get) {
            return;
        }

        const type = button.get('type');

        if (type === 'button' || type === 'default' || type === 'voodbuilder-cta-button') {
            button.set('type', 'voodbuilder-chrome-button');
        }

        button.set({
            name: '',
            text: '',
            content: '',
            badgable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
            layerable: false,
            editable: false,
            removable: false,
            draggable: false,
            copyable: false,
        }, { silent: true });

        restoreChromeIconButtonContent(button);
    });
}

function siteHeaderTraitOptions() {
    return [
        {
            type: 'select',
            label: 'Menu position',
            name: 'vpressMainNavAlign',
            changeProp: true,
            options: [
                { value: 'start', id: 'start', name: 'Left (next to logo)' },
                { value: 'center', id: 'center', name: 'Center' },
            ],
        },
        {
            type: 'select',
            label: 'Sticky',
            name: 'vpressStickyNav',
            changeProp: true,
            options: [
                { value: 'inherit', id: 'inherit', name: 'Site default' },
                { value: 'sticky', id: 'sticky', name: 'Sticky' },
                { value: 'static', id: 'static', name: 'Scrolls with page' },
            ],
        },
        {
            type: 'checkbox',
            label: 'Show search',
            name: 'vpressShowSearch',
            changeProp: true,
        },
        {
            type: 'checkbox',
            label: 'Show notifications',
            name: 'vpressShowNotifications',
            changeProp: true,
        },
        {
            type: 'checkbox',
            label: 'Show account menu',
            name: 'vpressShowProfileMenu',
            changeProp: true,
        },
    ];
}

function syncSiteHeaderConfig(component) {
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
    };

    component.set('vpressConfig', config, { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeVpressConfig(config),
    }, { silent: true });
}

const siteNavRefreshTimers = new WeakMap();

const STRUCTURAL_SITE_NAV_PROPS = new Set(['vpressStickyNav']);

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

function setNavChromeVisible(node, visible) {
    setChromeVisible(node, visible);
}

/**
 * Blade renders different DOM for start vs center — reshape canvas DOM for instant preview.
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
        )) ?? null;
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

function applySiteNavSettingsPreview(root, editor = null) {
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
    const alignCenter = root.get('vpressMainNavAlign') === 'center';
    const stickyMode = root.get('vpressStickyNav') ?? 'inherit';
    const { pinned, spacer } = resolveSiteNavStickyState(stickyMode, editor);

    const scope = el.querySelector('[data-voodbuilder-gjs-site-header]') ?? el;

    const siteHeader = scope.matches?.('[data-voodbuilder-gjs-site-header]')
        ? scope
        : scope.querySelector('[data-voodbuilder-gjs-site-header]');

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
        }
    });
}

function applySiteNavSettingChange(editor, root, name, value) {
    runWithSettingsChangeGuard(editor, () => {
        if (typeof value === 'boolean') {
            root.set(name, value, { silent: true });
        } else if (typeof value === 'string' && value !== '') {
            root.set(name, value, { silent: true });
        }

        syncSiteHeaderConfig(root);

        if (STRUCTURAL_SITE_NAV_PROPS.has(name)) {
            applySiteNavSettingsPreview(root, editor);
            scheduleSiteNavBlockRefresh(editor, root);

            return;
        }

        applySiteNavSettingsPreview(root, editor);
    });
}

function configureSiteNavTraits(component, editor) {
    migrateSiteNavBlockComponent(component);

    if (! isSiteNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
        return;
    }

    component.set('stylable', false);

    const config = component.get('vpressConfig') ?? {};

    component.set('vpressMainNavAlign', config.main_nav_align === 'center' ? 'center' : 'start', { silent: true });
    component.set('vpressStickyNav', config.sticky_nav ?? 'inherit', { silent: true });
    component.set('vpressShowSearch', config.show_search === true, { silent: true });
    component.set('vpressShowNotifications', config.show_notifications === true, { silent: true });
    component.set('vpressShowProfileMenu', config.show_profile_menu === true, { silent: true });

    // Never set('traits', plainObjects, { silent: true }) — that leaves a raw array and
    // TraitManager crashes with "e.get is not a function" on select/render.
    if (typeof component.setTraits === 'function') {
        component.setTraits(siteHeaderTraitOptions());
    } else {
        component.set('traits', siteHeaderTraitOptions());
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

function registerSiteNavTraitBridge(editor) {
    void editor;
}

function findSiteFooterRootComponent(component) {
    if (! component) {
        return null;
    }

    const dropZone = component.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR];

    if (dropZone === 'footer') {
        const primary = findPrimaryBlockInChromeDropZone(component);

        if (primary) {
            return findSiteFooterRootComponent(primary) ?? primary;
        }
    }

    let current = component;

    while (current) {
        const blockId = current.getAttributes?.()?.['data-voodbuilder-block'];

        if (isSiteFooterBlock(blockId)) {
            return current;
        }

        current = current.parent();
    }

    const zone = findChromeDropZoneAncestor(component);

    if (zone?.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR] === 'footer') {
        const primary = findPrimaryBlockInChromeDropZone(zone);

        if (primary) {
            return findSiteFooterRootComponent(primary) ?? primary;
        }
    }

    return null;
}

function registerSiteFooterSettingsUi(editor) {
    registerFooterSettings(editor);
}

function findVpressDynamicAncestor(component) {
    let parent = component?.parent?.();

    while (parent) {
        if (parent.get('type') === 'voodbuilder-dynamic') {
            return parent;
        }

        parent = parent.parent();
    }

    return null;
}

function isInsideProtectedSlot(component) {
    let current = component;

    while (current) {
        const attributes = current.getAttributes?.() ?? {};

        if (attributes['data-voodbuilder-menu'] || attributes['data-voodbuilder-brand']) {
            return true;
        }

        if (current.get('type') === 'voodbuilder-dynamic') {
            return false;
        }

        current = current.parent();
    }

    return false;
}

function registerDynamicBlockRefreshOnDrop(editor) {
    if (editor.__voodbuilderDynamicBlockRefreshOnDrop) {
        return;
    }

    editor.__voodbuilderDynamicBlockRefreshOnDrop = true;

    const footerOrNavHasPreviewStructure = (component) => {
        if ((component?.components?.()?.length ?? 0) > 0) {
            return true;
        }

        const el = component?.getEl?.();

        if (! el) {
            return false;
        }

        return Boolean(el.querySelector([
            '.container',
            '.voodbuilder-gjs-container',
            '[data-voodbuilder-footer-col]',
            '[data-voodbuilder-chrome]',
            'header[role="banner"]',
            '[data-voodbuilder-gjs-site-header]',
        ].join(', ')));
    };

    const shouldIgnoreAutoRefresh = (component) => Boolean(
        ! component
        || component.isRemoved?.()
        || component.__voodbuilderRefreshing
        || editor.__voodbuilderDynamicBlockRefreshing
        || editor.__voodbuilderLayoutStructureRefreshing
        || editor.__voodbuilderLayoutDynamicRefreshPending
        || editor.__voodbuilderActiveBlockDrag,
    );

    const scheduleRefresh = (component) => {
        if (shouldIgnoreAutoRefresh(component)) {
            return;
        }

        const blockId = component?.getAttributes?.()?.['data-voodbuilder-block'];

        if (! blockId) {
            return;
        }

        const root = isSiteFooterBlock(blockId) || isSiteNavBlock(blockId)
            ? component
            : (findSiteFooterRootComponent(component) ?? findSiteNavRootComponent(component));

        if (! root || shouldIgnoreAutoRefresh(root) || footerOrNavHasPreviewStructure(root)) {
            return;
        }

        editor.trigger('voodbuilder:refresh-dynamic-block', root);
    };

    editor.on('block:drag:stop', (component) => {
        if (! component) {
            return;
        }

        window.requestAnimationFrame(() => scheduleRefresh(component));
    });

    editor.on('component:add', (component) => {
        const blockId = component?.getAttributes?.()?.['data-voodbuilder-block'];

        if (! isSiteFooterBlock(blockId) && ! isSiteNavBlock(blockId)) {
            return;
        }

        // Do not refresh when the model already has children or the DOM is not mounted yet.
        // A null getEl() used to fall through and re-fetch forever (maps remount storm).
        if (shouldIgnoreAutoRefresh(component) || footerOrNavHasPreviewStructure(component)) {
            return;
        }

        const el = component.getEl?.();

        if (! el) {
            return;
        }

        window.requestAnimationFrame(() => scheduleRefresh(component));
    });
}

function registerDynamicBlockGuards(editor) {
    editor.on('component:remove', (removed) => {
        if (removed.get('type') === 'voodbuilder-dynamic') {
            return;
        }

        const dynamic = findVpressDynamicAncestor(removed);

        if (! dynamic?.parent()) {
            return;
        }

        if (isSiteFooterBlock(dynamic.getAttributes()['data-voodbuilder-block']) && ! isInsideProtectedSlot(removed)) {
            return;
        }

        window.queueMicrotask(() => {
            if (dynamic.parent()) {
                dynamic.remove();
            }
        });
    });
}

function pruneEmptyDynamicBlocks(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        const blockId = component.getAttributes()['data-voodbuilder-block'];

        if (isSiteFooterBlock(blockId)) {
            return;
        }

        if (component.components().length === 0) {
            component.remove();
        }
    });
}

function refreshDynamicSlots(component, freshRoot) {
    for (const selector of ['[data-voodbuilder-menu]', '[data-voodbuilder-brand]']) {
        const freshSlots = [...freshRoot.querySelectorAll(selector)];
        const componentSlots = safeFindComponents(component, selector);

        freshSlots.forEach((freshSlot, index) => {
            const target = componentSlots[index];

            if (! target) {
                return;
            }

            target.components(freshSlot.innerHTML);
            lockComponentTree(target);
        });
    }
}

function applyFreshFooterAttributes(component, fresh, blockId, freshConfig) {
    component.set('vpressConfig', freshConfig, { silent: true });

    if (fresh.tagName === 'FOOTER' && component.get('tagName') !== 'footer') {
        component.set('tagName', 'footer', { silent: true });
    }

    component.addAttributes({
        'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
        'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
        class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full',
        'data-voodbuilder-hydrate-slots': '1',
    });
}

function registerSiteNavChromeButtonType(editor) {
    // Always re-addType: grapesjs-plugin-forms registers `button` after the early-types
    // plugin, and addType unshifts — without a later re-register, forms wins isComponent
    // and wipes SVG children with the default "Send"/"Button" label.
    editor.__voodbuilderSiteNavChromeButtonRegistered = true;

    editor.DomComponents.addType('voodbuilder-chrome-button', {
        isComponent: (element) => {
            if (! isChromeIconButtonElement(element)) {
                return false;
            }

            return { type: 'voodbuilder-chrome-button' };
        },
        model: {
            defaults: {
                tagName: 'button',
                name: '',
                text: '',
                draggable: false,
                droppable: false,
                selectable: false,
                hoverable: false,
                copyable: false,
                removable: false,
                editable: false,
                stylable: false,
                layerable: false,
                highlightable: false,
                badgable: false,
            },
            init() {
                this.set('text', '', { silent: true });
                this.off('change:text');
            },
        },
    });
}

function registerSiteNavSettingsUi(editor) {
    registerNavSettings(editor);
}

function registerDynamicBlockType(editor) {
    editor.DomComponents.addType('voodbuilder-dynamic', {
        isComponent: (element) => {
            const blockId = element?.getAttribute?.('data-voodbuilder-block');

            if (! blockId) {
                return false;
            }

            const config = parseVpressConfig(element.getAttribute('data-voodbuilder-config') ?? '{}');
            const isFooter = element?.tagName === 'FOOTER' && isSiteFooterBlock(blockId);
            const defaultClass = isFooter
                ? 'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full border-t border-vp-divider bg-vp-bg'
                : (element.getAttribute('class') ?? 'voodbuilder-gjs-dynamic');

            return {
                type: 'voodbuilder-dynamic',
                tagName: isFooter ? 'footer' : (element.tagName?.toLowerCase() ?? 'div'),
                vpressConfig: config,
                attributes: {
                    'data-voodbuilder-block': blockId,
                    'data-voodbuilder-config': encodeVpressConfig(config),
                    class: defaultClass,
                    ...(element.hasAttribute('data-voodbuilder-hydrate-slots')
                        ? { 'data-voodbuilder-hydrate-slots': '1' }
                        : {}),
                },
            };
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Voodbuilder block',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                removable: true,
                stylable: true,
                layerable: true,
                highlightable: true,
                vpressConfig: {},
                vpressMainNavAlign: 'start',
                vpressStickyNav: 'inherit',
                vpressShowSearch: true,
                vpressShowNotifications: true,
                vpressShowProfileMenu: true,
                vpressShowFooterCol1: true,
                vpressShowFooterCol2: true,
                vpressShowFooterCol3: true,
                vpressShowFooterCol4: true,
                vpressShowNewsletter: true,
                vpressShowSocial: true,
                vpressShowFooterMenu: true,
                vpressShowTagline: true,
                vpressShowCopyright: true,
                vpressShowBrand: true,
                vpressFooterColumnsRedistribute: false,
                attributes: {
                    class: 'voodbuilder-gjs-dynamic',
                    'data-voodbuilder-block': '',
                    'data-voodbuilder-config': encodeVpressConfig({}),
                },
                traits: [],
            },
            init() {
                syncVpressDynamicAttributes(this);

                if (isSiteFooterBlock(this.getAttributes()['data-voodbuilder-block'])) {
                    configureSiteFooterTraits(this, editor);
                }

                if (isSiteNavBlock(this.getAttributes()['data-voodbuilder-block'])) {
                    configureSiteNavTraits(this, editor);
                }

                this.on('change:attributes:data-voodbuilder-config', () => {
                    syncVpressDynamicAttributes(this);
                });
            },
        },
    });
}

function registerBlocks(editor, blocks = []) {
    for (const block of blocks) {
        const content = typeof block.content === 'string'
            ? sanitizeBlockHtml(block.content)
            : block.content;

        const blockAttributes = {
            ...(block.attributes ?? {}),
            title: block.attributes?.title ?? block.label,
        };

        editor.BlockManager.add(block.id, {
            label: block.label,
            category: block.category,
            content,
            media: block.preview ?? block.media ?? `<div class="voodbuilder-gjs-block-fallback">${block.label}</div>`,
            attributes: blockAttributes,
        });
    }
}

function prioritizeBlockCategories(editor) {
    const categories = editor.BlockManager.getCategories?.();

    if (! categories?.each) {
        return;
    }

    categories.each((category) => {
        const categoryId = String(category.get('id') ?? '');

        if (isComponentCategoryId(categoryId)) {
            return;
        }

        const id = normalizeCategoryLabel(String(categoryId || category.get('label') || ''));

        category.set('label', id);
        category.set('order', resolveCategoryOrder(id));
    });
}

function pruneEmptySections(editor) {
    walkComponentTree(editor.getWrapper?.(), (section) => {
        if (section === editor.getWrapper() || String(section.get?.('tagName') ?? '').toLowerCase() !== 'section') {
            return;
        }

        const hasMeaningfulChild = safeFindComponents(section, 'img, h1, h2, h3, h4, h5, h6, p, a, button, ul, ol, table, form, svg').length > 0;

        if (! hasMeaningfulChild && section.components().length === 0) {
            section.remove();
        }
    });
}

function ensureLayoutSectionTraits(editor) {
    walkComponentTree(editor.getWrapper?.(), (section) => {
        if (String(section.get?.('tagName') ?? '').toLowerCase() !== 'section') {
            return;
        }

        if (section.get('type') === 'default') {
            section.set('type', 'voodbuilder-section');
        }

        if (isHeroCatalogSection(section)) {
            section.set('traits', []);
        }
    });

    walkComponentTree(editor.getWrapper?.(), (container) => {
        if (String(container.get?.('tagName') ?? '').toLowerCase() !== 'div') {
            return;
        }

        const classes = safeGetClasses(container);

        if (! classes.includes('container')) {
            return;
        }

        const parentSection = container.parent();

        if (! parentSection || parentSection.get('tagName') !== 'section') {
            return;
        }

        if (isHeroCatalogSection(parentSection)) {
            container.set('traits', []);
        }
    });
}

export {
    registerBlocks,
    sanitizeBlockHtml,
    syncVpressDynamicAttributes,
    lockDynamicPreviewContent,
    registerDynamicBlockGuards,
    pruneEmptyDynamicBlocks,
    applySiteFooterColumns,
    configureSiteFooterTraits,
    configureSiteNavTraits,
    registerSiteNavTraitBridge,
    registerSiteNavSettingsUi,
    registerSiteFooterSettingsUi,
    syncSiteHeaderConfig,
    syncSiteFooterConfig,
    applySiteNavSettingsPreview,
    applySiteFooterSettingsPreview,
    normalizeSiteNavMenuButtons,
    normalizeSiteNavChromeButtons,
    registerSiteNavChromeButtonType,
    refreshDynamicSlots,
    isSiteFooterBlock,
    isSiteNavBlock,
    isSiteHeaderBlock,
    findSiteNavRootComponent,
    findSiteFooterRootComponent,
    applyFreshFooterAttributes,
    prioritizeBlockCategories,
    ensureLayoutSectionTraits,
    pruneEmptySections,
};

export default function vpressGrapesJsPlugin(editor, options = {}) {
    editor.__voodbuilderSiteNavDefaults = options.siteNavDefaults ?? { stickyNav: false };
    editor.__voodbuilderFooterColumnOptions = options.footerColumnOptions ?? {};
    editor.__voodbuilderLabels = options.labels ?? {};

    registerTopDropSpacerType(editor);
    registerDropzoneTypes(editor);
    registerInnerDropSlots(editor);
    registerLinkableButtonTypes(editor);
    registerMediaSectionTypes(editor);
    registerBoundComponentType(editor);
    registerComponentInstanceType(editor, () => editor.__voodbuilderComponentsCatalog ?? []);

    registerDynamicBlockType(editor);
    registerSiteNavMenuButtonType(editor);
    registerSiteNavChromeButtonType(editor);
    registerLayoutSectionType(editor);
    registerSpacingStyleSync(editor);
    registerTailwindStyleSync(editor);
    registerDynamicBlockGuards(editor);
    registerDynamicBlockRefreshOnDrop(editor);
    registerBlocks(editor, options.blocks ?? []);
    prioritizeBlockCategories(editor);
}
