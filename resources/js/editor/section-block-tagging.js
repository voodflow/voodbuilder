/**
 * Tag catalog section blocks on drop and sync layer display names.
 */

import { syncLayerDisplayName } from './layer-display-name.js';
import { isComponentBlockId } from './component-block-utils.js';
import { resolveCanonicalBlockId } from './section-block-meta.js';
import { safeFindComponents } from './tailwind-visual-style.js';

function findSectionRoot(component) {
    let current = component;

    while (current) {
        const tag = String(current.get?.('tagName') ?? '').toLowerCase();

        if (tag === 'section') {
            return current;
        }

        current = typeof current.parent === 'function' ? current.parent() : null;
    }

    return null;
}

function tagSectionBlockFromDrop(component, block, editor) {
    const blockId = String(block?.get?.('id') ?? block?.id ?? '').trim();

    if (! blockId || isComponentBlockId(blockId)) {
        return;
    }

    const section = findSectionRoot(component);

    if (! section) {
        return;
    }

    const attrs = section.getAttributes?.() ?? {};
    const existingId = String(attrs['data-voodbuilder-section-block'] ?? '').trim();

    if (existingId) {
        const canonical = resolveCanonicalBlockId(existingId);

        if (canonical && canonical !== existingId) {
            section.addAttributes({ 'data-voodbuilder-section-block': canonical });
            syncLayerDisplayName(section, editor, { force: true });

            return;
        }

        syncLayerDisplayName(section, editor);

        return;
    }

    section.addAttributes({ 'data-voodbuilder-section-block': blockId });
    syncLayerDisplayName(section, editor, { force: true });
}

function normalizeSectionSignature(html) {
    return String(html ?? '')
        .replace(/\s+/g, ' ')
        .replace(/data:image[^"']+/gi, 'DATA_IMG')
        .trim();
}

function buildSectionBlockCatalog(editor) {
    const catalog = [];

    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = String(block.get?.('id') ?? block.id ?? '').trim();

        if (! blockId || isComponentBlockId(blockId) || ! blockId.startsWith('vb-')) {
            return;
        }

        const content = block.get?.('content');
        const html = typeof content === 'string' ? content : '';

        if (! html) {
            return;
        }

        catalog.push({
            id: blockId,
            signature: normalizeSectionSignature(html).slice(0, 220),
        });
    });

    return catalog;
}

function migrateLegacySectionBlocks(editor) {
    const catalog = buildSectionBlockCatalog(editor);
    const wrapper = editor.getWrapper?.();

    if (! catalog.length || ! wrapper) {
        return;
    }

    safeFindComponents(wrapper, 'section, header, footer').forEach((section) => {
        const attrs = section.getAttributes?.() ?? {};
        const existingId = String(attrs['data-voodbuilder-section-block'] ?? '').trim();

        if (existingId) {
            const canonical = resolveCanonicalBlockId(existingId);

            if (canonical && canonical !== existingId) {
                section.addAttributes({ 'data-voodbuilder-section-block': canonical });
                syncLayerDisplayName(section, editor, { force: true });

                return;
            }

            syncLayerDisplayName(section, editor);

            return;
        }

        const tag = String(section.get?.('tagName') ?? '').toLowerCase();

        if (tag !== 'section') {
            return;
        }

        const signature = normalizeSectionSignature(section.toHTML?.() ?? '').slice(0, 220);
        const match = catalog.find((entry) => {
            if (! entry.signature || entry.signature.length < 40) {
                return false;
            }

            return signature.startsWith(entry.signature.slice(0, 100))
                || entry.signature.startsWith(signature.slice(0, 100));
        });

        if (! match) {
            return;
        }

        section.addAttributes({ 'data-voodbuilder-section-block': match.id });
        syncLayerDisplayName(section, editor, { force: true });
    });
}

export function registerSectionBlockTagging(editor) {
    if (! editor || editor.__voodbuilderSectionBlockTaggingBound) {
        return;
    }

    editor.__voodbuilderSectionBlockTaggingBound = true;

    editor.on('block:drag:stop', (component, block) => {
        if (! component || ! block) {
            return;
        }

        tagSectionBlockFromDrop(component, block, editor);
    });

    editor.on('component:add', (component) => {
        // move() re-adds the node — do not retitle layers during tree reorder.
        if (editor.__voodbuilderLayerTreeSorting) {
            return;
        }

        const attrs = component.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-section-block']) {
            syncLayerDisplayName(component, editor);
        }
    });

    if (editor.getWrapper?.()) {
        migrateLegacySectionBlocks(editor);
    } else {
        editor.on('load', () => {
            migrateLegacySectionBlocks(editor);
        });
    }
}
