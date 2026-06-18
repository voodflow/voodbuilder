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
        height: '640px',
        fromElement: false,
        storageManager: false,
        noticeOnUnload: false,
        plugins: [grapesjsBlocksBasic],
        pluginsOpts: {
            [grapesjsBlocksBasic]: {
                flexGrid: true,
            },
        },
        canvas: {
            styles: options.canvasStyles ?? [],
        },
        assetManager: {
            upload: options.uploadUrl,
            uploadName: 'file',
            multiUpload: false,
            autoAdd: true,
        },
        blockManager: {
            appendTo: undefined,
        },
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

    const notify = () => {
        if (typeof options.onUpdate === 'function') {
            options.onUpdate(buildPayload(editor));
        }
    };

    editor.on('update', notify);
    editor.on('component:add', notify);
    editor.on('component:remove', notify);
    editor.on('style:change', notify);

    return editor;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('vpressGrapesJsBuilder', (config) => ({
        editor: null,

        init() {
            this.$nextTick(() => {
                const host = this.$refs.editor;

                if (! host) {
                    return;
                }

                this.editor = initVpressGrapesJs(host, {
                    ...config,
                    onUpdate: (payload) => this.syncToLivewire(payload),
                });
            });
        },

        syncToLivewire(payload) {
            if (this.$wire) {
                this.$wire.set('builder_payload', payload, false);
            }
        },

        destroy() {
            this.editor?.destroy();
        },
    }));
});
