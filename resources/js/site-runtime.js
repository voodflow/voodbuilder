import { initVideoFacades } from './grapesjs/video-runtime.js';
import { initBricksRuntime } from './grapesjs/bricks-runtime.js';

function bootSiteRuntime() {
    initVideoFacades();
    initBricksRuntime();
}

document.addEventListener('DOMContentLoaded', bootSiteRuntime);
document.addEventListener('livewire:navigated', bootSiteRuntime);
