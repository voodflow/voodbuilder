/**
 * Client-side mirror of GrapesJsImportedTailwindSupport for import preview.
 */

const CLASS_REPLACEMENTS = {
    'bg-linear-to-t': 'bg-gradient-to-t',
    'bg-linear-to-tr': 'bg-gradient-to-tr',
    'bg-linear-to-r': 'bg-gradient-to-r',
    'bg-linear-to-br': 'bg-gradient-to-br',
    'bg-linear-to-b': 'bg-gradient-to-b',
    'bg-linear-to-bl': 'bg-gradient-to-bl',
    'bg-linear-to-l': 'bg-gradient-to-l',
    'bg-linear-to-tl': 'bg-gradient-to-tl',
    'shadow-xs': 'shadow-sm',
};

const LINE_HEIGHT_REPLACEMENTS = {
    'text-sm/6': 'text-sm leading-6',
    'text-base/7': 'text-base leading-7',
    'text-xl/8': 'text-xl leading-8',
    'sm:text-xl/8': 'sm:text-xl sm:leading-8',
};

export function migrateImportedTailwindHtml(html) {
    let migrated = String(html ?? '');

    for (const [from, to] of Object.entries(LINE_HEIGHT_REPLACEMENTS)) {
        migrated = migrated.replace(new RegExp(`\\b${from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'g'), to);
    }

    migrated = migrated.replace(/\bclass=(["'])(.*?)\1/gi, (_, quote, classes) => {
        const next = classes
            .split(/\s+/)
            .filter(Boolean)
            .map((className) => CLASS_REPLACEMENTS[className] ?? className)
            .join(' ');

        return `class=${quote}${next}${quote}`;
    });

    migrated = migrated
        .replace(/<el-dialog-panel\b/gi, '<div')
        .replace(/<\/el-dialog-panel>/gi, '</div>')
        .replace(/<el-dialog\b/gi, '<div')
        .replace(/<\/el-dialog>/gi, '</div>')
        .replace(/\s(?:command|commandfor)=(["']).*?\1/gi, '');

    if (! /\bvoodbuilder-pasted-component\b/.test(migrated)) {
        const trimmed = migrated.trim();

        if (/^<(\w+)([^>]*)>[\s\S]*<\/\1>\s*$/i.test(trimmed)) {
            migrated = trimmed.replace(/^<(\w+)([^>]*)>/i, (match, tag, attrs) => {
                if (/\bclass=/.test(attrs)) {
                    return match.replace(/\bclass=(["'])(.*?)\1/i, (classMatch, quote, classes) => {
                        return `class=${quote}${classes} voodbuilder-pasted-component relative${quote}`;
                    });
                }

                return `<${tag}${attrs} class="voodbuilder-pasted-component relative">`;
            });
        } else if (trimmed) {
            migrated = `<div class="voodbuilder-pasted-component relative">${trimmed}</div>`;
        }
    }

    if (/\bdark:[A-Za-z0-9_\-!/\[\]#%.]+/i.test(migrated)
        && ! /\bvoodbuilder-pasted-component\b[^"'>]*\bdark\b/.test(migrated)) {
        migrated = migrated.replace(
            /\bclass=(["'])([^"']*\bvoodbuilder-pasted-component\b[^"']*)\1/i,
            (match, quote, classes) => {
                const tokens = classes.split(/\s+/).filter(Boolean);

                if (tokens.includes('dark')) {
                    return match;
                }

                return `class=${quote}dark ${classes}${quote}`;
            },
        );
    }

    return migrated;
}

export function importedTailwindPreviewCss() {
    return `
.voodbuilder-pasted-component { position: relative; }
.voodbuilder-pasted-component dialog:not([open]) { display: none; }
.voodbuilder-pasted-component:has(> header.absolute, header.absolute) { min-height: 42rem; }
.voodbuilder-pasted-component > header.absolute { position: absolute; left: 0; right: 0; top: 0; z-index: 50; }
.voodbuilder-pasted-component .hidden { display: none; }
.voodbuilder-pasted-component .flex { display: flex; }
.voodbuilder-pasted-component .inline-flex { display: inline-flex; }
.voodbuilder-pasted-component .absolute { position: absolute; }
.voodbuilder-pasted-component .relative { position: relative; }
.voodbuilder-pasted-component .inset-x-0 { left: 0; right: 0; }
.voodbuilder-pasted-component .top-0 { top: 0; }
.voodbuilder-pasted-component .z-50 { z-index: 50; }
.voodbuilder-pasted-component .items-center { align-items: center; }
.voodbuilder-pasted-component .justify-between { justify-content: space-between; }
.voodbuilder-pasted-component .justify-center { justify-content: center; }
@media (min-width: 1024px) {
  .voodbuilder-pasted-component .lg\\:flex { display: flex; }
  .voodbuilder-pasted-component .lg\\:hidden { display: none; }
  .voodbuilder-pasted-component .lg\\:flex-1 { flex: 1 1 0%; }
  .voodbuilder-pasted-component .lg\\:gap-x-12 { column-gap: 3rem; }
  .voodbuilder-pasted-component .lg\\:justify-end { justify-content: flex-end; }
  .voodbuilder-pasted-component .lg\\:px-8 { padding-left: 2rem; padding-right: 2rem; }
}
.voodbuilder-pasted-component .bg-indigo-600 { background-color: #4f46e5; }
.voodbuilder-pasted-component .hover\\:bg-indigo-500:hover { background-color: #6366f1; }
.voodbuilder-pasted-component .text-indigo-600 { color: #4f46e5; }
.voodbuilder-pasted-component .bg-gradient-to-tr { background-image: linear-gradient(to top right, var(--vb-tw-gradient-stops, #ff80b5, #9089fc)); }
.voodbuilder-pasted-component .from-\\[\\#ff80b5\\] { --vb-tw-gradient-from: #ff80b5; --vb-tw-gradient-stops: var(--vb-tw-gradient-from), var(--vb-tw-gradient-to, transparent); }
.voodbuilder-pasted-component .to-\\[\\#9089fc\\] { --vb-tw-gradient-to: #9089fc; }
.voodbuilder-pasted-component .blur-3xl { filter: blur(64px); }
.voodbuilder-pasted-component .shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
.voodbuilder-pasted-component .text-balance { text-wrap: balance; }
.voodbuilder-pasted-component .text-pretty { text-wrap: pretty; }
.voodbuilder-pasted-component .size-6 { width: 1.5rem; height: 1.5rem; }
.voodbuilder-pasted-component .aspect-1155\\/678 { aspect-ratio: 1155 / 678; }
.voodbuilder-pasted-component .w-144\\.5 { width: 36.125rem; }
@media (min-width: 640px) {
  .voodbuilder-pasted-component .sm\\:w-288\\.75 { width: 72.1875rem; }
}
`.trim();
}
