/**
 * Optional Editor community plugins — loaded based on voodbuilder.editor.plugins config.
 */
import grapesjsPluginForms from 'grapesjs-plugin-forms';
import grapesjsStyleBg from 'grapesjs-style-bg';
import grapesjsTabs from 'grapesjs-tabs';
import { editorTabsPluginOptions, registerVoodbuilderTabsBlocks } from './editor-tabs-blocks.js';
import { configureEditorTabsCanvas } from './editor-tabs-runtime.js';
import { configureEditorStepTabsCanvas } from './editor-step-tabs.js';
import {
    configureEditorFormsCanvas,
    editorFormsPluginOptions,
    registerVoodbuilderFormBlock,
} from './editor-forms-blocks.js';
import {
    configureUtilityBlocksCanvas,
    registerUtilityBlocks,
} from './editor-utility-blocks.js';
import {
    configureLayoutBlocksCanvas,
    registerLayoutBlocks,
} from './layout-blocks.js';
import {
    configureAnimatedCanvas,
    registerAnimatedBlocks,
} from './editor-animated-blocks.js';
import {
    configureLogoCloudCanvas,
    registerLogoCloudBlocks,
} from './editor-logo-cloud-blocks.js';
import { setEditorBlockAllowlist } from './block-allowlist.js';
import registerEditorTailwindPlugin from './editor-tailwind-plugin.js';
import { safeFindComponents } from './tailwind-visual-style.js';
import {
    chromeIconSvgForAttrs,
    isChromeIconPlaceholderText,
} from './chrome/icons.js';

const PLUGIN_MAP = {
    forms: grapesjsPluginForms,
    style_bg: grapesjsStyleBg,
    tabs: grapesjsTabs,
};

export function resolveEditorPlugins(enabled = {}) {
    const plugins = [];
    const pluginsOpts = {};

    for (const [key, plugin] of Object.entries(PLUGIN_MAP)) {
        if (enabled[key] === false) {
            continue;
        }

        plugins.push(plugin);

        if (plugin === grapesjsTabs) {
            pluginsOpts[plugin] = editorTabsPluginOptions();

            continue;
        }

        if (plugin === grapesjsPluginForms) {
            pluginsOpts[plugin] = editorFormsPluginOptions();

            continue;
        }

        pluginsOpts[plugin] = {};
    }

    // Opt-in only. Defaulting to on caused a MutationObserver ↔ style rebuild loop
    // (canvas already uses pre-built utilities + page-tailwind-autobuild).
    if (enabled.tailwind === true) {
        plugins.push(registerEditorTailwindPlugin);
        pluginsOpts[registerEditorTailwindPlugin] = {
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

    if (! existing?.model) {
        return;
    }

    const previousIsComponent = existing.isComponent;
    const Model = existing.model;

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

    // grapesjs-plugin-forms defaults text to "Send" and init() replaces empty /
    // icon-only <button> children with that label. Keep author markup intact.
    const defaults = Model.prototype.defaults;
    const resolvedDefaults = typeof defaults === 'function' ? defaults.call(Model.prototype) : { ...defaults };

    Model.prototype.defaults = {
        ...resolvedDefaults,
        text: '',
    };

    Model.prototype.init = function patchFormsButtonInit() {
        const components = this.components();
        const models = [...(components?.models ?? components ?? [])];
        const textNodes = models.filter((child) => {
            const type = child?.get?.('type');

            return child?.is?.('textnode') || type === 'textnode' || type === 'text';
        });
        const structural = models.filter((child) => {
            const type = child?.get?.('type');

            return ! (child?.is?.('textnode') || type === 'textnode' || type === 'text');
        });
        const fromChild = textNodes
            .map((child) => String(child.get?.('content') ?? '').trim())
            .filter(Boolean)
            .join(' ');

        this.off('change:text', this.__onTextChange);
        this.on('change:text', this.__onTextChange);

        if (structural.length > 0) {
            this.set('text', fromChild, { silent: true });

            return;
        }

        if (fromChild !== '') {
            this.set('text', fromChild, { silent: true });

            return;
        }

        // Empty <button></button> from HTML — do not invent "Send"/"Button".
        // Explicit block drops should pass components/text themselves (see form submit).
        this.set('text', '', { silent: true });
    };

    const buttonBlock = editor.BlockManager?.get?.('button');

    if (buttonBlock) {
        buttonBlock.set('content', {
            type: 'button',
            text: 'Send',
            components: 'Send',
        });
    }
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
    const domText = String(component.getEl?.()?.textContent ?? '').replace(/\s+/g, '');
    const placeholderText = isChromeIconPlaceholderText(text)
        || isChromeIconPlaceholderText(domText);

    if (placeholderText || (! hasSvg && text !== '')) {
        component.set({ text: '', content: '' }, { silent: true });
    }

    if (! hasSvg || placeholderText) {
        component.components(chromeIconSvgForAttrs(attrs));
    }
}

export function configureEditorPlugins(editor, options = {}) {
    const { formSubmitUrl, csrf, plugins: enabled = {}, labels = {}, blockAllowlist = null } = options;

    setEditorBlockAllowlist(editor, blockAllowlist);

    const registerLayout = () => registerLayoutBlocks(editor, labels);

    editor.on('load', registerLayout);
    registerLayout();
    configureLayoutBlocksCanvas(editor, labels);

    if (enabled.tabs !== false) {
        const registerTabs = () => registerVoodbuilderTabsBlocks(editor);

        editor.on('load', registerTabs);
        registerTabs();
        configureEditorTabsCanvas(editor);
        configureEditorStepTabsCanvas(editor);
    }

    if (enabled.forms !== false) {
        const registerForms = () => registerVoodbuilderFormBlock(editor);

        editor.on('load', registerForms);
        registerForms();
        configureEditorFormsCanvas(editor);
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
