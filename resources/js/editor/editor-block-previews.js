/**
 * Lightweight block preview icons for the Editor sidebar.
 * Prefer scaled HTML previews (real block markup); fall back to wireframes when needed.
 */

import { previewSvg, thumbWrap, htmlBlockPreview, blockHasHtmlPreview } from './editor-block-preview-utils.js';
import {
    BASIC_BLOCK_LABELS,
    resolveBlockLabel,
    resolveBlockWireframe,
    unifyBlockCategories,
} from './section-block-meta.js';
import { UTILITY_BLOCK_WIREFRAMES } from './editor-utility-blocks.js';

export const LIGHT_BLOCK_PREVIEWS = {
    column1: previewSvg('<rect x="11" y="13" width="26" height="22" rx="2.5" />'),
    column2: previewSvg(
        '<rect x="8" y="13" width="13" height="22" rx="2" />'
        + '<rect x="27" y="13" width="13" height="22" rx="2" />',
    ),
    column3: previewSvg(
        '<rect x="6" y="13" width="9" height="22" rx="1.75" />'
        + '<rect x="19.5" y="13" width="9" height="22" rx="1.75" />'
        + '<rect x="33" y="13" width="9" height="22" rx="1.75" />',
    ),
    'column3-7': previewSvg(
        '<rect x="6" y="13" width="11" height="22" rx="1.75" />'
        + '<rect x="22" y="13" width="20" height="22" rx="2" />',
    ),
};

