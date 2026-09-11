/**
 * Lucide icons (ISC License — https://lucide.dev/license).
 * Inlined for zero extra runtime deps in Voodbuilder Pro distributions.
 */

const BASE = 'xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

const PATHS = {
    monitor: '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
    tablet: '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>',
    smartphone: '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
    'undo-2': '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/>',
    'redo-2': '<path d="m15 14 5-5-5-5"/><path d="M20 9H9.5A5.5 5.5 0 0 0 4 14.5v0A5.5 5.5 0 0 0 9.5 20H13"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
    'align-left': '<path d="M15 12H3"/><path d="M17 18H3"/><path d="M21 6H3"/>',
    'align-center': '<path d="M17 12H7"/><path d="M19 18H5"/><path d="M21 6H3"/>',
    'align-right': '<path d="M21 12H9"/><path d="M21 18H7"/><path d="M21 6H3"/>',
    unlink: '<path d="m18.84 12.25 1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="m5.17 11.75-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><line x1="8" x2="8" y1="2" y2="5"/><line x1="2" x2="5" y1="8" y2="8"/><line x1="16" x2="16" y1="19" y2="22"/><line x1="19" x2="22" y1="16" y2="16"/>',
    eye: '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
    'box-select': '<path d="M5 3a2 2 0 0 0-2 2"/><path d="M19 3a2 2 0 0 1 2 2"/><path d="M21 19a2 2 0 0 1-2 2"/><path d="M3 21a2 2 0 0 1-2-2"/><path d="M9 3h1"/><path d="M9 21h1"/><path d="M14 3h1"/><path d="M14 21h1"/><path d="M3 9v1"/><path d="M21 9v1"/><path d="M3 14v1"/><path d="M21 14v1"/>',
    'square-dashed': '<rect width="18" height="18" x="3" y="3" rx="2" stroke-dasharray="4 3"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    history: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'download': '<path d="M12 15V3"/><path d="m7 10 5 5 5-5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>',
    upload: '<path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>',
    plus: '<path d="M5 12h14"/><path d="M12 5v14"/>',
    save: '<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'more-vertical': '<circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>',
    boxes: '<path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.57l-5-3-4.03 2.49Z"/><path d="m7 16.5-4.74-2.85"/><path d="m7 16.5 5-3"/><path d="M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.57l-5 3Z"/><path d="m17 16.5 4.74-2.85"/><path d="m17 16.5-5-3"/><path d="M17 16.5V21.67"/><path d="M12 8.57 7.03 5.7a2 2 0 0 0-2.06 0l-3 1.8A2 2 0 0 0 1 9.24v3.24a2 2 0 0 0 .97 1.71L7 16.5"/><path d="m12 8.57 5-3"/><path d="M12 8.57V3.4"/><path d="M7 5.7 2.26 2.85"/><path d="m17 5.7 4.74-2.85"/>',
    code: '<path d="m10 13-2 2 2 2"/><path d="m14 17 2-2-2-2"/><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M15 2v4a2 2 0 0 0 2 2h4"/>',
    'external-link': '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    'zoom-in': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/>',
    'zoom-out': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/>',
    'grip-vertical': '<circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>',
    move: '<path d="M5 9l-3 3 3 3"/><path d="M9 5l3-3 3 3"/><path d="M15 19l-3 3-3-3"/><path d="M19 9l3 3-3 3"/><path d="M2 12h20"/><path d="M12 2v20"/>',
    lock: '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    'chevrons-up': '<path d="m17 11-5-5-5 5"/><path d="m17 18-5-5-5 5"/>',
    'arrow-up': '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
    'arrow-down': '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
    'link-2': '<path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 0 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/>',
    'unlink-2': '<path d="M9 17H7A5 5 0 0 1 7 7"/><path d="M15 7h2a5 5 0 0 1 0 10h-2"/><line x1="10" x2="14" y1="10" y2="14"/><line x1="14" x2="10" y1="10" y2="14"/>',
    copy: '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    'copy-plus': '<path d="M15 12h6"/><path d="M18 9v6"/><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    clipboard: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    tags: '<path d="M13.172 2a2 2 0 0 1 1.414.586l6.71 6.71a2.4 2.4 0 0 1 0 3.408l-4.592 4.592a2.4 2.4 0 0 1-3.408 0l-6.71-6.71A2 2 0 0 1 6 9.172V3a1 1 0 0 1 1-1z"/><path d="M2 7v6.172a2 2 0 0 0 .586 1.414l6.71 6.71a2.4 2.4 0 0 0 3.414 0l4.586-4.586"/><circle cx="10.5" cy="6.5" r=".5" fill="currentColor"/>',
    pin: '<path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1z"/><path d="M9 5h6"/>',
    'pin-off': '<path d="M12 17v5"/><path d="m15 9.5-6.5 6.5"/><path d="m8.5 8.5 6.5 6.5"/><path d="M9 10.76V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4.76a2 2 0 0 0 1.11 1.79l1.78.9A2 2 0 0 1 21 15.24V16a1 1 0 0 1-1 1h-3"/><path d="M9 5h6"/>',
    'panel-left': '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
    'panel-right': '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/>',
    pencil: '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L8.28 12.732a1 1 0 0 0-.24.34l-1.321 3.958a1 1 0 0 0 1.263 1.263l3.958-1.321a1 1 0 0 0 .34-.24z"/><path d="m15 5 4 4"/>',
    'trash-2': '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
    palette: '<path d="M12 22a1 1 0 0 1 0-20 10 4 0 0 0 0 20Z"/><path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M19 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M5 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M16 16a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M8 16a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>',
    layers: '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>',
    'layout-grid': '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
    database: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>',
    'message-square': '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
    moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
    'sliders-horizontal': '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
    zap: '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
    crosshair: '<circle cx="12" cy="12" r="10"/><line x1="22" x2="18" y1="12" y2="12"/><line x1="6" x2="2" y1="12" y2="12"/><line x1="12" x2="12" y1="6" y2="2"/><line x1="12" x2="12" y1="22" y2="18"/>',
    calendar: '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
    'rotate-ccw': '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    list: '<path d="M3 12h.01"/><path d="M3 18h.01"/><path d="M3 6h.01"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M8 6h13"/>',
    'list-ordered': '<path d="M10 12h11"/><path d="M10 18h11"/><path d="M10 6h11"/><path d="M4 10h1"/><path d="M4 6h1.5a1.5 1.5 0 0 1 0 3H4"/><path d="M4 18h1.5a1.5 1.5 0 0 0 0-3H5"/>',
    type: '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" x2="15" y1="20" y2="20"/><line x1="12" x2="12" y1="4" y2="20"/>',
};

