/**
 * VoodBuilder tab section presets — replaces the default grapesjs-tabs block.
 */

export const TABS_BLOCK_CATEGORY = 'Tabs';

const TAB_LABELS = ['Overview', 'Details', 'Resources'];

const TAB_PANEL_PLACEHOLDER = `
<div class="vb-tab-panel-body">
    <h3 class="text-xl font-semibold tracking-tight text-vp-text-1 mb-3">Section title</h3>
    <p class="text-base leading-relaxed text-vp-text-2 max-w-3xl">
        Use this area for copy, lists, or nested blocks. Switch tabs above to organize related content on one page.
    </p>
</div>
`.trim();

function tabsWireframeSvg(activeIndex = 0) {
    const rects = [0, 1, 2].map((index) => {
        const x = 8 + index * 14;
        const fill = index === activeIndex ? '#0d94e6' : '#cbd5e1';

        return `<rect x="${x}" y="12" width="11" height="5" rx="2.5" fill="${fill}" />`;
    }).join('');

    return `
        <svg viewBox="0 0 48 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            ${rects}
            <rect x="8" y="22" width="32" height="12" rx="2" fill="#e2e8f0" />
            <path d="M12 27h20M12 30h14" stroke="#94a3b8" stroke-width="1.2" stroke-linecap="round" />
        </svg>
    `.trim();
}

function underlineWireframeSvg() {
    return `
        <svg viewBox="0 0 48 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M10 17h10M22 17h10M34 17h6" stroke="#64748b" stroke-width="1.4" stroke-linecap="round" />
            <path d="M10 17.5h10" stroke="#0d94e6" stroke-width="2" stroke-linecap="round" />
            <path d="M8 19h32" stroke="#cbd5e1" stroke-width="1" />
            <rect x="8" y="23" width="32" height="10" rx="1.5" fill="#f1f5f9" />
            <path d="M12 27h18M12 30h12" stroke="#94a3b8" stroke-width="1.2" stroke-linecap="round" />
        </svg>
    `.trim();
}

function segmentedWireframeSvg() {
    return `
        <svg viewBox="0 0 48 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="8" y="12" width="32" height="8" rx="3" fill="#e2e8f0" />
            <rect x="10" y="14" width="14" height="4" rx="2" fill="#ffffff" stroke="#cbd5e1" />
            <rect x="26" y="14" width="12" height="4" rx="2" fill="#e2e8f0" />
            <rect x="8" y="24" width="32" height="10" rx="2" fill="#f8fafc" stroke="#e2e8f0" />
            <path d="M12 28h18M12 31h12" stroke="#94a3b8" stroke-width="1.2" stroke-linecap="round" />
        </svg>
    `.trim();
}

function buildTabComponents(tabClass) {
    return TAB_LABELS.map((label) => ({
        type: 'tab',
        classes: tabClass,
        components: `<span data-gjs-highlightable="false">${label}</span>`,
    }));
}

function buildTabsSectionContent(variant) {
    return {
        type: 'section',
        classes: ['voodbuilder-editor-section', 'py-12', 'lg:py-16', 'bg-vp-bg'],
        components: [{
            type: 'default',
            tagName: 'div',
            classes: ['container', 'mx-auto', 'px-4', 'max-w-5xl'],
            components: [{
                type: 'tabs',
                classes: [variant.rootClass],
                attributes: {
                    'data-vb-tab-active-class': variant.activeClass,
                },
                classactive: variant.activeClass,
                selectortab: 'aria-controls',
                components: [
                    {
                        type: 'tab-container',
                        classes: variant.barClass,
                        components: buildTabComponents(variant.tabClass),
                    },
                    {
                        type: 'tab-contents',
                        classes: variant.contentsClass,
                    },
                ],
            }],
        }],
    };
}

const VARIANTS = [
    {
        id: 'voodbuilder-tabs-pills',
        label: 'Tabs · Pills',
        media: tabsWireframeSvg(0),
        rootClass: 'vb-tabs-pills',
        barClass: 'vb-tabs-pills__bar',
        tabClass: 'vb-tabs-pills__tab',
        activeClass: 'vb-tabs-pills__tab--active',
        contentsClass: 'vb-tabs-pills__contents',
    },
    {
        id: 'voodbuilder-tabs-underline',
        label: 'Tabs · Underline',
        media: underlineWireframeSvg(),
        rootClass: 'vb-tabs-underline',
        barClass: 'vb-tabs-underline__bar',
        tabClass: 'vb-tabs-underline__tab',
        activeClass: 'vb-tabs-underline__tab--active',
        contentsClass: 'vb-tabs-underline__contents',
    },
    {
        id: 'voodbuilder-tabs-segmented',
        label: 'Tabs · Segmented',
        media: segmentedWireframeSvg(),
        rootClass: 'vb-tabs-segmented',
        barClass: 'vb-tabs-segmented__bar',
        tabClass: 'vb-tabs-segmented__tab',
        activeClass: 'vb-tabs-segmented__tab--active',
        contentsClass: 'vb-tabs-segmented__contents',
    },
];

export function editorTabsPluginOptions() {
    return {
        tabsBlock: false,
        classTab: 'vb-tabs-pills__tab',
        classTabActive: 'vb-tabs-pills__tab--active',
        classTabContainer: 'vb-tabs-pills__bar',
        classTabContents: 'vb-tabs-pills__contents',
        classTabContent: 'vb-tab-panel',
        style: () => '',
        templateTab: () => '<span data-gjs-highlightable="false">Tab</span>',
        templateTabContent: () => TAB_PANEL_PLACEHOLDER,
        tabsProps: {
            classes: ['vb-tabs-pills'],
        },
    };
}

export function registerVoodbuilderTabsBlocks(editor) {
    const blockManager = editor.BlockManager;

    blockManager.remove('tabs');

    for (const variant of VARIANTS) {
        if (blockManager.get(variant.id)) {
            blockManager.remove(variant.id);
        }

        blockManager.add(variant.id, {
            label: variant.label,
            category: TABS_BLOCK_CATEGORY,
            content: buildTabsSectionContent(variant),
            media: variant.media,
            attributes: {
                title: variant.label,
            },
        });
    }
}
