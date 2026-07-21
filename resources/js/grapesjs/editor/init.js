/**
 * Voodbuilder GrapesJS bootstrap — integration layer only.
 *
 * Customise behaviour via init options, events, and plugins (voodbuilder-grapesjs.js).
 * Never patch node_modules/grapesjs: changes there are lost on npm update.
 */
import '../inspector-color-preload.js';
import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import 'grapesjs/dist/css/grapes.min.css';
import 'grapick/dist/grapick.min.css';

import { alertDialog } from '../editor-dialog.js';
import vpressGrapesJsPlugin, {
    applyFreshFooterAttributes,
    applySiteFooterColumns,
    applySiteFooterSettingsPreview,
    applySiteNavSettingsPreview,
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
    sanitizeBlockHtml,
    syncVpressDynamicAttributes,
    syncSiteHeaderConfig,
} from '../plugins/voodbuilder.js';
import { stripInvalidDomAttributes } from '../core/html-sanitize.js';
import { configureGrapesJsPlugins, resolveGrapesJsPlugins } from '../editor-plugins.js';
import { configureLinkableButtons, registerLinkableButtonTypes, scanLinkableButtons } from '../grapesjs-button-link.js';
import { registerNewsletterFormSettings } from '../grapesjs-forms-blocks.js';
import { registerChromeContentSlotType } from '../chrome-content-slot-utils.js';
import {
    finalizeLayoutInspectorBootstrap,
    promoteInspectableBlockSelection,
    rebuildLayoutChromeBlockRegistry,
    registerBlockSettingsUi,
    refreshBlockSettingsUi,
} from '../blocks/settings/index.js';
import { buildPayload } from './payload.js';
import {
    registerChromeLayoutInspectorSelection,
    wireInspector,
} from './inspector.js';
import { registerInspectorColorFix, installGlobalColorInputValueFix } from '../inspector-color-fix.js';
import { guardEditorLayersRender } from '../tailwind-visual-style.js';
import { configureVpressCodeBlock } from '../editor-code-block.js';
import { migrateEditorComponents, purgeBroadSectionBackgroundRules, purgeLegacyEditorStyles } from '../theme-tokens.js';
import { registerBindingsUi, syncBindingsForExport, syncRepeatBindingsForExport } from '../bindings-ui.js';
import { registerCanvasComponentToolbar, voodbuilderCopyCommandsPlugin } from '../canvas-component-toolbar.js';
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
import { registerGlobalClassesUi } from '../global-classes-ui.js';
import { registerRevisionsUi } from '../revisions-ui.js';
import { registerPageTemplatesSidebar } from '../page-templates-sidebar.js';
import { registerPopupsUi } from '../popups-ui.js';
import { pruneRedundantSpacingZeros, pruneRedundantSpacingZerosForExport, purgeDesyncedBackgroundCssRules, registerVisualStyleInspector, registerVisualStyleTarget, bakeAuthorStylesToComposerForExport, bakeSvgPaintForExport, syncPaintStylesForExport, syncSpacingStylesForExport, hydrateSvgPaintFromAttributes, purgeDesyncedPaintCssRules, restoreSvgPaintInspectorStyle, restoreSvgPaintInspectorStyles, safeFindComponents } from '../tailwind-visual-style.js';
import { configureEditorChrome, editorChromeInitOptions } from '../editor-chrome.js';
import { extractChromeShellPageHtml, registerChromeShellEditor } from '../editor-chrome-shell.js';
import { extractChromeLayoutHtml, registerChromeLayoutEditor, applyEditorScopeBlockVisibility, refreshChromeLayoutBlockCatalog, reconcileLayoutChromeBlockSettings } from '../editor-chrome-layout.js';
import {
    buildEditorShell,
    collapseBlockCategories,
    configureEditorLayout,
    editorLayoutInitOptions,
    refreshBlocksLibraryUi,
} from '../editor-layout.js';
import {
    finishEditorBoot,
    registerEditorBuildStatus,
    startEditorBoot,
    waitForEditorBootTasks,
} from '../editor-build-status.js';
import { applyLightBlockPreviews } from '../editor-block-previews.js';
import { registerEditorVideoSafety, syncVideoComponentsForExport } from '../editor-video.js';
import {
    initAnimatedCounters,
    initAnimatedCtas,
    initCarousels,
    initLogoScroll,
    initReadingTime,
    initSocialShare,
    replayEditorCanvasAnimations,
} from '../vb-runtime.js';
import { registerCanvasContextMenu } from '../canvas-context-menu.js';
import { bootCanvasSiteChrome, registerCanvasSiteChrome } from '../canvas-site-chrome.js';
import { registerLayersContextMenu } from '../layers-context-menu.js';
import { registerLayersDrag } from '../layers-drag.js';
import { registerLayersChromeFilter } from '../layers-chrome-filter.js';
import { registerTailwindClassSuggestions } from '../tailwind-class-suggestions.js';
import { registerStyleAnimationSector } from '../style-animation-sector.js';
import { registerCanvasClassHoverPopover } from '../canvas-class-hover-popover.js';
import { syncAllLayerDisplayNames } from '../layer-display-name.js';
import { registerBlocksContextMenu } from '../blocks-context-menu.js';
import { registerBlocksLibraryRenderHook } from '../blocks-library-sync.js';
import { registerSectionBlockTagging } from '../section-block-tagging.js';
import { registerSectionNestingGuard } from '../section-nesting-guard.js';
import { encodeVpressConfig, parseVpressConfig, serializeVpressConfig } from '../voodbuilder-dynamic-config.js';

