/**
 * Apply full-page templates to the Editor canvas.
 *
 * In chrome-shell page editors, templates replace only the page-content slot
 * (nav/footer come from the layout shell). A bulk flag suspends expensive
 * per-node handlers during setComponents / slot.components().
 */

import { debugSwallowed } from './debug-swallowed.js';
import { choiceDialog } from './editor-dialog.js';
import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { settleEditorCanvasPreview } from './vb-runtime.js';
import { scanLinkableButtons } from './editor-button-link.js';
import {
    restoreContentWidthFromAttributes,
} from './content-width-toolbar.js';
import {
    beginEditorBuild,
    endEditorBuild,
    setEditorBuildLabel,
} from './editor-build-status.js';

const CHROME_PLACEHOLDER_BLOCK_RE = /data-voodbuilder-block="(?:site_nav_simple|site_footer_columns_simple|site_header|site_footer[^"]*)"/i;
const TEMPLATE_APPLY_SCOPE = 'page-template';
const MEANINGFUL_MEDIA_TAGS = new Set(['img', 'video', 'iframe', 'svg', 'picture', 'table', 'form', 'canvas']);

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

/**
 * Editor-only chrome (drop spacers, template drop markers) is not author content.
 *
 * @param {object|null|undefined} component
 */
export function isIgnorablePageContentComponent(component) {
    if (! component) {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const type = String(component.get?.('type') ?? '');

    if (
        attrs['data-voodbuilder-top-drop-spacer']
        || attrs['data-voodbuilder-bottom-drop-spacer']
        || attrs['data-voodbuilder-inner-drop']
        || attrs['data-voodbuilder-page-template-drop']
        || type === 'voodbuilder-top-drop-spacer'
        || type === 'voodbuilder-bottom-drop-spacer'
        || type === 'voodbuilder-inner-drop-slot'
    ) {
        return true;
    }

    return false;
}

/**
 * True when the component tree has real page content (sections, copy, media).
 * Empty wrappers and drop sentinels alone must not trigger Replace/Append.
 *
 * @param {object|null|undefined} component
 */
export function componentHasMeaningfulPageContent(component) {
    if (! component || isIgnorablePageContentComponent(component)) {
        return false;
    }

    const children = typeof component.components === 'function'
        ? [...(component.components() ?? [])]
        : [];

    if (children.some((child) => componentHasMeaningfulPageContent(child))) {
        return true;
    }

    const text = String(component.get?.('content') ?? '').trim();

    if (text !== '') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (
        attrs['data-voodbuilder-section-block']
        || attrs['data-voodbuilder-block']
        || attrs['data-voodbuilder-component']
        || attrs.src
        || attrs['data-src']
    ) {
        return true;
    }

    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    return MEANINGFUL_MEDIA_TAGS.has(tag);
}

export function pageHasContent(editor) {
    if (editor?.__voodbuilderChromeShellMode) {
        const slot = findPageContentSlotInEditor(editor);

        if (! slot) {
            return false;
        }

        const children = typeof slot.components === 'function'
            ? [...(slot.components() ?? [])]
            : [];

        return children.some((component) => componentHasMeaningfulPageContent(component));
    }

    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return false;
    }

    const children = typeof wrapper.components === 'function'
        ? [...(wrapper.components() ?? [])]
        : [];

    return children.some((component) => componentHasMeaningfulPageContent(component));
}

function settleTemplateCanvas(editor) {
    try {
        const frameDoc = editor.Canvas?.getDocument?.();

        if (frameDoc) {
            settleEditorCanvasPreview({ root: frameDoc });
        }
    } catch (error) {
        // Optional preview settle.
        debugSwallowed(error);
    }
}

/**
 * Force page Tailwind JIT after template HTML lands.
 * Soft schedule-if-missing can no-op when fingerprints match a partial live sheet;
 * Save always recompiles — apply must be equally reliable when the template ships
 * no stylesheet (starters with `css: null`).
 *
 * Prefer {@see shouldForceCssRebuildAfterTemplate()} so stored full sheets skip Node.
 *
 * @param {object} editor
 * @param {number} [delayMs]
 */
