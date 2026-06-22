/**
 * Vpress GrapesJS bootstrap — integration layer only.
 *
 * Customise behaviour via init options, events, and plugins (vpress-grapesjs.js).
 * Never patch node_modules/grapesjs: changes there are lost on npm update.
 */
import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import 'grapesjs/dist/css/grapes.min.css';

import vpressGrapesJsPlugin, {
    applyFreshFooterAttributes,
    applySiteFooterColumns,
    isSiteFooterBlock,
    lockDynamicPreviewContent,
    prioritizeBlockCategories,
    pruneEmptyDynamicBlocks,
    refreshDynamicSlots,
    registerBlocks,
    sanitizeBlockHtml,
    syncVpressDynamicAttributes,
} from './plugins/vpress-grapesjs.js';
import { encodeVpressConfig, parseVpressConfig, serializeVpressConfig } from './vpress-dynamic-config.js';
import { configureGrapesJsPlugins, resolveGrapesJsPlugins } from './editor-plugins.js';
import { migrateEditorComponents } from './theme-tokens.js';
import { editorChromeInitOptions } from './editor-chrome.js';

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
    editor.getWrapper().find('[data-vpress-block]').forEach((component) => {
        syncVpressDynamicAttributes(component);
    });
}

function buildPayload(editor) {
    normalizeVpressDynamicComponents(editor);
    pruneEmptyDynamicBlocks(editor);

    return {
        html: editor.getHtml(),
        css: editor.getCss(),
    };
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

function canvasHasRenderedHtml(editor) {
    const html = editor.getHtml()?.replace(/\s/g, '') ?? '';

    return html.length > 20;
}

function applyInitialContent(editor, initial) {
    if (! initial.html?.trim()) {
        return;
    }

    if (canvasHasRenderedHtml(editor)) {
        return;
    }

    editor.setComponents(sanitizeBlockHtml(initial.html));

    if (initial.css) {
        editor.setStyle(initial.css);
    }
}

function ensureInitialContent(editor, initial) {
    if (initial.pageManager || hasProjectData(initial.project) || ! initial.html?.trim()) {
        return;
    }

    const apply = () => applyInitialContent(editor, initial);

    editor.on('load', apply);
    editor.on('canvas:frame:load', apply);
    window.requestAnimationFrame(apply);
    window.setTimeout(apply, 100);
}

function applyCanvasDocumentTheme(editor, subTheme) {
    if (! subTheme) {
        return;
    }

    const apply = (isDark = null) => {
        const doc = editor.Canvas.getDocument();

        if (! doc) {
            return;
        }

        doc.documentElement.setAttribute('data-vpress-sub-theme', subTheme);

        const useDark = isDark ?? document.documentElement.classList.contains('dark');

        if (useDark) {
            doc.documentElement.classList.add('dark');
        } else {
            doc.documentElement.classList.remove('dark');
        }
    };

    editor.on('canvas:frame:load', () => apply());
    window.addEventListener('vpress:theme-changed', (event) => {
        apply(event?.detail?.isDark);
    });
    apply();
}

export function initVpressGrapesJs(container, options = {}) {
    const initial = options.initial ?? {};
    const chromeOptions = editorChromeInitOptions();
    const pluginBundle = resolveGrapesJsPlugins(options.plugins ?? {});
    const editorOptions = {
        container,
        height: options.height ?? '640px',
        width: options.width ?? 'auto',
        fromElement: false,
        storageManager: false,
        noticeOnUnload: options.noticeOnUnload ?? false,
        showDevices: chromeOptions.showDevices,
        deviceManager: chromeOptions.deviceManager,
        plugins: [grapesjsBlocksBasic, ...pluginBundle.plugins, vpressGrapesJsPlugin],
        pluginsOpts: {
            [grapesjsBlocksBasic]: {
                flexGrid: true,
            },
            ...pluginBundle.pluginsOpts,
            [vpressGrapesJsPlugin]: {
                blocks: options.blocks ?? [],
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
            appendTo: options.blocksAppendTo ?? undefined,
        },
        layerManager: {
            showWrapper: true,
        },
        selectorManager: {
            componentFirst: true,
        },
        styleManager: chromeOptions.styleManager,
        panels: options.panels ?? undefined,
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

    configureGrapesJsPlugins(editor, {
        formSubmitUrl: options.formSubmitUrl,
        csrf: options.csrf,
        plugins: options.plugins ?? {},
    });

    applyCanvasDocumentTheme(editor, options.subTheme);
    ensureInitialContent(editor, initial);

    editor.on('load', () => {
        migrateEditorComponents(editor);
        void refreshDynamicBlocks(editor, options.blocksRenderUrl).finally(() => {
            editor.getWrapper().find('[data-vpress-block]').forEach((component) => {
                lockDynamicPreviewContent(component);

                if (isSiteFooterBlock(component.getAttributes()['data-vpress-block'])) {
                    applySiteFooterColumns(component, component.get('vpressConfig')?.columns ?? 4);
                }
            });
        });
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

async function refreshDynamicBlocks(editor, renderUrl) {
    if (! renderUrl) {
        return;
    }

    const components = editor.getWrapper().find('[data-vpress-block]');

    for (const component of components) {
        const attributes = component.getAttributes();
        const blockId = attributes['data-vpress-block'];

        if (! blockId) {
            continue;
        }

        const config = component.get('vpressConfig') ?? parseVpressConfig(attributes['data-vpress-config']);
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
                continue;
            }

            const payload = await response.json();
            const html = payload.html;

            if (typeof html !== 'string' || html === '') {
                continue;
            }

            const temp = document.createElement('div');

            temp.innerHTML = sanitizeBlockHtml(html);
            const fresh = temp.firstElementChild;

            if (! fresh) {
                continue;
            }

            const freshConfig = parseVpressConfig(
                fresh.getAttribute('data-vpress-config') ?? serializeVpressConfig(config),
            );

            const footerBlock = isSiteFooterBlock(blockId);

            if (footerBlock && fresh.tagName === 'FOOTER') {
                applyFreshFooterAttributes(component, fresh, blockId, freshConfig);

                if (component.find('[data-vpress-menu], [data-vpress-brand]').length > 0) {
                    refreshDynamicSlots(component, fresh);
                } else {
                    component.components(fresh.innerHTML);
                }
            } else {
                component.set('vpressConfig', freshConfig, { silent: true });
                component.setAttributes({
                    'data-vpress-block': fresh.getAttribute('data-vpress-block') ?? blockId,
                    'data-vpress-config': fresh.getAttribute('data-vpress-config') ?? encodeVpressConfig(freshConfig),
                    class: fresh.getAttribute('class') ?? 'vpress-gjs-dynamic',
                    ...(fresh.hasAttribute('data-vpress-hydrate-slots')
                        ? { 'data-vpress-hydrate-slots': '1' }
                        : {}),
                });

                const hydratesSlots = fresh.hasAttribute('data-vpress-hydrate-slots')
                    && component.find('[data-vpress-menu], [data-vpress-brand]').length > 0;

                if (hydratesSlots) {
                    refreshDynamicSlots(component, fresh);
                } else {
                    component.components(fresh.innerHTML);
                }
            }

            lockDynamicPreviewContent(component);

            if (footerBlock) {
                applySiteFooterColumns(component, freshConfig.columns ?? 4);
            }
        } catch (error) {
            console.error('Vpress GrapesJS: could not refresh dynamic block.', blockId, error);
        }
    }
}

async function loadBlocks(editor, blocksUrl) {
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
    } catch (error) {
        console.error('Vpress GrapesJS: could not load block catalog.', error);
    }
}

function refreshEditorLayout(editor) {
    if (editor) {
        editor.refresh();
    }
}

function readConfig() {
    const configNode = document.querySelector('[data-vpress-grapesjs-config]');

    if (! configNode) {
        return null;
    }

    try {
        return JSON.parse(configNode.textContent ?? '');
    } catch (error) {
        console.error('Vpress GrapesJS: invalid config JSON.', error);

        return null;
    }
}

function mountFrontendEditor() {
    const root = document.querySelector('[data-vpress-grapesjs-root]');
    const canvas = document.querySelector('[data-vpress-grapesjs-canvas]');
    const saveButton = document.querySelector('[data-vpress-grapesjs-save]');
    const savedIndicator = document.querySelector('[data-vpress-grapesjs-saved]');
    const saveLabel = document.querySelector('[data-vpress-grapesjs-save-label]');
    const config = readConfig();

    if (! root || ! canvas || ! config) {
        return;
    }

    const editor = initVpressGrapesJs(canvas, {
        height: '100%',
        noticeOnUnload: true,
        initial: config.initial ?? {},
        canvasStyles: config.canvasStyles ?? [],
        canvasFrameStyle: config.canvasFrameStyle,
        subTheme: config.subTheme,
        uploadUrl: config.uploadUrl,
        csrf: config.csrf,
        formSubmitUrl: config.formSubmitUrl,
        plugins: config.plugins ?? {},
        blocksRenderUrl: config.blocksRenderUrl,
    });

    const onResize = () => refreshEditorLayout(editor);

    window.addEventListener('resize', onResize);
    editor.on('load', onResize);

    if (config.blocksUrl) {
        void loadBlocks(editor, config.blocksUrl);
    } else if (Array.isArray(config.blocks) && config.blocks.length > 0) {
        registerBlocks(editor, config.blocks);
        prioritizeBlockCategories(editor);
    }

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
            const response = await fetch(config.saveUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: JSON.stringify(buildPayload(editor)),
            });

            if (! response.ok) {
                throw new Error('Save failed');
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
            window.alert(config.labels?.error ?? 'Could not save the page.');
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
