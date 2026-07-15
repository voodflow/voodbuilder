/**
 * Boot site header interactions inside the GrapesJS canvas iframe.
 */

let siteChromeRuntimePromise = null;

function loadSiteChromeRuntime() {
    if (! siteChromeRuntimePromise) {
        siteChromeRuntimePromise = import('./site-chrome-runtime.js');
    }

    return siteChromeRuntimePromise;
}

async function syncCanvasDeviceMode(editor) {
    const deviceId = editor.Devices?.getSelected?.()?.get?.('id') ?? 'desktop';
    const doc = editor.Canvas?.getDocument?.();

    if (! doc?.body) {
        return;
    }

    doc.documentElement.dataset.voodbuilderGjsDevice = deviceId;
    doc.body.dataset.voodbuilderGjsDevice = deviceId;

    if (deviceId === 'desktop' || deviceId === 'tablet') {
        const { setMobileNavOpen } = await loadSiteChromeRuntime();
        setMobileNavOpen(doc, false);
    }
}

export async function bootCanvasSiteChrome(editor) {
    const frameWindow = editor.Canvas?.getWindow?.();

    if (! frameWindow?.document) {
        return;
    }

    await syncCanvasDeviceMode(editor);

    const { initSiteChrome } = await loadSiteChromeRuntime();
    initSiteChrome(frameWindow.document);
}

export function registerCanvasSiteChrome(editor) {
    if (editor.__voodbuilderCanvasSiteChromeRegistered) {
        return;
    }

    editor.__voodbuilderCanvasSiteChromeRegistered = true;

    const boot = () => {
        window.requestAnimationFrame(() => {
            void bootCanvasSiteChrome(editor);
        });
    };

    editor.on('canvas:frame:load', boot);
    editor.on('load', boot);
    editor.on('device:select', boot);
    editor.on('sorter:drag:end', boot);
    editor.on('voodbuilder:site-chrome-updated', boot);
}
