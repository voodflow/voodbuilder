import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initBricksRuntime } from './grapesjs/bricks-runtime.js';
import { initSiteChrome } from './grapesjs/site-chrome-runtime.js';

function bootSiteRuntime() {
    initVideoFacades();
    initBricksRuntime();
    initSiteChrome();
}

document.addEventListener('DOMContentLoaded', bootSiteRuntime);
document.addEventListener('livewire:navigated', bootSiteRuntime);
