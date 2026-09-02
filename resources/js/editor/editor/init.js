/**
 * Voodbuilder Editor bootstrap — integration layer only.
 *
 * Customise behaviour via init options, events, and plugins (voodbuilder-editor.js).
 * Never patch node_modules/grapesjs: changes there are lost on npm update.
 */
import '../inspector-color-preload.js';
import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
// GrapesJS CSS is imported by resources/css/editor/editor.css, which Vite always emits
// alongside this entry (VoodbuilderPaths::viteInputEntries). Importing it here too shipped
// every gjs-* rule in both bundles.
import 'grapick/dist/grapick.min.css';

import { alertDialog } from '../editor-dialog.js';
import { createInspectorEmptyState } from '../inspector-empty-state.js';
import { registerMediaPickerCommands, registerVideoAssetType } from '../editor-assets.js';
import voodbuilderEditorPlugin, {
    applyFreshFooterAttributes,
    applySiteFooterColumns,
    applySiteFooterSettingsPreview,
    applySiteNavSettingsPreview,
    captureContainerAuthorClasses,
    configureSiteFooterTraits,
    configureSiteNavTraits,
    ensureLayoutSectionTraits,
    isSiteFooterBlock,
    isSiteNavBlock,
    isSiteHeaderBlock,
    lockDynamicPreviewContent,
    prioritizeBlockCategories,
    pruneEmptyDynamicBlocks,
    pruneEmptySections,
    refreshDynamicSlots,
    registerBlocks,
    registerSiteNavChromeButtonType,
    restoreContainerAuthorClasses,
    sanitizeBlockHtml,
    syncDynamicBlockAttributes,
    syncSiteHeaderConfig,
    syncSiteFooterConfig,
} from '../plugins/voodbuilder.js';
import { stripInvalidDomAttributes } from '../core/html-sanitize.js';
import { configureEditorPlugins, resolveEditorPlugins } from '../editor-plugins.js';
import { configureLinkableButtons, registerLinkableButtonTypes, scanLinkableButtons } from '../editor-button-link.js';
import { configureRichTextEditor } from '../rich-text-editor.js';
import { registerNewsletterFormSettings } from '../editor-forms-blocks.js';
import { registerChromeContentSlotType } from '../chrome-content-slot-utils.js';
import {
    finalizeLayoutInspectorBootstrap,
    promoteInspectableBlockSelection,
    rebuildLayoutChromeBlockRegistry,
    registerBlockSettingsUi,
    refreshBlockSettingsUi,
} from '../blocks/settings/index.js';
import { buildPayload, stripAuthorIdRules } from './payload.js';
import {
    registerChromeLayoutInspectorSelection,
    wireInspector,
} from './inspector.js';
import { bootEditorRegistries } from './compatibility-bridge.js';
import { bootEditorPlugins, exposeEditorBridge } from '../plugin-bridge.js';
import { registerFontsUi } from '../fonts/fonts-ui.js';
import { prefetchFontsFromCss } from '../fonts/font-loader.js';
import { registerInspectorColorFix, installGlobalColorInputValueFix } from '../inspector-color-fix.js';
import { guardEditorLayersRender } from '../tailwind-visual-style.js';
import { configureEditorCodeBlock } from '../editor-code-block.js';
import { migrateEditorComponents, purgeBroadSectionBackgroundRules, purgeLegacyEditorStyles } from '../theme-tokens.js';
import { registerBindingsUi, syncBindingsForExport, syncRepeatBindingsForExport } from '../bindings-ui.js';
import { registerCanvasComponentToolbar, voodbuilderCopyCommandsPlugin } from '../canvas-component-toolbar.js';
import { restoreContentWidthFromAttributes } from '../content-width-toolbar.js';
import { registerJoditImageEditor } from '../jodit-image-editor.js';
import { registerImageCanvasDblClick } from '../image-canvas-dblclick.js';
import { registerCanvasBlockCodeEditor } from '../canvas-block-code-editor.js';
import { registerCanvasBlockDrag, detachTopDropSpacerForExport, restoreTopDropSpacerAfterExport, gateGrapesAutoscrollToRealDrags } from '../canvas-block-drag.js';
import { registerConditionsUi, registerConditionsPersistence, syncConditionsForExport } from '../conditions-ui.js';
import {
    ensureComponentInstancesForExport,
    registerComponentsUi,
    syncComponentInstancePaintForExport,
    syncComponentInstancesForExport,
} from '../components-ui.js';
import { registerComponentTailwindAutobuild } from '../component-tailwind-autobuild.js';
import { registerPageTailwindAutobuild } from '../page-tailwind-autobuild.js';
import { registerRevisionsUi } from '../revisions-ui.js';
import { registerPageTemplatesSidebar } from '../page-templates-sidebar.js';
import { registerPopupsUi } from '../popups-ui.js';
import { pruneRedundantSpacingZeros, pruneRedundantSpacingZerosForExport, purgeDesyncedBackgroundCssRules, registerVisualStyleInspector, registerVisualStyleTarget, bakeAuthorStylesToComposerForExport, bakeSvgPaintForExport, syncPaintStylesForExport, syncSpacingStylesForExport, hydrateSvgPaintFromAttributes, purgeDesyncedPaintCssRules, restoreSvgPaintInspectorStyle, restoreSvgPaintInspectorStyles, safeFindComponents, promotePrivateStyleClassesToIdRules, hydrateAuthorStylesFromIdRules } from '../tailwind-visual-style.js';
import { configureEditorChrome, editorChromeInitOptions } from '../editor-chrome.js';
import { extractChromeShellPageHtml, registerChromeShellEditor } from '../editor-chrome-shell.js';
import { extractChromeLayoutHtml, registerChromeLayoutEditor, applyEditorScopeBlockVisibility, refreshChromeLayoutBlockCatalog, reconcileLayoutChromeBlockSettings } from '../editor-chrome-layout.js';
import {
    buildEditorShell,
    collapseBlockCategories,
    configureEditorLayout,
    editorLayoutInitOptions,
    refreshBlocksLibraryUi,
    resolveEditingContext,
} from '../editor-layout.js';
import {
    finishEditorBoot,
    registerEditorBuildStatus,
    setEditorBootPhase,
    startEditorBoot,
    waitForEditorBootTasks,
} from '../editor-build-status.js';
import { isEditorBooting } from '../editor-lifecycle.js';
import { applyLightBlockPreviews } from '../editor-block-previews.js';
import { registerEditorVideoSafety, syncVideoComponentsForExport } from '../editor-video.js';
import {
    initCarousels,
    initReadingTime,
    initSocialShare,
    settleEditorCanvasPreview,
} from '../vb-runtime.js';
import { registerCanvasContextMenu } from '../canvas-context-menu.js';
import { bootCanvasSiteChrome, registerCanvasSiteChrome } from '../canvas-site-chrome.js';
import { registerLayersContextMenu } from '../layers-context-menu.js';
import { registerLayersDrag } from '../layers-drag.js';
import { registerLayersChromeFilter } from '../layers-chrome-filter.js';
import { registerTailwindClassSuggestions } from '../tailwind-class-suggestions.js';
import { registerStyleAnimationSector } from '../style-animation-sector.js';
import { registerStyleTailwindPanel } from '../style-tailwind-panel.js';
import { registerCanvasClassHoverPopover } from '../canvas-class-hover-popover.js';
import { syncAllLayerDisplayNames, registerLayerDisplayNamePersistence } from '../layer-display-name.js';
import { registerBlocksContextMenu } from '../blocks-context-menu.js';
import { registerBlocksLibraryRenderHook } from '../blocks-library-sync.js';
import { registerSectionBlockTagging } from '../section-block-tagging.js';
import { registerSectionNestingGuard } from '../section-nesting-guard.js';
import { encodeBlockConfig, parseBlockConfig, serializeBlockConfig } from '../voodbuilder-dynamic-config.js';
import { registerDropzoneTypes } from '../dropzone-types.js';
import { registerInnerDropSlots } from '../inner-drop-slots.js';

function voodbuilderEarlyTypesPlugin(editor, pluginOpts = {}) {
    editor.__voodbuilderLabels = pluginOpts.labels ?? editor.__voodbuilderLabels ?? {};
    registerDropzoneTypes(editor);
    registerInnerDropSlots(editor);
    registerLinkableButtonTypes(editor);
    registerSiteNavChromeButtonType(editor);
    registerChromeContentSlotType(editor);
}

function hasProjectData(project) {
    if (project == null || typeof project !== 'object') {
        return false;
    }

    if (Array.isArray(project)) {
        return project.length > 0;
    }

    if (Object.keys(project).length === 0) {
        return false;
    }

    if (Array.isArray(project.pages)) {
        return project.pages.length > 0;
    }

    return true;
}

function normalizeDynamicBlockComponents(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        syncDynamicBlockAttributes(component);

        if (isSiteNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
            syncSiteHeaderConfig(component);
        }
    });
}

export { buildPayload } from './payload.js';

function resolvePageManager(initial) {
    if (initial.pageManager && typeof initial.pageManager === 'object') {
        return initial.pageManager;
    }

    if (! initial.html) {
        return null;
    }

    return {
        pages: [{
            id: 'main',
            component: initial.html,
            styles: initial.css || '',
        }],
    };
}

function canvasIframeHasContent(editor) {
    const doc = editor.Canvas?.getDocument?.();
    const bodyHtml = doc?.body?.innerHTML?.replace(/\s/g, '') ?? '';

    return bodyHtml.length > 20;
}

function canvasHasRenderedHtml(editor) {
    if (canvasIframeHasContent(editor)) {
        return true;
    }

    const html = editor.getHtml()?.replace(/\s/g, '') ?? '';

    return html.length > 20;
}

