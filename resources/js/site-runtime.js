import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initBricksRuntime } from './grapesjs/bricks-runtime.js';
import { initProfileMenus } from './grapesjs/site-chrome-runtime.js';

function bootSiteRuntime() {
    initVideoFacades();
    initBricksRuntime();
    initProfileMenus();
}

document.addEventListener('DOMContentLoaded', bootSiteRuntime);
document.addEventListener('livewire:navigated', bootSiteRuntime);