export function forceTemplatePageCssRebuild(editor, delayMs = 250) {
    const run = () => {
        try {
            editor.__voodbuilderForcePageCssRebuild?.(0);
        } catch {
            try {
                editor.__voodbuilderInvalidatePageCss?.();
            } catch (error) {
                // Optional JIT hooks.
                debugSwallowed(error);
            }
        }

        try {
            editor.trigger?.('voodbuilder:page-css-invalidate');
        } catch (error) {
            // Optional invalidate bus.
            debugSwallowed(error);
        }
    };

    // Grapes finishes parsing setComponents/append after a few frames — compiling
    // too early yields CSS for a partial tree (styles only appear after Save).
    const schedule = globalThis.requestAnimationFrame?.bind(globalThis)
        ?? ((cb) => globalThis.setTimeout(cb, 0));

    schedule(() => {
        globalThis.setTimeout(run, delayMs);
    });
}

/**
 * True when stored CSS looks like a published/JIT utility sheet (not only #id author rules).
 * Author-only sheets are small; Tailwind output has many class selectors.
 *
 * @param {string} css
 * @returns {boolean}
 */
export function cssLooksLikeCompiledUtilitySheet(css) {
    const sheet = String(css ?? '').trim();

    if (sheet === '') {
        return false;
    }

    // Tailwind JIT output is dense with class selectors; author #id / BEM sheets are sparse.
    const classSelectors = sheet.match(/(?:^|[,{\s}])\.[a-zA-Z_]/gm);

    return (classSelectors?.length ?? 0) >= 12;
}

/**
 * Templates saved with live_css (or a full published sheet) already include utilities.
 * Re-running compile-css after apply only adds multi-second wait.
 * Author-only / empty css still needs Node JIT (starters with `css: null`).
 *
 * @param {object} template
 * @param {'replace'|'keep'} [mode]
 */
export function shouldForceCssRebuildAfterTemplate(template, _mode = 'replace') {
    const css = String(templatePayload(template).css ?? '').trim();

    if (css === '') {
        return true;
    }

    // Append/replace both skip when the template already ships utilities
    // (apply merges CSS; missing tokens can be patched by a later soft schedule).
    return ! cssLooksLikeCompiledUtilitySheet(css);
}

/**
 * Resolve the apply overlay without spawning Node when the live sheet is ready.
 *
 * @param {object} editor
 * @param {string} [css]
 */
export function notifyPageCssReadyFromTemplate(editor, css = '') {
    try {
        editor.__voodbuilderSyncPageCssBootTracking?.();
    } catch (error) {
        // Optional boot sync.
        debugSwallowed(error);
    }

    try {
        editor.trigger?.('voodbuilder:page-css-compiled', {
            css: String(css ?? ''),
            html: '',
        });
    } catch (error) {
        // Optional compile bus.
        debugSwallowed(error);
    }
}

/**
 * @param {object} editor
 * @param {number} [timeoutMs]
 * @returns {Promise<void>}
 */
function waitForPageCssCompiled(editor, timeoutMs = 20_000) {
    return new Promise((resolve) => {
        let settled = false;

        const finish = () => {
            if (settled) {
                return;
            }

            settled = true;
            editor.off?.('voodbuilder:page-css-compiled', onCompiled);
            globalThis.clearTimeout(timer);
            resolve();
        };

        const onCompiled = () => finish();
        const timer = globalThis.setTimeout(finish, timeoutMs);

        editor.on?.('voodbuilder:page-css-compiled', onCompiled);
    });
}

/**
 * Apply stored template CSS without wiping live utilities when the payload has none.
 * Starter templates often ship `css: null` and rely entirely on canvas JIT.
 *
 * @param {object} editor
 * @param {string} css
 * @param {{ replace?: boolean }} [options]
 */
function applyTemplateCssPayload(editor, css, options = {}) {
    const normalized = String(css ?? '').trim();
    const replace = options.replace === true;

    if (normalized !== '') {
        editor.setStyle(normalized);
        editor.__voodbuilderApplyPageLiveCss?.(normalized);

        return;
    }

    if (replace) {
        // Clear Style Manager / composer rules from the previous page, but keep
        // the live utility sheet until forceTemplatePageCssRebuild replaces it.
        editor.setStyle('');
    }
}

