/**
 * Optional GrapesJS community plugins — loaded based on voodbuilder.grapesjs.plugins config.
 */
import grapesjsPluginForms from 'grapesjs-plugin-forms';
import grapesjsStyleBg from 'grapesjs-style-bg';
import grapesjsTabs from 'grapesjs-tabs';
import { grapesJsTabsPluginOptions, registerVoodbuilderTabsBlocks } from './grapesjs-tabs-blocks.js';
import { configureGrapesJsTabsCanvas } from './grapesjs-tabs-runtime.js';
import { configureGrapesJsStepTabsCanvas } from './grapesjs-step-tabs.js';
import {
    configureGrapesJsFormsCanvas,
    grapesJsFormsPluginOptions,
    registerVoodbuilderFormBlock,
} from './grapesjs-forms-blocks.js';
import {
    configureUtilityBlocksCanvas,
    registerUtilityBlocks,
} from './grapesjs-utility-blocks.js';
import {
    configureLayoutBlocksCanvas,
    registerLayoutBlocks,
} from './layout-blocks.js';
import {
    configureAnimatedCanvas,
    registerAnimatedBlocks,
} from './grapesjs-animated-blocks.js';
import {
    configureLogoCloudCanvas,
    registerLogoCloudBlocks,
} from './grapesjs-logo-cloud-blocks.js';
import registerGrapesJsTailwindPlugin from './grapesjs-tailwind-plugin.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const PLUGIN_MAP = {
    forms: grapesjsPluginForms,
    style_bg: grapesjsStyleBg,
    tabs: grapesjsTabs,
};

export function resolveGrapesJsPlugins(enabled = {}) {
    const plugins = [];
    const pluginsOpts = {};

    for (const [key, plugin] of Object.entries(PLUGIN_MAP)) {
        if (enabled[key] === false) {
            continue;
        }

        plugins.push(plugin);

        if (plugin === grapesjsTabs) {
            pluginsOpts[plugin] = grapesJsTabsPluginOptions();

            continue;
        }

        if (plugin === grapesjsPluginForms) {
            pluginsOpts[plugin] = grapesJsFormsPluginOptions();

            continue;
        }

        pluginsOpts[plugin] = {};
    }

    // Opt-in only. Defaulting to on caused a MutationObserver ↔ style rebuild loop
    // (canvas already uses pre-built utilities + page-tailwind-autobuild).
    if (enabled.tailwind === true) {
        plugins.push(registerGrapesJsTailwindPlugin);
        pluginsOpts[registerGrapesJsTailwindPlugin] = {
            // Never enable MutationObserver autobuild unless explicitly requested —
            // it rebuilds on every canvas DOM mutation and can spin forever at boot.
            autobuild: optionsTailwindAutobuild(enabled),
            autocomplete: false,
            buildButton: false,
        };
    }

    return { plugins, pluginsOpts };
}

function optionsTailwindAutobuild(enabled) {
    if (typeof enabled.tailwind === 'object' && enabled.tailwind !== null) {
        return enabled.tailwind.autobuild === true;
    }

    return false;
}

