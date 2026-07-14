/**
 * Boot site header interactions inside the GrapesJS canvas iframe.
 */

import { initSiteChrome, setMobileNavOpen } from './site-chrome-runtime.js';

function syncCanvasDeviceMode(editor) {
    const deviceId = editor.Devices?.getSelected?.()?.get?.('id') ?? 'desktop';
    const doc = editor.Canvas?.getDocument?.();

    if (! doc?.body) {
        return;
    }

    doc.documentElement.dataset.voodbuilderGjsDevice = deviceId;
    doc.body.dataset.voodbuilderGjsDevice = deviceId;

    if (deviceId === 'desktop' || deviceId === 'tablet') {
        setMobileNavOpen(doc, false);
    }
}

export function bootCanvasSiteChrome(editor) {
    const frameWindow = editor.Canvas?.getWindow?.();

    if (! frameWindow?.document) {
        return;
    }

    syncCanvasDeviceMode(editor);
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
    editor.on('device:select', boot);
    editor.on('sorter:drag:end', boot);
    editor.on('voodbuilder:site-chrome-updated', boot);
}