function applyInitialContent(editor, initial) {
    if (! initial.html?.trim() && ! initial.css?.trim()) {
        return;
    }

    const hasCanvas = canvasIframeHasContent(editor);

    if (initial.html?.trim() && ! hasCanvas) {
        editor.setComponents(sanitizeBlockHtml(initial.html));
    }

    if (initial.css) {
        editor.setStyle(initial.css);
        // CssComposer alone does not reliably paint Tailwind utilities in the iframe.
        // Seed the live JIT sheet immediately so reload matches the saved frontend CSS.
        if (typeof editor.__voodbuilderApplyPageLiveCss === 'function') {
            editor.__voodbuilderApplyPageLiveCss(initial.css);
        } else {
            editor.__voodbuilderPendingPageLiveCss = String(initial.css);
        }
    }

    // Always hydrate #id → inline so Style Manager shows fonts/colors after reload,
    // including when pageManager already filled the canvas (hasCanvas=true).
    try {
        bakeAuthorStylesToComposerForExport(editor);
        hydrateAuthorStylesFromIdRules(editor);
    } catch {
        // Ignore hydrate errors during early boot.
    }

    if (! initial.html?.trim()) {
        return;
    }

    try {
        restoreContentWidthFromAttributes(editor);
    } catch {
        // Ignore hydrate errors during early boot.
    }
}

function ensureInitialContent(editor, initial) {
    if (! initial.html?.trim()) {
        return;
    }

    const apply = () => applyInitialContent(editor, initial);
    const usesPageManager = Boolean(initial.pageManager) || hasProjectData(initial.project);

    if (! usesPageManager) {
        editor.on('load', apply);
        editor.on('canvas:frame:load', apply);
        window.requestAnimationFrame(apply);
        window.setTimeout(apply, 100);

        return;
    }

    const ensureRendered = () => {
        window.requestAnimationFrame(() => {
            applyInitialContent(editor, initial);
        });
    };

    editor.on('canvas:frame:load', ensureRendered);
    editor.on('load', () => {
        window.setTimeout(ensureRendered, 50);
    });
}

function resolveEditorChromePrefersDark(fallback = false) {
    try {
        const stored = window.localStorage?.getItem('theme');

        if (stored === 'dark') {
            return true;
        }

        if (stored === 'light') {
            return false;
        }
    } catch {
        // Ignore storage failures.
    }

    if (document.documentElement.classList.contains('dark')) {
        return true;
    }

    const scheme = document.documentElement.style.colorScheme;

    if (scheme === 'dark') {
        return true;
    }

    if (scheme === 'light') {
        return false;
    }

    return Boolean(fallback);
}

function applyCanvasDocumentTheme(editor, subTheme, themeOptions = {}) {
    if (! subTheme) {
        return;
    }

    const resolveDark = (isDark = null) => {
        if (typeof isDark === 'boolean') {
            return isDark;
        }

        // Prefer live editor chrome (theme-script / localStorage) over SSR
        // canvasPrefersDark — PHP cannot see the user's stored preference.
        return resolveEditorChromePrefersDark(themeOptions.canvasPrefersDark);
    };

    const ensurePaletteStyle = (doc) => {
        const css = String(themeOptions.themePaletteCss ?? '').trim();

        if (! css || ! doc?.head) {
            return;
        }

        let style = doc.getElementById('voodbuilder-canvas-theme-palette');

        if (! style) {
            style = doc.createElement('style');
            style.id = 'voodbuilder-canvas-theme-palette';
            doc.head.appendChild(style);
        }

        if (style.textContent !== css) {
            style.textContent = css;
        }
    };

    const ensureChromeLayoutStyle = (doc) => {
        const css = String(themeOptions.chromeLayoutCss ?? '').trim();

        if (! doc?.head) {
            return;
        }

        let style = doc.getElementById('voodbuilder-canvas-chrome-layout-css');

        if (css === '') {
            style?.remove();

            return;
        }

        if (! style) {
            style = doc.createElement('style');
            style.id = 'voodbuilder-canvas-chrome-layout-css';
            doc.head.appendChild(style);
        }

        if (style.textContent !== css) {
            style.textContent = css;
        }
    };

    const apply = (isDark = null) => {
        const doc = editor.Canvas.getDocument();

        if (! doc) {
            return;
        }

        doc.documentElement.setAttribute('data-voodbuilder-sub-theme', subTheme);

        const prefersDark = resolveDark(isDark);

        doc.documentElement.classList.toggle('dark', prefersDark);
        doc.documentElement.style.colorScheme = prefersDark ? 'dark' : 'light';

        if (themeOptions.popupMode) {
            doc.body?.classList.add('voodbuilder-popup-editor-canvas');
        } else {
            doc.body?.classList.remove('voodbuilder-popup-editor-canvas');
        }

        ensurePaletteStyle(doc);
        ensureChromeLayoutStyle(doc);
    };

    editor.on('canvas:frame:load', () => apply());
    editor.on('load', () => apply());
    window.addEventListener('voodbuilder:theme-changed', (event) => {
        apply(event?.detail?.isDark);
    });
    apply();
}

function waitForStylesheetLink(link, timeoutMs = 2_500) {
    if (link.sheet) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const done = () => {
            window.clearTimeout(timer);
            resolve();
        };

        const timer = window.setTimeout(done, timeoutMs);

        link.addEventListener('load', done, { once: true });
        link.addEventListener('error', done, { once: true });
    });
}

function waitForCanvasStyles(frameWindow, timeoutMs = 4_000) {
    const doc = frameWindow?.document;

    if (! doc) {
        return Promise.resolve();
    }

    const links = [...doc.querySelectorAll('link[rel="stylesheet"]')];

    if (links.length === 0) {
        return Promise.resolve();
    }

    return Promise.race([
        Promise.all(links.map((link) => waitForStylesheetLink(link))),
        new Promise((resolve) => {
            window.setTimeout(resolve, timeoutMs);
        }),
    ]);
}

function normalizePageContentWidth(options = {}) {
    const raw = options.pageContentWidth;

    if (raw && typeof raw === 'object' && typeof raw.mode === 'string') {
        const mode = ['full', 'standard', 'custom', 'contained'].includes(raw.mode)
            ? raw.mode
            : 'full';
        const maxWidth = typeof raw.maxWidth === 'string' && raw.maxWidth.trim() !== ''
            ? raw.maxWidth.trim()
            : (mode === 'full' ? null : '80rem');
        const customMaxWidth = typeof raw.customMaxWidth === 'string' && raw.customMaxWidth.trim() !== ''
            ? raw.customMaxWidth.trim()
            : (mode === 'custom' && maxWidth ? maxWidth : null);

        return {
            mode: mode === 'contained' ? 'standard' : mode,
            maxWidth,
            customMaxWidth,
        };
    }

    const fullWidthPage = options.fullWidthPage !== false;

    return {
        mode: fullWidthPage ? 'full' : 'standard',
        maxWidth: fullWidthPage ? null : '80rem',
        customMaxWidth: null,
    };
}

function normalizeChromeWidth(options = {}) {
    return options.chromeWidth === 'content' ? 'content' : 'full';
}

function revealCanvasDocument(frameWindow, options = {}) {
    const doc = frameWindow?.document;

    if (! doc?.body) {
        return false;
    }

    const { mode, maxWidth, customMaxWidth } = normalizePageContentWidth(options);
    const chromeWidth = normalizeChromeWidth(options);
    const isFull = mode === 'full';
    const editorScope = options.chromeLayoutMode
        ? 'layout'
        : (options.chromeShellMode ? 'page' : 'page');
    // Respect layout setting in both editors — only the workspace frame stays full-bleed.
    const canvasChromeWidth = chromeWidth;

    doc.body.classList.add('voodbuilder-canvas-ready', 'VPRichPage', 'VPRichPage--landing');
    // Canvas-only attrs — do NOT set data-voodbuilder-page-width on the iframe body.
    // Frontend landing.css uses that attr to set --width-vp-layout on the whole document,
    // which would shrink nav/chrome inside the editor.
    doc.body.dataset.voodbuilderCanvasContentWidth = mode;
    doc.documentElement.dataset.voodbuilderCanvasContentWidth = mode;
    delete doc.body.dataset.voodbuilderPageWidth;
    delete doc.documentElement.dataset.voodbuilderPageWidth;
    doc.body.dataset.voodbuilderChromeWidth = canvasChromeWidth;
    doc.documentElement.dataset.voodbuilderChromeWidth = canvasChromeWidth;
    doc.body.dataset.voodbuilderEditorScope = editorScope;
    doc.documentElement.dataset.voodbuilderEditorScope = editorScope;

    // Canvas root always uses full layout token; only the page-content slot is constrained.
    // Use !important so theme palette / theme.css cannot shrink the whole iframe.
    doc.body.style.setProperty('--width-vp-layout', '100%', 'important');
    doc.documentElement.style.setProperty('--width-vp-layout', '100%', 'important');
    doc.documentElement.classList.add('voodbuilder-canvas-ready');

    if (isFull || ! maxWidth) {
        doc.body.style.removeProperty('--voodbuilder-page-content-max');
        doc.documentElement.style.removeProperty('--voodbuilder-page-content-max');
    } else {
        doc.body.style.setProperty('--voodbuilder-page-content-max', maxWidth);
        doc.documentElement.style.setProperty('--voodbuilder-page-content-max', maxWidth);
    }

    // chrome_width=full + content full: bar is edge-to-edge, but inner nav/footer
    // containers use this measure (mirrors landing.css --voodbuilder-chrome-layout-max).
    // Prefer layout "Max content width" (e.g. 72rem), else standard 80rem.
    const chromeLayoutMax = customMaxWidth || '80rem';
    doc.body.style.setProperty('--voodbuilder-chrome-layout-max', chromeLayoutMax);
    doc.documentElement.style.setProperty('--voodbuilder-chrome-layout-max', chromeLayoutMax);

    if (customMaxWidth) {
        doc.body.style.setProperty('--voodbuilder-element-content-max', customMaxWidth);
        doc.documentElement.style.setProperty('--voodbuilder-element-content-max', customMaxWidth);
    } else {
        doc.body.style.removeProperty('--voodbuilder-element-content-max');
        doc.documentElement.style.removeProperty('--voodbuilder-element-content-max');
    }

    return true;
}

