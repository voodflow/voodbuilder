/**
 * Voodbuilder GrapesJS bootstrap — integration layer only.
 *
 * Customise behaviour via init options, events, and plugins (voodbuilder-grapesjs.js).
 * Never patch node_modules/grapesjs: changes there are lost on npm update.
 */
import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import 'grapesjs/dist/css/grapes.min.css';

import { alertDialog } from './editor-dialog.js';
import vpressGrapesJsPlugin, {
    applyFreshFooterAttributes,
    applySiteFooterColumns,
    applySiteFooterSettingsPreview,
    applySiteNavSettingsPreview,
    configureSiteFooterTraits,
    configureSiteNavTraits,
    ensureLayoutSectionTraits,
    registerSiteFooterSettingsUi,
    registerSiteNavSettingsUi,
    isSiteFooterBlock,
    isSiteNavBlock,
    isSiteHeaderBlock,
    lockDynamicPreviewContent,
    prioritizeBlockCategories,
    pruneEmptyDynamicBlocks,
    pruneEmptySections,
    refreshDynamicSlots,
    registerBlocks,
    sanitizeBlockHtml,
    syncVpressDynamicAttributes,
    syncSiteHeaderConfig,
} from './plugins/voodbuilder-grapesjs.js';
import { encodeVpressConfig, parseVpressConfig, serializeVpressConfig } from './voodbuilder-dynamic-config.js';
import { configureGrapesJsPlugins, resolveGrapesJsPlugins } from './editor-plugins.js';
import { configureLinkableButtons, registerLinkableButtonTypes, scanLinkableButtons } from './grapesjs-button-link.js';
import { registerNewsletterFormSettings } from './grapesjs-forms-blocks.js';

function voodbuilderEarlyTypesPlugin(editor, pluginOpts = {}) {
    editor.__voodbuilderLabels = pluginOpts.labels ?? editor.__voodbuilderLabels ?? {};
    registerLinkableButtonTypes(editor);
}
import { initReadingTime, initSocialShare, initCarousels } from './bricks-runtime.js';
import { configureVpressCodeBlock } from './editor-code-block.js';
import { migrateEditorComponents, purgeBroadSectionBackgroundRules, purgeLegacyEditorStyles } from './theme-tokens.js';
import { registerBindingsUi, syncBindingsForExport, syncRepeatBindingsForExport } from './bindings-ui.js';
import { registerCanvasComponentToolbar } from './canvas-component-toolbar.js';
import { registerCanvasBlockDrag, detachTopDropSpacerForExport, restoreTopDropSpacerAfterExport } from './canvas-block-drag.js';
import { registerConditionsUi, registerConditionsPersistence, syncConditionsForExport } from './conditions-ui.js';
import {
    ensureComponentInstancesForExport,
    registerComponentsUi,
    syncComponentInstancePaintForExport,
    syncComponentInstancesForExport,
} from './components-ui.js';
import { registerComponentTailwindAutobuild } from './component-tailwind-autobuild.js';
import { registerGlobalClassesUi } from './global-classes-ui.js';
import { registerRevisionsUi } from './revisions-ui.js';
import { pruneRedundantSpacingZeros, pruneRedundantSpacingZerosForExport, purgeDesyncedBackgroundCssRules, registerVisualStyleInspector, registerVisualStyleTarget, bakeSvgPaintForExport, syncPaintStylesForExport, syncSpacingStylesForExport, hydrateSvgPaintFromAttributes, purgeDesyncedPaintCssRules, restoreSvgPaintInspectorStyle, restoreSvgPaintInspectorStyles, safeFindComponents } from './tailwind-visual-style.js';
import { configureEditorChrome, editorChromeInitOptions } from './editor-chrome.js';
import {
    buildEditorShell,
    collapseBlockCategories,
    configureEditorLayout,
    editorLayoutInitOptions,
    refreshBlocksLibraryUi,
} from './editor-layout.js';
import {
    finishEditorBoot,
    registerEditorBuildStatus,
    startEditorBoot,
    waitForEditorBootTasks,
} from './editor-build-status.js';
import { applyLightBlockPreviews } from './editor-block-previews.js';
import { registerEditorVideoSafety, syncVideoComponentsForExport } from './editor-video.js';
import { registerCanvasContextMenu } from './canvas-context-menu.js';
import { bootCanvasSiteChrome, registerCanvasSiteChrome } from './canvas-site-chrome.js';
import { registerLayersContextMenu } from './layers-context-menu.js';
import { registerLayersDrag } from './layers-drag.js';
import { registerTailwindClassSuggestions } from './tailwind-class-suggestions.js';
import { syncAllLayerDisplayNames } from './layer-display-name.js';
import { registerBlocksContextMenu } from './blocks-context-menu.js';
import { registerBlocksLibraryRenderHook } from './blocks-library-sync.js';
import { registerSectionBlockTagging } from './section-block-tagging.js';
import { registerSectionNestingGuard } from './section-nesting-guard.js';

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