/** Tabler icons (MIT — https://tabler.io/icons) for library tabs. */
const TABLER_PATHS = {
    wall: '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2l0 -12"/><path d="M4 8h16"/><path d="M20 12h-16"/><path d="M4 16h16"/><path d="M9 4v4"/><path d="M14 8v4"/><path d="M8 12v4"/><path d="M16 12v4"/><path d="M11 16v4"/>',
    components: '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12l3 3l3 -3l-3 -3l-3 3"/><path d="M15 12l3 3l3 -3l-3 -3l-3 3"/><path d="M9 6l3 3l3 -3l-3 -3l-3 3"/><path d="M9 18l3 3l3 -3l-3 -3l-3 3"/>',
    template: '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1l0 -2"/><path d="M4 13a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -6"/><path d="M14 12l6 0"/><path d="M14 16l6 0"/><path d="M14 20l6 0"/>',
    'color-swatch': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 3h-4a2 2 0 0 0 -2 2v12a4 4 0 0 0 8 0v-12a2 2 0 0 0 -2 -2"/><path d="M13 7.35l-2 -2a2 2 0 0 0 -2.828 0l-2.828 2.828a2 2 0 0 0 0 2.828l9 9"/><path d="M7.3 13h-2.3a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h12"/><path d="M17 17l0 .01"/>',
    directions: '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21v-4"/><path d="M12 13v-4"/><path d="M12 5v-2"/><path d="M10 21h4"/><path d="M8 5v4h11l2 -2l-2 -2l-11 0"/><path d="M14 13v4h-8l-2 -2l2 -2l8 0"/>',
    'box-margin': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8h8v8h-8l0 -8"/><path d="M4 4v.01"/><path d="M8 4v.01"/><path d="M12 4v.01"/><path d="M16 4v.01"/><path d="M20 4v.01"/><path d="M4 20v.01"/><path d="M8 20v.01"/><path d="M12 20v.01"/><path d="M16 20v.01"/><path d="M20 20v.01"/><path d="M20 16v.01"/><path d="M20 12v.01"/><path d="M20 8v.01"/><path d="M4 16v.01"/><path d="M4 12v.01"/><path d="M4 8v.01"/>',
    /* Layout library thumbs (https://tabler.io/icons) */
    'layout-distribute-horizontal': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4l16 0"/><path d="M4 20l16 0"/><path d="M6 9m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/>',
    'layout-distribute-vertical': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4l0 16"/><path d="M20 4l0 16"/><path d="M9 6m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>',
    'columns-1': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 3m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z"/>',
    container: '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 4v.01"/><path d="M20 20v.01"/><path d="M20 16v.01"/><path d="M20 12v.01"/><path d="M20 8v.01"/><path d="M8 4m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M4 4v.01"/><path d="M4 20v.01"/><path d="M4 16v.01"/><path d="M4 12v.01"/><path d="M4 8v.01"/>',
    /* https://tabler.io/icons/icon/arrow-autofit-width — spacer path omitted (toolbar CSS forces stroke on all paths). */
    'arrow-autofit-width': '<path d="M4 12v-6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6"/><path d="M10 18h-7"/><path d="M21 18h-7"/><path d="M6 15l-3 3l3 3"/><path d="M18 15l3 3l-3 3"/>',
    /* https://tabler.io/icons/icon/replace — spacer path omitted (toolbar CSS forces stroke on all paths). */
    replace: '<path d="M3 4a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M15 16a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M21 11v-3a2 2 0 0 0 -2 -2h-6l3 3m0 -6l-3 3" /><path d="M3 13v3a2 2 0 0 0 2 2h6l-3 -3m0 6l3 -3" />',
    'file-x': '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" /><path d="M10 12l4 4m0 -4l-4 4" />',
};

export function tablerIcon(name, size = 18) {
    const paths = TABLER_PATHS[name];

    if (! paths) {
        return '';
    }

    return `<svg ${BASE} width="${size}" height="${size}" viewBox="0 0 24 24" aria-hidden="true" class="voodbuilder-editor-icon voodbuilder-editor-icon--tabler">${paths}</svg>`;
}

export function lucideIcon(name, size = 18) {
    const paths = PATHS[name];

    if (! paths) {
        return '';
    }

    return `<svg ${BASE} width="${size}" height="${size}" viewBox="0 0 24 24" aria-hidden="true" class="voodbuilder-editor-icon">${paths}</svg>`;
}
