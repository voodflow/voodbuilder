/**
 * Keep layout chrome nodes out of the Layers panel.
 */

import { walkComponentTree } from './tailwind-visual-style.js';

function shouldHideFromLayers(component, editor) {
    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-gjs-site-header']) {
        return true;
    }

    return false;
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
    } else if (component.get('layerable') === false && component.get('draggable') === false) {
        component.set({
            layerable: true,
            draggable: true,
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

    let layersRenderFrame = null;

    const scheduleLayersRender = () => {
        if (layersRenderFrame != null) {
            return;
        }

        layersRenderFrame = window.requestAnimationFrame(() => {
            layersRenderFrame = null;
            editor.Layers?.render?.();
        });
    };

    const syncAll = () => {
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        walkComponentTree(wrapper, (component) => {
            applyLayersChromeFilter(component, editor);
        });

        scheduleLayersRender();
    };

    const syncSubtree = (component) => {
        if (! component) {
            return;
        }

        applyLayersChromeFilter(component, editor);
        scheduleLayersRender();
    };

    editor.on('load', syncAll);
    editor.on('component:add', syncSubtree);
    editor.on('component:remove', syncAll);
    editor.on('voodbuilder:site-chrome-updated', syncAll);
}
