/**
 * GrapesJS toolbar and style-manager setup for Vpress.
 * Uses only the public GrapesJS API (Panels, Commands, Devices).
 */

import { lucideIcon } from './editor-icons.js';

const CMD_DEVICE_DESKTOP = 'vpress-set-device-desktop';
const CMD_DEVICE_TABLET = 'vpress-set-device-tablet';
const CMD_DEVICE_MOBILE = 'vpress-set-device-mobile';

export const STYLE_MANAGER_SECTORS = [
    {
        name: 'General',
        open: false,
        buildProps: ['display', 'float', 'position', 'top', 'right', 'left', 'bottom'],
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
    },
    {
        name: 'Dimension',
        open: false,
        buildProps: ['width', 'height', 'max-width', 'min-height', 'margin', 'padding'],
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
    },
    {
        name: 'Decorations',
        open: false,
        buildProps: ['background-color', 'border-radius', 'border', 'box-shadow', 'background'],
    },
    {
        name: 'Extra',
        open: false,
        buildProps: ['opacity', 'transition', 'transform'],
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

function syncDeviceButtons(editor) {
    const selectedId = editor.Devices.getSelected()?.get('id');
    const buttons = [
        ['vpress-device-desktop', 'desktop'],
        ['vpress-device-tablet', 'tablet'],
        ['vpress-device-mobile', 'mobilePortrait'],
    ];

    for (const [buttonId, deviceId] of buttons) {
        editor.Panels.getButton('commands', buttonId)?.set('active', selectedId === deviceId);
    }
}

function syncDeviceShellAttribute(editor, shellRoot) {
    if (! shellRoot) {
        return;
    }

    const deviceId = editor.Devices.getSelected()?.get('id') ?? 'desktop';
    shellRoot.dataset.vpressDevice = deviceId;
}

function addToolbarButtons(editor, labels = {}) {
    const { Panels } = editor;

    const deviceButtons = [
        {
            id: 'vpress-device-desktop',
            command: CMD_DEVICE_DESKTOP,
            label: lucideIcon('monitor'),
            title: labels.deviceDesktop ?? 'Desktop',
            active: true,
        },
        {
            id: 'vpress-device-tablet',
            command: CMD_DEVICE_TABLET,
            label: lucideIcon('tablet'),
            title: labels.deviceTablet ?? 'Tablet',
        },
        {
            id: 'vpress-device-mobile',
            command: CMD_DEVICE_MOBILE,
            label: lucideIcon('smartphone'),
            title: labels.deviceMobile ?? 'Mobile',
        },
    ];

    for (const button of deviceButtons) {
        Panels.addButton('commands', {
            id: button.id,
            command: button.command,
            label: button.label,
            className: 'vpress-gjs-pn-btn',
            togglable: true,
            active: button.active ?? false,
            attributes: { title: button.title },
        });
    }

    Panels.addButton('options', {
        id: 'undo',
        label: lucideIcon('undo-2'),
        className: 'vpress-gjs-pn-btn',
        command: 'core:undo',
        attributes: { title: labels.undo ?? 'Undo (Ctrl/Cmd+Z)' },
    });

    Panels.addButton('options', {
        id: 'redo',
        label: lucideIcon('redo-2'),
        className: 'vpress-gjs-pn-btn',
        command: 'core:redo',
        attributes: { title: labels.redo ?? 'Redo (Ctrl/Cmd+Shift+Z)' },
    });

    Panels.addButton('options', {
        id: 'sw-visibility',
        label: lucideIcon('box-select'),
        className: 'vpress-gjs-pn-btn',
        command: 'core:component-outline',
        context: 'sw-visibility',
        attributes: { title: labels.outline ?? 'Show element outlines' },
    });

    Panels.addButton('options', {
        id: 'preview',
        label: lucideIcon('eye'),
        className: 'vpress-gjs-pn-btn',
        command: 'preview',
        context: 'preview',
        attributes: { title: labels.preview ?? 'Preview' },
    });
}

export function configureEditorChrome(editor, options = {}) {
    registerDeviceCommands(editor);
    addToolbarButtons(editor, options.labels ?? {});

    const shellRoot = options.shellRoot ?? null;
    const sync = () => {
        syncDeviceButtons(editor);
        syncDeviceShellAttribute(editor, shellRoot);
        window.requestAnimationFrame(() => editor.refresh());
    };

    editor.on('load', sync);
    editor.on('device:select', sync);
    sync();
}
