import { initVideoFacades } from './editor/video-runtime.js';
import { initHeroBackgroundVideos } from './editor/hero-video-runtime.js';
import { initVbRuntime } from './editor/vb-runtime.js';
import { initSiteChrome } from './editor/site-chrome-runtime.js';

function bootPopupsIfConfigured() {
    if (! document.querySelector('[data-voodbuilder-popups-config]')) {
        return;
    }

    // Dynamic import: skip the popup chunk entirely when no popups are published.
    void import('./popups-runtime.js').then(({ initPopups }) => {
        initPopups();
    }).catch(() => {
        // Optional on public pages.
    });
}

function bootSiteRuntime() {
    initVideoFacades();
    initHeroBackgroundVideos();
    initVbRuntime();
    initSiteChrome();
    bootPopupsIfConfigured();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSiteRuntime);
} else {
    bootSiteRuntime();
}

document.addEventListener('livewire:navigated', bootSiteRuntime);
