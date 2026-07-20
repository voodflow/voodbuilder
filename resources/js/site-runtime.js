import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initVbRuntime } from './grapesjs/vb-runtime.js';
import { initSiteChrome } from './grapesjs/site-chrome-runtime.js';
import { initPopups } from './popups-runtime.js';

function bootSiteRuntime() {
    initVideoFacades();
    initVbRuntime();
    initSiteChrome();
    initPopups();
}

document.addEventListener('DOMContentLoaded', bootSiteRuntime);
document.addEventListener('livewire:navigated', bootSiteRuntime);
