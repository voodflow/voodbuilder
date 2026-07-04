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
import {
    isClearedBackground,
    restoreBackgroundClasses,
    stripBackgroundClasses,
    stripBorderColorClasses,
    stripRoundedClasses,
    stripTextColorClasses,
} from '../theme-tokens.js';

function isSiteFooterBlock(blockId) {
    return blockId === 'site_footer' || (typeof blockId === 'string' && blockId.startsWith('site_footer_'));
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

        if (property === 'background-color' || property === 'background') {
            const background = wrapperStyle[property]
                ?? wrapperStyle['background-color']
                ?? wrapperStyle.background
                ?? targetStyle[property]
                ?? targetStyle['background-color']
                ?? targetStyle.background;

            if (isClearedBackground(background)) {
                restoreBackgroundClasses(target);
                clearBackgroundCssRules(editor, component);
            } else if (background != null && background !== '') {
                stripBackgroundClasses(target);
            }
        }

        if (property === 'color') {
            stripTextColorClasses(target);
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

    return normalized.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`);
}

function sectionPaddingTarget(section) {
    return safeFindComponents(section, '.container')[0] ?? section.components().at(0);
}

function readSectionPadding(container) {
    const classes = safeGetClasses(container);

    return SECTION_PADDING_CLASSES.find((className) => classes.includes(className)) ?? 'py-24';
}

function registerLayoutSectionType(editor) {
    const paddingTrait = {
        type: 'select',
        label: 'Vertical padding (Tailwind)',
        name: 'vpressSectionPy',
        options: [
            { id: 'py-0', name: 'None' },
            { id: 'py-12', name: 'Compact (3rem)' },
            { id: 'py-16', name: 'Medium (4rem)' },
            { id: 'py-24', name: 'Large (6rem)' },
        ],
    };

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
                vpressSectionPy: 'py-24',
                traits: [paddingTrait],
                droppable: (srcComponent) => {
                    if (! srcComponent?.get) {
                        return true;
                    }

                    if (String(srcComponent.get('tagName') ?? '').toLowerCase() === 'section') {
                        return false;
                    }

                    return (srcComponent.find?.('section[data-voodbuilder-section-block]') ?? []).length === 0;
                },
            },
            init() {
                const container = sectionPaddingTarget(this);

                if (container) {
                    this.set('vpressSectionPy', readSectionPadding(container), { silent: true });
                }

                this.on('change:vpressSectionPy', () => {
                    applySectionPadding(this, this.get('vpressSectionPy'));
                });
            },
        },
    };

    editor.DomComponents.addType('voodbuilder-section', sectionTypeDefinition);

    editor.DomComponents.addType('voodbuilder-container', {
        isComponent: (element) => {
            if (element?.tagName !== 'DIV') {
                return false;
            }

            return element.classList.contains('container');
        },
        extend: 'default',
        model: {
            defaults: {
                name: 'Container',
                vpressSectionPy: 'py-24',
                traits: [paddingTrait],
            },
            init() {
                this.set('vpressSectionPy', readSectionPadding(this), { silent: true });

                this.on('change:vpressSectionPy', () => {
                    applySectionPaddingToElement(this, this.get('vpressSectionPy'));
                });
            },
        },
    });
}

function applySectionPaddingToElement(component, pyClass) {
    const classes = safeGetClasses(component)
        .filter((className) => ! SECTION_PADDING_CLASSES.includes(className) && ! className.startsWith('md:py-'));

    if (pyClass && pyClass !== 'py-0') {
        classes.push(pyClass);
    }

    component.setClass(classes);
}

function applySectionPadding(section, pyClass) {
    const container = sectionPaddingTarget(section);

    if (! container) {
        return;
    }

    applySectionPaddingToElement(container, pyClass);
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
        layerable: ! protectedSlot,
        editable: false,
        stylable: ! protectedSlot,
    });

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function lockDynamicPreviewContent(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (isSiteFooterBlock(blockId)) {
        safeFindComponents(component, '[data-voodbuilder-menu], [data-voodbuilder-brand]').forEach((slot) => {
            lockComponentTree(slot);
        });

        return;
    }

    if (isSiteNavBlock(blockId)) {
        component.set({
            selectable: true,
            highlightable: true,
            hoverable: true,
            layerable: true,
        });
        migrateSiteNavBlockComponent(component);
        component.components().forEach((child) => {
            lockSiteNavPreviewTree(child);
        });
        normalizeSiteNavMenuButtons(component);
        normalizeSiteNavChromeButtons(component);

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
    return resolveSiteNavBlockId(blockId) === 'site_nav_simple';
}

function isSiteHeaderBlock(blockId) {
    return isSiteNavBlock(blockId) || blockId === 'site_header';
}

function resolveSiteNavBlockId(blockId) {
    if (typeof blockId !== 'string' || blockId === '') {
        return blockId;
    }

    if (blockId === 'site_header' || (blockId.startsWith('site_nav_') && blockId !== 'site_nav_simple')) {
        return 'site_nav_simple';
    }

    return blockId;
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

function findSiteNavRootComponent(component) {
    let current = component;

    while (current) {
        const blockId = current.getAttributes?.()?.['data-voodbuilder-block'];

        if (isSiteHeaderBlock(blockId)) {
            migrateSiteNavBlockComponent(current);

            return current;
        }

        if (current.getAttributes?.()?.['data-voodbuilder-gjs-site-header']) {
            return findVpressDynamicAncestor(current) ?? current;
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

function normalizeSiteNavChromeButtons(root) {
    safeFindComponents(root, 'button.voodbuilder-header-icon-btn').forEach((button) => {
        if (button.get('type') === 'button') {
            button.set('type', 'default');
        }

        const text = String(button.get('text') ?? '').trim();

        if (text === 'Send' && safeFindComponents(button, 'svg').length === 0) {
            button.set('text', '');
        }
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

const STRUCTURAL_SITE_NAV_PROPS = new Set(['vpressMainNavAlign', 'vpressStickyNav']);

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
        editor.trigger('voodbuilder:refresh-dynamic-block', root);
    }, 280));
}

function setNavChromeVisible(node, visible) {
    if (! node) {
        return;
    }

    if (visible) {
        node.removeAttribute('data-voodbuilder-chrome-hidden');
    } else {
        node.setAttribute('data-voodbuilder-chrome-hidden', '');
    }
}

function applySiteNavSettingsPreview(root, editor = null) {
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
    component.set('traits', siteHeaderTraitOptions());

    applySiteNavSettingsPreview(component, editor);

    if (editor?.TraitManager && editor.getSelected?.() === component) {
        editor.TraitManager.select(component);
    }
}

function registerSiteNavTraitBridge(editor) {
    if (editor.__voodbuilderSiteNavTraitBridgeRegistered) {
        return;
    }

    editor.__voodbuilderSiteNavTraitBridgeRegistered = true;

    editor.on('component:selected', (component) => {
        const root = findSiteNavRootComponent(component);

        if (! root) {
            return;
        }

        configureSiteNavTraits(root, editor);

        if (root !== component && isSiteNavInteractiveComponent(component)) {
            return;
        }
    });

    editor.on('trait:value', ({ trait, component, value }) => {
        const blockId = component?.getAttributes?.()?.['data-voodbuilder-block'];

        if (! isSiteNavBlock(blockId)) {
            return;
        }

        const traitName = trait?.get?.('name');

        if (traitName !== 'vpressMainNavAlign'
            && traitName !== 'vpressStickyNav'
            && traitName !== 'vpressShowSearch'
            && traitName !== 'vpressShowNotifications'
            && traitName !== 'vpressShowProfileMenu') {
            return;
        }

        window.requestAnimationFrame(() => {
            applySiteNavSettingChange(editor, component, traitName, value);
        });
    });
}

function applySiteFooterColumns(component, columns) {
    const count = Math.max(1, Math.min(4, Number(columns) || 4));

    safeFindComponents(component, '[data-voodbuilder-footer-col]').forEach((column) => {
        const index = Number(column.getAttributes()['data-voodbuilder-footer-col'] ?? 0);
        const classes = safeGetClasses(column)
            .filter((className) => className !== 'hidden');

        if (index > count) {
            classes.push('hidden');
        }

        column.setClass(classes);
    });

    component.addAttributes({ 'data-voodbuilder-footer-columns': String(count) });

    const config = {
        ...(component.get('vpressConfig') ?? {}),
        columns: count,
    };

    component.set('vpressConfig', config, { silent: true });
    component.set('vpressFooterColumns', String(count), { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeVpressConfig(config),
    });
}

function configureSiteFooterTraits(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (blockId === 'site_footer_social' || blockId === 'site_footer_centered' || safeFindComponents(component, '[data-voodbuilder-footer-col]').length === 0) {
        component.set('traits', []);

        return;
    }

    component.set('traits', [
        {
            type: 'select',
            label: 'Columns',
            name: 'vpressFooterColumns',
            changeProp: true,
            options: [
                { value: '1', id: '1', name: '1 column' },
                { value: '2', id: '2', name: '2 columns' },
                { value: '3', id: '3', name: '3 columns' },
                { value: '4', id: '4', name: '4 columns' },
            ],
        },
    ]);

    const columns = String(component.get('vpressConfig')?.columns ?? 4);

    component.set('vpressFooterColumns', columns, { silent: true });
    applySiteFooterColumns(component, columns);

    component.on('change:vpressFooterColumns', () => {
        applySiteFooterColumns(component, component.get('vpressFooterColumns'));
    });
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
    component.addAttributes({
        'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
        'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
        class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full',
        'data-voodbuilder-hydrate-slots': '1',
    });
}

function registerSiteNavChromeButtonType(editor) {
    if (editor.__voodbuilderSiteNavChromeButtonRegistered) {
        return;
    }

    editor.__voodbuilderSiteNavChromeButtonRegistered = true;

    editor.DomComponents.addType('voodbuilder-chrome-button', {
        isComponent: (element) => {
            if (element?.tagName !== 'BUTTON') {
                return false;
            }

            if (element.classList?.contains('voodbuilder-header-icon-btn')) {
                return { type: 'voodbuilder-chrome-button' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'button',
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
            },
        },
    });
}

function registerSiteNavSettingsUi(editor, mount) {
    if (! mount || editor.__voodbuilderSiteNavSettingsUiRegistered) {
        return;
    }

    editor.__voodbuilderSiteNavSettingsUiRegistered = true;

    const traitsMount = mount.closest('[data-voodbuilder-inspector="content"]')
        ?.querySelector('.voodbuilder-gjs-traits-mount');

    const render = () => {
        const selected = editor.getSelected();
        const root = findSiteNavRootComponent(selected);

        if (! root || ! isSiteNavBlock(root.getAttributes()['data-voodbuilder-block'])) {
            mount.hidden = true;
            mount.replaceChildren();
            traitsMount?.classList.remove('hidden');

            return;
        }

        configureSiteNavTraits(root, editor);

        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        mount.replaceChildren();

        const applyChange = (name, value) => {
            applySiteNavSettingChange(editor, root, name, value);
        };

        const { section, fields } = createFormSection('Navbar settings');

        fields.append(
            createSelectField({
                label: 'Menu position',
                name: 'vpressMainNavAlign',
                value: root.get('vpressMainNavAlign') === 'center' ? 'center' : 'start',
                options: [
                    { value: 'start', label: 'Left (next to logo)' },
                    { value: 'center', label: 'Center' },
                ],
                onChange: (value) => applyChange('vpressMainNavAlign', value),
            }),
            createSelectField({
                label: 'Sticky',
                name: 'vpressStickyNav',
                value: root.get('vpressStickyNav') ?? 'inherit',
                options: [
                    { value: 'inherit', label: 'Site default' },
                    { value: 'sticky', label: 'Sticky' },
                    { value: 'static', label: 'Scrolls with page' },
                ],
                onChange: (value) => applyChange('vpressStickyNav', value),
            }),
            createCheckboxField({
                label: 'Show search',
                name: 'vpressShowSearch',
                checked: root.get('vpressShowSearch') === true,
                onChange: (checked) => applyChange('vpressShowSearch', checked),
            }),
            createCheckboxField({
                label: 'Show notifications',
                name: 'vpressShowNotifications',
                checked: root.get('vpressShowNotifications') === true,
                onChange: (checked) => applyChange('vpressShowNotifications', checked),
            }),
            createCheckboxField({
                label: 'Show account menu',
                name: 'vpressShowProfileMenu',
                checked: root.get('vpressShowProfileMenu') === true,
                onChange: (checked) => applyChange('vpressShowProfileMenu', checked),
            }),
        );

        mount.appendChild(section);
    };

    editor.on('component:selected', render);
    editor.on('component:deselected', render);
    editor.on('load', render);
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
                    configureSiteFooterTraits(this);
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

        const container = sectionPaddingTarget(section);

        if (! container) {
            return;
        }

        const type = section.get('type');

        if (type === 'default') {
            section.set('type', 'voodbuilder-section');
        }

        if (section.get('_vpressTraitsBound')) {
            return;
        }

        section.set('_vpressTraitsBound', true);

        if (! section.get('vpressSectionPy')) {
            section.set('vpressSectionPy', readSectionPadding(container), { silent: true });
        }

        section.on('change:vpressSectionPy', () => {
            applySectionPadding(section, section.get('vpressSectionPy'));
        });
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

        if (container.get('_vpressTraitsBound')) {
            return;
        }

        container.set('_vpressTraitsBound', true);

        if (! container.get('vpressSectionPy')) {
            container.set('vpressSectionPy', readSectionPadding(container), { silent: true });
        }

        container.on('change:vpressSectionPy', () => {
            applySectionPaddingToElement(container, container.get('vpressSectionPy'));
        });
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
    configureSiteNavTraits,
    registerSiteNavTraitBridge,
    registerSiteNavSettingsUi,
    syncSiteHeaderConfig,
    applySiteNavSettingsPreview,
    normalizeSiteNavMenuButtons,
    refreshDynamicSlots,
    isSiteFooterBlock,
    isSiteNavBlock,
    isSiteHeaderBlock,
    applyFreshFooterAttributes,
    prioritizeBlockCategories,
    ensureLayoutSectionTraits,
    pruneEmptySections,
};

export default function vpressGrapesJsPlugin(editor, options = {}) {
    editor.__voodbuilderSiteNavDefaults = options.siteNavDefaults ?? { stickyNav: false };

    registerTopDropSpacerType(editor);
    registerBoundComponentType(editor);
    registerComponentInstanceType(editor, () => editor.__voodbuilderComponentsCatalog ?? []);

    registerDynamicBlockType(editor);
    registerSiteNavMenuButtonType(editor);
    registerSiteNavChromeButtonType(editor);
    registerSiteNavTraitBridge(editor);
    registerLayoutSectionType(editor);
    registerSpacingStyleSync(editor);
    registerTailwindStyleSync(editor);
    registerDynamicBlockGuards(editor);
    registerBlocks(editor, options.blocks ?? []);
    prioritizeBlockCategories(editor);
}