function waitForCanvasPresentation(editor, options = {}) {
    const pageContentWidth = normalizePageContentWidth(options);
    const chromeWidth = normalizeChromeWidth(options);
    const chromeLayoutMode = Boolean(options.chromeLayoutMode);
    const chromeShellMode = Boolean(options.chromeShellMode);

    return waitForEditorBootTasks(editor, [
        waitForCanvasFrame(editor).then(() => {
            const frameWindow = editor.Canvas?.getWindow?.();

            return waitForCanvasStyles(frameWindow);
        }),
    ]).then(() => {
        const revealOptions = {
            pageContentWidth,
            chromeWidth,
            chromeLayoutMode,
            chromeShellMode,
        };

        if (! revealCanvasDocument(editor.Canvas?.getWindow?.(), revealOptions)) {
            window.requestAnimationFrame(() => {
                revealCanvasDocument(editor.Canvas?.getWindow?.(), revealOptions);
            });
        }
    });
}

function configureLayoutBlocks(editor) {
    const videoBlock = editor.BlockManager.get('video');

    if (videoBlock) {
        videoBlock.set('content', {
            type: 'video',
            provider: 'yt',
            videoId: '',
            classes: ['w-full', 'rounded', 'aspect-video'],
            style: {
                width: '100%',
                'max-width': '100%',
                height: 'auto',
            },
        });
    }
}

function waitForCanvasFrame(editor, timeoutMs = 10_000) {
    if (editor.Canvas?.getFrameEl?.()) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const timeout = window.setTimeout(resolve, timeoutMs);

        const onFrameLoad = () => {
            window.clearTimeout(timeout);
            editor.off('canvas:frame:load', onFrameLoad);
            resolve();
        };

        editor.on('canvas:frame:load', onFrameLoad);
    });
}

function registerCanvasBootGate(editor, shellRoot, shell, options = {}) {
    if (! shellRoot) {
        return;
    }

    startEditorBoot(editor);

    let revealPromise = null;
    const pageContentWidth = normalizePageContentWidth(options);
    const chromeWidth = normalizeChromeWidth(options);
    const revealOptions = {
        pageContentWidth,
        chromeWidth,
        chromeLayoutMode: Boolean(options.chromeLayoutMode),
        chromeShellMode: Boolean(options.chromeShellMode),
    };

    const dismissBootSplash = () => {
        finishEditorBoot(editor);
        shellRoot.classList.remove('voodbuilder-editor-root--booting');
    };

    /**
     * Dynamic blocks fetch their markup from the server, so their content lands after the
     * canvas is painted. Handing over before that means the author sees the page reflow
     * under the cursor; waiting for it is bounded, because the gate always resolves and
     * startEditorBoot() holds a hard deadline over the whole boot.
     */
    const waitForDynamicBlocks = () => Promise.race([
        Promise.resolve(editor.__voodbuilderDynamicBlocksPending),
        new Promise((resolve) => {
            window.setTimeout(resolve, 6_000);
        }),
    ]);

    const reveal = () => {
        if (! revealPromise) {
            revealPromise = (async () => {
                try {
                    setEditorBootPhase(editor, 'canvas');
                    await waitForCanvasPresentation(editor, revealOptions);
                    setEditorBootPhase(editor, 'content');
                    await waitForDynamicBlocks();
                } catch (error) {
                    console.warn('Voodbuilder Editor: canvas presentation wait failed.', error);
                } finally {
                    // The splash used to be dismissed here *and* before the await, which
                    // handed over an editor that had not rendered its page yet: it looked
                    // ready and ignored input for seconds. The phases now report the real
                    // milestones, so waiting is honest instead of silent.
                    dismissBootSplash();
                }
            })();
        }

        return revealPromise;
    };

    editor.on('canvas:frame:load', () => {
        revealCanvasDocument(editor.Canvas?.getWindow?.(), revealOptions);
        void reveal();
    });

    editor.on('load', () => {
        void reveal();
    });

    // Start the wait even if neither event fires (blocked main thread, cached frame).
    window.setTimeout(() => {
        void reveal();
    }, 2_000);

    // Init may finish after Grapes already loaded the project — don't wait for a second load.
    if (editor.getWrapper?.()) {
        void reveal();
    }
}

function createDynamicBlocksPending() {
    let resolvePending = null;
    const pending = new Promise((resolve) => {
        resolvePending = resolve;
    });

    return {
        pending,
        resolve: () => {
            resolvePending?.();
            resolvePending = null;
        },
    };
}

