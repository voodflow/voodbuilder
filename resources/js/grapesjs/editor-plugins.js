/**
 * Optional GrapesJS community plugins — loaded based on voodbuilder.grapesjs.plugins config.
 */
import grapesjsPluginForms from 'grapesjs-plugin-forms';
import grapesjsStyleBg from 'grapesjs-style-bg';
import grapesjsTabs from 'grapesjs-tabs';
import grapesjsCustomCode from 'grapesjs-custom-code';

const PLUGIN_MAP = {
    forms: grapesjsPluginForms,
    style_bg: grapesjsStyleBg,
    tabs: grapesjsTabs,
    custom_code: grapesjsCustomCode,
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
            pluginsOpts[plugin] = {
                tabsBlock: {
                    label: 'Tabs section',
                    category: 'Sections · Content',
                },
            };

            continue;
        }

        if (plugin === grapesjsCustomCode) {
            // Keep component type for legacy pages; block lives under Sections · Content.
            pluginsOpts[plugin] = {
                blockCustomCode: false,
            };

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

    if (enabled.forms !== false && formSubmitUrl) {
        const applyToForms = (component) => patchFormComponent(component, formSubmitUrl, csrf);

        editor.on('load', () => {
            editor.getWrapper().find('form').forEach(applyToForms);
        });

        editor.on('component:add', applyToForms);
    }
}
