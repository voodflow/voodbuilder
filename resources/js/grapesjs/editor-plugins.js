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

    return { plugins, pluginsOpts };
}

function patchFormComponent(component, formSubmitUrl, csrf) {
    if (component.get('tagName') !== 'form') {
        return;
    }

    component.addClass('vb-gjs-form');

    component.addAttributes({
        action: formSubmitUrl,
        method: 'post',
    });

    if (! csrf) {
        return;
    }

    const hasToken = component
        .components()
        .some((child) => child.getAttributes()?.name === '_token');

    if (! hasToken) {
        component.append(`<input type="hidden" name="_token" value="${csrf}">`);
    }
}

export function configureGrapesJsPlugins(editor, options = {}) {
    const { formSubmitUrl, csrf, plugins: enabled = {} } = options;

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
    }

    if (enabled.forms !== false && formSubmitUrl) {
        const applyToForms = (component) => patchFormComponent(component, formSubmitUrl, csrf);

        editor.on('load', () => {
            editor.getWrapper().find('form').forEach(applyToForms);
        });

        editor.on('component:add', applyToForms);
    }
}