export function initVoodbuilderEditor(container, options = {}) {
    // Before grapesjs.init — StyleManager sets color inputs during construction.
    installGlobalColorInputValueFix();

    const initial = options.initial ?? {};
    const labels = options.labels ?? {};
    const useLayout = options.layout !== false;
    const shell = useLayout ? buildEditorShell(container, labels, {
        exitUrl: options.exitUrl,
        brand: options.builderBrand ?? 'VoodBuilder',
        // In chrome layout editor we still want the "Templates" tab visible for base templates.
        // We only hide the tab when we cannot load templates at all.
        hideTemplates: !Boolean(options.pageTemplatesUrl),
        editingContext: resolveEditingContext(options, labels),
    }) : null;

    if (shell?.mounts) {
        for (const key of ['blocks', 'layers', 'traits', 'selectors', 'styles', 'dynamic']) {
            shell.mounts[key]?.replaceChildren?.();
        }
    }

    const layoutOptions = shell ? editorLayoutInitOptions(shell.mounts) : {};
    const editorContainer = shell?.mounts.canvas ?? container;
    const chromeOptions = editorChromeInitOptions();
    const pluginBundle = resolveEditorPlugins(options.plugins ?? {});
    const editorOptions = {
        container: editorContainer,
        height: options.height ?? '640px',
        width: options.width ?? 'auto',
        fromElement: false,
        // Inline styles (with !important from Style Manager) override Tailwind utilities in the canvas.
        avoidInlineStyle: false,
        jsInHtml: false,
        storageManager: false,
        noticeOnUnload: options.noticeOnUnload ?? false,
        showDevices: layoutOptions.showDevices ?? chromeOptions.showDevices,
        deviceManager: chromeOptions.deviceManager,
        plugins: [voodbuilderCopyCommandsPlugin, voodbuilderEarlyTypesPlugin, grapesjsBlocksBasic, ...pluginBundle.plugins, voodbuilderEditorPlugin],
        pluginsOpts: {
            [voodbuilderCopyCommandsPlugin]: {
                labels: options.labels ?? {},
            },
            [voodbuilderEarlyTypesPlugin]: {
                labels: options.labels ?? {},
            },
            // Prefer string id — Editor 0.23 resolves pluginsOpts by id/toString;
            // empty `blocks` disables stock Column/Text/Link (we ship Basic ourselves).
            [grapesjsBlocksBasic]: {
                flexGrid: true,
                category: 'Basic',
                blocks: [],
            },
            'gjs-blocks-basic': {
                flexGrid: true,
                category: 'Basic',
                blocks: [],
            },
            ...pluginBundle.pluginsOpts,
            [voodbuilderEditorPlugin]: {
                blocks: options.blocks ?? [],
                siteNavDefaults: options.siteNavDefaults ?? { stickyNav: false },
                footerColumnOptions: options.footerColumnOptions ?? {},
                labels: options.labels ?? {},
            },
        },
        canvas: {
            styles: options.canvasStyles ?? [],
            frameStyle: [options.canvasFrameStyle, options.themePaletteCss]
                .filter((part) => typeof part === 'string' && part.trim() !== '')
                .join('\n'),
        },
        assetManager: options.uploadUrl
            ? {
                  upload: options.uploadUrl,
                  uploadName: 'file',
                  multiUpload: false,
                  autoAdd: true,
                  credentials: 'same-origin',
                  headers: options.csrf
                      ? {
                            'X-CSRF-TOKEN': options.csrf,
                            Accept: 'application/json',
                        }
                      : {
                            Accept: 'application/json',
                        },
              }
            : false,
        blockManager: {
            ...(layoutOptions.blockManager ?? {}),
            appendTo: options.blocksAppendTo ?? layoutOptions.blockManager?.appendTo ?? undefined,
        },
        layerManager: {
            showWrapper: true,
            sortable: true,
            ...(layoutOptions.layerManager ?? {}),
        },
        traitManager: layoutOptions.traitManager ?? undefined,
        selectorManager: {
            componentFirst: true,
            ...(layoutOptions.selectorManager ?? {}),
        },
        styleManager: {
            ...(layoutOptions.styleManager ?? chromeOptions.styleManager),
        },
        panels: options.panels ?? layoutOptions.panels ?? undefined,
    };

    if (initial.pageManager && typeof initial.pageManager === 'object') {
        editorOptions.pageManager = initial.pageManager;
    } else if (hasProjectData(initial.project)) {
        editorOptions.projectData = initial.project;
    } else {
        const pageManager = resolvePageManager(initial);

        if (pageManager) {
            editorOptions.pageManager = pageManager;
        }
    }

    const editor = grapesjs.init(editorOptions);

    editor.__voodbuilderLabels = labels;
    registerVideoAssetType(editor);
    editor.__voodbuilderMediaLibraryUrl = options.mediaLibraryUrl ?? '';
    editor.__voodbuilderMediaGalleriesUrl = options.mediaGalleriesUrl ?? '';
    editor.__voodbuilderUploadUrl = options.uploadUrl ?? '';
    editor.__voodbuilderCsrf = options.csrf ?? '';
    registerMediaPickerCommands(editor);
    // Do not preload thousands of assets into GrapesJS AM — the media browser loads pages on demand.

    exposeEditorBridge();

    try {
        registerFontsUi(editor, {
            fonts: options.fonts ?? null,
            initialCss: options.initial?.css ?? '',
            labels: options.labels ?? {},
        });
    } catch (error) {
        console.error('Voodbuilder Editor: fonts UI setup failed.', error);
    }

    bootEditorRegistries(editor, {
        popupMode: Boolean(options.popupMode),
        chromeLayoutMode: Boolean(options.chromeLayoutMode),
        conditionsEnabled: options.conditionsEnabled !== false,
    });

    // Entitlements before companion mount — plugins gate UI on EditorGate flags.
    editor.__voodbuilderEntitlements = options.entitlements ?? {};
    editor.__voodbuilderLabels = labels;

    void bootEditorPlugins(editor, {
        labels,
        entitlements: options.entitlements ?? {},
        vevents: options.vevents ?? null,
        urls: {
            components: options.componentsUrl ?? null,
            popups: options.popupsUrl ?? null,
            bindings: options.bindingsUrl ?? null,
            pageTemplates: options.pageTemplatesUrl ?? null,
            upload: options.uploadUrl ?? null,
            linkTargets: options.linkTargetsUrl ?? null,
            elementsSource: options.elementsSourceUrl ?? null,
        },
        elementsSourceUrl: options.elementsSourceUrl ?? null,
        elementsCatalogs: options.elementsCatalogs ?? [],
        flags: {
            popupMode: Boolean(options.popupMode),
            chromeLayoutMode: Boolean(options.chromeLayoutMode),
            chromeShellMode: Boolean(options.chromeShellMode),
            conditionsEnabled: options.conditionsEnabled !== false,
            imageEditor: options.imageEditor !== false,
        },
        csrf: options.csrf ?? '',
    });

    editor.__voodbuilderVevents = options.vevents ?? null;

    editor.__voodbuilderGlobalTextTags = options.globalTextTags ?? {};
    editor.__voodbuilderLinkTargets = { pages: [], menuItems: [] };
    editor.__voodbuilderLinkTargetsUrl = options.linkTargetsUrl ?? null;
    editor.__voodbuilderPageContentWidth = normalizePageContentWidth(options);
    editor.__voodbuilderChromeWidth = normalizeChromeWidth(options);
    editor.__voodbuilderFullWidthPage = editor.__voodbuilderPageContentWidth.mode === 'full'
        || options.fullWidthPage === true
        || editor.__voodbuilderChromeWidth === 'full';

    try {
        configureRichTextEditor(editor, labels);
    } catch (error) {
        console.error('Voodbuilder Editor: rich text editor setup failed.', error);
    }

    // Register copy toolbar commands before project hydration / first selection.
    registerCanvasComponentToolbar(editor, {
        makeDynamic: labels.makeDynamic,
        clearDynamic: labels.clearDynamic,
        selectParent: labels.selectParent,
        drag: labels.drag,
        moveUp: labels.moveUp,
        moveDown: labels.moveDown,
        clone: labels.clone,
        delete: labels.delete,
        editBlockCode: labels.editBlockCode,
        editImage: labels.editImage,
        copyComponentCode: labels.copyComponentCode,
        copyComponentClasses: labels.copyComponentClasses,
        copyComponentCodeSuccess: labels.copyComponentCodeSuccess,
        copyComponentCodeFailed: labels.copyComponentCodeFailed,
        classCopySuccess: labels.classCopySuccess,
        classCopyEmpty: labels.classCopyEmpty,
        classCopyFailed: labels.classCopyFailed,
        contentWidthTitle: labels.contentWidthTitle,
        contentWidthFull: labels.contentWidthFull,
        contentWidthNormal: labels.contentWidthNormal,
        contentWidthCustom: labels.contentWidthCustom,
        imageEditorTitle: labels.imageEditorTitle,
        imageEditorSave: labels.imageEditorSave,
        imageEditorSaveAs: labels.imageEditorSaveAs,
        imageEditorLoading: labels.imageEditorLoading,
        imageEditorSaving: labels.imageEditorSaving,
        imageEditorLoadError: labels.imageEditorLoadError,
        imageEditorUploadError: labels.imageEditorUploadError,
        imageEditorUploadMissing: labels.imageEditorUploadMissing,
        imageEditorPlaceholderHint: labels.imageEditorPlaceholderHint,
        dialogCancel: labels.dialogCancel,
        modalCancel: labels.modalCancel,
    });
    registerJoditImageEditor(editor, {
        enabled: options.imageEditor !== false,
        uploadUrl: options.uploadUrl ?? '',
        replaceUrl: options.mediaReplaceUrl ?? '',
        csrf: options.csrf ?? '',
        labels,
    });
    registerImageCanvasDblClick(editor);
    editor.__voodbuilderChromeShellMode = options.chromeShellMode ?? false;
    editor.__voodbuilderChromeLayoutMode = options.chromeLayoutMode ?? false;
    editor.__voodbuilderChromeShellName = options.chromeShellName ?? options.chromeLayoutName ?? null;
    editor.__voodbuilderChromeLayoutName = options.chromeLayoutName ?? options.chromeShellName ?? null;
    editor.__voodbuilderLastSavedPageHtml = String(
        options.pageContentHtml
        ?? options.savedPageHtml
        ?? (options.chromeShellMode ? '' : (initial.html ?? '')),
    );

    if (editor.em) {
        editor.em.__voodbuilderChromeShellMode = editor.__voodbuilderChromeShellMode;
        editor.em.__voodbuilderChromeLayoutMode = editor.__voodbuilderChromeLayoutMode;
    }

    gateGrapesAutoscrollToRealDrags(editor);

    registerChromeLayoutInspectorSelection(editor);
    registerLinkableButtonTypes(editor);

    const dynamicBlocksGate = createDynamicBlocksPending();
    editor.__voodbuilderDynamicBlocksPending = dynamicBlocksGate.pending;
    editor.__voodbuilderDynamicBlocksRefresh = dynamicBlocksGate.pending;

    registerEditorVideoSafety(editor);

    if (shell) {
        const shellRoot = shell.shell?.closest('.voodbuilder-editor-root') ?? container;
        shellRoot.classList.add('voodbuilder-editor-root--booting');
        registerEditorBuildStatus(editor, shell, labels, {
            brand: options.builderBrand ?? 'VoodBuilder',
            version: options.packageVersion ?? '',
        });
        registerCanvasBootGate(editor, shellRoot, shell, {
            pageContentWidth: options.pageContentWidth,
            chromeWidth: options.chromeWidth,
            chromeLayoutMode: options.chromeLayoutMode ?? false,
            chromeShellMode: options.chromeShellMode ?? false,
            fullWidthPage: options.fullWidthPage !== false,
        });
        configureEditorLayout(editor, shell, labels);
        wireInspector(editor, shell, options, labels);

        if (shell?.mounts?.layers) {
            guardEditorLayersRender(editor);
            registerLayersChromeFilter(editor);
        }
    }

    if (shell?.mounts?.components) {
        if (options.componentsUrl) {
            registerCanvasBlockCodeEditor(editor, {
                componentsUrl: options.componentsUrl,
                csrf: options.csrf,
                labels,
                canvasStyles: options.canvasStyles ?? [],
            });

            try {
                registerComponentsUi(editor, {
                    componentsUrl: options.componentsUrl,
                    csrf: options.csrf,
                    labels,
                    componentsMount: shell.mounts.components,
                    componentPropsMount: shell.mounts?.componentProps ?? null,
                    canvasStyles: options.canvasStyles ?? [],
                    componentCategories: options.componentCategories ?? [],
                });
            } catch (error) {
                console.error('Voodbuilder Editor: could not mount components library UI.', error);
            }
        } else {
            // Soft commercial gate: keep the Components tab visible with an upsell.
            const mount = shell.mounts.components;
            mount.replaceChildren();
            mount.appendChild(createInspectorEmptyState({
                classNameExtra: 'voodbuilder-editor-components-library__locked',
                title: labels.componentsPluginRequiredTitle ?? 'Voodbuilder Components',
                message: labels.componentsPluginRequiredBody
                    ?? 'Save and reuse page sections as a component library. Requires the Voodbuilder Components plugin.',
                labels,
                linkUrl: labels.marketingUrl ?? options.marketingUrl ?? null,
                linkLabel: labels.learnMore ?? 'Learn more',
            }));
        }
    }

    // Page/component Tailwind JIT must not depend on the Components tab mount —
    // compile-css is required whenever the compile endpoint is available.
    if (options.compileCssUrl || options.componentsUrl) {
        registerComponentTailwindAutobuild(editor, {
            componentsUrl: options.componentsUrl,
            compileCssUrl: options.compileCssUrl,
            csrf: options.csrf,
        });

        registerPageTailwindAutobuild(editor, {
            componentsUrl: options.componentsUrl,
            compileCssUrl: options.compileCssUrl,
            csrf: options.csrf,
        });

        const pendingLiveCss = String(editor.__voodbuilderPendingPageLiveCss ?? options.initial?.css ?? '').trim();

        if (pendingLiveCss !== '') {
            editor.__voodbuilderApplyPageLiveCss?.(pendingLiveCss);
            delete editor.__voodbuilderPendingPageLiveCss;
        }
    }

    configureEditorChrome(editor, {
        labels,
        shellRoot: shell?.shell ?? null,
        shell,
        toolsMount: shell?.mounts?.canvasToolbar ?? null,
        actionsMount: shell?.shell?.querySelector('.voodbuilder-editor-topbar__actions') ?? null,
        viewPageUrl: options.viewPageUrl ?? options.exitUrl ?? null,
    });

    configureEditorPlugins(editor, {
        formSubmitUrl: options.formSubmitUrl,
        csrf: options.csrf,
        plugins: options.plugins ?? {},
        labels,
        blockAllowlist: options.blockAllowlist ?? null,
    });
    // Forms plugin registers `button` after early-types; re-add chrome type so icons win.
    registerSiteNavChromeButtonType(editor);

    configureEditorCodeBlock(editor, {
        codeHighlightUrl: options.codeHighlightUrl,
        csrf: options.csrf,
    });

    registerVisualStyleTarget(editor);
    registerVisualStyleInspector(editor);

    applyCanvasDocumentTheme(editor, options.subTheme, {
        canvasPrefersDark: options.canvasPrefersDark,
        themePaletteCss: options.themePaletteCss ?? '',
        chromeLayoutCss: options.chromeLayoutCss ?? '',
        popupMode: Boolean(options.popupMode),
    });
    editor.__voodbuilderPopupMode = Boolean(options.popupMode);
    editor.__voodbuilderPopupDisplayWidthPx = (() => {
        const raw = String(options.popupDisplayWidth ?? '').trim();
        const rem = raw.endsWith('rem') ? Number.parseFloat(raw) : Number.NaN;

        if (Number.isFinite(rem) && rem > 0) {
            return Math.round(rem * 16);
        }

        const px = Number.parseFloat(raw);

        return Number.isFinite(px) && px > 0 ? Math.round(px) : 672;
    })();
    ensureInitialContent(editor, initial);

    registerChromeShellEditor(editor, {
        chromeShellMode: options.chromeShellMode ?? false,
        chromeShellParts: options.chromeShellParts ?? null,
        subTheme: options.subTheme ?? null,
        pageContentPlaceholder: labels.pageContentPlaceholder ?? 'Drag blocks here to build your page',
    });

    if (options.blocksRenderUrl && options.chromeLayoutMode && ! options.chromeShellMode) {
        editor.__voodbuilderLayoutDynamicRefreshPending = true;
    }

    registerChromeLayoutEditor(editor, {
        chromeLayoutMode: options.chromeLayoutMode ?? false,
        layoutContentSlotPlaceholder: labels.layoutContentSlotPlaceholder
            ?? 'Page content — filled automatically by each page.',
        layoutNavZonePlaceholder: labels.layoutNavZonePlaceholder
            ?? 'Drop header blocks here',
        layoutFooterZonePlaceholder: labels.layoutFooterZonePlaceholder
            ?? 'Drop footer blocks here',
    });

    if (options.blocksRenderUrl && options.chromeLayoutMode && ! options.chromeShellMode) {
        const setupLayoutDynamicRefresh = () => {
            if (editor.__voodbuilderLayoutDynamicRefreshSetup) {
                return;
            }

            editor.__voodbuilderLayoutDynamicRefreshSetup = true;

            editor.on('voodbuilder:refresh-dynamic-block', (component) => {
                if (! component) {
                    return;
                }

                scheduleDynamicBlockRefresh(editor, options.blocksRenderUrl, component);
            });

            const runInitialDynamicRefresh = () => {
                const componentsToRefresh = collectTopLevelDynamicBlocks(editor);

                return componentsToRefresh.length > 0
                    ? refreshDynamicBlockList(editor, options.blocksRenderUrl, componentsToRefresh)
                    : refreshDynamicBlocks(editor, options.blocksRenderUrl);
            };

            const refresh = new Promise((resolve) => {
                let started = false;
                let settled = false;

                const done = () => {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    resolve();
                };

                const start = () => {
                    if (started) {
                        return;
                    }

                    started = true;
                    void runInitialDynamicRefresh().finally(done);
                };

                if (editor.__voodbuilderChromeLayoutReady) {
                    start();
                } else if (typeof editor.once === 'function') {
                    editor.once('voodbuilder:chrome-layout-ready', start);
                } else {
                    editor.on('voodbuilder:chrome-layout-ready', start);
                }

                // Don't block layout forever if chrome-ready never fires.
                window.setTimeout(start, 300);
                window.setTimeout(done, 12_000);
            });

            editor.__voodbuilderDynamicBlocksRefresh = refresh;
            void refresh.finally(() => {
                editor.__voodbuilderLayoutDynamicRefreshPending = false;
                dynamicBlocksGate.resolve();
                migrateEditorComponents(editor);
                scanLinkableButtons(editor);
                reconcileLayoutChromeBlockSettings(editor);
                rebuildLayoutChromeBlockRegistry(editor);
                finalizeLayoutInspectorBootstrap(editor);
                refreshBlockSettingsUi(editor);

                for (const component of safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]')) {
                    try {
                        const blockId = component.getAttributes()['data-voodbuilder-block'];

                        if (isSiteFooterBlock(blockId)) {
                            configureSiteFooterTraits(component, editor);
                            applySiteFooterSettingsPreview(component, editor);
                        }

                        if (isSiteNavBlock(blockId)) {
                            configureSiteNavTraits(component, editor);
                            applySiteNavSettingsPreview(component, editor);
                        }
                    } catch (lockError) {
                        console.warn('Voodbuilder Editor: could not configure layout chrome block.', lockError);
                    }
                }

                editor.trigger('voodbuilder:site-chrome-updated');
                editor.trigger('voodbuilder:dynamic-blocks-refreshed');
            });
        };

        if (editor.getWrapper?.()) {
            setupLayoutDynamicRefresh();
        } else {
            editor.on('load', setupLayoutDynamicRefresh);
        }
    }

            editor.on('load', () => {
        purgeLegacyEditorStyles(editor);
        purgeBroadSectionBackgroundRules(editor);
        migrateEditorComponents(editor);
        try {
            // Split clone-shared .cXXXX before baking paints onto #id rules.
            promotePrivateStyleClassesToIdRules(editor);
            bakeAuthorStylesToComposerForExport(editor);
            // After CSS load, mirror #id paints into inline so Style Manager fields
            // (font family, color, …) are not empty/"-" on first select.
            hydrateAuthorStylesFromIdRules(editor);
            window.requestAnimationFrame(() => {
                try {
                    hydrateAuthorStylesFromIdRules(editor);
                } catch {
                    // Ignore hydrate race during boot.
                }
            });
        } catch {
            // Ignore hydrate errors during early boot.
        }
        ensureLayoutSectionTraits(editor);
        pruneEmptySections(editor);

        try {
            applyLightBlockPreviews(editor);
        } catch (error) {
            console.error('Voodbuilder Editor: block previews failed.', error);
        }

        editor.__voodbuilderSyncComponentsCatalog?.();
        registerBlocksLibraryRenderHook(editor);
        registerSectionBlockTagging(editor);
        registerSectionNestingGuard(editor);
        refreshBlocksLibraryUi(editor);

        configureLayoutBlocks(editor);

        syncAllLayerDisplayNames(editor);
        registerLayerDisplayNamePersistence(editor);

        wireInspector(editor, shell, options, labels);

        editor.__voodbuilderLabels = labels;
        editor.__voodbuilderNewsletterLists = options.newsletterLists ?? {};

        configureLinkableButtons(editor);
        configureRichTextEditor(editor, labels);
        scanLinkableButtons(editor);

        registerCanvasContextMenu(editor, { labels });

        try {
            if (shell?.mounts?.layers) {
                registerLayersContextMenu(editor, { mount: shell.mounts.layers, labels });
                registerLayersDrag(editor, { mount: shell.mounts.layers });
            }

            if (shell?.mounts?.selectors) {
                registerTailwindClassSuggestions(editor, {
                    mount: shell.mounts.selectors,
                    labels,
                });
            }

            if (shell?.mounts?.styles) {
                registerStyleTailwindPanel(editor, {
                    mount: shell.mounts.styles,
                    labels,
                });
                registerStyleAnimationSector(editor, {
                    mount: shell.mounts.styles,
                    labels,
                });
            }

            registerCanvasClassHoverPopover(editor, { labels, shellRoot: shell?.shell ?? null });

            if (shell?.shell) {
                registerBlocksContextMenu(editor, shell.shell, labels);
            }
        } catch (error) {
            console.error('Voodbuilder Editor: inspector menus failed.', error);
        }

        if (! options.popupMode) {
            registerRevisionsUi(editor, {
                revisionsUrl: options.revisionsUrl,
                revisionsRestoreUrl: options.revisionsRestoreUrl,
                csrf: options.csrf,
                labels,
                toolbarMount: shell?.shell?.querySelector('.voodbuilder-editor-topbar__actions') ?? null,
            });
        }

        if (! options.popupMode) {
            if (options.pageTemplatesUrl) {
                registerPageTemplatesSidebar(editor, {
                    pageTemplatesUrl: options.pageTemplatesUrl,
                    pageTemplatesCatalogUrl: options.entitlements?.templatesRemoteInstall
                        ? (options.pageTemplatesCatalogUrl ?? null)
                        : null,
                    csrf: options.csrf,
                    labels,
                    templateCategories: options.templateCategories ?? [],
                    defaultTemplateCategory: 'Ecommerce',
                    templatesMount: shell?.mounts?.templates ?? null,
                    popupMode: options.popupMode ?? false,
                    templatesPluginInstalled: options.entitlements?.templatesPluginInstalled === true,
                    canAuthorTemplates: options.entitlements?.templatesAuthoring === true,
                    canImportTemplates: options.entitlements?.templatesImport === true,
                    canExportTemplates: options.entitlements?.templatesExport === true,
                    canImportTemplatesFromUrl: options.entitlements?.templatesImportUrl !== false,
                });
            }

            registerPopupsUi(editor, {
                popupsUrl: options.popupsUrl,
                popupsPagePathsUrl: options.popupsPagePathsUrl ?? null,
                csrf: options.csrf,
                labels,
                popupMode: options.popupMode ?? false,
                toolbarMount: shell?.shell?.querySelector('.voodbuilder-editor-topbar__actions') ?? null,
            });
        }

        if (! options.blocksRenderUrl) {
            dynamicBlocksGate.resolve();
        } else if (options.chromeShellMode) {
            const lockChromeShellDynamicBlocks = () => {
                for (const component of safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]')) {
                    try {
                        lockDynamicPreviewContent(component, editor);

                        // Chrome shell is server-rendered + read-only. Do NOT run
                        // applySiteFooter/NavSettingsPreview here: those toggle grid/flex
                        // utility classes that are not in the frozen layout CssComposer,
                        // and page live CSS intentionally excludes chrome HTML — so the
                        // footer/nav look unstyled until Save (layout editor) or forever
                        // (page editor / front when page CSS was the only compile path).
                        if (isSiteFooterBlock(component.getAttributes()['data-voodbuilder-block'])) {
                            configureSiteFooterTraits(component, editor);
                        }

                        if (isSiteNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
                            configureSiteNavTraits(component, editor);
                        }
                    } catch (lockError) {
                        console.warn('Voodbuilder Editor: could not lock chrome shell block.', lockError);
                    }
                }
            };

            const runShellDynamicRefresh = () => {
                // Chrome shell parts already ship server-rendered nav/footer HTML.
                // Re-fetching remounts Maps embeds and retriggers Layers.render (console spam).
                return Promise.resolve();
            };

            const refresh = new Promise((resolve) => {
                let started = false;

                const start = () => {
                    if (started) {
                        return;
                    }

                    started = true;
                    void runShellDynamicRefresh().then(resolve);
                };

                editor.on('load', () => window.setTimeout(start, 120));
                window.setTimeout(start, 400);
            });

            editor.__voodbuilderDynamicBlocksRefresh = refresh;
            void refresh.finally(() => {
                dynamicBlocksGate.resolve();
                lockChromeShellDynamicBlocks();
            });

            editor.on('voodbuilder:refresh-dynamic-block', (component) => {
                if (! component) {
                    return;
                }

                scheduleDynamicBlockRefresh(editor, options.blocksRenderUrl, component);
            });
        } else if (! options.chromeLayoutMode) {
            const runInitialDynamicRefresh = () => refreshDynamicBlocks(editor, options.blocksRenderUrl);

            editor.on('voodbuilder:refresh-dynamic-block', (component) => {
                if (! component) {
                    return;
                }

                scheduleDynamicBlockRefresh(editor, options.blocksRenderUrl, component);
            });

            editor.__voodbuilderDynamicBlocksRefresh = runInitialDynamicRefresh();
            void editor.__voodbuilderDynamicBlocksRefresh.finally(() => {
                dynamicBlocksGate.resolve();
                migrateEditorComponents(editor);
                scanLinkableButtons(editor);
                for (const component of safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]')) {
                    try {
                        lockDynamicPreviewContent(component, editor);

                        if (isSiteFooterBlock(component.getAttributes()['data-voodbuilder-block'])) {
                            configureSiteFooterTraits(component, editor);
                            applySiteFooterSettingsPreview(component, editor);
                        }

                        if (isSiteNavBlock(component.getAttributes()['data-voodbuilder-block'])) {
                            configureSiteNavTraits(component, editor);
                            applySiteNavSettingsPreview(component, editor);
                        }
                    } catch (lockError) {
                        console.warn('Voodbuilder Editor: could not lock dynamic block.', component.getAttributes()['data-voodbuilder-block'], lockError);
                    }
                }
            });
        }
    });

    editor.on('canvas:frame:load', () => {
        try {
            const frameDoc = editor.Canvas?.getDocument?.() ?? document;

            initReadingTime();
            initSocialShare();
            initCarousels();
            // Large page templates: settle to final state — do not force-replay
            // counters/keyframes/logo-scroll (that janks the editor + spam Layers).
            settleEditorCanvasPreview({ root: frameDoc });
            // Chrome shell remounts page content after frame:load — settle again.
            window.setTimeout(() => {
                try {
                    settleEditorCanvasPreview({
                        root: editor.Canvas?.getDocument?.() ?? frameDoc,
                    });
                } catch {
                    // Optional.
                }
            }, 350);
        } catch {
            // VB runtime is optional in the editor canvas.
        }

        try {
            hydrateSvgPaintFromAttributes(editor);
            purgeDesyncedPaintCssRules(editor);
            hydrateAuthorStylesFromIdRules(editor);
        } catch {
            // Ignore paint sync errors during early frame mount.
        }
    });

    let pruneEmptySectionsTimer = null;

    editor.on('component:add', () => {
        if (editor.__voodbuilderBulkStructureUpdate) {
            return;
        }

        window.clearTimeout(pruneEmptySectionsTimer);
        pruneEmptySectionsTimer = window.setTimeout(() => {
            if (editor.__voodbuilderBulkStructureUpdate) {
                return;
            }

            pruneEmptySections(editor);
        }, 180);
    });

    if (typeof options.onUpdate === 'function') {
        // Read-only snapshot: mutate:false must not run export purges/bakes that
        // wipe CssComposer while the author is still editing.
        let updateTimer = null;
        const notify = () => {
            if (editor.__voodbuilderBulkStructureUpdate) {
                return;
            }

            window.clearTimeout(updateTimer);
            updateTimer = window.setTimeout(() => {
                if (editor.__voodbuilderBulkStructureUpdate) {
                    return;
                }

                options.onUpdate(buildPayload(editor, { mutate: false }));
            }, 250);
        };

        editor.on('update', notify);
        editor.on('component:add', notify);
        editor.on('component:remove', notify);
        editor.on('style:change', notify);
    }

    return editor;
}

