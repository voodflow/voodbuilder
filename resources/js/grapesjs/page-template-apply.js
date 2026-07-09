/**
 * Apply full-page templates to the GrapesJS canvas.
 */

import { choiceDialog } from './editor-dialog.js';

export function templatePayload(template) {
    if (template?.builder_payload) {
        return template.builder_payload;
    }

    return {
        html: template?.html ?? '',
        css: template?.css ?? '',
        js: template?.js ?? '',
    };
}

export function pageHasContent(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return false;
    }

    return wrapper.components().some((component) => {
        if (component.components().length > 0) {
            return true;
        }

        const text = String(component.get('content') ?? '').trim();

        return text.length > 0;
    });
}

export function applyTemplatePayload(editor, template) {
    const payload = templatePayload(template);

    editor.setComponents(payload.html ?? '');
    editor.setStyle(payload.css ?? '');

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        editor.setJs?.(payload.js);
    }

    editor.__voodbuilderApplyPageLiveCss?.(payload.css ?? '');
    editor.__voodbuilderSchedulePageCssRebuild?.(0);
}

export function appendTemplatePayload(editor, template) {
    const payload = templatePayload(template);
    const wrapper = editor.getWrapper();

    if (payload.html) {
        wrapper.append(payload.html);
    }

    if (payload.css) {
        const existingCss = String(editor.getCss?.() ?? '').trim();
        const mergedCss = [existingCss, payload.css].filter((chunk) => chunk !== '').join('\n');
        editor.setStyle(mergedCss);
        editor.__voodbuilderApplyPageLiveCss?.(mergedCss);
        editor.__voodbuilderSchedulePageCssRebuild?.(0);
    }

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        const existingJs = String(editor.getJs?.() ?? '').trim();
        editor.setJs?.([existingJs, payload.js].filter((chunk) => chunk !== '').join('\n'));
    }
}

export async function applyPageTemplateWithPrompt(editor, template, labels = {}) {
    let mode = 'replace';

    if (pageHasContent(editor)) {
        const choice = await choiceDialog({
            title: labels.pageTemplatesApplyChoiceTitle ?? 'Apply page template',
            message: labels.pageTemplatesApplyChoiceMessage ?? 'This page already has content. Replace it or add the template below the existing content?',
            labels,
            choices: [
                {
                    id: 'replace',
                    label: labels.pageTemplatesApplyReplace ?? 'Replace existing content',
                    primary: true,
                },
                {
                    id: 'keep',
                    label: labels.pageTemplatesApplyKeep ?? 'Keep existing content',
                },
                {
                    id: 'cancel',
                    label: labels.dialogCancel ?? 'Cancel',
                    ghost: true,
                },
            ],
        });

        if (! choice || choice === 'cancel') {
            return false;
        }

        mode = choice;
    }

    if (mode === 'keep') {
        appendTemplatePayload(editor, template);
    } else {
        applyTemplatePayload(editor, template);
    }

    window.requestAnimationFrame(() => {
        editor.trigger('voodbuilder:site-chrome-updated');
    });

    return true;
}
