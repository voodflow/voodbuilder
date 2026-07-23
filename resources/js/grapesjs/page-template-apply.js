/**
 * Apply full-page templates to the GrapesJS canvas.
 *
 * In chrome-shell page editors, templates replace only the page-content slot
 * (nav/footer come from the layout shell). A bulk flag suspends expensive
 * per-node handlers during setComponents / slot.components().
 */

import { choiceDialog } from './editor-dialog.js';
import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { settleEditorCanvasPreview } from './vb-runtime.js';
import { scanLinkableButtons } from './grapesjs-button-link.js';

const CHROME_PLACEHOLDER_BLOCK_RE = /data-voodbuilder-block="(?:site_nav_simple|site_footer_columns_simple|site_header|site_footer[^"]*)"/i;

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

/**
 * Drop nav/footer placeholders — chrome shell already owns those regions.
 *
 * @param {string} html
 * @returns {string}
 */
export function stripChromePlaceholdersFromTemplateHtml(html) {
    const raw = String(html ?? '').trim();

    if (raw === '') {
        return '';
    }

    try {
        const doc = new DOMParser().parseFromString(`<body>${raw}</body>`, 'text/html');

        doc.body.querySelectorAll('[data-voodbuilder-block]').forEach((node) => {
            const blockId = String(node.getAttribute('data-voodbuilder-block') ?? '');

            if (
                blockId === 'site_nav_simple'
                || blockId === 'site_header'
                || blockId.startsWith('site_footer')
            ) {
                node.remove();
            }
        });

        return doc.body.innerHTML.trim();
    } catch {
        if (! CHROME_PLACEHOLDER_BLOCK_RE.test(raw)) {
            return raw;
        }

        return raw
            .replace(/<div\b[^>]*data-voodbuilder-block="site_nav_simple"[^>]*>\s*<\/div>/gi, '')
            .replace(/<div\b[^>]*data-voodbuilder-block="site_header"[^>]*>\s*<\/div>/gi, '')
            .replace(/<div\b[^>]*data-voodbuilder-block="site_footer[^"]*"[^>]*>\s*<\/div>/gi, '')
            .trim();
    }
}

export function pageHasContent(editor) {
    if (editor?.__voodbuilderChromeShellMode) {
        const slot = findPageContentSlotInEditor(editor);

        if (! slot) {
            return false;
        }

        return slot.components().some((component) => {
            if (component.components().length > 0) {
                return true;
            }

            const text = String(component.get('content') ?? '').trim();

            return text.length > 0;
        });
    }

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

function settleTemplateCanvas(editor) {
    try {
        const frameDoc = editor.Canvas?.getDocument?.();

        if (frameDoc) {
            settleEditorCanvasPreview({ root: frameDoc });
        }
    } catch {
        // Optional preview settle.
    }
}

/**
 * @param {object} editor
 * @param {() => void} work
 */
function runBulkStructureUpdate(editor, work) {
    editor.__voodbuilderBulkStructureUpdate = true;

    try {
        work();
    } finally {
        window.requestAnimationFrame(() => {
            editor.__voodbuilderBulkStructureUpdate = false;
            settleTemplateCanvas(editor);

            try {
                const root = editor.__voodbuilderChromeShellMode
                    ? findPageContentSlotInEditor(editor)
                    : editor.getWrapper?.();

                scanLinkableButtons(editor, root);
            } catch {
                // Optional CTA upgrade after bulk apply.
            }

            editor.trigger('voodbuilder:site-chrome-updated');

            if ((editor.__voodbuilderCssRebuildSuspendDepth ?? 0) > 0) {
                // Prompt/drop still owns the suspend lock — compile after it releases.
                editor.__voodbuilderFlushCssRebuildOnResume = true;
            } else {
                editor.__voodbuilderSchedulePageCssRebuild?.(200);
            }

            editor.__voodbuilderAfterBulkStructureUpdate?.();
        });
    }
}

function resolveTemplateHtml(editor, template) {
    const payload = templatePayload(template);
    let html = String(payload.html ?? '');

    if (editor.__voodbuilderChromeShellMode) {
        html = stripChromePlaceholdersFromTemplateHtml(html);
    }

    return { payload, html };
}

export function applyTemplatePayload(editor, template) {
    const { payload, html } = resolveTemplateHtml(editor, template);

    runBulkStructureUpdate(editor, () => {
        if (editor.__voodbuilderChromeShellMode) {
            const slot = findPageContentSlotInEditor(editor);

            if (slot?.components) {
                slot.components(html);

                return;
            }
        }

        editor.setComponents(html);
    });

    editor.setStyle(payload.css ?? '');

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        editor.setJs?.(payload.js);
    }

    editor.__voodbuilderApplyPageLiveCss?.(payload.css ?? '');
}

export function appendTemplatePayload(editor, template) {
    const { payload, html } = resolveTemplateHtml(editor, template);

    runBulkStructureUpdate(editor, () => {
        if (editor.__voodbuilderChromeShellMode) {
            const slot = findPageContentSlotInEditor(editor);

            if (slot?.append && html) {
                slot.append(html);

                return;
            }
        }

        const wrapper = editor.getWrapper();

        if (html) {
            wrapper.append(html);
        }
    });

    if (payload.css) {
        const existingCss = String(editor.getCss?.() ?? '').trim();
        const mergedCss = [existingCss, payload.css].filter((chunk) => chunk !== '').join('\n');
        editor.setStyle(mergedCss);
        editor.__voodbuilderApplyPageLiveCss?.(mergedCss);
    }

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        const existingJs = String(editor.getJs?.() ?? '').trim();
        editor.setJs?.([existingJs, payload.js].filter((chunk) => chunk !== '').join('\n'));
    }
}

export async function applyPageTemplateWithPrompt(editor, template, labels = {}, options = {}) {
    const manageSuspend = options.alreadySuspended !== true;

    if (manageSuspend) {
        editor.__voodbuilderSetCssRebuildSuspended?.(true);
    }

    try {
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

        return true;
    } finally {
        if (manageSuspend) {
            editor.__voodbuilderSetCssRebuildSuspended?.(false);
        }
    }
}
