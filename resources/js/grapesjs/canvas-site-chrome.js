/**
 * Boot site header interactions inside the GrapesJS canvas iframe.
 */

import { initSiteChrome } from './site-chrome-runtime.js';

function bootCanvasSiteChrome(editor) {
    const frameWindow = editor.Canvas?.getWindow?.();

    if (! frameWindow?.document) {
        return;
    }

    initSiteChrome(frameWindow.document);
}

export function registerCanvasSiteChrome(editor) {
    if (editor.__voodbuilderCanvasSiteChromeRegistered) {
        return;
    }

    editor.__voodbuilderCanvasSiteChromeRegistered = true;

    const boot = () => {
        window.requestAnimationFrame(() => {
            bootCanvasSiteChrome(editor);
        });
    };

    editor.on('canvas:frame:load', boot);
    editor.on('load', boot);
    editor.on('component:add', boot);
    editor.on('component:update', boot);
    editor.on('sorter:drag:end', boot);
}
