import { initVideoFacades } from './editor/video-runtime.js';
import { initHeroBackgroundVideos } from './editor/hero-video-runtime.js';
import { initVbRuntime } from './editor/vb-runtime.js';
import { initSiteChrome } from './editor/site-chrome-runtime.js';

/** Shared promise so DOMContentLoaded + livewire:navigated cannot double-fetch the chunk. */
let popupsRuntimeImport = null;

function bootPopupsIfConfigured() {
    if (! document.querySelector('[data-voodbuilder-popups-config], [data-vpopups-config]')) {
        return;
    }

    // Dynamic import: skip the popup chunk entirely when no popups are published.
    if (! popupsRuntimeImport) {
        popupsRuntimeImport = import('./popups-runtime.js');
    }

    void popupsRuntimeImport.then(({ initPopups }) => {
        initPopups();
    }).catch(() => {
        // Optional on public pages.
        popupsRuntimeImport = null;
    });
}

let siteRuntimeCoreBooted = false;

function bootSiteRuntime() {
    // Core inits are idempotent / element-bound; still skip the heavy first pass twice
    // when Livewire fires `livewire:navigated` on the initial paint.
    if (! siteRuntimeCoreBooted) {
        initVideoFacades();
        initHeroBackgroundVideos();
        initVbRuntime();
        initSiteChrome();
        siteRuntimeCoreBooted = true;
    } else {
        // Soft re-bind after Livewire navigations (new header/drawer nodes).
        initSiteChrome();
        initVideoFacades();
        initHeroBackgroundVideos();
        initVbRuntime();
    }

    bootPopupsIfConfigured();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSiteRuntime);
} else {
    bootSiteRuntime();
}

document.addEventListener('livewire:navigated', bootSiteRuntime);
