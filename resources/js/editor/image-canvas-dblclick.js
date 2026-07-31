/**
 * Canvas double-click on <img> opens Asset Manager + Content image settings.
 * Uses capture phase so parent <a>/text components cannot swallow the event.
 * Dynamic images are ignored.
 */

import { isDynamicallyBoundImage, isEditableImageComponent } from './jodit-image-editor.js';
import { openMediaAssets } from './editor-assets.js';

/**
 * @param {import('grapesjs').Editor} editor
 * @param {Element} element
 * @returns {import('grapesjs').Component | null}
 */
function findComponentByElement(editor, element) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper || ! element) {
        return null;
    }

    let match = null;

    const walk = (component) => {
        if (! component || match) {
            return;
        }

        const el = component.getEl?.() ?? component.view?.el ?? null;

        if (el === element) {
            match = component;

            return;
        }

        const children = component.components?.();

        if (! children) {
            return;
        }

        const models = typeof children.models !== 'undefined'
            ? children.models
            : (typeof children.forEach === 'function' ? null : []);

        if (models) {
            models.forEach((child) => walk(child));

            return;
        }

        children.forEach?.((child) => walk(child));
    };

    walk(wrapper);

    return match;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} image
 */
function openImageAssets(editor, image) {
    if (image.get?.('editable') === false) {
        image.set?.('editable', true);
    }

    const labels = editor?.__voodbuilderLabels ?? {};

    openMediaAssets({
        editor,
        kinds: ['image'],
        labelKind: 'image',
        labels,
        onSelect: (src) => {
            image.set('src', src);
            image.addAttributes?.({ src });
        },
    });
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerImageCanvasDblClick(editor) {
    if (editor.__voodbuilderImageCanvasDblClickRegistered) {
        return;
    }

    editor.__voodbuilderImageCanvasDblClickRegistered = true;

    const onDblClick = (event) => {
        const target = event.target;

        if (! (target instanceof Element)) {
            return;
        }

        const img = target.closest('img');

        if (! img) {
            return;
        }

        const component = findComponentByElement(editor, img);

        if (! component || ! isEditableImageComponent(component) || isDynamicallyBoundImage(component)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        editor.select(component);
        editor.__voodbuilderActivateInspectorTab?.('content');
        window.requestAnimationFrame(() => {
            editor.__voodbuilderBlockSettingsRender?.();
            openImageAssets(editor, component);
        });
    };

    const bindFrame = () => {
        const doc = editor.Canvas?.getDocument?.();

        if (! doc || doc.__voodbuilderImageDblClickBound) {
            return;
        }

        doc.__voodbuilderImageDblClickBound = true;
        doc.addEventListener('dblclick', onDblClick, true);
    };

    editor.on('load', bindFrame);
    editor.on('canvas:frame:load', bindFrame);
    bindFrame();
}
