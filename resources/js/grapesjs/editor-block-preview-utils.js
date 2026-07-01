/**
 * Shared SVG helpers for GrapesJS block thumbnails.
 */

const STROKE = 1.15;

export function previewSvg(paths, viewBox = '0 0 48 48') {
    return `<svg class="voodbuilder-gjs-block-icon" xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" fill="none" stroke="currentColor" stroke-width="${STROKE}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
}

export function thumbWrap(svg) {
    return `<div class="voodbuilder-gjs-block-thumb" aria-hidden="true">${svg}</div>`;
}
