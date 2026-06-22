/**
 * GrapesJS toolbar and style-manager setup for Vpress.
 * Uses only the public GrapesJS API (Panels, Commands, Devices).
 */

const CMD_DEVICE_DESKTOP = 'vpress-set-device-desktop';
const CMD_DEVICE_TABLET = 'vpress-set-device-tablet';
const CMD_DEVICE_MOBILE = 'vpress-set-device-mobile';

const ICON_STYLE = 'style="display:block;max-width:22px"';

const DEVICE_DESKTOP_ICON = `<svg ${ICON_STYLE} viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7v2H8v2h8v-2h-2v-2h7c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z"/></svg>`;

const DEVICE_TABLET_ICON = `<svg ${ICON_STYLE} viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 0H6a2 2 0 0 0-2 2v20a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM6 2h12v16H6V2z"/></svg>`;

const DEVICE_MOBILE_ICON = `<svg ${ICON_STYLE} viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16 1H8a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm0 18H8V5h8v14z"/></svg>`;

const UNDO_ICON = `<svg ${ICON_STYLE} viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>`;

const REDO_ICON = `<svg ${ICON_STYLE} viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>`;

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
                    width: '',
                },
                {
                    id: 'tablet',
                    name: 'Tablet',
                    width: '768px',
                    widthMedia: '992px',
                },
                {
                    id: 'mobilePortrait',
                    name: 'Mobile',
                    width: '375px',
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

function addToolbarButtons(editor) {
    const { Panels } = editor;

    const deviceButtons = [
        {
            id: 'vpress-device-desktop',
            command: CMD_DEVICE_DESKTOP,
            label: DEVICE_DESKTOP_ICON,
            title: 'Desktop',
            active: true,
        },
        {
            id: 'vpress-device-tablet',
            command: CMD_DEVICE_TABLET,
            label: DEVICE_TABLET_ICON,
            title: 'Tablet',
        },
        {
            id: 'vpress-device-mobile',
            command: CMD_DEVICE_MOBILE,
            label: DEVICE_MOBILE_ICON,
            title: 'Mobile',
        },
    ];

    for (const button of deviceButtons) {
        Panels.addButton('commands', {
            id: button.id,
            command: button.command,
            label: button.label,
            togglable: true,
            active: button.active ?? false,
            attributes: { title: button.title },
        });
    }

    Panels.addButton('options', {
        id: 'undo',
        label: UNDO_ICON,
        command: 'core:undo',
        attributes: { title: 'Undo (Ctrl/Cmd+Z)' },
    });

    Panels.addButton('options', {
        id: 'redo',
        label: REDO_ICON,
        command: 'core:redo',
        attributes: { title: 'Redo (Ctrl/Cmd+Shift+Z)' },
    });
}

export function configureEditorChrome(editor) {
    registerDeviceCommands(editor);
    addToolbarButtons(editor);

    const sync = () => syncDeviceButtons(editor);

    editor.on('load', sync);
    editor.on('device:select', sync);
    sync();
}
