import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initVbRuntime } from './grapesjs/vb-runtime.js';
import { initSiteChrome } from './grapesjs/site-chrome-runtime.js';

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
