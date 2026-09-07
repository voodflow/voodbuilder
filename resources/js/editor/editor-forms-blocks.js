/**
 * Styled Editor form blocks — theme-aligned fields for canvas and frontend.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { isEditorBlockAllowed } from './block-allowlist.js';
import { registerBlockSettings } from './block-settings/index.js';

export const FORMS_BLOCK_CATEGORY = 'Forms';

const FORM_BLOCK_ID = 'voodbuilder-form';

function fieldRow(label, fieldComponents, extraClasses = []) {
    return {
        tagName: 'div',
        classes: ['vb-gjs-form__field', ...extraClasses],
        components: [
            { type: 'label', components: label },
            ...(Array.isArray(fieldComponents) ? fieldComponents : [fieldComponents]),
        ],
    };
}

function buildStyledFormContent() {
    return {
        type: 'form',
        classes: ['vb-gjs-form'],
        attributes: {
            method: 'post',
        },
        components: [
            fieldRow('Name', {
                type: 'input',
                attributes: {
                    type: 'text',
                    name: 'name',
                    placeholder: 'Your name',
                },
            }),
            fieldRow('Email', {
                type: 'input',
                attributes: {
                    type: 'email',
                    name: 'email',
                    placeholder: 'you@example.com',
                },
            }),
            {
                tagName: 'div',
                classes: ['vb-gjs-form__field', 'vb-gjs-form__field--inline'],
                components: [
                    { type: 'label', components: 'Gender' },
                    {
                        tagName: 'div',
                        classes: ['vb-gjs-form__radio-group'],
                        components: [
                            {
                                tagName: 'label',
                                classes: ['vb-gjs-form__choice'],
                                components: [
                                    { type: 'radio', attributes: { name: 'gender', value: 'M', checked: true } },
                                    ' Male',
                                ],
                            },
                            {
                                tagName: 'label',
                                classes: ['vb-gjs-form__choice'],
                                components: [
                                    { type: 'radio', attributes: { name: 'gender', value: 'F' } },
                                    ' Female',
                                ],
                            },
                        ],
                    },
                ],
            },
            fieldRow('Message', {
                type: 'textarea',
                attributes: {
                    name: 'message',
                    placeholder: 'Your message…',
                },
            }),
            {
                tagName: 'div',
                classes: ['vb-gjs-form__field'],
                components: [
                    {
                        type: 'button',
                        attributes: { type: 'submit' },
                        text: 'Send',
                    },
                ],
            },
        ],
    };
}

function formWireframeSvg() {
    return thumbWrap(previewSvg(
        '<rect x="8" y="10" width="32" height="28" rx="2.5" />'
        + '<path d="M12 16h24M12 21h20M12 26h14" />'
        + '<rect x="12" y="31" width="12" height="4" rx="1" fill="currentColor" opacity="0.2" />',
    ));
}

function ensureFormClasses(component) {
    if (component.get('tagName') !== 'form') {
        return;
    }

    if (! component.getClasses().includes('vb-gjs-form')) {
        component.addClass('vb-gjs-form');
    }
}

function normalizeLegacyFormRows(form) {
    form.components().forEach((row) => {
        if (row.get('tagName') !== 'div') {
            return;
        }

        const classes = row.getClasses();

        if (classes.includes('vb-gjs-form__field')) {
            return;
        }

        const hasChoice = row.find('input[type=checkbox], input[type=radio]').length > 0;

        row.addClass('vb-gjs-form__field');

        if (hasChoice) {
            row.addClass('vb-gjs-form__field--inline');
        }
    });
}

export function editorFormsPluginOptions() {
    return {
        blocks: ['input', 'textarea', 'select', 'button', 'label', 'checkbox', 'radio'],
        category: 'Forms',
    };
}

export function registerVoodbuilderFormBlock(editor) {
    const blockManager = editor.BlockManager;

    blockManager.remove('form');

    // Contact form tile lives in Elements companion; Core keeps form canvas behaviour.
    if (blockManager.get(FORM_BLOCK_ID)) {
        blockManager.remove(FORM_BLOCK_ID);
    }
}

/** Elements companion — contact form BlockManager tile. */
export function registerCompanionFormBlocks(editor) {
    const blockManager = editor.BlockManager;

    blockManager.remove('form');

    if (! isEditorBlockAllowed(editor, FORM_BLOCK_ID)) {
        if (blockManager.get(FORM_BLOCK_ID)) {
            blockManager.remove(FORM_BLOCK_ID);
        }

        return;
    }

    if (! blockManager.get(FORM_BLOCK_ID)) {
        blockManager.add(FORM_BLOCK_ID, {
            label: resolveBlockLabel(FORM_BLOCK_ID, 'Contact form'),
            category: FORMS_BLOCK_CATEGORY,
            media: formWireframeSvg(),
            content: buildStyledFormContent(),
        });
    }
}

