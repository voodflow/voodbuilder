/**
 * Link traits for CTA buttons in section blocks (URL + same/new tab).
 */

function extractButtonLabel(component) {
    const element = component.getView?.()?.el;

    if (element?.textContent?.trim()) {
        return element.textContent.trim();
    }

    if (component.get('text')) {
        return String(component.get('text'));
    }

    if (component.get('content')) {
        return String(component.get('content'));
    }

    const children = component.components?.();

    if (children?.length === 1) {
        const child = children.at(0);

        if (child?.get('type') === 'textnode') {
            return String(child.get('content') ?? '').trim();
        }
    }

    return 'Button';
}

function buttonLinkTraitSchema(labels = {}) {
    return [
        {
            type: 'text',
            name: 'href',
            label: labels.buttonLinkUrl ?? 'Link URL',
            placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
            changeProp: true,
        },
        {
            type: 'select',
            name: 'target',
            label: labels.buttonLinkTarget ?? 'Open in',
            options: [
                { id: '', name: labels.buttonLinkSameTab ?? 'Same tab' },
                { id: '_blank', name: labels.buttonLinkNewTab ?? 'New tab' },
            ],
            changeProp: true,
        },
    ];
}

function linkTraitsFor(editor) {
    return buttonLinkTraitSchema(editor.__voodbuilderLabels ?? {});
}

function syncLinkableButtonTraits(component, editor) {
    component.set('traits', linkTraitsFor(editor));

    if (editor?.getSelected?.() === component && editor.TraitManager) {
        editor.TraitManager.select(component);
    }
}

function readLinkProps(component) {
    const attrs = component.getAttributes?.() ?? {};

    return {
        href: String(component.get('href') ?? attrs.href ?? '').trim(),
        target: String(component.get('target') ?? attrs.target ?? '').trim(),
    };
}

function hydrateLinkPropsFromAttributes(component) {
    const { href, target } = readLinkProps(component);
    const updates = {};

    if (href !== '' && component.get('href') !== href) {
        updates.href = href;
    }

    if (target !== component.get('target')) {
        updates.target = target;
    }

    if (Object.keys(updates).length > 0) {
        component.set(updates, { silent: true });
    }
}

