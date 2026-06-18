import grapesjs from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import 'grapesjs/dist/css/grapes.min.css';

function buildPayload(editor) {
    return {
        html: editor.getHtml(),
        css: editor.getCss(),
        project: editor.getProjectData(),
    };
}

export function initVpressGrapesJs(container, options = {}) {
    const editor = grapesjs.init({
        container,
        height: options.height ?? '640px',
        width: options.width ?? 'auto',
        fromElement: false,
        storageManager: false,
        noticeOnUnload: options.noticeOnUnload ?? false,
        plugins: [grapesjsBlocksBasic],
        pluginsOpts: {
            [grapesjsBlocksBasic]: {
                flexGrid: true,
            },
        },
        canvas: {
            styles: options.canvasStyles ?? [],
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
        panels: options.panels ?? undefined,
    });

    for (const block of options.blocks ?? []) {
        editor.BlockManager.add(block.id, {
            label: block.label,
            category: block.category,
            content: block.content,
            attributes: block.attributes ?? {},
        });
    }

    const initial = options.initial ?? {};

    if (initial.project && typeof initial.project === 'object') {
        editor.loadProjectData(initial.project);
    } else {
        if (initial.html) {
            editor.setComponents(initial.html);
        }

        if (initial.css) {
            editor.setStyle(initial.css);
        }
    }

    if (typeof options.onUpdate === 'function') {
        const notify = () => options.onUpdate(buildPayload(editor));

        editor.on('update', notify);
        editor.on('component:add', notify);
        editor.on('component:remove', notify);
        editor.on('style:change', notify);
    }

    return editor;
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
        height: 'calc(100vh - 3.5rem)',
        noticeOnUnload: true,
        initial: config.initial ?? {},
        blocks: config.blocks ?? [],
        canvasStyles: config.canvasStyles ?? [],
        uploadUrl: config.uploadUrl,
    });

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
