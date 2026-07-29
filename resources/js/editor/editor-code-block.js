/**
 * Voodbuilder code block — same vp-code-block shell as vdocs / vtuts.
 */

import {
    CODE_BLOCK_CATEGORY,
    resolveBlockLabel,
    resolveBlockWireframe,
} from './section-block-meta.js';

const CODE_PROP = 'voodbuilderCodeContent';
const LANG_PROP = 'voodbuilderCodeLang';
const LEGACY_CODE_ATTR = 'custom-code-plugin__code';
const EMPTY_PLACEHOLDER = 'Double-click to add code…';

const LANGUAGE_OPTIONS = [
    { id: 'text', name: 'Plain text' },
    { id: 'json', name: 'JSON' },
    { id: 'php', name: 'PHP' },
    { id: 'javascript', name: 'JavaScript' },
    { id: 'typescript', name: 'TypeScript' },
    { id: 'html', name: 'HTML' },
    { id: 'css', name: 'CSS' },
    { id: 'bash', name: 'Shell' },
    { id: 'sql', name: 'SQL' },
    { id: 'yaml', name: 'YAML' },
];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function readLanguageFromElement(element) {
    const codeEl = element.querySelector('code[class*="language-"]');

    if (! codeEl) {
        return 'text';
    }

    const match = [...codeEl.classList].find((className) => className.startsWith('language-'));

    return match ? match.replace('language-', '') : 'text';
}

function readCodeFromElement(element) {
    const codeEl = element.querySelector('code');

    return codeEl?.textContent ?? '';
}

function languageLabel(language) {
    const lang = language || 'text';

    if (lang === 'text') {
        return 'Code';
    }

    return lang.toUpperCase();
}

function buildCodeBlockHtml(language, code) {
    const lang = language || 'text';
    const label = languageLabel(lang);
    const trimmed = String(code ?? '').trim();
    const isEmpty = trimmed === '';
    const escaped = isEmpty
        ? `<span class="vp-code-block__placeholder">${EMPTY_PLACEHOLDER}</span>`
        : escapeHtml(code);

    return `<div class="vp-code-block__header">
        <span class="vp-code-block__lang">${label}</span>
        <button type="button" class="vp-code-block__copy" data-code-copy${isEmpty ? ' disabled' : ''}>Copy</button>
    </div>
    <div class="vp-code-block__body${isEmpty ? ' vp-code-block__body--empty' : ''}">
        <pre class="m-0 whitespace-pre-wrap break-words bg-transparent p-0 font-mono text-[13px] leading-[1.35]"><code class="language-${lang}">${escaped}</code></pre>
    </div>`;
}

function guessLanguage(code) {
    const trimmed = String(code ?? '').trim();

    if (trimmed === '') {
        return 'text';
    }

    if ((trimmed.startsWith('{') || trimmed.startsWith('[')) && trimmed.includes('"')) {
        try {
            JSON.parse(trimmed);

            return 'json';
        } catch {
            // Not valid JSON — fall through.
        }
    }

    if (trimmed.startsWith('<?php') || trimmed.includes('namespace ') || trimmed.includes('function ')) {
        return 'php';
    }

    if (/^\s*(import|export|const|let|function)\s/m.test(trimmed)) {
        return 'javascript';
    }

    if (/^\s*(<html|<div|<section|<svg)\b/i.test(trimmed)) {
        return 'html';
    }

    return 'text';
}

function wireCodeCopyButtons(documentRoot) {
    if (! documentRoot) {
        return;
    }

    documentRoot.querySelectorAll('[data-code-copy]').forEach((button) => {
        if (button.dataset.voodbuilderCodeCopyBound) {
            return;
        }

        button.dataset.voodbuilderCodeCopyBound = '1';

        button.addEventListener('mousedown', (event) => {
            event.stopPropagation();
        });

        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const block = button.closest('[data-code-block]');
            const code = block?.querySelector('code')?.textContent?.trim() ?? '';

            if (code === '' || code === EMPTY_PLACEHOLDER) {
                return;
            }

            try {
                await navigator.clipboard.writeText(code);
                const original = button.textContent;
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = original;
                }, 1600);
            } catch {
                button.textContent = 'Failed';
                window.setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1600);
            }
        });
    });
}

