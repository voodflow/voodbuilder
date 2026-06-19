/**
 * Vpress GrapesJS plugin — uses the public GrapesJS plugin API only.
 * @see https://grapesjs.com/docs/modules/Plugins.html
 */

function fixGrapesJsSrcUri(value) {
    return value.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');
}

function sanitizeBlockHtml(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    return html.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`);
}

function registerDynamicBlockType(editor) {
    editor.DomComponents.addType('vpress-dynamic', {
        isComponent: (element) => {
            if (element?.getAttribute?.('data-vpress-block')) {
                return { type: 'vpress-dynamic' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Vpress block',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                stylable: false,
                layerable: true,
                highlightable: true,
                attributes: {
                    class: 'vpress-gjs-dynamic',
                },
                traits: [
                    {
                        type: 'text',
                        label: 'Block ID',
                        name: 'data-vpress-block',
                    },
                    {
                        type: 'text',
                        label: 'Config (JSON)',
                        name: 'data-vpress-config',
                    },
                ],
            },
        },
    });
}

function registerBlocks(editor, blocks = []) {
    for (const block of blocks) {
        const content = typeof block.content === 'string'
            ? sanitizeBlockHtml(block.content)
            : block.content;

        editor.BlockManager.add(block.id, {
            label: block.label,
            category: block.category,
            content,
            media: block.preview ?? block.media ?? `<div class="vpress-gjs-block-fallback">${block.label}</div>`,
            attributes: block.attributes ?? {},
        });
    }
}

export { registerBlocks, sanitizeBlockHtml };

export default function vpressGrapesJsPlugin(editor, options = {}) {
    registerDynamicBlockType(editor);
    registerBlocks(editor, options.blocks ?? []);
}
