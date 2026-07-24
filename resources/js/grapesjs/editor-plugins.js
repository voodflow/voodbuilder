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

function protectSiteHeaderButton(component) {
    const classes = component.getClasses?.() ?? [];

    if (component.get('tagName') !== 'button' || ! classes.includes('voodbuilder-header-icon-btn')) {
        return;
    }

    if (component.get('type') === 'button') {
        component.set('type', 'default');
    }

    const text = String(component.get('text') ?? '').trim();

    if (text === 'Send' && safeFindComponents(component, 'svg').length === 0) {
        component.set('text', '');
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

        editor.on('component:add', (component) => {
            window.requestAnimationFrame(() => {
                protectSiteHeaderButton(component);
            });
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
