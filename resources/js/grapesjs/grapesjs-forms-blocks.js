/**
 * Styled GrapesJS form blocks — theme-aligned fields for canvas and frontend.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';

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

export function grapesJsFormsPluginOptions() {
    return {
        blocks: ['input', 'textarea', 'select', 'button', 'label', 'checkbox', 'radio'],
        category: 'Forms',
    };
}

export function registerVoodbuilderFormBlock(editor) {
    const blockManager = editor.BlockManager;

    blockManager.remove('form');

    if (! blockManager.get(FORM_BLOCK_ID)) {
        blockManager.add(FORM_BLOCK_ID, {
            label: resolveBlockLabel(FORM_BLOCK_ID, 'Contact form'),
            category: FORMS_BLOCK_CATEGORY,
            media: formWireframeSvg(),
            content: buildStyledFormContent(),
        });
    }
}

export function configureGrapesJsFormsCanvas(editor) {
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