const dynamicBlockRefreshTimers = new WeakMap();

function scheduleDynamicBlockRefresh(editor, renderUrl, component) {
    if (! component || component.isRemoved?.()) {
        return;
    }

    if (
        component.__voodbuilderRefreshing
        || editor.__voodbuilderDynamicBlockRefreshing
        || editor.__voodbuilderLayoutStructureRefreshing
        || editor.__voodbuilderBulkStructureUpdate
    ) {
        return;
    }

    const existing = dynamicBlockRefreshTimers.get(component);

    if (existing) {
        window.clearTimeout(existing);
    }

    dynamicBlockRefreshTimers.set(component, window.setTimeout(() => {
        dynamicBlockRefreshTimers.delete(component);
        void refreshDynamicBlockComponent(editor, renderUrl, component);
    }, 80));
}

function dynamicBlockRenderFingerprint(blockId, config) {
    return `${blockId}::${serializeBlockConfig(config ?? {})}`;
}

async function refreshDynamicBlockComponent(editor, renderUrl, component) {
    if (! renderUrl || ! component || component.isRemoved?.()) {
        return;
    }

    if (component.__voodbuilderRefreshing) {
        return;
    }

    component.__voodbuilderRefreshing = true;
    editor.__voodbuilderDynamicBlockRefreshing = (editor.__voodbuilderDynamicBlockRefreshing ?? 0) + 1;

    let blockId = '';

    try {
        const attributes = component.getAttributes();
        blockId = attributes['data-voodbuilder-block'] ?? '';

        if (! blockId) {
            return;
        }

        const hasDynamicBindings = safeFindComponents(component, '[data-voodbuilder-bind], [data-voodbuilder-repeat]').length > 0;

        if (hasDynamicBindings) {
            lockDynamicPreviewContent(component, editor);

            if (editor.__voodbuilderChromeLayoutMode) {
                reconcileLayoutChromeBlockSettings(editor);
            }

            return;
        }

        if (isSiteNavBlock(blockId)) {
            syncSiteHeaderConfig(component);
        }

        if (isSiteFooterBlock(blockId)) {
            syncSiteFooterConfig(component);
        }

        const config = component.get('voodbuilderConfig') ?? parseBlockConfig(attributes['data-voodbuilder-config']);
        const fingerprint = dynamicBlockRenderFingerprint(blockId, config);

        // Skip identical re-fetch/remount (footer refresh was remounting Google Maps forever).
        if (
            component.__voodbuilderLastDynamicRenderFingerprint === fingerprint
            && (component.components?.()?.length ?? 0) > 0
        ) {
            return;
        }

        const params = new URLSearchParams({
            block: blockId,
            config: serializeBlockConfig(config),
        });

        const response = await fetch(`${renderUrl}?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            const body = await response.text().catch(() => '');
            const preview = body.length > 240 ? `${body.slice(0, 240)}…` : body;
            console.error('Voodbuilder Editor: could not refresh dynamic block.', blockId, response.status, preview);

            return;
        }

        const payload = await response.json();
        const html = payload.html;

        if (typeof html !== 'string' || html === '') {
            return;
        }

        const temp = document.createElement('div');

        temp.innerHTML = sanitizeBlockHtml(html);
        stripInvalidDomAttributes(temp);
        const fresh = temp.firstElementChild;

        if (! fresh) {
            return;
        }

        const freshConfig = {
            ...parseBlockConfig(fresh.getAttribute('data-voodbuilder-config') ?? '{}'),
            ...config,
        };
        const freshFingerprint = dynamicBlockRenderFingerprint(
            fresh.getAttribute('data-voodbuilder-block') ?? blockId,
            freshConfig,
        );

        if (
            component.__voodbuilderLastDynamicRenderFingerprint === freshFingerprint
            && (component.components?.()?.length ?? 0) > 0
            && component.__voodbuilderLastDynamicRenderHtml === fresh.innerHTML
        ) {
            return;
        }

        if (isSiteNavBlock(blockId)) {
            const authorContainerClasses = captureContainerAuthorClasses(component);
            component.set('voodbuilderConfig', freshConfig, { silent: true });
            component.setAttributes({
                'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
                'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeBlockConfig(freshConfig),
                class: fresh.getAttribute('class') ?? 'voodbuilder-editor-dynamic',
            });
            component.components(fresh.innerHTML);
            restoreContainerAuthorClasses(component, authorContainerClasses);
            component.__voodbuilderLastDynamicRenderFingerprint = freshFingerprint;
            component.__voodbuilderLastDynamicRenderHtml = fresh.innerHTML;
            const preserveSelection = editor.getSelected?.();

            window.requestAnimationFrame(() => {
                lockDynamicPreviewContent(component, editor);

                if (editor.__voodbuilderChromeLayoutMode) {
                    reconcileLayoutChromeBlockSettings(editor);
                    configureSiteNavTraits(component, editor);
                    applySiteNavSettingsPreview(component, editor);
                } else {
                    configureSiteNavTraits(component, editor);
                    applySiteNavSettingsPreview(component, editor);
                }

                bootCanvasSiteChrome(editor);
                editor.trigger('voodbuilder:dynamic-blocks-refreshed');

                // In chrome-shell page editor, site-chrome-updated reshuffles layers forever
                // after every nav remount (touchstart spam). Layout editor still needs it.
                if (! editor.__voodbuilderChromeShellMode) {
                    editor.trigger('voodbuilder:site-chrome-updated');
                }

                window.requestAnimationFrame(() => {
                    if (preserveSelection && ! preserveSelection.isRemoved?.()) {
                        editor.select(preserveSelection);
                    }

                    if (! editor.__voodbuilderChromeLayoutMode) {
                        applySiteNavSettingsPreview(component, editor);
                    }
                });
            });

            return;
        }

        const footerBlock = isSiteFooterBlock(blockId);
        const authorContainerClasses = captureContainerAuthorClasses(component);

        if (footerBlock && fresh.tagName === 'FOOTER') {
            applyFreshFooterAttributes(component, fresh, blockId, freshConfig);

            if (safeFindComponents(component, '[data-voodbuilder-menu], [data-voodbuilder-brand], [data-voodbuilder-chrome="brand"]').length > 0) {
                refreshDynamicSlots(component, fresh);
            } else {
                component.components(fresh.innerHTML);
                restoreContainerAuthorClasses(component, authorContainerClasses);
            }
        } else {
            component.set('voodbuilderConfig', freshConfig, { silent: true });
            component.setAttributes({
                'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
                'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeBlockConfig(freshConfig),
                class: fresh.getAttribute('class') ?? 'voodbuilder-editor-dynamic',
                ...(fresh.hasAttribute('data-voodbuilder-hydrate-slots')
                    ? { 'data-voodbuilder-hydrate-slots': '1' }
                    : {}),
            });

            const hydratesSlots = fresh.hasAttribute('data-voodbuilder-hydrate-slots')
                && safeFindComponents(component, '[data-voodbuilder-menu], [data-voodbuilder-brand], [data-voodbuilder-chrome="brand"]').length > 0;

            if (hydratesSlots) {
                refreshDynamicSlots(component, fresh);
            } else {
                component.components(fresh.innerHTML);
                restoreContainerAuthorClasses(component, authorContainerClasses);
            }
        }

        component.__voodbuilderLastDynamicRenderFingerprint = freshFingerprint;
        component.__voodbuilderLastDynamicRenderHtml = fresh.innerHTML;

        window.requestAnimationFrame(() => {
            try {
                lockDynamicPreviewContent(component, editor);

                if (editor.__voodbuilderChromeLayoutMode) {
                    reconcileLayoutChromeBlockSettings(editor);

                    if (footerBlock) {
                        configureSiteFooterTraits(component, editor);
                        applySiteFooterSettingsPreview(component, editor);
                    }

                    editor.trigger('voodbuilder:dynamic-blocks-refreshed');
                } else if (footerBlock) {
                    configureSiteFooterTraits(component, editor);
                    applySiteFooterSettingsPreview(component, editor);
                    bootCanvasSiteChrome(editor);
                    editor.trigger('voodbuilder:dynamic-blocks-refreshed');

                    if (! editor.__voodbuilderChromeShellMode) {
                        editor.trigger('voodbuilder:site-chrome-updated');
                    }
                }
            } catch (lockError) {
                console.error('Voodbuilder Editor: could not lock dynamic block.', blockId, lockError);
            }
        });
    } catch (error) {
        console.error('Voodbuilder Editor: could not refresh dynamic block.', blockId || 'unknown', error);
    } finally {
        component.__voodbuilderRefreshing = false;
        editor.__voodbuilderDynamicBlockRefreshing = Math.max(
            0,
            (editor.__voodbuilderDynamicBlockRefreshing ?? 1) - 1,
        );

        if (editor.__voodbuilderChromeLayoutMode) {
            rebuildLayoutChromeBlockRegistry(editor);
        }
    }
}

async function refreshDynamicBlockList(editor, renderUrl, components) {
    if (! renderUrl || components.length === 0) {
        return;
    }

    const withTimeout = (component) => Promise.race([
        refreshDynamicBlockComponent(editor, renderUrl, component),
        new Promise((resolve) => {
            window.setTimeout(resolve, 8_000);
        }),
    ]);

    await Promise.all(components.map((component) => withTimeout(component)));
}

function collectDynamicBlocksInTree(component) {
    if (! component?.getAttributes) {
        return [];
    }

    if (component.getAttributes()['data-voodbuilder-block']) {
        return [component];
    }

    const blocks = [];

    for (const child of component.components().models ?? [...component.components()]) {
        blocks.push(...collectDynamicBlocksInTree(child));
    }

    return blocks;
}

function collectTopLevelDynamicBlocks(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return [];
    }

    const topLevel = wrapper.components().models ?? [...wrapper.components()];
    const blocks = [];

    for (const component of topLevel) {
        if (component.getAttributes?.()['data-voodbuilder-block']) {
            blocks.push(component);

            continue;
        }

        if (component.getAttributes?.()['data-voodbuilder-chrome-drop-zone']) {
            blocks.push(...collectDynamicBlocksInTree(component));

            continue;
        }

        if (
            component.getAttributes?.()['data-voodbuilder-chrome-shell']
            || component.getAttributes?.()['data-voodbuilder-chrome-shell-part']
        ) {
            blocks.push(...collectDynamicBlocksInTree(component));
        }
    }

    return blocks;
}

async function refreshDynamicBlocks(editor, renderUrl) {
    if (! renderUrl) {
        return;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const components = safeFindComponents(wrapper, '[data-voodbuilder-block]');

    await refreshDynamicBlockList(editor, renderUrl, components);
}

async function loadLinkTargets(editor, linkTargetsUrl) {
    if (! linkTargetsUrl) {
        return;
    }

    try {
        const response = await fetch(linkTargetsUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(`Link targets request failed (${response.status})`);
        }

        const payload = await response.json();
        editor.__voodbuilderLinkTargets = {
            pages: Array.isArray(payload.pages) ? payload.pages : [],
            menuItems: Array.isArray(payload.menuItems) ? payload.menuItems : [],
        };
    } catch (error) {
        console.error('Voodbuilder Editor: could not load link targets.', error);
    }
}

async function loadBlocks(editor, blocksUrl, labels = {}) {
    try {
        const response = await fetch(blocksUrl, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(`Blocks request failed (${response.status})`);
        }

        const payload = await response.json();
        registerBlocks(editor, payload.blocks ?? []);
        prioritizeBlockCategories(editor);
        collapseBlockCategories(editor);

        applyEditorScopeBlockVisibility(editor);
        refreshChromeLayoutBlockCatalog(editor);

        try {
            applyLightBlockPreviews(editor);
        } catch (error) {
            console.error('Voodbuilder Editor: block previews failed after catalog load.', error);
        }

        editor.__voodbuilderSyncComponentsCatalog?.();
        refreshBlocksLibraryUi(editor);
    } catch (error) {
        console.error('Voodbuilder Editor: could not load block catalog.', error);
        refreshBlocksLibraryUi(editor);

        void alertDialog({
            message: labels.blocksLoadError ?? 'Could not load the block library. Reload the editor or check your session.',
            labels,
        });
    }
}

function refreshEditorLayout(editor) {
    if (! editor || isEditorBooting(editor)) {
        return;
    }

    editor.refresh();
}

function readConfig() {
    const configNode = document.querySelector('[data-voodbuilder-editor-config]');

    if (! configNode) {
        return null;
    }

    try {
        return JSON.parse(configNode.textContent ?? '');
    } catch (error) {
        console.error('Voodbuilder Editor: invalid config JSON.', error);

        return null;
    }
}

function resolveCsrfToken(fallback = '') {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? fallback;
}

function isEditorApiSavePath(path) {
    return path.startsWith('/voodbuilder/editor/')
        || path.startsWith('/vpopups/editor/');
}

function fallbackEditorSavePath(config) {
    if (config?.popupMode && config?.popupId) {
        return `/voodbuilder/editor/popups/${config.popupId}/content`;
    }

    if (config?.chromeLayoutMode && config?.chromeLayoutId) {
        return `/voodbuilder/editor/chrome-layouts/${config.chromeLayoutId}/content`;
    }

    if (config?.pageId) {
        return `/voodbuilder/editor/pages/${config.pageId}`;
    }

    return '';
}

/**
 * Resolve the editor persistence endpoint.
 *
 * Absolute public page URLs (e.g. homepage exitUrl `http://host?locale=en`) must never
 * be used: stripping them to pathname `/` makes nginx return 405 on PUT.
 */
function resolveSaveUrl(config) {
    let path = typeof config?.saveUrl === 'string' ? config.saveUrl.trim() : '';

    if (path.startsWith('http://') || path.startsWith('https://')) {
        try {
            path = new URL(path).pathname;
        } catch {
            path = '';
        }
    }

    const queryIndex = path.indexOf('?');
    if (queryIndex >= 0) {
        path = path.slice(0, queryIndex);
    }

    const hashIndex = path.indexOf('#');
    if (hashIndex >= 0) {
        path = path.slice(0, hashIndex);
    }

    if (! path.startsWith('/')) {
        path = path ? `/${path}` : '';
    }

    if (! isEditorApiSavePath(path)) {
        path = fallbackEditorSavePath(config);
    }

    if (! path) {
        throw new Error('Missing Editor save URL.');
    }

    return new URL(path, window.location.origin).href;
}

async function persistPagePayload(saveUrl, payload, csrf) {
    const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
    };

    const fetchOptions = {
        method: 'PUT',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify(payload),
        // Never follow 302 "back" redirects from validation/CSRF — those used to
        // surface as 405 on `/` or a 200 HTML page and look like a successful save.
        redirect: 'manual',
    };

    let response = await fetch(saveUrl, fetchOptions);

    if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
        throw new Error(`Save redirected (${response.status}). Check CSRF/session and payload size.`);
    }

    if (response.status === 405) {
        response = await fetch(saveUrl, {
            ...fetchOptions,
            method: 'POST',
            headers: {
                ...headers,
                'X-HTTP-Method-Override': 'PUT',
            },
        });

        if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
            throw new Error(`Save redirected (${response.status}). Check CSRF/session and payload size.`);
        }
    }

    return response;
}

