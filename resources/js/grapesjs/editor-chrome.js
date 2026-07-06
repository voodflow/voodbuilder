/**
 * GrapesJS toolbar and style-manager setup for Voodbuilder.
 * Uses only the public GrapesJS API (Commands, Devices, Canvas).
 */

import { lucideIcon } from './editor-icons.js';
import { registerEditorPanelToggles } from './editor-panel-toggles.js';

const CMD_DEVICE_DESKTOP = 'voodbuilder-set-device-desktop';
const CMD_DEVICE_TABLET = 'voodbuilder-set-device-tablet';
const CMD_DEVICE_MOBILE = 'voodbuilder-set-device-mobile';
const CMD_PREVIEW = 'voodbuilder-preview';
const CMD_COMPONENT_OUTLINE = 'core:component-outline';
const OUTLINE_STORAGE_KEY = 'voodbuilder:component-outline-visible';

const TOGGLE_COMMANDS = [CMD_COMPONENT_OUTLINE, CMD_PREVIEW];

function stylePropsImportant(propertyNames) {
    return propertyNames.map((property) => ({ property, important: true }));
}

export const STYLE_MANAGER_SECTORS = [
    {
        name: 'General',
        open: false,
        buildProps: ['display', 'float', 'position', 'top', 'right', 'left', 'bottom'],
        extendBuilded: true,
        properties: stylePropsImportant(['display', 'float', 'position', 'top', 'right', 'left', 'bottom']),
    },
    {
        name: 'Flex',
        open: false,
        buildProps: [
            'flex-direction',
            'flex-wrap',
            'justify-content',
            'align-items',
            'align-content',
            'order',
            'flex-basis',
            'flex-grow',
            'flex-shrink',
            'align-self',
        ],
        extendBuilded: true,
        properties: stylePropsImportant([
            'flex-direction',
            'flex-wrap',
            'justify-content',
            'align-items',
            'align-content',
            'order',
            'flex-basis',
            'flex-grow',
            'flex-shrink',
            'align-self',
        ]),
    },
    {
        name: 'Dimension',
        open: false,
        buildProps: [
            'width',
            'height',
            'max-width',
            'min-height',
            'margin-top',
            'margin-right',
            'margin-bottom',
            'margin-left',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
        ],
        extendBuilded: true,
        properties: stylePropsImportant([
            'width',
            'height',
            'max-width',
            'min-height',
            'margin-top',
            'margin-right',
            'margin-bottom',
            'margin-left',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
        ]),
    },
    {
        name: 'Typography',
        open: true,
        buildProps: [
            'font-family',
            'font-size',
            'font-weight',
            'letter-spacing',
            'color',
            'line-height',
            'text-align',
            'text-shadow',
        ],
        extendBuilded: true,
        properties: [
            { property: 'font-family', important: true },
            { property: 'font-size', important: true },
            { property: 'font-weight', important: true },
            { property: 'letter-spacing', important: true },
            { property: 'color', important: true },
            { property: 'line-height', important: true },
            { property: 'text-align', important: true },
            { property: 'text-shadow', important: true },
        ],
    },
    {
        name: 'Decorations',
        open: false,
        buildProps: ['background-color', 'border-radius', 'border', 'box-shadow', 'background', 'fill', 'stroke'],
        extendBuilded: true,
        properties: [
            { property: 'background-color', important: true },
            { property: 'background', important: true },
            { property: 'fill', important: true },
            { property: 'stroke', important: true },
            { property: 'border-radius', important: true },
            { property: 'border-top-left-radius', important: true },
            { property: 'border-top-right-radius', important: true },
            { property: 'border-bottom-left-radius', important: true },
            { property: 'border-bottom-right-radius', important: true },
            { property: 'border', important: true },
            { property: 'border-width', important: true },
            { property: 'border-style', important: true },
            { property: 'border-color', important: true },
            { property: 'box-shadow', important: true },
        ],
    },
    {
        name: 'Extra',
        open: false,
        buildProps: ['opacity', 'transition', 'transform'],
        extendBuilded: true,
        properties: stylePropsImportant(['opacity', 'transition', 'transform']),
    },
];