function buildPayload(editor) {
    const runExportStep = (label, step) => {
        try {
            step();
        } catch (error) {
            console.warn(`Voodbuilder buildPayload: ${label} failed`, error);
        }
    };

    runExportStep('normalizeVpressDynamicComponents', () => normalizeVpressDynamicComponents(editor));
    runExportStep('pruneEmptyDynamicBlocks', () => pruneEmptyDynamicBlocks(editor));
    runExportStep('syncBindingsForExport', () => syncBindingsForExport(editor));
    runExportStep('ensureComponentInstancesForExport', () => ensureComponentInstancesForExport(editor));
    runExportStep('purgeDesyncedBackgroundCssRules', () => purgeDesyncedBackgroundCssRules(editor));
    runExportStep('syncSpacingStylesForExport', () => syncSpacingStylesForExport(editor));
    runExportStep('syncPaintStylesForExport', () => syncPaintStylesForExport(editor));
    runExportStep('syncComponentInstancePaintForExport', () => syncComponentInstancePaintForExport(editor));
    runExportStep('bakeSvgPaintForExport', () => bakeSvgPaintForExport(editor));
    runExportStep('pruneRedundantSpacingZerosForExport', () => pruneRedundantSpacingZerosForExport(editor));
    runExportStep('syncComponentInstancesForExport', () => syncComponentInstancesForExport(editor));
    runExportStep('syncRepeatBindingsForExport', () => syncRepeatBindingsForExport(editor));
    runExportStep('syncConditionsForExport', () => syncConditionsForExport(editor));
    runExportStep('syncVideoComponentsForExport', () => syncVideoComponentsForExport(editor));
    runExportStep('detachTopDropSpacerForExport', () => detachTopDropSpacerForExport(editor));

    const payload = {
        html: editor.getHtml({
            cleanId: false,
            withProps: true,
            keepInlineStyle: true,
        }),
        css: editor.getCss(),
        js: editor.getJs(),
    };

    restoreTopDropSpacerAfterExport(editor);

    restoreSvgPaintInspectorStyles(editor);

    const selected = editor.getSelected?.();

    if (selected && String(selected.get?.('tagName') ?? '').toLowerCase() === 'svg') {
        window.requestAnimationFrame(() => {
            restoreSvgPaintInspectorStyle(selected);
            editor.StyleManager?.select?.(selected);
        });
    }

    return payload;
}

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