function mountFrontendEditor() {
    const root = document.querySelector('[data-voodbuilder-editor-root]');
    const canvas = document.querySelector('[data-voodbuilder-editor-canvas]');
    const config = readConfig();

    if (! root || ! canvas || ! config || root.dataset.voodbuilderGrapesjsMounted === 'true') {
        return;
    }

    root.dataset.voodbuilderGrapesjsMounted = 'true';

    const editor = initVoodbuilderEditor(canvas, {
        height: '100%',
        noticeOnUnload: true,
        exitUrl: config.exitUrl,
        viewPageUrl: config.viewPageUrl ?? config.exitUrl,
        initial: config.initial ?? {},
        canvasStyles: config.canvasStyles ?? [],
        canvasFrameStyle: config.canvasFrameStyle,
        themePaletteCss: config.themePaletteCss ?? '',
        subTheme: config.subTheme,
        canvasPrefersDark: config.canvasPrefersDark,
        uploadUrl: config.uploadUrl,
        mediaLibraryUrl: config.mediaLibraryUrl ?? null,
        mediaGalleriesUrl: config.mediaGalleriesUrl ?? null,
        imageEditor: config.imageEditor !== false,
        csrf: config.csrf,
        formSubmitUrl: config.formSubmitUrl,
        bindingsUrl: config.bindingsUrl,
        linkTargetsUrl: config.linkTargetsUrl,
        bindingsPreviewUrl: config.bindingsPreviewUrl,
        conditionOptions: config.conditionOptions ?? [],
        conditionsEnabled: config.conditionsEnabled !== false,
        globalClassesUrl: config.globalClassesUrl,
        componentsUrl: config.componentsUrl,
        compileCssUrl: config.compileCssUrl,
        componentCategories: config.componentCategories ?? [],
        templateCategories: config.templateCategories ?? [],
        revisionsUrl: config.revisionsUrl,
        revisionsRestoreUrl: config.revisionsRestoreUrl,
        pageTemplatesUrl: config.pageTemplatesUrl,
        pageTemplatesCatalogUrl: config.pageTemplatesCatalogUrl ?? null,
        fonts: config.fonts ?? null,
        popupsUrl: config.popupsUrl ?? null,
        popupsPagePathsUrl: config.popupsPagePathsUrl ?? null,
        dynamicDataCollections: config.dynamicDataCollections === true,
        entitlements: config.entitlements ?? {},
        popupMode: config.popupMode ?? false,
        popupName: config.popupName ?? null,
        popupDisplayWidth: config.popupDisplayWidth ?? null,
        pageId: config.pageId ?? null,
        pageTitle: config.pageTitle ?? null,
        chromeShellMode: config.chromeShellMode ?? false,
        chromeShellName: config.chromeShellName ?? null,
        chromeShellParts: config.chromeShellParts ?? null,
        chromeLayoutCss: config.chromeLayoutCss ?? '',
        chromeLayoutMode: config.chromeLayoutMode ?? false,
        chromeLayoutName: config.chromeLayoutName ?? null,
        pageContentWidth: config.pageContentWidth ?? null,
        chromeWidth: config.chromeWidth ?? 'full',
        fullWidthPage: config.fullWidthPage !== false,
        labels: config.labels ?? {},
        bindingLabels: config.labels ?? {},
        globalTextTags: config.globalTextTags ?? {},
        builderBrand: config.builderBrand ?? 'VoodBuilder',
        packageVersion: config.packageVersion ?? '',
        plugins: config.plugins ?? {},
        blocksRenderUrl: config.blocksRenderUrl,
        blocksUrl: config.blocksUrl ?? null,
        blockAllowlist: config.blockAllowlist ?? null,
        elementsSourceUrl: config.elementsSourceUrl ?? null,
        elementsCatalogs: config.elementsCatalogs ?? [],
        siteNavDefaults: config.siteNavDefaults ?? { stickyNav: false },
        footerColumnOptions: config.footerColumnOptions ?? {},
        newsletterLists: config.newsletterLists ?? {},
        vevents: config.vevents ?? null,
        dynamicPage: config.dynamicPage ?? null,
    });

    const onResize = () => refreshEditorLayout(editor);

    window.addEventListener('resize', onResize);
    editor.on('load', () => {
        window.requestAnimationFrame(() => {
            refreshEditorLayout(editor);
        });
    });

    if (config.linkTargetsUrl) {
        void loadLinkTargets(editor, config.linkTargetsUrl);
    }

    if (config.blocksUrl) {
        void loadBlocks(editor, config.blocksUrl, config.labels ?? {});
    } else if (Array.isArray(config.blocks) && config.blocks.length > 0) {
        registerBlocks(editor, config.blocks);
        prioritizeBlockCategories(editor);
        collapseBlockCategories(editor);
        applyLightBlockPreviews(editor);
        editor.__voodbuilderSyncComponentsCatalog?.();
        refreshBlocksLibraryUi(editor);
    }

    const saveButton = document.querySelector('[data-voodbuilder-editor-save]');
    const savedIndicator = document.querySelector('[data-voodbuilder-editor-saved]');
    const saveLabel = document.querySelector('[data-voodbuilder-editor-save-label]');

    if (! saveButton) {
        return;
    }

    saveButton.addEventListener('click', async () => {
        saveButton.disabled = true;

        if (saveLabel) {
            saveLabel.textContent = config.labels?.saving ?? 'Saving…';
        }

        if (savedIndicator) {
            savedIndicator.hidden = true;
        }

        try {
            let payload;

            try {
                payload = buildPayload(editor);
            } catch (buildError) {
                console.error('VoodBuilder buildPayload failed', buildError);
                throw buildError;
            }

            const saveUrl = resolveSaveUrl(config);
            const response = await persistPagePayload(
                saveUrl,
                payload,
                resolveCsrfToken(config.csrf),
            );

            if (! response.ok) {
                const body = await response.text().catch(() => '');
                console.error('VoodBuilder page save failed', response.status, saveUrl, body);

                let detail = body;

                try {
                    const parsed = JSON.parse(body);
                    detail = parsed?.message
                        ?? (parsed?.errors ? Object.values(parsed.errors).flat().join(' ') : body);
                } catch {
                    // keep raw body
                }

                throw new Error(detail || `Save failed (${response.status})`);
            }

            const saved = await response.json().catch(() => ({}));

            if (typeof saved?.css === 'string' && saved.css.trim() !== '') {
                // Do NOT editor.setStyle(saved.css): replacing CssComposer blanks the
                // canvas for a frame (theme fallback flash). Author paints stay in
                // CssComposer from buildPayload bake. Live sheet gets utilities only —
                // never re-inject #id font rules (they would sit last and override
                // the next font change until Save). Prefetch must not reassert.
                editor.__voodbuilderApplyPageLiveCss?.(stripAuthorIdRules(saved.css));
                void prefetchFontsFromCss(editor, saved.css);
            } else {
                editor.__voodbuilderInvalidatePageCss?.()
                    ?? editor.__voodbuilderSchedulePageCssRebuild?.(0);
            }

            if (savedIndicator) {
                savedIndicator.hidden = false;
            }

            window.setTimeout(() => {
                if (savedIndicator) {
                    savedIndicator.hidden = true;
                }
            }, 2500);
        } catch (error) {
            console.error('VoodBuilder page save failed', error);

            const detail = error instanceof Error ? error.message.trim() : '';
            const base = config.labels?.error ?? 'Could not save the page.';

            await alertDialog({
                message: detail && detail !== base ? `${base}\n\n${detail}` : base,
                labels: config.labels ?? {},
            });
        } finally {
            saveButton.disabled = false;

            if (saveLabel) {
                saveLabel.textContent = config.labels?.save ?? 'Save';
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountFrontendEditor);
} else {
    mountFrontendEditor();
}
