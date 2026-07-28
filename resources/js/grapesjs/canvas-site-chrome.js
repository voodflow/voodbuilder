/**
 * Boot site header interactions inside the GrapesJS canvas iframe.
 *
 * Static import (not dynamic): shared site-chrome-runtime must not become an
 * async chunk of editor/init.js, or public site-runtime would modulepreload GrapesJS.
 */

import { initSiteChrome, setMobileNavOpen } from './site-chrome-runtime.js';

function findMobileNavComponent(editor, el) {
    if (el?.__gjsv?.model) {
        return el.__gjsv.model;
    }

    const matches = editor.DomComponents?.getWrapper?.()?.find?.('[data-mobile-nav]');

    return Array.isArray(matches) ? matches[0] : matches?.at?.(0) ?? null;
}

function syncMobileNavComponentClasses(editor, open) {
    const doc = editor.Canvas?.getDocument?.();
    const el = doc?.querySelector?.('[data-mobile-nav]');
    const model = findMobileNavComponent(editor, el);

    if (! model?.addClass || ! model?.removeClass) {
        return;
    }

    if (open) {
        model.addClass('is-open');
        model.removeAttributes?.('hidden');
    } else {
        model.removeClass('is-open');
        model.addAttributes?.({ hidden: true });
    }
}

async function syncCanvasDeviceMode(editor) {
    const deviceId = editor.Devices?.getSelected?.()?.get?.('id')
        ?? editor.getDevice?.()
        ?? 'desktop';
    const doc = editor.Canvas?.getDocument?.();

    if (! doc?.body) {
        return;
    }

    doc.documentElement.dataset.voodbuilderGjsDevice = deviceId;
    doc.body.dataset.voodbuilderGjsDevice = deviceId;
    doc.documentElement.setAttribute('data-voodbuilder-gjs-device', deviceId);
    doc.body.setAttribute('data-voodbuilder-gjs-device', deviceId);

    // Explicit logo viewport mode so nav/footer switch even if theme media
    // queries disagree with GrapesJS frame sizing.
    const logoMode = deviceId === 'mobilePortrait' ? 'mobile' : 'desktop';
    doc.documentElement.setAttribute('data-vb-logo-mode', logoMode);
    doc.body.setAttribute('data-vb-logo-mode', logoMode);

    // Always close the drawer when switching viewport — avoids a stuck open panel.
    setMobileNavOpen(doc, false);
    syncMobileNavComponentClasses(editor, false);
}

export async function bootCanvasSiteChrome(editor) {
    const frameWindow = editor.Canvas?.getWindow?.();

    if (! frameWindow?.document) {
        return;
    }

    await syncCanvasDeviceMode(editor);

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
    editor.on('change:device', boot);
    editor.on('sorter:drag:end', boot);
    editor.on('voodbuilder:site-chrome-updated', boot);

    // Keep GrapesJS component model in sync when the runtime toggles the drawer,
    // otherwise a later render restores `is-open` and the menu never closes.
    editor.on('load', () => {
        const frame = editor.Canvas?.getFrameEl?.();
        const doc = frame?.contentDocument;

        if (! doc || doc.documentElement.dataset.voodbuilderMobileNavModelSync === 'true') {
            return;
        }

        doc.documentElement.dataset.voodbuilderMobileNavModelSync = 'true';

        doc.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;

            if (! target) {
                return;
            }

            const isClose = Boolean(target.closest('[data-mobile-nav-close]'));
            const isToggle = Boolean(target.closest('[data-mobile-nav-toggle]'));

            if (! isClose && ! isToggle) {
                return;
            }

            // Defer until after site-chrome-runtime toggles the DOM classes.
            queueMicrotask(() => {
                if (isClose) {
                    syncMobileNavComponentClasses(editor, false);

                    return;
                }

                const nav = doc.querySelector('[data-mobile-nav]');
                const open = Boolean(
                    nav?.classList.contains('is-open')
                    || doc.documentElement.classList.contains('voodbuilder-mobile-nav-open'),
                );
                syncMobileNavComponentClasses(editor, open);
            });
        }, true);
    });
}