export function editorChromeInitOptions() {
    return {
        showDevices: false,
        deviceManager: {
            default: 'desktop',
            devices: [
                {
                    id: 'desktop',
                    name: 'Desktop',
                    width: '100%',
                },
                {
                    id: 'tablet',
                    name: 'Tablet',
                    width: '834px',
                    widthMedia: '1024px',
                },
                {
                    id: 'mobilePortrait',
                    name: 'Mobile',
                    width: '390px',
                    widthMedia: '480px',
                },
            ],
        },
        styleManager: {
            sectors: STYLE_MANAGER_SECTORS,
        },
    };
}

function readOutlinePreference() {
    try {
        return localStorage.getItem(OUTLINE_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function saveOutlinePreference(active) {
    try {
        localStorage.setItem(OUTLINE_STORAGE_KEY, active ? '1' : '0');
    } catch {
        // Ignore storage errors (private mode, quota, etc.).
    }
}

function syncOutlineVisibility(editor, shellRoot) {
    const active = editor.Commands.isActive(CMD_COMPONENT_OUTLINE);

    shellRoot?.classList.toggle('is-component-outline-visible', active);

    editor.Canvas.getFrames().forEach((frame) => {
        frame.view?.getBody()?.classList.toggle('voodbuilder-component-outline-visible', active);
    });
}

function applyComponentOutlinePreference(editor, shellRoot, preferred = readOutlinePreference()) {
    if (preferred) {
        editor.runCommand(CMD_COMPONENT_OUTLINE);
    } else {
        editor.stopCommand(CMD_COMPONENT_OUTLINE);
    }

    syncOutlineVisibility(editor, shellRoot);
}

function setupComponentOutlineToggle(editor, button, shellRoot) {
    const syncButton = () => {
        button.classList.toggle('is-active', editor.Commands.isActive(CMD_COMPONENT_OUTLINE));
        syncOutlineVisibility(editor, shellRoot);
    };

    button.addEventListener('click', () => {
        const next = ! editor.Commands.isActive(CMD_COMPONENT_OUTLINE);

        saveOutlinePreference(next);

        if (next) {
            editor.runCommand(CMD_COMPONENT_OUTLINE);
        } else {
            editor.stopCommand(CMD_COMPONENT_OUTLINE);
        }

        syncButton();
    });

    editor.on(`run:${CMD_COMPONENT_OUTLINE}`, syncButton);
    editor.on(`stop:${CMD_COMPONENT_OUTLINE}`, syncButton);

    editor.on('load', () => {
        applyComponentOutlinePreference(editor, shellRoot);
        syncButton();
    });

    editor.on('canvas:frame:load', () => {
        applyComponentOutlinePreference(editor, shellRoot);
        syncButton();
    });

    editor.stopCommand(CMD_COMPONENT_OUTLINE);
    syncButton();
}

function registerPreviewCommand(editor, shellRoot) {
    const { Commands, Canvas } = editor;
    let outlineWasActive = false;

    Commands.add(CMD_PREVIEW, {
        run(ed) {
            if (! shellRoot) {
                return;
            }

            outlineWasActive = Commands.isActive(CMD_COMPONENT_OUTLINE);

            if (outlineWasActive) {
                ed.stopCommand(CMD_COMPONENT_OUTLINE);
            }

            shellRoot.classList.add('is-preview-mode');
            ed.select();
            ed.getModel().stopDefault();

            const toolbar = Canvas.getToolbarEl();

            if (toolbar) {
                toolbar.style.display = 'none';
            }

            ed.refresh();
        },
        stop(ed) {
            shellRoot?.classList.remove('is-preview-mode');
            ed.getModel().runDefault();

            const toolbar = Canvas.getToolbarEl();

            if (toolbar) {
                toolbar.style.display = '';
            }

            if (outlineWasActive) {
                ed.runCommand(CMD_COMPONENT_OUTLINE);
                outlineWasActive = false;
            }

            syncOutlineVisibility(ed, shellRoot);

            ed.refresh();
        },
    });
}

function registerDeviceCommands(editor) {
    const { Commands } = editor;

    Commands.add(CMD_DEVICE_DESKTOP, {
        run(ed) {
            ed.setDevice('desktop');
        },
    });

    Commands.add(CMD_DEVICE_TABLET, {
        run(ed) {
            ed.setDevice('tablet');
        },
    });

    Commands.add(CMD_DEVICE_MOBILE, {
        run(ed) {
            ed.setDevice('mobilePortrait');
        },
    });
}

function createToolButton({ id, icon, title, active = false }) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-topbar-tool-btn';
    button.dataset.voodbuilderTopbarTool = id;

    if (active) {
        button.classList.add('is-active');
    }

    button.title = title;
    button.innerHTML = lucideIcon(icon, 18);

    return button;
}

function createToolGroup(children) {
    const group = document.createElement('div');
    group.className = 'voodbuilder-gjs-topbar-tool-group';
    group.append(...children);

    return group;
}

function createToolDivider() {
    const divider = document.createElement('div');
    divider.className = 'voodbuilder-gjs-topbar-tool-divider';
    divider.setAttribute('aria-hidden', 'true');

    return divider;
}

function wireToggleCommand(editor, button, commandId) {
    const sync = () => {
        button.classList.toggle('is-active', editor.Commands.isActive(commandId));
    };

    button.addEventListener('click', () => {
        if (editor.Commands.isActive(commandId)) {
            editor.stopCommand(commandId);
        } else {
            editor.runCommand(commandId);
        }
    });

    editor.on(`run:${commandId}`, sync);
    editor.on(`stop:${commandId}`, sync);
    sync();
}

function updateDeviceReadout(editor, readout) {
    if (! readout) {
        return;
    }

    const device = editor.Devices.getSelected();
    const zoom = Math.round(editor.Canvas.getZoom() ?? 100);
    const deviceId = device?.get('id') ?? 'desktop';

    if (deviceId === 'desktop') {
        readout.textContent = `${zoom}%`;
        readout.title = `Canvas zoom ${zoom}%`;

        return;
    }

    const width = String(device?.get('width') ?? '').replace(/px$/, 'px');
    readout.textContent = width ? `${width} · ${zoom}%` : `${zoom}%`;
    readout.title = `${device?.get('name') ?? deviceId} · ${zoom}%`;
}

function mountEditorTopbar(editor, mount, labels = {}, shellRoot = null) {
    if (! mount) {
        return null;
    }

    mount.replaceChildren();

    const root = document.createElement('div');
    root.className = 'voodbuilder-gjs-topbar-tools-inner';
    root.dataset.voodbuilderTopbar = '';

    const deviceButtons = [
        {
            id: 'desktop',
            command: CMD_DEVICE_DESKTOP,
            icon: 'monitor',
            title: labels.deviceDesktop ?? 'Desktop',
            active: true,
        },
        {
            id: 'tablet',
            command: CMD_DEVICE_TABLET,
            icon: 'tablet',
            title: labels.deviceTablet ?? 'Tablet',
        },
        {
            id: 'mobile',
            command: CMD_DEVICE_MOBILE,
            icon: 'smartphone',
            title: labels.deviceMobile ?? 'Mobile',
        },
    ];

    const deviceGroup = createToolGroup(
        deviceButtons.map((spec) => {
            const button = createToolButton(spec);
            button.addEventListener('click', () => editor.runCommand(spec.command));

            return button;
        }),
    );

    const undoButton = createToolButton({
        id: 'undo',
        icon: 'undo-2',
        title: labels.undo ?? 'Undo (Ctrl/Cmd+Z)',
    });
    undoButton.addEventListener('click', () => editor.runCommand('core:undo'));

    const redoButton = createToolButton({
        id: 'redo',
        icon: 'redo-2',
        title: labels.redo ?? 'Redo (Ctrl/Cmd+Shift+Z)',
    });
    redoButton.addEventListener('click', () => editor.runCommand('core:redo'));

    const historyGroup = createToolGroup([undoButton, redoButton]);

    const outlineButton = createToolButton({
        id: 'outline',
        icon: 'box-select',
        title: labels.outline ?? 'Show element outlines',
    });
    setupComponentOutlineToggle(editor, outlineButton, shellRoot);

    const previewButton = createToolButton({
        id: 'preview',
        icon: 'eye',
        title: labels.preview ?? 'Preview mode',
    });
    wireToggleCommand(editor, previewButton, CMD_PREVIEW);

    const canvasGroup = createToolGroup([outlineButton, previewButton]);

    const zoomOutButton = createToolButton({
        id: 'zoom-out',
        icon: 'zoom-out',
        title: labels.zoomOut ?? 'Zoom out',
    });
    zoomOutButton.addEventListener('click', () => {
        const canvas = editor.Canvas;
        const next = Math.max(25, Math.round((canvas.getZoom() ?? 100) - 10));
        canvas.setZoom(next);
    });

    const readout = document.createElement('span');
    readout.className = 'voodbuilder-gjs-topbar-device-readout';
    readout.dataset.voodbuilderDeviceReadout = '';

    const zoomInButton = createToolButton({
        id: 'zoom-in',
        icon: 'zoom-in',
        title: labels.zoomIn ?? 'Zoom in',
    });
    zoomInButton.addEventListener('click', () => {
        const canvas = editor.Canvas;
        const next = Math.min(200, Math.round((canvas.getZoom() ?? 100) + 10));
        canvas.setZoom(next);
    });

    const zoomGroup = createToolGroup([zoomOutButton, readout, zoomInButton]);

    root.append(
        deviceGroup,
        createToolDivider(),
        historyGroup,
        createToolDivider(),
        canvasGroup,
        createToolDivider(),
        zoomGroup,
    );

    mount.append(root);

    const syncDevices = () => {
        const selectedId = editor.Devices.getSelected()?.get('id') ?? 'desktop';
        const map = {
            desktop: 'desktop',
            tablet: 'tablet',
            mobilePortrait: 'mobile',
        };

        for (const button of deviceGroup.querySelectorAll('[data-voodbuilder-topbar-tool]')) {
            const toolId = button.dataset.voodbuilderTopbarTool;
            button.classList.toggle('is-active', map[selectedId] === toolId);
        }

        updateDeviceReadout(editor, readout);
    };

    editor.on('device:select', syncDevices);
    editor.on('canvas:zoom', syncDevices);
    editor.on('load', syncDevices);
    syncDevices();

    return { readout, syncDevices, root };
}

function mountViewPageAction(actionsMount, viewPageUrl, labels = {}) {
    if (! actionsMount || ! viewPageUrl) {
        return;
    }

    if (actionsMount.querySelector('[data-voodbuilder-view-page]')) {
        return;
    }

    const link = document.createElement('a');
    link.href = viewPageUrl;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost voodbuilder-gjs-topbar__btn--icon';
    link.dataset.voodbuilderViewPage = '';
    link.title = labels.viewPage ?? 'View page';
    link.setAttribute('aria-label', labels.viewPage ?? 'View page');
    link.innerHTML = lucideIcon('external-link', 18);

    const exitLink = actionsMount.querySelector('.voodbuilder-gjs-topbar__btn--ghost[href]');

    if (exitLink) {
        actionsMount.insertBefore(link, exitLink);
    } else {
        actionsMount.prepend(link);
    }
}

function syncDeviceShellAttribute(editor, shellRoot) {
    if (! shellRoot) {
        return;
    }

    const deviceId = editor.Devices.getSelected()?.get('id') ?? 'desktop';
    shellRoot.dataset.voodbuilderDevice = deviceId;
}

export function configureEditorChrome(editor, options = {}) {
    const shellRoot = options.shellRoot ?? null;

    registerDeviceCommands(editor);
    registerPreviewCommand(editor, shellRoot);
    const topbar = mountEditorTopbar(editor, options.toolsMount ?? null, options.labels ?? {}, shellRoot);

    mountViewPageAction(
        options.actionsMount ?? null,
        options.viewPageUrl ?? null,
        options.labels ?? {},
    );

    if (options.shell) {
        registerEditorPanelToggles(options.shell, options.labels ?? {}, topbar?.root ?? null);
    }

    const sync = () => {
        topbar?.syncDevices?.();
        syncDeviceShellAttribute(editor, shellRoot);
        window.requestAnimationFrame(() => editor.refresh());
    };

    editor.on('load', sync);
    editor.on('device:select', sync);

    for (const commandId of TOGGLE_COMMANDS) {
        editor.on(`run:${commandId}`, sync);
        editor.on(`stop:${commandId}`, sync);
    }

    sync();
}
