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

document.addEventListener('alpine:init', () => {
    Alpine.data('vpressGrapesJsFrontendEditor', (config) => ({
        editor: null,
        saving: false,
        saved: false,

        init() {
            this.$nextTick(() => {
                const host = this.$refs.editor;

                if (! host) {
                    return;
                }

                this.editor = initVpressGrapesJs(host, {
                    height: 'calc(100vh - 3.5rem)',
                    noticeOnUnload: true,
                    initial: config.initial ?? {},
                    blocks: config.blocks ?? [],
                    canvasStyles: config.canvasStyles ?? [],
                    uploadUrl: config.uploadUrl,
                });
            });
        },

        async save() {
            if (! this.editor) {
                return;
            }

            this.saving = true;
            this.saved = false;

            try {
                const response = await fetch(config.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify(buildPayload(this.editor)),
                });

                if (! response.ok) {
                    throw new Error('Save failed');
                }

                this.saved = true;

                window.setTimeout(() => {
                    this.saved = false;
                }, 2500);
            } catch (error) {
                window.alert(config.labels?.error ?? 'Could not save the page.');
            } finally {
                this.saving = false;
            }
        },

        destroy() {
            this.editor?.destroy();
        },
    }));
});
