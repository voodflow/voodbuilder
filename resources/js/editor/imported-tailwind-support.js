/**
 * Client-side mirror of EditorImportedTailwindSupport for import preview.
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

const LAYOUT_PSEUDO_TAGS = ['container', 'row', 'col', 'column', 'columns', 'fragment'];

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

    migrated = inlineBackgroundImageClasses(migrated);

    migrated = migrated
        .replace(/<el-dialog-panel\b/gi, '<div')
        .replace(/<\/el-dialog-panel>/gi, '</div>')
        .replace(/<el-dialog\b/gi, '<div')
        .replace(/<\/el-dialog>/gi, '</div>')
        .replace(/\s(?:command|commandfor)=(["']).*?\1/gi, '');

    migrated = simplifyFrameworkLayoutTags(migrated);

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

    return ensureEditorLayoutShell(migrated);
}

/**
 * Astro/React layout helpers → plain divs (mirrors PHP simplifyCustomElements).
 *
 * @param {string} html
 * @returns {string}
 */
function simplifyFrameworkLayoutTags(html) {
    let next = String(html ?? '');

    for (const tag of LAYOUT_PSEUDO_TAGS) {
        const open = new RegExp(`<${tag}\\b([^>]*)>`, 'gi');
        const close = new RegExp(`</${tag}\\s*>`, 'gi');

        next = next.replace(open, (_, attrs) => {
            let attrsStr = String(attrs ?? '');

            if (tag.toLowerCase() !== 'container') {
                return `<div${attrsStr}>`;
            }

            let classAttr = '';
            const classMatch = attrsStr.match(/\bclass=(["'])(.*?)\1/i);

            if (classMatch) {
                const tokens = classMatch[2].split(/\s+/).filter(Boolean);

                if (! tokens.includes('voodbuilder-editor-container')) {
                    tokens.push('voodbuilder-editor-container');
                }

                if (! tokens.includes('w-full')) {
                    tokens.push('w-full');
                }

                classAttr = ` class="${tokens.join(' ')}"`;
                attrsStr = attrsStr.replace(/\bclass=(["'])(.*?)\1/i, '');
            } else {
                classAttr = ' class="voodbuilder-editor-container w-full"';
            }

            if (! /\bdata-voodbuilder-role=/.test(attrsStr)) {
                attrsStr += ' data-voodbuilder-role="content"';
            }

            return `<div${attrsStr}${classAttr}>`;
        });
        next = next.replace(close, '</div>');
    }

    return next;
}

/**
 * Ensure section → content shell for content-width toolbar (mirrors PHP).
 *
 * @param {string} html
 * @returns {string}
 */
export function ensureEditorLayoutShell(html) {
    const trimmed = String(html ?? '').trim();

    if (! trimmed) {
        return trimmed;
    }

    if (/\bvoodbuilder-editor-section\b/.test(trimmed)
        && /\bdata-voodbuilder-role=(["'])content\1/.test(trimmed)) {
        return trimmed;
    }

    if (typeof DOMParser === 'undefined') {
        return [
            '<section class="voodbuilder-editor-section voodbuilder-pasted-component relative w-full">',
            '<div class="voodbuilder-editor-container mx-auto w-full max-w-[80rem]" data-voodbuilder-role="content" data-voodbuilder-content-width="normal" style="width:100%;max-width:80rem;margin-left:auto;margin-right:auto;">',
            trimmed,
            '</div></section>',
        ].join('');
    }

    const document = new DOMParser().parseFromString(`<body>${trimmed}</body>`, 'text/html');
    const body = document.body;
    const roots = [...body.children];

    if (roots.length === 0) {
        return trimmed;
    }

    const section = document.createElement('section');
    section.className = 'voodbuilder-editor-section voodbuilder-pasted-component relative w-full';

    const container = document.createElement('div');
    container.className = 'voodbuilder-editor-container mx-auto w-full max-w-[80rem]';
    container.setAttribute('data-voodbuilder-role', 'content');
    container.setAttribute('data-voodbuilder-content-width', 'normal');
    container.setAttribute('style', 'width:100%;max-width:80rem;margin-left:auto;margin-right:auto;');

    if (roots.length === 1) {
        const only = roots[0];
        const id = only.getAttribute('id');

        if (id) {
            section.id = id;
            only.removeAttribute('id');
        }

        only.classList.remove('voodbuilder-pasted-component');

        const directShell = [...only.children].find((child) => (
            child.classList?.contains('voodbuilder-editor-container')
            || child.getAttribute('data-voodbuilder-role') === 'content'
        ));

        if (directShell) {
            while (only.firstChild) {
                section.appendChild(only.firstChild);
            }

            const shell = section.querySelector('[data-voodbuilder-role="content"], .voodbuilder-editor-container');

            if (shell) {
                shell.classList.add('voodbuilder-editor-container', 'mx-auto', 'w-full', 'max-w-[80rem]');
                shell.setAttribute('data-voodbuilder-role', 'content');

                if (! shell.hasAttribute('data-voodbuilder-content-width')) {
                    shell.setAttribute('data-voodbuilder-content-width', 'normal');
                }
            }

            return section.outerHTML;
        }

        const isTrivial = only.tagName === 'DIV'
            && [...only.classList].every((token) => ['relative', 'voodbuilder-pasted-component'].includes(token));

        if (isTrivial) {
            while (only.firstChild) {
                container.appendChild(only.firstChild);
            }
        } else {
            container.appendChild(only);
        }
    } else {
        for (const root of roots) {
            root.classList?.remove?.('voodbuilder-pasted-component');
            container.appendChild(root);
        }
    }

    section.appendChild(container);

    return section.outerHTML;
}

function parseBackgroundUrlClass(className) {
    if (! className.startsWith('bg-[url(') || ! className.endsWith(')]')) {
        return null;
    }

    const inner = className.slice(8, -2).trim();

    if (! inner) {
        return null;
    }

    const quote = inner[0];

    if ((quote === '"' || quote === "'") && inner.endsWith(quote)) {
        return inner.slice(1, -1);
    }

    return inner;
}

function mergeStyleProperty(style, property, value) {
    const declaration = `${property}: ${value};`;
    const trimmed = String(style ?? '').trim();

    if (! trimmed) {
        return declaration;
    }

    const pattern = new RegExp(`\\b${property}\\s*:[^;]*;?`, 'i');

    if (pattern.test(trimmed)) {
        return trimmed.replace(pattern, declaration).trim();
    }

    return `${trimmed.replace(/;$/, '')};${declaration}`;
}

function cssUrl(url) {
    const escaped = String(url ?? '').trim().replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    return `url('${escaped}')`;
}

export function inlineBackgroundImageClasses(html) {
    if (! html || ! html.includes('bg-[url')) {
        return html;
    }

    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');

    doc.body.querySelectorAll('[class]').forEach((element) => {
        const classes = element.getAttribute('class')?.split(/\s+/).filter(Boolean) ?? [];
        const remaining = [];
        let backgroundUrl = null;

        for (const className of classes) {
            const url = parseBackgroundUrlClass(className);

            if (url !== null) {
                backgroundUrl = url;
            } else {
                remaining.push(className);
            }
        }

        if (backgroundUrl === null) {
            return;
        }

        let style = element.getAttribute('style') ?? '';
        style = mergeStyleProperty(style, 'background-image', cssUrl(backgroundUrl));

        if (remaining.includes('bg-cover')) {
            style = mergeStyleProperty(style, 'background-size', 'cover');
        }

        if (remaining.includes('bg-center')) {
            style = mergeStyleProperty(style, 'background-position', 'center');
        }

        if (remaining.includes('bg-no-repeat')) {
            style = mergeStyleProperty(style, 'background-repeat', 'no-repeat');
        }

        element.setAttribute('style', style);
        element.setAttribute('class', remaining.join(' '));
    });

    return doc.body.innerHTML.trim();
}

export function importedTailwindPreviewCss() {
    return `
.voodbuilder-pasted-component { position: relative; --color-primary: var(--color-vp-brand-2); --color-primary-hover: var(--color-vp-brand-1); --color-primary-focus: var(--color-vp-brand-1); --color-primary-line: var(--color-vp-brand-2); --color-primary-foreground: #ffffff; --color-foreground: var(--color-vp-text-1); --color-layer: var(--color-vp-bg-elv); --color-layer-hover: var(--color-vp-bg-alt); --color-layer-focus: var(--color-vp-bg-alt); --color-layer-line: var(--color-vp-divider); --color-layer-foreground: var(--color-vp-text-1); --color-surface-1: var(--color-vp-bg-alt); --color-surface: var(--color-vp-bg-alt); --color-plain: var(--color-vp-bg-elv); --color-inverse: #ffffff; --color-foreground-inverse: #ffffff; --color-muted-hover: var(--color-vp-bg-alt); --color-muted-focus: var(--color-vp-bg-alt); --color-travia-transparent: transparent; }
.voodbuilder-pasted-component dialog:not([open]) { display: none; }
.voodbuilder-pasted-component:has(> header.absolute, header.absolute) { min-height: 42rem; }
.voodbuilder-pasted-component > header.absolute { position: absolute; left: 0; right: 0; top: 0; z-index: 50; }
.voodbuilder-pasted-component .hidden { display: none; }
.voodbuilder-pasted-component .flex { display: flex; }
.voodbuilder-pasted-component .inline-flex { display: inline-flex; }
.voodbuilder-pasted-component .grid { display: grid; }
.voodbuilder-pasted-component .absolute { position: absolute; }
.voodbuilder-pasted-component .relative { position: relative; }
.voodbuilder-pasted-component .inset-x-0 { left: 0; right: 0; }
.voodbuilder-pasted-component .inset-s-0 { inset-inline-start: 0; }
.voodbuilder-pasted-component .inset-e-0 { inset-inline-end: 0; }
.voodbuilder-pasted-component .top-0 { top: 0; }
.voodbuilder-pasted-component .z-50 { z-index: 50; }
.voodbuilder-pasted-component .items-center { align-items: center; }
.voodbuilder-pasted-component .justify-between { justify-content: space-between; }
.voodbuilder-pasted-component .justify-center { justify-content: center; }
.voodbuilder-pasted-component .text-white { color: var(--color-white, #ffffff); }
.voodbuilder-pasted-component .text-primary { color: var(--color-primary); }
.voodbuilder-pasted-component .text-foreground { color: var(--color-foreground); }
.voodbuilder-pasted-component .text-foreground-inverse { color: var(--color-foreground-inverse); }
.voodbuilder-pasted-component .text-inverse { color: var(--color-inverse); }
.voodbuilder-pasted-component .dark\\:text-neutral-900 { color: var(--color-neutral-900, #171717); }
.voodbuilder-pasted-component .text-primary-foreground { color: var(--color-primary-foreground); }
.voodbuilder-pasted-component .text-layer-foreground { color: var(--color-layer-foreground); }
.voodbuilder-pasted-component .text-foreground-inverse { color: var(--color-foreground-inverse); }
.voodbuilder-pasted-component .text-inverse { color: var(--color-inverse); }
.voodbuilder-pasted-component .bg-primary { background-color: var(--color-primary); }
.voodbuilder-pasted-component .hover\\:bg-primary-hover:hover { background-color: var(--color-primary-hover); }
.voodbuilder-pasted-component .focus\\:bg-primary-focus:focus { background-color: var(--color-primary-focus); }
.voodbuilder-pasted-component .bg-layer { background-color: var(--color-layer); }
.voodbuilder-pasted-component .bg-surface { background-color: var(--color-surface); }
.voodbuilder-pasted-component .bg-plain { background-color: var(--color-plain); }
.voodbuilder-pasted-component .hover\\:bg-layer-hover:hover { background-color: var(--color-layer-hover); }
.voodbuilder-pasted-component .hover\\:bg-muted-hover:hover { background-color: var(--color-muted-hover); }
.voodbuilder-pasted-component .focus\\:bg-layer-focus:focus { background-color: var(--color-layer-focus); }
.voodbuilder-pasted-component .focus\\:bg-muted-focus:focus { background-color: var(--color-muted-focus); }
.voodbuilder-pasted-component .border-primary-line { border-color: var(--color-primary-line); }
.voodbuilder-pasted-component .border-layer-line { border-color: var(--color-layer-line); }
.voodbuilder-pasted-component .from-surface-1 { --vb-tw-gradient-from: var(--color-surface-1); --vb-tw-gradient-stops: var(--vb-tw-gradient-from), var(--vb-tw-gradient-to, transparent); }
.voodbuilder-pasted-component .to-travia-transparent { --vb-tw-gradient-to: var(--color-travia-transparent); }
.voodbuilder-pasted-component .size-4 { width: 1rem; height: 1rem; }
.voodbuilder-pasted-component .shadow-2xs { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
@media (min-width: 1024px) {
  .voodbuilder-pasted-component .lg\\:flex { display: flex; }
  .voodbuilder-pasted-component .lg\\:hidden { display: none; }
  .voodbuilder-pasted-component .lg\\:flex-1 { flex: 1 1 0%; }
  .voodbuilder-pasted-component .lg\\:gap-x-12 { column-gap: 3rem; }
  .voodbuilder-pasted-component .lg\\:justify-end { justify-content: flex-end; }
  .voodbuilder-pasted-component .lg\\:px-8 { padding-left: 2rem; padding-right: 2rem; }
}
.voodbuilder-pasted-component .bg-indigo-600 { background-color: var(--color-vp-brand-3); }
.voodbuilder-pasted-component .hover\\:bg-indigo-500:hover { background-color: var(--color-vp-brand-2); }
.voodbuilder-pasted-component .bg-vp-brand-3 { background-color: var(--color-vp-brand-3); }
.voodbuilder-pasted-component .hover\\:bg-vp-brand-2:hover { background-color: var(--color-vp-brand-2); }
.voodbuilder-pasted-component .text-indigo-600 { color: var(--color-vp-brand-2); }
.voodbuilder-pasted-component .bg-gradient-to-tr { background-image: linear-gradient(to top right, var(--vb-tw-gradient-stops, var(--color-surface-1), transparent)); }
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
