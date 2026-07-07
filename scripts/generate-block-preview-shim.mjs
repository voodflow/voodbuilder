import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

const TAILWIND_MAP = {
    flex: 'display:flex',
    'inline-flex': 'display:inline-flex',
    block: 'display:block',
    hidden: 'display:none',
    'flex-col': 'flex-direction:column',
    'flex-wrap': 'flex-wrap:wrap',
    'flex-nowrap': 'flex-wrap:nowrap',
    'items-center': 'align-items:center',
    'items-start': 'align-items:flex-start',
    'items-end': 'align-items:flex-end',
    'justify-center': 'justify-content:center',
    'justify-between': 'justify-content:space-between',
    'justify-start': 'justify-content:flex-start',
    'justify-end': 'justify-content:flex-end',
    'shrink-0': 'flex-shrink:0',
    grow: 'flex-grow:1',
    relative: 'position:relative',
    absolute: 'position:absolute',
    'inset-0': 'inset:0',
    'w-full': 'width:100%',
    'h-full': 'height:100%',
    'mx-auto': 'margin-left:auto;margin-right:auto',
    'text-center': 'text-align:center',
    'text-left': 'text-align:left',
    'text-right': 'text-align:right',
    'font-medium': 'font-weight:500',
    'font-semibold': 'font-weight:600',
    'font-bold': 'font-weight:700',
    'leading-none': 'line-height:1',
    'leading-relaxed': 'line-height:1.625',
    'tracking-widest': 'letter-spacing:0.1em',
    uppercase: 'text-transform:uppercase',
    'overflow-hidden': 'overflow:hidden',
    'object-cover': 'object-fit:cover',
    'object-center': 'object-position:center',
    rounded: 'border-radius:0.25rem',
    'rounded-lg': 'border-radius:0.5rem',
    'rounded-full': 'border-radius:9999px',
    border: 'border-width:1px;border-style:solid',
    'border-0': 'border-width:0',
    container: 'width:100%;max-width:72rem;margin-left:auto;margin-right:auto',
    'voodbuilder-gjs-container': 'width:100%;max-width:90rem;margin-left:auto;margin-right:auto',
    'body-font': 'font-family:system-ui,sans-serif;color:#4b5563',
    'title-font': 'font-family:system-ui,sans-serif;letter-spacing:-0.025em',
    'w-3': 'width:0.75rem', 'h-3': 'height:0.75rem',
    'w-4': 'width:1rem', 'h-4': 'height:1rem',
    'w-5': 'width:1.25rem', 'h-5': 'height:1.25rem',
    'w-6': 'width:1.5rem', 'h-6': 'height:1.5rem',
    'w-8': 'width:2rem', 'h-8': 'height:2rem',
    'w-10': 'width:2.5rem', 'h-10': 'height:2.5rem',
    'w-12': 'width:3rem', 'h-12': 'height:3rem',
    'w-16': 'width:4rem', 'h-16': 'height:4rem',
    'p-2': 'padding:0.5rem', 'p-4': 'padding:1rem',
    'px-4': 'padding-left:1rem;padding-right:1rem',
    'px-5': 'padding-left:1.25rem;padding-right:1.25rem',
    'py-1': 'padding-top:0.25rem;padding-bottom:0.25rem',
    'py-2': 'padding-top:0.5rem;padding-bottom:0.5rem',
    'py-3': 'padding-top:0.75rem;padding-bottom:0.75rem',
    'py-6': 'padding-top:1.5rem;padding-bottom:1.5rem',
    'py-8': 'padding-top:2rem;padding-bottom:2rem',
    'py-24': 'padding-top:2rem;padding-bottom:2rem',
    'mb-1': 'margin-bottom:0.25rem', 'mb-2': 'margin-bottom:0.5rem', 'mb-3': 'margin-bottom:0.75rem',
    'mb-4': 'margin-bottom:1rem', 'mb-10': 'margin-bottom:2.5rem',
    'mt-3': 'margin-top:0.75rem', 'mt-4': 'margin-top:1rem',
    'mr-2': 'margin-right:0.5rem', 'ml-2': 'margin-left:0.5rem',
    'pl-4': 'padding-left:1rem',
    'text-xs': 'font-size:0.75rem;line-height:1rem',
    'text-sm': 'font-size:0.875rem;line-height:1.25rem',
    'text-base': 'font-size:1rem;line-height:1.5rem',
    'text-lg': 'font-size:1.125rem;line-height:1.75rem',
    'text-xl': 'font-size:1.25rem;line-height:1.75rem',
    'text-2xl': 'font-size:1.5rem;line-height:2rem',
    'text-gray-400': 'color:#9ca3af', 'text-gray-500': 'color:#6b7280', 'text-gray-600': 'color:#4b5563',
    'text-gray-800': 'color:#1f2937', 'text-gray-900': 'color:#111827',
    'text-white': 'color:#fff', 'text-indigo-500': 'color:#6366f1',
    'bg-gray-100': 'background-color:#f3f4f6', 'bg-gray-200': 'background-color:#e5e7eb',
    'bg-indigo-100': 'background-color:#e0e7ff', 'bg-indigo-500': 'background-color:#6366f1',
    'border-gray-200': 'border-color:#e5e7eb', 'border-gray-300': 'border-color:#d1d5db',
    'w-1/2': 'width:50%', 'w-1/3': 'width:33.333333%', 'w-1/4': 'width:25%',
    'lg:w-1/3': 'width:33.333333%', 'lg:w-1/4': 'width:25%', 'md:w-1/2': 'width:50%',
    'sm:w-1/2': 'width:50%',
};

function escapeClass(className) {
    return className.replace(/([:!\\/[\].])/g, '\\$1');
}

const blocks = JSON.parse(fs.readFileSync(path.join(root, 'resources/grapesjs/section-blocks.json'), 'utf8'));
const used = new Set();

for (const block of blocks) {
    const html = `${block.content ?? ''}${block.preview ?? ''}`;

    for (const match of html.matchAll(/class="([^"]+)"/g)) {
        for (const className of match[1].split(/\s+/)) {
            if (className) {
                used.add(className);
            }
        }
    }
}

const scope = '.voodbuilder-gjs-block-preview__scale';
let css = '/** Sidebar block preview utility shim (generated). */\n\n';
css += `${scope}{font-family:system-ui,-apple-system,sans-serif;font-size:14px;line-height:1.4;color:#4b5563;box-sizing:border-box}\n`;
css += `${scope} *,${scope} *::before,${scope} *::after{box-sizing:border-box}\n`;
css += `${scope} img{max-width:100%;height:auto;display:block}\n`;
css += `${scope} svg{display:inline-block;flex-shrink:0;width:1.25rem;height:1.25rem;max-width:1.25rem;max-height:1.25rem}\n`;
css += `${scope} .rounded-full svg,${scope} [class*="rounded-full"] svg{width:1rem;height:1rem;max-width:1rem;max-height:1rem}\n`;

const written = new Set();

for (const className of [...used].sort()) {
    if (! className || className.includes('[')) {
        continue;
    }

    const declaration = TAILWIND_MAP[className] ?? TAILWIND_MAP[className.replace(/^(sm|md|lg|xl|2xl):/, '')];

    if (! declaration || written.has(className)) {
        continue;
    }

    written.add(className);
    css += `${scope} .${escapeClass(className)}{${declaration}}\n`;
}

const out = path.join(root, 'resources/css/grapesjs/block-preview-shim.css');
fs.writeFileSync(out, css);
console.log(`Wrote ${written.size} rules to ${out}`);