const SECTION_WIREFRAMES = {
    hero(variant) {
        const split = variant === 'B' || variant === 'C';

        return previewSvg(
            (split
                ? '<rect x="8" y="10" width="14" height="28" rx="2" />'
                + '<path d="M26 16h14M26 22h12M26 28h10" />'
                + '<rect x="26" y="32" width="14" height="5" rx="1.5" />'
                : '<path d="M12 16h24M14 22h20M16 28h16" />'
                + '<rect x="17" y="33" width="14" height="5" rx="1.5" />'),
        );
    },
    content(variant) {
        if (variant === 'B' || variant === 'D') {
            return previewSvg(
                '<path d="M10 14h18M10 20h28M10 26h24" />'
                + '<rect x="10" y="31" width="10" height="8" rx="1.5" />'
                + '<rect x="22" y="31" width="10" height="8" rx="1.5" />'
                + '<rect x="34" y="31" width="10" height="8" rx="1.5" />',
            );
        }

        return previewSvg(
            '<path d="M12 14h24M12 20h20M12 26h16" />'
            + '<rect x="12" y="31" width="24" height="5" rx="1.5" />',
        );
    },
    blog(variant) {
        if (variant === 'D' || variant === 'E') {
            return previewSvg(
                '<path d="M10 14h8M10 20h24M10 26h20M10 32h16" />'
                + '<path d="M34 14v22M10 14v22" />',
            );
        }

        const cols = variant === 'C' ? 1 : 3;
        const cards = cols === 1
            ? '<rect x="14" y="14" width="20" height="24" rx="2" />'
            : '<rect x="8" y="14" width="9" height="24" rx="1.5" />'
            + '<rect x="19.5" y="14" width="9" height="24" rx="1.5" />'
            + '<rect x="31" y="14" width="9" height="24" rx="1.5" />';

        return previewSvg(cards);
    },
    contact(variant) {
        if (variant === 'A') {
            return previewSvg(
                '<rect x="8" y="10" width="32" height="28" rx="2.5" />'
                + '<path d="M14 16h20M14 22h20M14 28h20" />'
                + '<rect x="14" y="32" width="12" height="4" rx="1" />',
            );
        }

        return previewSvg(
            '<rect x="8" y="12" width="14" height="24" rx="2" />'
            + '<rect x="26" y="12" width="14" height="24" rx="2" />'
            + '<path d="M28 18h10M28 24h10M28 30h10" />',
        );
    },
    cta() {
        return previewSvg(
            '<rect x="8" y="14" width="32" height="20" rx="2.5" />'
            + '<path d="M14 20h20M16 26h16" />'
            + '<rect x="17" y="29" width="14" height="4" rx="1" />',
        );
    },
    features(variant) {
        const cols = variant === 'A' ? 4 : 2;

        if (cols === 4) {
            return previewSvg(
                '<rect x="8" y="16" width="7" height="7" rx="1.5" />'
                + '<rect x="17" y="16" width="7" height="7" rx="1.5" />'
                + '<rect x="26" y="16" width="7" height="7" rx="1.5" />'
                + '<rect x="35" y="16" width="7" height="7" rx="1.5" />'
                + '<path d="M8 28h7M17 28h7M26 28h7M35 28h7" />',
            );
        }

        return previewSvg(
            '<rect x="10" y="14" width="12" height="10" rx="2" />'
            + '<rect x="26" y="14" width="12" height="10" rx="2" />'
            + '<path d="M10 30h12M26 30h12" />',
        );
    },
    gallery(variant) {
        if (variant === 'A' || variant === 'C') {
            return previewSvg(
                '<rect x="8" y="12" width="32" height="24" rx="2" />'
                + '<path d="M8 24h32" />',
            );
        }

        return previewSvg(
            '<rect x="8" y="14" width="14" height="10" rx="1.5" />'
            + '<rect x="26" y="14" width="14" height="10" rx="1.5" />'
            + '<rect x="8" y="28" width="14" height="10" rx="1.5" />'
            + '<rect x="26" y="28" width="14" height="10" rx="1.5" />',
        );
    },
    header() {
        return previewSvg(
            '<rect x="8" y="16" width="32" height="10" rx="2" />'
            + '<circle cx="14" cy="21" r="2" />'
            + '<path d="M20 21h8M30 21h4M36 21h4" />',
        );
    },
    footer() {
        return previewSvg(
            '<rect x="8" y="18" width="32" height="14" rx="2" />'
            + '<path d="M12 22h6M22 22h6M32 22h6M12 27h6M22 27h6M32 27h6" />',
        );
    },
    pricing() {
        return previewSvg(
            '<rect x="8" y="14" width="9" height="22" rx="1.5" />'
            + '<rect x="19.5" y="12" width="9" height="24" rx="1.5" />'
            + '<rect x="31" y="14" width="9" height="22" rx="1.5" />',
        );
    },
    statistics() {
        return previewSvg(
            '<path d="M10 32V22M18 32V16M26 32V20M34 32V12" />'
            + '<path d="M8 34h32" />',
        );
    },
    steps() {
        return previewSvg(
            '<circle cx="12" cy="24" r="3" />'
            + '<circle cx="24" cy="24" r="3" />'
            + '<circle cx="36" cy="24" r="3" />'
            + '<path d="M15 24h6M27 24h6" />',
        );
    },
    team() {
        return previewSvg(
            '<circle cx="12" cy="20" r="4" />'
            + '<circle cx="24" cy="20" r="4" />'
            + '<circle cx="36" cy="20" r="4" />'
            + '<path d="M8 32h8M20 32h8M32 32h8" />',
        );
    },
    testimonial() {
        return previewSvg(
            '<rect x="10" y="14" width="28" height="18" rx="3" />'
            + '<path d="M15 20h18M15 25h14M15 30h10" />',
        );
    },
    tabs() {
        return previewSvg(
            '<rect x="8" y="12" width="11" height="5" rx="2.5" fill="#0d94e6" />'
            + '<rect x="22" y="12" width="11" height="5" rx="2.5" />'
            + '<rect x="36" y="12" width="6" height="5" rx="2.5" />'
            + '<rect x="8" y="22" width="32" height="12" rx="2" />'
            + '<path d="M12 27h20M12 30h14" />',
        );
    },
    commerce() {
        return previewSvg(
            '<rect x="10" y="14" width="12" height="12" rx="1.5" />'
            + '<rect x="26" y="14" width="12" height="12" rx="1.5" />'
            + '<path d="M12 30h8M28 30h8" />',
        );
    },
    ecommerce() {
        return SECTION_WIREFRAMES.commerce();
    },
};

function blockCategoryLabel(category) {
    if (category == null) {
        return '';
    }

    if (typeof category === 'object') {
        return String(category.get?.('label') ?? category.get?.('id') ?? category.label ?? category.id ?? '');
    }

    return String(category);
}

function sectionCategoryKey(category) {
    const label = blockCategoryLabel(category);
    const match = label.match(/Sections\s*·\s*([\w]+)/i);

    return match ? match[1].toLowerCase() : label.toLowerCase();
}

function sectionVariant(label) {
    const numbered = String(label ?? '').trim().match(/·\s*(\d+)\s*$/);

    if (numbered) {
        return ['A', 'B', 'C', 'D', 'E'][Number(numbered[1]) - 1] ?? 'A';
    }

    const match = String(label ?? '').trim().match(/\b([A-E])$/i);

    return match ? match[1].toUpperCase() : 'A';
}

