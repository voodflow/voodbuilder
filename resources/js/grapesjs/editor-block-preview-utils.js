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

export function compactBlockPreviewHtml(html) {
    return String(html ?? '')
        .replace(/\bpy-24\b/g, 'py-8')
        .replace(/\bpy-16\b/g, 'py-6')
        .replace(/\bmd:py-24\b/g, 'md:py-8')
        .replace(/\bmd:py-16\b/g, 'md:py-6');
}

export function htmlBlockPreview(html) {
    const trimmed = String(html ?? '').trim();

    if (trimmed === '') {
        return '';
    }

    return `<div class="voodbuilder-gjs-block-preview"><div class="voodbuilder-gjs-block-preview__scale">${compactBlockPreviewHtml(trimmed)}</div></div>`;
}

export function blockHasHtmlPreview(media) {
    return typeof media === 'string' && media.includes('voodbuilder-gjs-block-preview');
}
