/**
 * Popup editor: preview as a Theme Studio area (Site pages, Docs, …).
 * Live-switches canvas + host shell tokens without reloading (keeps unsaved edits).
 */

const STORAGE_KEY = 'voodbuilder.popup-editor.preview-theme';
const QUERY_KEY = 'preview_theme';
const HOST_PALETTE_STYLE_ID = 'voodbuilder-popup-editor-host-theme-palette';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function readStoredAreaId() {
    try {
        return String(sessionStorage.getItem(STORAGE_KEY) ?? '').trim();
    } catch {
        return '';
    }
}

function storeAreaId(areaId) {
    try {
        sessionStorage.setItem(STORAGE_KEY, areaId);
    } catch {
        // Ignore private mode / quota errors.
    }
}

function syncUrl(areaId) {
    try {
        const url = new URL(window.location.href);

        if (areaId) {
            url.searchParams.set(QUERY_KEY, areaId);
        } else {
            url.searchParams.delete(QUERY_KEY);
        }

        window.history.replaceState({}, '', url);
    } catch {
        // Ignore.
    }
}

function applyHostShellTheme(subTheme, paletteCss) {
    const root = document.documentElement;

    if (subTheme) {
        root.setAttribute('data-voodbuilder-sub-theme', subTheme);
    }

    const css = String(paletteCss ?? '').trim();
    let style = document.getElementById(HOST_PALETTE_STYLE_ID);

    if (! css) {
        style?.remove();

        return;
    }

    if (! style) {
        style = document.createElement('style');
        style.id = HOST_PALETTE_STYLE_ID;
        document.head.appendChild(style);
    }

    if (style.textContent !== css) {
        style.textContent = css;
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{
 *   popupMode?: boolean,
 *   previewThemeArea?: string,
 *   previewThemeOptions?: Array<{id: string, label: string, subTheme: string}>,
 *   previewThemePalettes?: Record<string, string>,
 *   subTheme?: string,
 *   themePaletteCss?: string,
 *   labels?: Record<string, string>,
 * }} options
 * @param {{ toolsMount?: HTMLElement|null }} shell
 */
export function registerPopupPreviewThemeSelect(editor, options = {}, shell = {}) {
    if (! options.popupMode) {
        return;
    }

    const optionsList = Array.isArray(options.previewThemeOptions)
        ? options.previewThemeOptions
        : [];

    if (optionsList.length < 2) {
        return;
    }

    const palettes = options.previewThemePalettes && typeof options.previewThemePalettes === 'object'
        ? options.previewThemePalettes
        : {};

    const toolsMount = shell.toolsMount
        ?? shell.shell?.querySelector?.('.voodbuilder-editor-topbar__tools')
        ?? null;

    if (! toolsMount) {
        return;
    }

    const labels = options.labels ?? {};
    const byId = new Map(optionsList.map((row) => [row.id, row]));

    const initialFromUrl = (() => {
        try {
            return String(new URL(window.location.href).searchParams.get(QUERY_KEY) ?? '').trim();
        } catch {
            return '';
        }
    })();

    const preferred = initialFromUrl
        || readStoredAreaId()
        || String(options.previewThemeArea ?? '').trim()
        || optionsList[0]?.id;

    let currentId = byId.has(preferred)
        ? preferred
        : (byId.has(options.previewThemeArea) ? options.previewThemeArea : optionsList[0].id);

    const wrap = document.createElement('label');
    wrap.className = 'voodbuilder-editor-topbar__preview-theme';
    wrap.title = labels.previewThemeHint
        ?? 'Preview colors as they appear on this site area (runtime still follows the host page).';

    const select = document.createElement('select');
    select.className = 'voodbuilder-editor-topbar__preview-theme-select';
    select.setAttribute('aria-label', labels.previewTheme ?? 'Preview theme');
    select.innerHTML = optionsList.map((row) => `
        <option value="${escapeHtml(row.id)}" ${row.id === currentId ? 'selected' : ''}>
            ${escapeHtml(row.label)}
        </option>
    `).join('');

    const caption = document.createElement('span');
    caption.className = 'voodbuilder-editor-topbar__preview-theme-label';
    caption.textContent = labels.previewTheme ?? 'Preview as';

    wrap.append(caption, select);
    toolsMount.prepend(wrap);

    const applyArea = (areaId, { persist = true } = {}) => {
        const row = byId.get(areaId);

        if (! row) {
            return;
        }

        currentId = areaId;
        select.value = areaId;

        const css = String(palettes[areaId] ?? options.themePaletteCss ?? '').trim();

        editor.__voodbuilderSubTheme = row.subTheme;
        editor.__voodbuilderThemePaletteCss = css;
        editor.__voodbuilderApplyCanvasTheme?.();

        applyHostShellTheme(row.subTheme, css);

        if (persist) {
            storeAreaId(areaId);
            syncUrl(areaId);
        }
    };

    select.addEventListener('change', () => {
        applyArea(select.value);
    });

    // Re-apply stored/URL choice when it differs from SSR default (no reload).
    if (currentId !== String(options.previewThemeArea ?? '').trim()
        || String(options.subTheme ?? '') !== String(byId.get(currentId)?.subTheme ?? '')) {
        applyArea(currentId, { persist: true });
    } else {
        applyHostShellTheme(
            byId.get(currentId)?.subTheme ?? options.subTheme,
            palettes[currentId] ?? options.themePaletteCss,
        );
        storeAreaId(currentId);
    }
}