function isExcludedLinkableButton(component) {
    const type = component.get('type');

    if (type === 'voodbuilder-nav-menu-button' || type === 'voodbuilder-chrome-button') {
        return true;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();

    if (tag !== 'button') {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};
    const classes = component.getClasses?.() ?? [];

    if (classes.includes('voodbuilder-header-icon-btn')) {
        return true;
    }

    if (attrs['data-voodbuilder-nav-dropdown-toggle'] || attrs['data-voodbuilder-nav-mobile-toggle']) {
        return true;
    }

    if (attrs['data-carousel-prev'] || attrs['data-carousel-next']) {
        return true;
    }

    if (attrs['data-voodbuilder-bind']) {
        return true;
    }

    const form = component.closest?.('form');

    if (! form) {
        return false;
    }

    if (attrs.type === 'submit') {
        return true;
    }

    const formAttrs = form.getAttributes?.() ?? {};
    const formClasses = form.getClasses?.() ?? [];

    if (formAttrs['data-voodbuilder-form'] || formClasses.includes('vb-gjs-form')) {
        return true;
    }

    return false;
}

function ensureTextLabel(component, label) {
    if (label === '') {
        return;
    }

    const children = component.components?.();

    if (! children || children.length === 0) {
        component.components(label);

        return;
    }

    if (children.length === 1) {
        const child = children.at(0);

        if (child?.get('type') === 'textnode') {
            child.set('content', label);

            return;
        }
    }
}

function applyCtaButtonLink(component) {
    const { href, target } = readLinkProps(component);
    const hasLink = href !== '' && href !== '#';
    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const classes = [...(component.getClasses?.() ?? [])];

    if (hasLink && tag === 'button') {
        const label = extractButtonLabel(component);

        component.set({
            tagName: 'a',
            type: 'voodbuilder-cta-button',
            editable: true,
            highlightable: true,
            selectable: true,
            href,
            target,
        });

        if (classes.length > 0) {
            component.setClass(classes);
        }

        component.setAttributes({
            href,
            target: target || null,
            rel: target === '_blank' ? 'noopener noreferrer' : null,
            role: 'button',
            'data-voodbuilder-cta': 'true',
        });
        component.removeAttributes(['type', 'onclick']);

        ensureTextLabel(component, label);

        return;
    }

    if (! hasLink && tag === 'a' && component.getAttributes()?.role === 'button') {
        const label = extractButtonLabel(component);

        component.set({
            tagName: 'button',
            type: 'voodbuilder-cta-button',
            editable: false,
            href: '',
            target: '',
        });

        if (classes.length > 0) {
            component.setClass(classes);
        }

        component.setAttributes({
            type: 'button',
            'data-voodbuilder-cta': 'true',
        });
        component.removeAttributes(['href', 'target', 'rel', 'role']);

        ensureTextLabel(component, label);

        return;
    }

    if (tag === 'a') {
        component.set({ href, target });
        component.addAttributes({
            href: hasLink ? href : '#',
            target: target || null,
            rel: target === '_blank' ? 'noopener noreferrer' : null,
            role: 'button',
            'data-voodbuilder-cta': 'true',
        });
    }
}

function assignLinkableButtonType(component) {
    if (component.get('type') === 'voodbuilder-cta-button') {
        return;
    }

    component.set('type', 'voodbuilder-cta-button');
}

function isLinkableCtaComponent(component) {
    const tag = String(component.get('tagName') ?? '').toLowerCase();

    if (tag === 'button') {
        return true;
    }

    return tag === 'a' && component.getAttributes?.()?.['data-voodbuilder-cta'] === 'true';
}

function upgradeLinkableButton(component, editor) {
    if (! component || ! editor) {
        return;
    }

    if (isExcludedLinkableButton(component)) {
        return;
    }

    if (! isLinkableCtaComponent(component)) {
        return;
    }

    assignLinkableButtonType(component);
    hydrateLinkPropsFromAttributes(component);
    component.addAttributes({ 'data-voodbuilder-cta': 'true' });
    syncLinkableButtonTraits(component, editor);

    if (component.get('type') === 'voodbuilder-cta-button' && component.__vbLinkMorphApplied) {
        return;
    }

    applyCtaButtonLink(component);
    component.__vbLinkMorphApplied = true;
}

function registerLinkableButtonType(editor) {
    if (editor.__voodbuilderLinkableButtonTypeRegistered) {
        return;
    }

    editor.__voodbuilderLinkableButtonTypeRegistered = true;

    const labels = editor.__voodbuilderLabels ?? {};

    editor.DomComponents.addType('voodbuilder-cta-button', {
        isComponent: (element) => {
            if (element?.getAttribute?.('data-voodbuilder-cta') === 'true') {
                return { type: 'voodbuilder-cta-button' };
            }

            if (element?.tagName === 'A' && element.getAttribute('role') === 'button') {
                return { type: 'voodbuilder-cta-button' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'button',
                attributes: {
                    type: 'button',
                    'data-voodbuilder-cta': 'true',
                },
                traits: buttonLinkTraitSchema(labels),
                href: '',
                target: '',
                name: 'Button',
            },
            init() {
                hydrateLinkPropsFromAttributes(this);

                this.on('change:href change:target', () => {
                    this.__vbLinkMorphApplied = true;
                    applyCtaButtonLink(this);
                });
            },
        },
    });
}

function registerCtaLinkPreview(editor) {
    if (editor.__voodbuilderCtaLinkPreviewRegistered) {
        return;
    }

    editor.__voodbuilderCtaLinkPreviewRegistered = true;

    editor.on('load', () => {
        const frame = editor.Canvas?.getFrameEl?.();

        if (! frame?.contentDocument) {
            return;
        }

        frame.contentDocument.addEventListener('click', (event) => {
            const anchor = event.target?.closest?.('a[data-voodbuilder-cta][href]');

            if (! anchor) {
                return;
            }

            if (! (event.metaKey || event.ctrlKey || event.altKey)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const target = anchor.getAttribute('target') || '_self';

            if (target === '_blank') {
                window.open(anchor.href, '_blank', 'noopener,noreferrer');
            } else {
                window.open(anchor.href, '_self');
            }
        }, true);
    });
}

export function registerLinkableButtonTypes(editor) {
    registerLinkableButtonType(editor);
}

export function scanLinkableButtons(editor, root = editor.getWrapper?.()) {
    if (! root) {
        return;
    }

    const visit = (component) => {
        if (isLinkableCtaComponent(component)) {
            upgradeLinkableButton(component, editor);
        }

        component.components?.().forEach((child) => visit(child));
    };

    visit(root);
}

export function configureLinkableButtons(editor) {
    registerLinkableButtonType(editor);
    registerCtaLinkPreview(editor);

    if (editor.__voodbuilderLinkableButtonsConfigured) {
        return;
    }

    editor.__voodbuilderLinkableButtonsConfigured = true;

    editor.on('load', () => {
        scanLinkableButtons(editor);
    });

    const pendingLinkableRoots = new Set();
    let linkableScanFrame = null;

    const flushLinkableScan = () => {
        linkableScanFrame = null;

        const visit = (node) => {
            if (isLinkableCtaComponent(node)) {
                upgradeLinkableButton(node, editor);
            }

            node.components?.().forEach((child) => visit(child));
        };

        for (const root of pendingLinkableRoots) {
            visit(root);
        }

        pendingLinkableRoots.clear();
    };

    editor.on('component:add', (component) => {
        if (! component) {
            return;
        }

        pendingLinkableRoots.add(component);

        if (linkableScanFrame == null) {
            linkableScanFrame = window.requestAnimationFrame(flushLinkableScan);
        }
    });

    editor.on('component:selected', (component) => {
        if (! isLinkableCtaComponent(component)) {
            return;
        }

        assignLinkableButtonType(component);
        hydrateLinkPropsFromAttributes(component);
        syncLinkableButtonTraits(component, editor);
    });

    scanLinkableButtons(editor);
}