/**
 * @param {object} editor
 * @param {() => void} work
 * @param {{ rebuildCss?: boolean }} [options]
 */
function runBulkStructureUpdate(editor, work, options = {}) {
    const rebuildCss = options.rebuildCss === true;
    editor.__voodbuilderBulkStructureUpdate = true;

    try {
        work();
    } finally {
        window.requestAnimationFrame(() => {
            editor.__voodbuilderBulkStructureUpdate = false;
            settleTemplateCanvas(editor);
            restoreContentWidthFromAttributes(editor);

            try {
                const root = editor.__voodbuilderChromeShellMode
                    ? findPageContentSlotInEditor(editor)
                    : editor.getWrapper?.();

                scanLinkableButtons(editor, root);
            } catch (error) {
                // Optional CTA upgrade after bulk apply.
                debugSwallowed(error);
            }

            editor.trigger('voodbuilder:site-chrome-updated');

            // CSS rebuild is owned by applyPageTemplateWithPrompt (or callers that
            // pass rebuildCss). Auto-forcing here raced unlock and always recompiled
            // even when the template already shipped a utility sheet.
            if (rebuildCss) {
                if ((editor.__voodbuilderCssRebuildSuspendDepth ?? 0) > 0) {
                    editor.__voodbuilderFlushCssRebuildOnResume = true;
                } else {
                    editor.__voodbuilderForcePageCssRebuild?.(200);
                }
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
    }, { rebuildCss: false });

    applyTemplateCssPayload(editor, payload.css ?? '', { replace: true });

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        editor.setJs?.(payload.js);
    }
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
    }, { rebuildCss: false });

    if (String(payload.css ?? '').trim() !== '') {
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
    let applied = false;
    let buildStarted = false;
    let mode = 'replace';

    if (manageSuspend) {
        editor.__voodbuilderSetCssRebuildSuspended?.(true);
    }

    try {
        const hasContent = pageHasContent(editor);

        if (hasContent) {
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

        beginEditorBuild(editor, TEMPLATE_APPLY_SCOPE);
        buildStarted = true;
        setEditorBuildLabel(
            editor,
            labels.pageTemplatesApplying ?? 'Applying template…',
        );

        // Yield so the canvas overlay paints before Grapes parses a large HTML tree.
        await new Promise((resolve) => globalThis.requestAnimationFrame(() => resolve()));

        if (mode === 'keep') {
            appendTemplatePayload(editor, template);
        } else {
            applyTemplatePayload(editor, template);
        }

        applied = true;

        const willCompile = shouldForceCssRebuildAfterTemplate(template, mode);

        setEditorBuildLabel(
            editor,
            willCompile
                ? (labels.compilingStyles ?? 'Compiling styles…')
                : (labels.pageTemplatesApplyingStyles ?? labels.applyingStyles ?? 'Applying styles…'),
        );

        return true;
    } finally {
        const needsCssRebuild = applied && shouldForceCssRebuildAfterTemplate(template, mode);

        if (manageSuspend) {
            // Avoid a surprise compile-css on unlock when the template CSS was already applied.
            editor.__voodbuilderFlushCssRebuildOnResume = needsCssRebuild;
            editor.__voodbuilderSetCssRebuildSuspended?.(false);
        }

        // Skip Node JIT when the template already shipped a compiled sheet (Save with
        // live_css / import). Starters and author-only css still force a rebuild.
        if (applied) {
            const css = String(templatePayload(template).css ?? '').trim();

            if (needsCssRebuild) {
                // When the outer drop handler still holds suspend, Force is deferred —
                // wait only after we own unlock (manageSuspend), else the drop finally
                // flushes and a second wait would hang until the 20s failsafe.
                forceTemplatePageCssRebuild(editor);

                if (manageSuspend || (editor.__voodbuilderCssRebuildSuspendDepth ?? 0) === 0) {
                    await waitForPageCssCompiled(editor, 45_000);
                }
            } else {
                notifyPageCssReadyFromTemplate(editor, css);
            }
        }

        if (buildStarted) {
            endEditorBuild(editor, TEMPLATE_APPLY_SCOPE);
        }
    }
}
