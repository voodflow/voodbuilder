/**
 * Vpress code snippet block — same vp-code-block shell as vdocs / vtuts.
 */

const CODE_PROP = 'vpressCodeContent';
const LANG_PROP = 'vpressCodeLang';
const LEGACY_CODE_ATTR = 'custom-code-plugin__code';

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

function buildCodeBlockHtml(language, code) {
    const lang = language || 'text';
    const label = lang.toUpperCase();
    const escaped = escapeHtml(code);

    return `<div class="vp-code-block__header">
        <span class="vp-code-block__lang">${label}</span>
        <button type="button" class="vp-code-block__copy" data-code-copy>Copy</button>
    </div>
    <div class="vp-code-block__body">
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
        if (button.dataset.vpressCodeCopyBound) {
            return;
        }

        button.dataset.vpressCodeCopyBound = '1';

        button.addEventListener('click', async () => {
            const block = button.closest('[data-code-block]');
            const code = block?.querySelector('code')?.textContent?.trim() ?? '';

            if (code === '') {
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

function renderCodeBlockComponent(component) {
    const language = component.get(LANG_PROP) || 'text';
    const code = component.get(CODE_PROP) || '';

    component.components(buildCodeBlockHtml(language, code));
}

function migrateLegacyCustomCode(editor) {
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
            type: 'vpress-code-block',
            [LANG_PROP]: guessLanguage(legacyCode),
            [CODE_PROP]: legacyCode.trim(),
        });

        component.replaceWith(replacement);
    }

    editor.getWrapper().find('[data-vpress-code]').forEach((component) => {
        if (component.get('type') !== 'vpress-code-block') {
            component.set('type', 'vpress-code-block');
        }

        if (! component.get(LANG_PROP)) {
            component.set(LANG_PROP, readLanguageFromElement(component.getEl()), { silent: true });
        }

        if (! component.get(CODE_PROP)) {
            component.set(CODE_PROP, readCodeFromElement(component.getEl()), { silent: true });
        }

        renderCodeBlockComponent(component);
    });
}

export function configureVpressCodeBlock(editor) {
    const { DomComponents, BlockManager } = editor;

    DomComponents.addType('vpress-code-block', {
        isComponent: (element) => {
            if (! element?.classList?.contains('vp-code-block')) {
                return false;
            }

            return element.hasAttribute('data-vpress-code') || element.hasAttribute('data-code-block');
        },
        model: {
            defaults: {
                name: 'Code snippet',
                tagName: 'div',
                droppable: false,
                editable: false,
                attributes: {
                    class: 'vp-code-block',
                    'data-code-block': '',
                    'data-line-numbers': '',
                    'data-vpress-code': '',
                },
                traits: [
                    {
                        type: 'select',
                        label: 'Language',
                        name: LANG_PROP,
                        options: LANGUAGE_OPTIONS,
                    },
                    {
                        type: 'textarea',
                        label: 'Code',
                        name: CODE_PROP,
                        changeProp: true,
                    },
                ],
                [LANG_PROP]: 'text',
                [CODE_PROP]: '',
            },
            init() {
                this.on(`change:${LANG_PROP}`, () => renderCodeBlockComponent(this));
                this.on(`change:${CODE_PROP}`, () => renderCodeBlockComponent(this));

                if (! this.get(CODE_PROP) && this.getEl()) {
                    this.set({
                        [LANG_PROP]: readLanguageFromElement(this.getEl()),
                        [CODE_PROP]: readCodeFromElement(this.getEl()),
                    }, { silent: true });
                }

                renderCodeBlockComponent(this);
            },
        },
        view: {
            events: {
                dblclick: 'onEdit',
            },
            onEdit() {
                const trait = this.model.getTrait(CODE_PROP);

                if (trait) {
                    trait.view?.el?.focus?.();
                }
            },
        },
    });

    if (! BlockManager.get('vpress-code-block')) {
        BlockManager.add('vpress-code-block', {
            label: 'Code snippet',
            category: 'Sections · Content',
            media: `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
            </svg>`,
            content: {
                type: 'vpress-code-block',
                [LANG_PROP]: 'text',
                [CODE_PROP]: '',
            },
        });
    }

    editor.on('load', () => {
        migrateLegacyCustomCode(editor);
        wireCodeCopyButtons(editor.Canvas.getDocument());
    });

    editor.on('canvas:frame:load', () => {
        wireCodeCopyButtons(editor.Canvas.getDocument());
    });

    editor.on('component:add', (component) => {
        if (component.get('type') === 'vpress-code-block') {
            renderCodeBlockComponent(component);
        }
    });
}
