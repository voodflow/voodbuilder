/**
 * Hide site chrome and obsolete top-level header/footer nodes from the Layers panel.
 */

import { isSiteFooterBlock, isSiteNavBlock } from './plugins/voodbuilder-grapesjs.js';
import { walkComponentTree } from './tailwind-visual-style.js';

function isWrapperChild(component, editor) {
    const parent = component.parent?.();

    if (! parent) {
        return false;
    }

    const wrapper = editor.getWrapper?.();

    return parent === wrapper || parent.get?.('type') === 'wrapper';
}

function shouldHideFromLayers(component, editor) {
    const attrs = component.getAttributes?.() ?? {};
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    if (isSiteNavBlock(blockId) || isSiteFooterBlock(blockId)) {
        return true;
    }

    if (attrs['data-voodbuilder-gjs-site-header']) {
        return true;
    }

    if (! isWrapperChild(component, editor)) {
        return false;
    }

    if (tag === 'header' || tag === 'footer') {
        return true;
    }

    const name = String(component.getName?.() ?? '').trim().toLowerCase();

    return name === 'header' || name === 'footer';
}

function applyLayersChromeFilter(component, editor) {
    if (! component || component.get?.('type') === 'wrapper') {
        return;
    }

    if (shouldHideFromLayers(component, editor)) {
        component.set({
            layerable: false,
            draggable: false,
        });
    }

    component.components?.().forEach((child) => {
        applyLayersChromeFilter(child, editor);
    });
}

export function registerLayersChromeFilter(editor) {
    if (editor.__voodbuilderLayersChromeFilterRegistered) {
        return;
    }

    editor.__voodbuilderLayersChromeFilterRegistered = true;

    const sync = () => {
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        walkComponentTree(wrapper, (component) => {
            applyLayersChromeFilter(component, editor);
        });

        window.requestAnimationFrame(() => {
            editor.Layers?.render?.();
        });
    };

    editor.on('load', sync);
    editor.on('component:add', sync);
    editor.on('component:remove', sync);
    editor.on('voodbuilder:site-chrome-updated', sync);
}