function extractHighlightedInnerHtml(html) {
    if (typeof DOMParser === 'undefined') {
        return null;
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const block = doc.querySelector('.vp-code-block');

    return block?.innerHTML ?? null;
}

function renderCodeBlockComponent(component, options = {}) {
    const language = component.get(LANG_PROP) || 'text';
    const code = component.get(CODE_PROP) || '';
    const token = (component._voodbuilderCodeRenderToken ?? 0) + 1;
    component._voodbuilderCodeRenderToken = token;

    component.components(buildCodeBlockHtml(language, code));

    const viewEl = component.getView()?.el;

    if (viewEl) {
        wireCodeCopyButtons(viewEl);
    }

    const trimmed = String(code).trim();
    const { codeHighlightUrl, csrf } = options;

    if (! trimmed || ! codeHighlightUrl) {
        return;
    }

    void fetch(codeHighlightUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify({ language, code }),
        credentials: 'same-origin',
    })
        .then(async (response) => {
            if (! response.ok || component._voodbuilderCodeRenderToken !== token) {
                return null;
            }

            return response.json();
        })
        .then((payload) => {
            if (! payload || component._voodbuilderCodeRenderToken !== token) {
                return;
            }

            const inner = extractHighlightedInnerHtml(payload.html ?? '');

            if (! inner) {
                return;
            }

            component.components(inner);

            const highlightedEl = component.getView()?.el;

            if (highlightedEl) {
                wireCodeCopyButtons(highlightedEl);
            }
        })
        .catch(() => {
            // Keep plain fallback in the canvas.
        });
}

function openCodeEditorModal(editor, component) {
    const language = component.get(LANG_PROP) || 'text';
    const code = component.get(CODE_PROP) || '';
    const modal = editor.Modal;

    modal.setTitle('Edit code');
    modal.setContent(`
        <div class="voodbuilder-code-editor-modal">
            <label class="voodbuilder-code-editor-modal__label" for="voodbuilder-code-editor-lang">Language</label>
            <select id="voodbuilder-code-editor-lang" class="voodbuilder-code-editor-modal__select">
                ${LANGUAGE_OPTIONS.map((option) => `<option value="${option.id}"${option.id === language ? ' selected' : ''}>${option.name}</option>`).join('')}
            </select>
            <label class="voodbuilder-code-editor-modal__label" for="voodbuilder-code-editor-content">Source</label>
            <textarea id="voodbuilder-code-editor-content" class="voodbuilder-code-editor-modal__textarea" spellcheck="false" placeholder="// Paste or type your code here">${escapeHtml(code)}</textarea>
            <div class="voodbuilder-code-editor-modal__actions">
                <button type="button" class="voodbuilder-code-editor-modal__save" data-voodbuilder-code-save>Apply</button>
            </div>
        </div>
    `);

    modal.open();

    const textarea = modal.getContentEl()?.querySelector('#voodbuilder-code-editor-content');
    const select = modal.getContentEl()?.querySelector('#voodbuilder-code-editor-lang');
    const saveButton = modal.getContentEl()?.querySelector('[data-voodbuilder-code-save]');

    textarea?.focus();

    saveButton?.addEventListener('click', () => {
        const nextCode = textarea?.value ?? '';
        const nextLang = select?.value || guessLanguage(nextCode);

        component.set({
            [LANG_PROP]: nextLang,
            [CODE_PROP]: nextCode,
        });

        modal.close();
    });
}