function applyCanvasDocumentTheme(editor, subTheme, themeOptions = {}) {
    if (! subTheme) {
        return;
    }

    const resolveDark = (isDark = null) => {
        if (isDark != null) {
            return Boolean(isDark);
        }

        if (themeOptions.canvasPrefersDark != null) {
            return Boolean(themeOptions.canvasPrefersDark);
        }

        return document.documentElement.classList.contains('dark');
    };

    const apply = (isDark = null) => {
        const doc = editor.Canvas.getDocument();

        if (! doc) {
            return;
        }

        doc.documentElement.setAttribute('data-voodbuilder-sub-theme', subTheme);

        if (resolveDark(isDark)) {
            doc.documentElement.classList.add('dark');
        } else {
            doc.documentElement.classList.remove('dark');
        }
    };

    editor.on('canvas:frame:load', () => apply());
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

function registerInspectorExtensions(editor, shell, options, labels) {
    if (editor.__voodbuilderInspectorExtensionsRegistered) {
        return;
    }

    editor.__voodbuilderInspectorExtensionsRegistered = true;

    registerCanvasComponentToolbar(editor, {
        makeDynamic: labels.makeDynamic,
        clearDynamic: labels.clearDynamic,
        selectParent: labels.selectParent,
        drag: labels.drag,
        clone: labels.clone,
        delete: labels.delete,
    });

    registerCanvasBlockDrag(editor);

    registerCanvasSiteChrome(editor);

    void registerBindingsUi(editor, {
        bindingsUrl: options.bindingsUrl,
        bindingsPreviewUrl: options.bindingsPreviewUrl,
        labels: options.bindingLabels ?? labels,
        dynamicMount: shell?.mounts?.dynamic ?? null,
    });

    registerConditionsUi(editor, {
        mount: shell?.mounts?.conditions ?? null,
        labels,
        conditionOptions: options.conditionOptions ?? [],
    });

    registerConditionsPersistence(editor);

    registerGlobalClassesUi(editor, {
        globalClassesUrl: options.globalClassesUrl,
        csrf: options.csrf,
        labels,
        mount: shell?.mounts?.globalClasses ?? null,
    });

    registerSiteFooterSettingsUi(editor, shell?.mounts?.siteChromeSettings ?? null);
    registerSiteNavSettingsUi(editor, shell?.mounts?.siteChromeSettings ?? null);
    registerNewsletterFormSettings(editor);
}

export function initVpressGrapesJs(container, options = {}) {
    const initial = options.initial ?? {};
    const labels = options.labels ?? {};
    const useLayout = options.layout !== false;
    const shell = useLayout ? buildEditorShell(container, labels, {
        exitUrl: options.exitUrl,
        brand: options.builderBrand ?? 'VoodBuilder',
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
        plugins: [voodbuilderEarlyTypesPlugin, grapesjsBlocksBasic, ...pluginBundle.plugins, vpressGrapesJsPlugin],
        pluginsOpts: {
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
            frameStyle: options.canvasFrameStyle,
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
    registerLinkableButtonTypes(editor);

    const dynamicBlocksGate = createDynamicBlocksPending();
    editor.__voodbuilderDynamicBlocksPending = dynamicBlocksGate.pending;
    editor.__voodbuilderDynamicBlocksRefresh = dynamicBlocksGate.pending;

    registerEditorVideoSafety(editor);

    if (shell) {
        const shellRoot = shell.shell?.closest('.voodbuilder-gjs-root') ?? container;
        shellRoot.classList.add('voodbuilder-gjs-root--booting');
        registerEditorBuildStatus(editor, shell, labels);
        registerCanvasBootGate(editor, shellRoot, shell);
        configureEditorLayout(editor, shell, labels);
        registerInspectorExtensions(editor, shell, options, labels);
    }

    if (shell && options.componentsUrl) {
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
    });
    ensureInitialContent(editor, initial);

    editor.on('load', () => {
        purgeLegacyEditorStyles(editor);
        purgeBroadSectionBackgroundRules(editor);
        migrateEditorComponents(editor);
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

        registerInspectorExtensions(editor, shell, options, labels);

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

            if (shell?.shell) {
                registerBlocksContextMenu(editor, shell.shell, labels);
            }
        } catch (error) {
            console.error('Voodbuilder GrapesJS: inspector menus failed.', error);
        }

        registerRevisionsUi(editor, {
            revisionsUrl: options.revisionsUrl,
            revisionsRestoreUrl: options.revisionsRestoreUrl,
            csrf: options.csrf,
            labels,
            toolbarMount: shell?.shell?.querySelector('.voodbuilder-gjs-topbar__actions') ?? null,
        });

        if (! options.blocksRenderUrl) {
            dynamicBlocksGate.resolve();
        } else {
            editor.on('voodbuilder:refresh-dynamic-block', (component) => {
                if (! component) {
                    return;
                }

                void refreshDynamicBlockComponent(editor, options.blocksRenderUrl, component);
            });

            const refresh = refreshDynamicBlocks(editor, options.blocksRenderUrl);
            editor.__voodbuilderDynamicBlocksRefresh = Promise.resolve(refresh);
            void editor.__voodbuilderDynamicBlocksRefresh.finally(() => {
                dynamicBlocksGate.resolve();
                migrateEditorComponents(editor);
                scanLinkableButtons(editor);
                for (const component of safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]')) {
                    try {
                        lockDynamicPreviewContent(component);

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
        initReadingTime();
        initSocialShare();
        initCarousels();

        try {
            hydrateSvgPaintFromAttributes(editor);
            purgeDesyncedPaintCssRules(editor);
        } catch {
            // Ignore paint sync errors during early frame mount.
        }
    });

    editor.on('component:add', () => {
        window.requestAnimationFrame(() => pruneEmptySections(editor));
    });

    if (typeof options.onUpdate === 'function') {
        const notify = () => options.onUpdate(buildPayload(editor));

        editor.on('update', notify);
        editor.on('component:add', notify);
        editor.on('component:remove', notify);
        editor.on('style:change', notify);
    }

    return editor;
}

async function refreshDynamicBlockComponent(editor, renderUrl, component) {
    if (! renderUrl || ! component) {
        return;
    }

    const attributes = component.getAttributes();
    const blockId = attributes['data-voodbuilder-block'];

    if (! blockId) {
        return;
    }

    const hasDynamicBindings = safeFindComponents(component, '[data-voodbuilder-bind], [data-voodbuilder-repeat]').length > 0;

    if (hasDynamicBindings) {
        lockDynamicPreviewContent(component);

        return;
    }

    if (isSiteNavBlock(blockId)) {
        syncSiteHeaderConfig(component);
    }

    const config = component.get('vpressConfig') ?? parseVpressConfig(attributes['data-voodbuilder-config']);
    const params = new URLSearchParams({
        block: blockId,
        config: serializeVpressConfig(config),
    });

    try {
        const response = await fetch(`${renderUrl}?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            const body = await response.text().catch(() => '');
            console.error('Voodbuilder GrapesJS: could not refresh dynamic block.', blockId, response.status, body);

            return;
        }

        const payload = await response.json();
        const html = payload.html;

        if (typeof html !== 'string' || html === '') {
            return;
        }

        const temp = document.createElement('div');

        temp.innerHTML = sanitizeBlockHtml(html);
        const fresh = temp.firstElementChild;

        if (! fresh) {
            return;
        }

        const freshConfig = parseVpressConfig(
            fresh.getAttribute('data-voodbuilder-config') ?? serializeVpressConfig(config),
        );

        if (isSiteNavBlock(blockId)) {
            component.set('vpressConfig', freshConfig, { silent: true });
            component.setAttributes({
                'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
                'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
                class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic',
            });
            component.components(fresh.innerHTML);
            const preserveSelection = editor.getSelected?.();

            window.requestAnimationFrame(() => {
                lockDynamicPreviewContent(component);
                configureSiteNavTraits(component, editor);
                applySiteNavSettingsPreview(component, editor);
                bootCanvasSiteChrome(editor);
                editor.trigger('voodbuilder:site-chrome-updated');

                window.requestAnimationFrame(() => {
                    if (preserveSelection && ! preserveSelection.isRemoved?.()) {
                        editor.select(preserveSelection);
                    }

                    applySiteNavSettingsPreview(component, editor);
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

        window.requestAnimationFrame(() => {
            try {
                lockDynamicPreviewContent(component);

                if (footerBlock) {
                    configureSiteFooterTraits(component, editor);
                    applySiteFooterSettingsPreview(component, editor);
                    bootCanvasSiteChrome(editor);
                    editor.trigger('voodbuilder:site-chrome-updated');
                }
            } catch (lockError) {
                console.error('Voodbuilder GrapesJS: could not lock dynamic block.', blockId, lockError);
            }
        });
    } catch (error) {
        console.error('Voodbuilder GrapesJS: could not refresh dynamic block.', blockId, error);
    }
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
        subTheme: config.subTheme,
        canvasPrefersDark: config.canvasPrefersDark,
        uploadUrl: config.uploadUrl,
        csrf: config.csrf,
        formSubmitUrl: config.formSubmitUrl,
        bindingsUrl: config.bindingsUrl,
        bindingsPreviewUrl: config.bindingsPreviewUrl,
        conditionOptions: config.conditionOptions ?? [],
        globalClassesUrl: config.globalClassesUrl,
        componentsUrl: config.componentsUrl,
        componentCategories: config.componentCategories ?? [],
        revisionsUrl: config.revisionsUrl,
        revisionsRestoreUrl: config.revisionsRestoreUrl,
        labels: config.labels ?? {},
        bindingLabels: config.labels ?? {},
        builderBrand: config.builderBrand ?? 'VoodBuilder',
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