export function configureEditorFormsCanvas(editor) {
    const applyForm = (component) => {
        if (component.get('tagName') === 'form') {
            ensureFormClasses(component);
            normalizeLegacyFormRows(component);
        }
    };

    editor.on('load', () => {
        editor.getWrapper().find('form').forEach(applyForm);
    });

    editor.on('component:add', (component) => {
        applyForm(component);

        const form = component.closest('form');

        if (form) {
            ensureFormClasses(form);
        }
    });
}

function newsletterFormRoot(component) {
    if (! component?.get) {
        return null;
    }

    if (component.get('tagName') === 'form') {
        return component.getAttributes()?.['data-voodbuilder-form'] === 'newsletter' ? component : null;
    }

    return component.closest?.('form[data-voodbuilder-form="newsletter"]') ?? null;
}

function newsletterSettingLabel(editor, key, fallback) {
    return editor.__voodbuilderLabels?.[key] ?? fallback;
}

export function registerNewsletterFormSettings(editor) {
    registerBlockSettings({
        id: 'newsletter-form',
        findRoot: (component) => newsletterFormRoot(component),
        matchesRoot: (root) => root.getAttributes()?.['data-voodbuilder-form'] === 'newsletter',
        render: ({ mount, root, editor }) => {
            const lists = editor.__voodbuilderNewsletterLists ?? {};
            const currentList = String(root.getAttributes()?.['data-voodbuilder-newsletter-list'] ?? 'default');
            const wrapper = document.createElement('div');
            wrapper.className = 'voodbuilder-editor-settings voodbuilder-editor-settings--newsletter';

            const title = document.createElement('p');
            title.className = 'voodbuilder-editor-settings__title';
            title.textContent = newsletterSettingLabel(editor, 'newsletterTitle', 'Newsletter');
            wrapper.appendChild(title);

            const selectId = 'voodbuilder-editor-newsletter-list';

            const label = document.createElement('label');
            label.className = 'voodbuilder-editor-settings__label';
            label.htmlFor = selectId;
            label.textContent = newsletterSettingLabel(editor, 'newsletterList', 'Newsletter list');

            const select = document.createElement('select');
            select.id = selectId;
            select.className = 'voodbuilder-editor-settings__input';

            Object.entries(lists).forEach(([value, text]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                option.selected = value === currentList;
                select.appendChild(option);
            });

            select.addEventListener('change', () => {
                root.addAttributes({
                    'data-voodbuilder-newsletter-list': select.value,
                });
            });

            wrapper.appendChild(label);
            wrapper.appendChild(select);

            const hint = document.createElement('p');
            hint.className = 'voodbuilder-editor-settings__hint';
            hint.textContent = newsletterSettingLabel(
                editor,
                'newsletterHint',
                'Submissions are handled via EditorFormSubmitted (form_type newsletter).',
            );
            wrapper.appendChild(hint);

            mount.appendChild(wrapper);
        },
    });
}