function migrateLegacyCustomCode(editor, render) {
    const legacyComponents = [
        ...editor.getWrapper().find('[data-gjs-type=custom-code]'),
        ...editor.getWrapper().find('custom-code'),
    ];

    for (const component of legacyComponents) {
        const legacyCode = component.get(LEGACY_CODE_ATTR)
            ?? component.components().map((child) => child.get('content') ?? '').join('');

        if (! legacyCode?.trim()) {
            continue;
        }

        const replacement = editor.Components.createComponent({
            type: 'voodbuilder-code-block',
            [LANG_PROP]: guessLanguage(legacyCode),
            [CODE_PROP]: legacyCode.trim(),
        });

        component.replaceWith(replacement);
    }

    editor.getWrapper().find('[data-voodbuilder-code]').forEach((component) => {
        if (component.get('type') !== 'voodbuilder-code-block') {
            component.set('type', 'voodbuilder-code-block');
        }

        if (! component.get(LANG_PROP)) {
            component.set(LANG_PROP, readLanguageFromElement(component.getEl()), { silent: true });
        }

        if (! component.get(CODE_PROP)) {
            component.set(CODE_PROP, readCodeFromElement(component.getEl()), { silent: true });
        }

        renderCodeBlockComponent(component, render.options);
    });
}

export function configureEditorCodeBlock(editor, options = {}) {
    const renderOptions = {
        codeHighlightUrl: options.codeHighlightUrl ?? '',
        csrf: options.csrf ?? '',
    };
    const render = (component) => renderCodeBlockComponent(component, renderOptions);
    render.options = renderOptions;

    const { DomComponents, BlockManager } = editor;

    editor.Commands.add('voodbuilder:edit-code', {
        run(ed) {
            const component = ed.getSelected();

            if (component?.get('type') === 'voodbuilder-code-block') {
                openCodeEditorModal(ed, component);
            }
        },
    });

    DomComponents.addType('voodbuilder-code-block', {
        isComponent: (element) => {
            if (! element?.classList?.contains('vp-code-block')) {
                return false;
            }

            return element.hasAttribute('data-voodbuilder-code') || element.hasAttribute('data-code-block');
        },
        model: {
            defaults: {
                name: 'Code block',
                tagName: 'div',
                droppable: false,
                editable: false,
                attributes: {
                    class: 'vp-code-block voodbuilder-code-block',
                    'data-code-block': '',
                    'data-line-numbers': '',
                    'data-voodbuilder-code': '',
                },
                traits: [
                    {
                        type: 'select',
                        label: 'Language',
                        name: LANG_PROP,
                        options: LANGUAGE_OPTIONS,
                    },
                    {
                        type: 'button',
                        label: 'Source',
                        text: 'Edit code…',
                        full: true,
                        command: 'voodbuilder:edit-code',
                    },
                ],
                [LANG_PROP]: 'text',
                [CODE_PROP]: '',
            },
            init() {
                this.on(`change:${LANG_PROP}`, () => render(this));
                this.on(`change:${CODE_PROP}`, () => render(this));

                if (! this.get(CODE_PROP) && this.getEl()) {
                    this.set({
                        [LANG_PROP]: readLanguageFromElement(this.getEl()),
                        [CODE_PROP]: readCodeFromElement(this.getEl()),
                    }, { silent: true });
                }

                render(this);
            },
        },
        view: {
            events: {
                dblclick: 'onEdit',
            },
            onEdit(event) {
                event?.preventDefault?.();
                event?.stopPropagation?.();
                openCodeEditorModal(editor, this.model);
            },
        },
    });

    if (! BlockManager.get('voodbuilder-code-block')) {
        BlockManager.add('voodbuilder-code-block', {
            label: resolveBlockLabel('voodbuilder-code-block', 'Code block'),
            category: CODE_BLOCK_CATEGORY,
            media: resolveBlockWireframe('voodbuilder-code-block'),
            content: {
                type: 'voodbuilder-code-block',
                [LANG_PROP]: 'php',
                [CODE_PROP]: "<?php echo 'Hello';",
            },
        });
    }

    editor.on('load', () => {
        migrateLegacyCustomCode(editor, render);
        wireCodeCopyButtons(editor.Canvas.getDocument());
    });

    editor.on('canvas:frame:load', () => {
        wireCodeCopyButtons(editor.Canvas.getDocument());
    });

    editor.on('component:add', (component) => {
        if (component.get('type') === 'voodbuilder-code-block') {
            render(component);
        }
    });

    editor.on('component:selected', (component) => {
        if (component?.get('type') === 'voodbuilder-code-block') {
            const viewEl = component.getView()?.el;

            if (viewEl) {
                wireCodeCopyButtons(viewEl);
            }
        }
    });
}