function voodbuilderEarlyTypesPlugin(editor, pluginOpts = {}) {
    editor.__voodbuilderLabels = pluginOpts.labels ?? editor.__voodbuilderLabels ?? {};
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

function normalizeVpressDynamicComponents(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        syncVpressDynamicAttributes(component);

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
    if (! initial.html?.trim()) {
        return;
    }

    if (canvasIframeHasContent(editor)) {
        return;
    }

    editor.setComponents(sanitizeBlockHtml(initial.html));

    if (initial.css) {
        editor.setStyle(initial.css);
    }

    // CssComposer #id paints → component inline so Style Manager / reload keep them.
    try {
        bakeAuthorStylesToComposerForExport(editor);
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

function revealCanvasDocument(frameWindow) {
    const doc = frameWindow?.document;

    if (! doc?.body) {
        return false;
    }

    doc.body.classList.add('voodbuilder-canvas-ready', 'VPRichPage', 'VPRichPage--landing');

    return true;
}

function waitForCanvasPresentation(editor) {
    return waitForEditorBootTasks(editor, [
        waitForCanvasFrame(editor).then(() => {
            const frameWindow = editor.Canvas?.getWindow?.();

            return waitForCanvasStyles(frameWindow);
        }),
    ]).then(() => {
        if (! revealCanvasDocument(editor.Canvas?.getWindow?.())) {
            window.requestAnimationFrame(() => {
                revealCanvasDocument(editor.Canvas?.getWindow?.());
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

function registerCanvasBootGate(editor, shellRoot, shell) {
    if (! shellRoot) {
        return;
    }

    startEditorBoot(editor);

    let revealPromise = null;

    const reveal = () => {
        if (! revealPromise) {
            revealPromise = (async () => {
                try {
                    await waitForCanvasPresentation(editor);
                } finally {
                    finishEditorBoot(editor);
                    shellRoot.classList.remove('voodbuilder-gjs-root--booting');
                }
            })();
        }

        return revealPromise;
    };

    editor.on('canvas:frame:load', () => {
        void reveal();
    });

    editor.on('load', () => {
        void reveal();
    });

    window.setTimeout(() => {
        void reveal();
    }, 4_000);
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

export function initVpressGrapesJs(container, options = {}) {
    // Before grapesjs.init — StyleManager sets color inputs during construction.
    installGlobalColorInputValueFix();

    const initial = options.initial ?? {};
    const labels = options.labels ?? {};
    const useLayout = options.layout !== false;
    const shell = useLayout ? buildEditorShell(container, labels, {
        exitUrl: options.exitUrl,
        brand: options.builderBrand ?? 'VoodBuilder',
        editingBadgeTitle: options.popupMode && options.popupName
            ? (labels.popupsEditingBadge ?? 'Editing popup: {name}').replace('{name}', String(options.popupName))
            : null,
        editingBadgeHint: null,
    }) : null;

    if (shell?.mounts) {
        for (const key of ['blocks', 'layers', 'traits', 'selectors', 'styles', 'dynamic']) {
            shell.mounts[key]?.replaceChildren?.();
        }
    }

    const layoutOptions = shell ? editorLayoutInitOptions(shell.mounts) : {};
    const editorContainer = shell?.mounts.canvas ?? container;
    const chromeOptions = editorChromeInitOptions();
    const pluginBundle = resolveGrapesJsPlugins(options.plugins ?? {});
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
        plugins: [voodbuilderCopyCommandsPlugin, voodbuilderEarlyTypesPlugin, grapesjsBlocksBasic, ...pluginBundle.plugins, vpressGrapesJsPlugin],
        pluginsOpts: {
            [voodbuilderCopyCommandsPlugin]: {
                labels: options.labels ?? {},
            },
            [voodbuilderEarlyTypesPlugin]: {
                labels: options.labels ?? {},
            },
            [grapesjsBlocksBasic]: {
                flexGrid: true,
                category: 'Layout',
                blocks: ['column1', 'column2', 'column3', 'column3-7', 'image', 'video'],
            },
            ...pluginBundle.pluginsOpts,
            [vpressGrapesJsPlugin]: {
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
    editor.__voodbuilderLinkTargets = { pages: [], menuItems: [] };
    editor.__voodbuilderLinkTargetsUrl = options.linkTargetsUrl ?? null;
    // Register copy toolbar commands before project hydration / first selection.
    registerCanvasComponentToolbar(editor, {
        makeDynamic: labels.makeDynamic,
        clearDynamic: labels.clearDynamic,
        selectParent: labels.selectParent,
        drag: labels.drag,
        clone: labels.clone,
        delete: labels.delete,
        editBlockCode: labels.editBlockCode,
        copyComponentCode: labels.copyComponentCode,
        copyComponentClasses: labels.copyComponentClasses,
        copyComponentCodeSuccess: labels.copyComponentCodeSuccess,
        copyComponentCodeFailed: labels.copyComponentCodeFailed,
        classCopySuccess: labels.classCopySuccess,
        classCopyEmpty: labels.classCopyEmpty,
        classCopyFailed: labels.classCopyFailed,
    });
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
        const shellRoot = shell.shell?.closest('.voodbuilder-gjs-root') ?? container;
        shellRoot.classList.add('voodbuilder-gjs-root--booting');
        registerEditorBuildStatus(editor, shell, labels, {
            brand: options.builderBrand ?? 'VoodBuilder',
            version: options.packageVersion ?? '',
        });
        registerCanvasBootGate(editor, shellRoot, shell);
        configureEditorLayout(editor, shell, labels);
        wireInspector(editor, shell, options, labels);

        if (shell?.mounts?.layers) {
            guardEditorLayersRender(editor);
            registerLayersChromeFilter(editor);
        }
    }

    if (shell && options.componentsUrl) {
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
                componentsMount: shell.mounts?.components ?? null,
                componentPropsMount: shell.mounts?.componentProps ?? null,
                canvasStyles: options.canvasStyles ?? [],
                componentCategories: options.componentCategories ?? [],
            });
        } catch (error) {
            console.error('Voodbuilder GrapesJS: could not mount components library UI.', error);
        }

        registerComponentTailwindAutobuild(editor, {
            componentsUrl: options.componentsUrl,
            csrf: options.csrf,
        });

        registerPageTailwindAutobuild(editor, {
            componentsUrl: options.componentsUrl,
            csrf: options.csrf,
        });
    }

    configureEditorChrome(editor, {
        labels,
        shellRoot: shell?.shell ?? null,
        shell,
        toolsMount: shell?.mounts?.canvasToolbar ?? null,
        actionsMount: shell?.shell?.querySelector('.voodbuilder-gjs-topbar__actions') ?? null,
        viewPageUrl: options.viewPageUrl ?? options.exitUrl ?? null,
    });

    configureGrapesJsPlugins(editor, {
        formSubmitUrl: options.formSubmitUrl,
        csrf: options.csrf,
        plugins: options.plugins ?? {},
    });

    configureVpressCodeBlock(editor, {
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

                const start = () => {
                    if (started) {
                        return;
                    }

                    started = true;
                    void runInitialDynamicRefresh().then(resolve);
                };

                if (editor.__voodbuilderChromeLayoutReady) {
                    start();
                } else if (typeof editor.once === 'function') {
                    editor.once('voodbuilder:chrome-layout-ready', start);
                } else {
                    editor.on('voodbuilder:chrome-layout-ready', start);
                }

                window.setTimeout(start, 300);
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
                        console.warn('Voodbuilder GrapesJS: could not configure layout chrome block.', lockError);
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
            bakeAuthorStylesToComposerForExport(editor);
        } catch {
            // Ignore hydrate errors during early boot.
        }
        ensureLayoutSectionTraits(editor);
        pruneEmptySections(editor);

        try {
            applyLightBlockPreviews(editor);
        } catch (error) {
            console.error('Voodbuilder GrapesJS: block previews failed.', error);
        }

        editor.__voodbuilderSyncComponentsCatalog?.();
        registerBlocksLibraryRenderHook(editor);
        registerSectionBlockTagging(editor);
        registerSectionNestingGuard(editor);
        refreshBlocksLibraryUi(editor);

        configureLayoutBlocks(editor);

        syncAllLayerDisplayNames(editor);

        wireInspector(editor, shell, options, labels);

        editor.__voodbuilderLabels = labels;
        editor.__voodbuilderNewsletterLists = options.newsletterLists ?? {};

        configureLinkableButtons(editor);
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
                registerStyleAnimationSector(editor, {
                    mount: shell.mounts.styles,
                    labels,
                });
            }

            registerCanvasClassHoverPopover(editor, { labels });

            if (shell?.shell) {
                registerBlocksContextMenu(editor, shell.shell, labels);
            }
        } catch (error) {
            console.error('Voodbuilder GrapesJS: inspector menus failed.', error);
        }

        if (! options.popupMode) {
            registerRevisionsUi(editor, {
                revisionsUrl: options.revisionsUrl,
                revisionsRestoreUrl: options.revisionsRestoreUrl,
                csrf: options.csrf,
                labels,
                toolbarMount: shell?.shell?.querySelector('.voodbuilder-gjs-topbar__actions') ?? null,
            });
        }

        if (! options.popupMode) {
            registerPageTemplatesSidebar(editor, {
                pageTemplatesUrl: options.pageTemplatesUrl,
                pageTemplatesCatalogUrl: options.pageTemplatesCatalogUrl ?? null,
                csrf: options.csrf,
                labels,
                templateCategories: options.templateCategories ?? [],
                defaultTemplateCategory: 'Ecommerce',
                templatesMount: shell?.mounts?.templates ?? null,
                popupMode: options.popupMode ?? false,
            });

            registerPopupsUi(editor, {
                popupsUrl: options.popupsUrl,
                popupsPagePathsUrl: options.popupsPagePathsUrl ?? null,
                csrf: options.csrf,
                labels,
                popupMode: options.popupMode ?? false,
                toolbarMount: shell?.shell?.querySelector('.voodbuilder-gjs-topbar__actions') ?? null,
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
                        console.warn('Voodbuilder GrapesJS: could not lock chrome shell block.', lockError);
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
                        console.warn('Voodbuilder GrapesJS: could not lock dynamic block.', component.getAttributes()['data-voodbuilder-block'], lockError);
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
            // Replay animations in the editor canvas so authors can preview them.
            initAnimatedCounters({ root: frameDoc, force: true, preferImmediate: true });
            initAnimatedCtas({ root: frameDoc, force: true });
            replayEditorCanvasAnimations({ root: frameDoc });
            initLogoScroll({ root: frameDoc });
        } catch {
            // VB runtime is optional in the editor canvas.
        }

        try {
            hydrateSvgPaintFromAttributes(editor);
            purgeDesyncedPaintCssRules(editor);
        } catch {
            // Ignore paint sync errors during early frame mount.
        }
    });

    let pruneEmptySectionsTimer = null;

    editor.on('component:add', () => {
        window.clearTimeout(pruneEmptySectionsTimer);
        pruneEmptySectionsTimer = window.setTimeout(() => {
            pruneEmptySections(editor);
        }, 180);
    });

    if (typeof options.onUpdate === 'function') {
        // Read-only snapshot: mutate:false must not run export purges/bakes that
        // wipe CssComposer while the author is still editing.
        let updateTimer = null;
        const notify = () => {
            window.clearTimeout(updateTimer);
            updateTimer = window.setTimeout(() => {
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
    return `${blockId}::${serializeVpressConfig(config ?? {})}`;
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

        const config = component.get('vpressConfig') ?? parseVpressConfig(attributes['data-voodbuilder-config']);
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
            config: serializeVpressConfig(config),
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
            console.error('Voodbuilder GrapesJS: could not refresh dynamic block.', blockId, response.status, preview);

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

        const freshConfig = parseVpressConfig(
            fresh.getAttribute('data-voodbuilder-config') ?? serializeVpressConfig(config),
        );
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
            component.set('vpressConfig', freshConfig, { silent: true });
            component.setAttributes({
                'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
                'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
                class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic',
            });
            component.components(fresh.innerHTML);
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

        if (footerBlock && fresh.tagName === 'FOOTER') {
            applyFreshFooterAttributes(component, fresh, blockId, freshConfig);

            if (safeFindComponents(component, '[data-voodbuilder-menu], [data-voodbuilder-brand]').length > 0) {
                refreshDynamicSlots(component, fresh);
            } else {
                component.components(fresh.innerHTML);
            }
        } else {
            component.set('vpressConfig', freshConfig, { silent: true });
            component.setAttributes({
                'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
                'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
                class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic',
                ...(fresh.hasAttribute('data-voodbuilder-hydrate-slots')
                    ? { 'data-voodbuilder-hydrate-slots': '1' }
                    : {}),
            });

            const hydratesSlots = fresh.hasAttribute('data-voodbuilder-hydrate-slots')
                && safeFindComponents(component, '[data-voodbuilder-menu], [data-voodbuilder-brand]').length > 0;

            if (hydratesSlots) {
                refreshDynamicSlots(component, fresh);
            } else {
                component.components(fresh.innerHTML);
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
                console.error('Voodbuilder GrapesJS: could not lock dynamic block.', blockId, lockError);
            }
        });
    } catch (error) {
        console.error('Voodbuilder GrapesJS: could not refresh dynamic block.', blockId || 'unknown', error);
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

    await Promise.all(components.map((component) => refreshDynamicBlockComponent(editor, renderUrl, component)));
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

    for (const component of components) {
        await refreshDynamicBlockComponent(editor, renderUrl, component);
    }
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
        console.error('Voodbuilder GrapesJS: could not load link targets.', error);
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
            console.error('Voodbuilder GrapesJS: block previews failed after catalog load.', error);
        }

        editor.__voodbuilderSyncComponentsCatalog?.();
        refreshBlocksLibraryUi(editor);
    } catch (error) {
        console.error('Voodbuilder GrapesJS: could not load block catalog.', error);
        refreshBlocksLibraryUi(editor);

        void alertDialog({
            message: labels.blocksLoadError ?? 'Could not load the block library. Reload the editor or check your session.',
            labels,
        });
    }
}

function refreshEditorLayout(editor) {
    if (! editor || editor.__voodbuilderBooting === true) {
        return;
    }

    editor.refresh();
}

function readConfig() {
    const configNode = document.querySelector('[data-voodbuilder-grapesjs-config]');

    if (! configNode) {
        return null;
    }

    try {
        return JSON.parse(configNode.textContent ?? '');
    } catch (error) {
        console.error('Voodbuilder GrapesJS: invalid config JSON.', error);

        return null;
    }
}

function resolveCsrfToken(fallback = '') {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? fallback;
}

function resolveSaveUrl(config) {
    const pageId = config?.pageId;
    let path = typeof config?.saveUrl === 'string' ? config.saveUrl.trim() : '';

    if (path.startsWith('http://') || path.startsWith('https://')) {
        try {
            path = new URL(path).pathname;
        } catch {
            path = '';
        }
    }

    if (! path && pageId) {
        path = `/voodbuilder/grapesjs/pages/${pageId}`;
    }

    if (! path) {
        throw new Error('Missing GrapesJS save URL.');
    }

    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    return new URL(normalizedPath, window.location.origin).href;
}

async function persistPagePayload(saveUrl, payload, csrf) {
    const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
    };

    let response = await fetch(saveUrl, {
        method: 'PUT',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify(payload),
    });

    if (response.status === 405) {
        response = await fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                ...headers,
                'X-HTTP-Method-Override': 'PUT',
            },
            body: JSON.stringify(payload),
        });
    }

    return response;
}

function mountFrontendEditor() {
    const root = document.querySelector('[data-voodbuilder-grapesjs-root]');
    const canvas = document.querySelector('[data-voodbuilder-grapesjs-canvas]');
    const config = readConfig();

    if (! root || ! canvas || ! config || root.dataset.voodbuilderGrapesjsMounted === 'true') {
        return;
    }

    root.dataset.voodbuilderGrapesjsMounted = 'true';

    const editor = initVpressGrapesJs(canvas, {
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
        csrf: config.csrf,
        formSubmitUrl: config.formSubmitUrl,
        bindingsUrl: config.bindingsUrl,
        linkTargetsUrl: config.linkTargetsUrl,
        bindingsPreviewUrl: config.bindingsPreviewUrl,
        conditionOptions: config.conditionOptions ?? [],
        globalClassesUrl: config.globalClassesUrl,
        componentsUrl: config.componentsUrl,
        componentCategories: config.componentCategories ?? [],
        templateCategories: config.templateCategories ?? [],
        revisionsUrl: config.revisionsUrl,
        revisionsRestoreUrl: config.revisionsRestoreUrl,
        pageTemplatesUrl: config.pageTemplatesUrl,
        pageTemplatesCatalogUrl: config.pageTemplatesCatalogUrl ?? null,
        popupsUrl: config.popupsUrl ?? null,
        popupsPagePathsUrl: config.popupsPagePathsUrl ?? null,
        popupMode: config.popupMode ?? false,
        popupName: config.popupName ?? null,
        popupDisplayWidth: config.popupDisplayWidth ?? null,
        chromeShellMode: config.chromeShellMode ?? false,
        chromeShellName: config.chromeShellName ?? null,
        chromeShellParts: config.chromeShellParts ?? null,
        chromeLayoutCss: config.chromeLayoutCss ?? '',
        chromeLayoutMode: config.chromeLayoutMode ?? false,
        chromeLayoutName: config.chromeLayoutName ?? null,
        labels: config.labels ?? {},
        bindingLabels: config.labels ?? {},
        builderBrand: config.builderBrand ?? 'VoodBuilder',
        packageVersion: config.packageVersion ?? '',
        plugins: config.plugins ?? {},
        blocksRenderUrl: config.blocksRenderUrl,
        siteNavDefaults: config.siteNavDefaults ?? { stickyNav: false },
        footerColumnOptions: config.footerColumnOptions ?? {},
        newsletterLists: config.newsletterLists ?? {},
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

    const saveButton = document.querySelector('[data-voodbuilder-grapesjs-save]');
    const savedIndicator = document.querySelector('[data-voodbuilder-grapesjs-saved]');
    const saveLabel = document.querySelector('[data-voodbuilder-grapesjs-save-label]');

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
                throw new Error(body || `Save failed (${response.status})`);
            }

            const saved = await response.json().catch(() => ({}));

            if (typeof saved?.css === 'string' && saved.css.trim() !== '') {
                // Layout editor: CssComposer must get the compiled utilities.
                // Page chrome-shell: never wipe live CSS with a possibly-stale/partial
                // server bundle first — invalidate and recompile from current HTML so
                // drop-time styles (theme buttons, flex layouts) stay intact.
                if (editor.__voodbuilderChromeShellMode) {
                    editor.__voodbuilderInvalidatePageCss?.()
                        ?? editor.__voodbuilderSchedulePageCssRebuild?.(0);
                } else if (editor.__voodbuilderChromeLayoutMode) {
                    editor.setStyle(saved.css);
                    editor.__voodbuilderApplyPageLiveCss?.(saved.css);
                } else {
                    editor.setStyle(saved.css);
                    editor.__voodbuilderApplyPageLiveCss?.(saved.css);
                }
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

            await alertDialog({
                message: config.labels?.error ?? 'Could not save the page.',
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