function patchFormComponent(component, formSubmitUrl, csrf) {
    if (component.get('tagName') !== 'form') {
        return;
    }

    const attributes = component.getAttributes?.() ?? {};
    const isNewsletter = attributes['data-voodbuilder-form'] === 'newsletter';

    component.addClass('vb-gjs-form');

    if (isNewsletter) {
        component.addClass('vb-gjs-newsletter-form');
    }

    component.addAttributes({
        action: formSubmitUrl,
        method: 'post',
    });

    if (! csrf) {
        return;
    }

    const children = component.components();

    const ensureHidden = (name, value) => {
        const hasField = children.some((child) => child.getAttributes()?.name === name);

        if (! hasField) {
            component.append(`<input type="hidden" name="${name}" value="${value}">`);
        }
    };

    ensureHidden('_token', csrf);

    if (isNewsletter) {
        ensureHidden('form_type', 'newsletter');
    }
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

function patchFormsButtonType(editor) {
    const existing = editor.DomComponents.getType('button');

    if (! existing) {
        return;
    }

    const previousIsComponent = existing.isComponent;

    // Mutate in place — do not addType('button') again or it would outrank
    // voodbuilder-chrome-button (addType unshifts).
    existing.isComponent = (element) => {
        if (isChromeIconButtonElement(element)) {
            return false;
        }

        if (typeof previousIsComponent === 'function') {
            return previousIsComponent(element);
        }

        return element?.tagName === 'BUTTON' ? { type: 'button' } : false;
    };
}

function protectSiteHeaderButton(component) {
    const classes = component.getClasses?.() ?? [];
    const attrs = component.getAttributes?.() ?? {};
    const isChromeIcon = classes.includes('voodbuilder-header-icon-btn')
        || attrs['data-voodbuilder-search-open'] != null
        || attrs['data-voodbuilder-notification-bell-preview'] != null
        || attrs['data-voodbuilder-profile-menu-toggle'] != null
        || attrs['data-mobile-nav-toggle'] != null
        || attrs['data-mobile-nav-close'] != null
        || attrs['data-theme-toggle'] != null;

    if (component.get('tagName') !== 'button' || ! isChromeIcon) {
        return;
    }

    if (component.get('type') === 'button' || component.get('type') === 'voodbuilder-cta-button') {
        component.set('type', 'voodbuilder-chrome-button');
    }

    const text = String(component.get('text') ?? '').trim();
    const hasSvg = safeFindComponents(component, 'svg').length > 0;

    if (['Send', 'Button', 'Notifications'].includes(text) || (! hasSvg && text !== '')) {
        component.set('text', '', { silent: true });
    }

    if (! hasSvg) {
        // Recovery when forms-plugin button init already wiped icon children.
        const placeholder = text === '' || ['Send', 'Button', 'Notifications'].includes(text);

        if (placeholder) {
            let svg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';

            if (attrs['data-voodbuilder-search-open'] != null) {
                svg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
            } else if (attrs['data-voodbuilder-notification-bell-preview'] != null) {
                svg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
            } else if (attrs['data-mobile-nav-toggle'] != null || attrs['data-mobile-nav-close'] != null) {
                svg = '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
            }

            component.components(svg);
        }
    }
}

export function configureGrapesJsPlugins(editor, options = {}) {
    const { formSubmitUrl, csrf, plugins: enabled = {}, labels = {} } = options;

    const registerLayout = () => registerLayoutBlocks(editor, labels);

    editor.on('load', registerLayout);
    registerLayout();
    configureLayoutBlocksCanvas(editor, labels);

    if (enabled.tabs !== false) {
        const registerTabs = () => registerVoodbuilderTabsBlocks(editor);

        editor.on('load', registerTabs);
        registerTabs();
        configureGrapesJsTabsCanvas(editor);
        configureGrapesJsStepTabsCanvas(editor);
    }

    if (enabled.forms !== false) {
        const registerForms = () => registerVoodbuilderFormBlock(editor);

        editor.on('load', registerForms);
        registerForms();
        configureGrapesJsFormsCanvas(editor);
        patchFormsButtonType(editor);

        editor.on('component:add', (component) => {
            window.requestAnimationFrame(() => {
                protectSiteHeaderButton(component);
            });
        });
        editor.on('load', () => {
            safeFindComponents(
                editor.getWrapper?.(),
                'button.voodbuilder-header-icon-btn, button[data-voodbuilder-search-open], button[data-voodbuilder-notification-bell-preview], button[data-voodbuilder-profile-menu-toggle], button[data-mobile-nav-toggle], button[data-mobile-nav-close], button[data-theme-toggle]',
            ).forEach((button) => protectSiteHeaderButton(button));
        });
    }

    if (enabled.forms !== false && formSubmitUrl) {
        const applyToForms = (component) => patchFormComponent(component, formSubmitUrl, csrf);

        editor.on('load', () => {
            editor.getWrapper().find('form').forEach(applyToForms);
        });

        editor.on('component:add', applyToForms);
    }

    const registerUtility = () => registerUtilityBlocks(editor);

    editor.on('load', registerUtility);
    registerUtility();
    configureUtilityBlocksCanvas(editor);

    const registerAnimated = () => registerAnimatedBlocks(editor);

    editor.on('load', registerAnimated);
    registerAnimated();
    configureAnimatedCanvas(editor);

    const registerLogoCloud = () => registerLogoCloudBlocks(editor);

    editor.on('load', registerLogoCloud);
    registerLogoCloud();
    configureLogoCloudCanvas(editor);
}