function sectionWireframe(category, variant) {
    const key = category in SECTION_WIREFRAMES ? category : 'content';
    const render = SECTION_WIREFRAMES[key];

    return thumbWrap(render(variant));
}

function applyBasicBlockPreviews(blockManager) {
    for (const [blockId, media] of Object.entries(LIGHT_BLOCK_PREVIEWS)) {
        const block = blockManager.get(blockId);

        if (! block) {
            continue;
        }

        block.set('media', media);

        const label = BASIC_BLOCK_LABELS[blockId];

        if (label) {
            block.set('label', label);
        }
    }
}

function resolveHtmlPreviewForBlock(block) {
    const blockId = String(block.get('id') ?? '');

    // Layout + curated Basic/Media thumbs must keep Tabler/icon media (not scaled HTML).
    if (
        blockId.startsWith('voodbuilder-layout-')
        || Object.prototype.hasOwnProperty.call(UTILITY_BLOCK_WIREFRAMES, blockId)
    ) {
        return '';
    }

    const existingMedia = block.get('media');

    if (blockHasHtmlPreview(existingMedia)) {
        return existingMedia;
    }

    const content = block.get('content');

    if (typeof content === 'string' && content.trim() !== '') {
        return htmlBlockPreview(content);
    }

    return '';
}

function applySectionBlockPreviews(blockManager) {
    blockManager.getAll().forEach((block) => {
        const blockId = String(block.get('id') ?? '');
        const htmlPreview = resolveHtmlPreviewForBlock(block);

        if (htmlPreview) {
            block.set('media', htmlPreview);
        } else if (blockId.startsWith('vb-') || blockId.startsWith('site_') || blockId.startsWith('voodbuilder-site_')) {
            const customWireframe = resolveBlockWireframe(blockId);

            if (customWireframe) {
                block.set('media', customWireframe);
            } else {
                const category = sectionCategoryKey(block.get('category'));
                const variant = sectionVariant(block.get('label'));

                block.set('media', sectionWireframe(category, variant));
            }
        }

        const label = resolveBlockLabel(blockId, block.get('label'));

        if (label && label !== block.get('label')) {
            block.set('label', label);
        }
    });
}

function applyFormsBlockPreviews(blockManager) {
    const formsCategoryIds = new Set(['Forms', 'forms']);

    blockManager.getAll().forEach((block) => {
        const blockId = String(block.get('id') ?? '');
        const category = blockCategoryLabel(block.get('category'));

        if (! formsCategoryIds.has(category) && ! blockId.startsWith('voodbuilder-form')) {
            return;
        }

        const wireframe = resolveBlockWireframe(blockId);
        const htmlPreview = resolveHtmlPreviewForBlock(block);

        if (htmlPreview) {
            block.set('media', htmlPreview);
        } else if (wireframe) {
            block.set('media', wireframe);
        }

        const label = resolveBlockLabel(blockId, block.get('label'));

        if (label && label !== block.get('label')) {
            block.set('label', label);
        }
    });
}

function applyUtilityBlockPreviews(blockManager) {
    blockManager.getAll().forEach((block) => {
        const blockId = String(block.get('id') ?? '');
        const wireframe = UTILITY_BLOCK_WIREFRAMES[blockId] ?? resolveBlockWireframe(blockId);

        if (UTILITY_BLOCK_WIREFRAMES[blockId]) {
            block.set('media', UTILITY_BLOCK_WIREFRAMES[blockId]);
        } else {
            const htmlPreview = resolveHtmlPreviewForBlock(block);

            if (htmlPreview) {
                block.set('media', htmlPreview);
            } else if (wireframe) {
                block.set('media', wireframe);
            } else {
                return;
            }
        }

        const label = resolveBlockLabel(blockId, block.get('label'));

        if (label && label !== block.get('label')) {
            block.set('label', label);
        }
    });
}

export function applyLightBlockPreviews(editor) {
    const blockManager = editor.BlockManager;

    if (! blockManager) {
        return;
    }

    unifyBlockCategories(editor);
    applyBasicBlockPreviews(blockManager);
    applySectionBlockPreviews(blockManager);
    applyFormsBlockPreviews(blockManager);
    applyUtilityBlockPreviews(blockManager);

    if (blockManager.getContainer()) {
        blockManager.render();
    }
}
