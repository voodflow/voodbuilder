import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initBricksRuntime } from './grapesjs/bricks-runtime.js';
import { initSiteChrome } from './grapesjs/site-chrome-runtime.js';
import { initPopups } from './popups-runtime.js';

function bootSiteRuntime() {
    initVideoFacades();
    initBricksRuntime();
    initSiteChrome();
    initPopups();
}

document.addEventListener('DOMContentLoaded', bootSiteRuntime);
document.addEventListener('livewire:navigated', bootSiteRuntime);
