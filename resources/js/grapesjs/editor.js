import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import 'grapesjs/dist/css/grapes.min.css';

import vpressGrapesJsPlugin, { registerBlocks, sanitizeBlockHtml } from './plugins/vpress-grapesjs.js';

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

function buildPayload(editor) {
    return {
        html: editor.getHtml(),
        css: editor.getCss(),
        project: editor.getProjectData(),
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
    if (hasProjectData(initial.project) || ! initial.html?.trim()) {
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
    const editorOptions = {
        container,
        height: options.height ?? '640px',
        width: options.width ?? 'auto',
        fromElement: false,
        storageManager: false,
        noticeOnUnload: options.noticeOnUnload ?? false,
        plugins: [grapesjsBlocksBasic, vpressGrapesJsPlugin],
        pluginsOpts: {
            [grapesjsBlocksBasic]: {
                flexGrid: true,
            },
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
        styleManager: {
            sectors: [
                {
                    name: 'Typography',
                    open: true,
                    buildProps: ['font-family', 'font-size', 'font-weight', 'color', 'line-height', 'text-align'],
                },
                {
                    name: 'Spacing',
                    open: false,
                    buildProps: ['width', 'height', 'padding', 'margin'],
                },
                {
                    name: 'Decorations',
                    open: false,
                    buildProps: ['background-color', 'border-radius', 'border', 'box-shadow'],
                },
            ],
        },
        panels: options.panels ?? undefined,
    };

    if (hasProjectData(initial.project)) {
        editorOptions.projectData = initial.project;
    } else {
        const pageManager = resolvePageManager(initial);

        if (pageManager) {
            editorOptions.pageManager = pageManager;
        }
    }

    const editor = grapesjs.init(editorOptions);

    applyCanvasDocumentTheme(editor, options.subTheme);
    ensureInitialContent(editor, initial);

    if (typeof options.onUpdate === 'function') {
        const notify = () => options.onUpdate(buildPayload(editor));

        editor.on('update', notify);
        editor.on('component:add', notify);
        editor.on('component:remove', notify);
        editor.on('style:change', notify);
    }

    return editor;
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
    } catch (error) {
        console.error('Vpress GrapesJS: could not load block catalog.', error);
    }
}

function computeEditorHeight() {
    const header = document.querySelector('header[role="banner"]');
    const toolbar = document.querySelector('.vpress-grapesjs-frontend__toolbar');
    const headerHeight = header?.getBoundingClientRect().height ?? 64;
    const toolbarHeight = toolbar?.getBoundingClientRect().height ?? 52;

    return Math.max(Math.round(window.innerHeight - headerHeight - toolbarHeight), 320);
}

function syncEditorCanvasHeight(canvas, editor = null) {
    const height = computeEditorHeight();

    canvas.style.height = `${height}px`;

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

    syncEditorCanvasHeight(canvas);

    const editor = initVpressGrapesJs(canvas, {
        height: '100%',
        noticeOnUnload: true,
        initial: config.initial ?? {},
        canvasStyles: config.canvasStyles ?? [],
        canvasFrameStyle: config.canvasFrameStyle,
        subTheme: config.subTheme,
        uploadUrl: config.uploadUrl,
    });

    const onResize = () => syncEditorCanvasHeight(canvas, editor);

    window.addEventListener('resize', onResize);
    editor.on('load', onResize);

    if (config.blocksUrl) {
        void loadBlocks(editor, config.blocksUrl);
    } else if (Array.isArray(config.blocks) && config.blocks.length > 0) {
        registerBlocks(editor, config.blocks);
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
