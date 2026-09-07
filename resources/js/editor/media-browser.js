/**
 * Modern media browser for the editor (replaces GrapesJS Asset Manager Choose UI).
 * Paginated API · gallery nav · photo/video filter · lazy thumbs · compact upload.
 */

const VIDEO_EXT_RE = /\.(mp4|webm|ogg|ogv|mov|m4v)(?:\?|#|$)/i;

function isVideoAssetSrc(src) {
    return VIDEO_EXT_RE.test(String(src ?? '').trim());
}

let activeBrowser = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function csrfToken(editor) {
    return editor?.__voodbuilderCsrf
        ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? '';
}

function buildUrl(base, params = {}) {
    const raw = String(base ?? '').trim();

    if (raw === '') {
        return '';
    }

    try {
        const parsed = new URL(raw, window.location.origin);

        for (const [key, value] of Object.entries(params)) {
            if (value == null || value === '') {
                parsed.searchParams.delete(key);
            } else {
                parsed.searchParams.set(key, String(value));
            }
        }

        return parsed.pathname + parsed.search;
    } catch {
        return raw;
    }
}

/**
 * @param {{
 *   editor: object,
 *   kinds?: Array<'image'|'video'>,
 *   labelKind?: 'image'|'video',
 *   labels?: Record<string, string>,
 *   onSelect: (src: string, meta?: { caption?: string|null, name?: string, id?: number }) => void,
 *   onClose?: () => void,
 * }} args
 */
export async function openMediaBrowser(args) {
    if (activeBrowser) {
        activeBrowser.close(false);
    }

    const {
        editor,
        kinds = ['image'],
        labelKind = null,
        labels = {},
        onSelect,
        onClose,
    } = args;

    const libraryUrl = editor?.__voodbuilderMediaLibraryUrl ?? labels.mediaLibraryUrl ?? '';
    const galleriesUrl = editor?.__voodbuilderMediaGalleriesUrl ?? labels.mediaGalleriesUrl ?? '';
    const uploadUrl = editor?.__voodbuilderUploadUrl
        ?? labels.uploadUrl
        ?? editor?.getConfig?.()?.assetManager?.upload
        ?? '';

    // Requires the Media companion galleries API — otherwise Core uses GrapesJS AM.
    if (! libraryUrl || ! galleriesUrl) {
        return false;
    }

    const wantsVideo = kinds.includes('video');
    const wantsImage = kinds.includes('image') || ! wantsVideo;
    const lockedType = wantsVideo && ! wantsImage
        ? 'video'
        : (wantsImage && ! wantsVideo ? 'image' : null);

    const title = labelKind === 'video' || (wantsVideo && ! wantsImage)
        ? (labels.assetManagerVideoTitle ?? labels.videoSettingsTitle ?? 'Select video')
        : (labels.assetManagerImageTitle ?? 'Select image');

    const state = {
        type: lockedType,
        galleryId: null,
        search: '',
        page: 1,
        items: [],
        hasMore: false,
        total: 0,
        loading: false,
        uploading: false,
        galleries: [],
        uploadGalleryId: null,
        uploadGallery: null,
        observer: null,
        searchTimer: null,
    };

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-editor-modal voodbuilder-media-browser';
    modal.setAttribute('role', 'presentation');

    modal.innerHTML = `
        <div class="voodbuilder-editor-modal__backdrop" data-mb-close></div>
        <div class="voodbuilder-editor-modal__panel voodbuilder-media-browser__panel" role="dialog" aria-modal="true" aria-labelledby="voodbuilder-media-browser-title">
            <header class="voodbuilder-editor-modal__head voodbuilder-media-browser__head">
                <div class="voodbuilder-media-browser__head-text">
                    <h2 class="voodbuilder-editor-modal__title" id="voodbuilder-media-browser-title">${escapeHtml(title)}</h2>
                    <p class="voodbuilder-media-browser__subtitle" data-mb-subtitle></p>
                </div>
                <button type="button" class="voodbuilder-editor-modal__close" data-mb-close aria-label="Close">×</button>
            </header>
            <div class="voodbuilder-media-browser__layout">
                <aside class="voodbuilder-media-browser__sidebar" data-mb-sidebar aria-label="Galleries"></aside>
                <div class="voodbuilder-media-browser__main">
                    <div class="voodbuilder-media-browser__toolbar">
                        <div class="voodbuilder-media-browser__type-toggle" role="group" aria-label="Media type" data-mb-types>
                            <button type="button" class="voodbuilder-media-browser__seg" data-mb-type="" aria-pressed="true">All</button>
                            <button type="button" class="voodbuilder-media-browser__seg" data-mb-type="image" aria-pressed="false">Photos</button>
                            <button type="button" class="voodbuilder-media-browser__seg" data-mb-type="video" aria-pressed="false">Videos</button>
                        </div>
                        <label class="voodbuilder-media-browser__search">
                            <span class="voodbuilder-media-browser__sr">Search</span>
                            <input type="search" data-mb-search placeholder="Search by name…" autocomplete="off" />
                        </label>
                        <select class="voodbuilder-media-browser__gallery-select" data-mb-gallery-select aria-label="Gallery"></select>
                    </div>
                    <div class="voodbuilder-media-browser__dropzone" data-mb-dropzone>
                        <input type="file" data-mb-file multiple hidden aria-label="Upload files" />
                        <div class="voodbuilder-media-browser__dropzone-inner">
                            <strong data-mb-drop-title>Upload</strong>
                            <span data-mb-drop-hint>Select a gallery to set the upload destination</span>
                        </div>
                        <div class="voodbuilder-media-browser__upload-progress" data-mb-progress hidden></div>
                    </div>
                    <div class="voodbuilder-media-browser__grid-wrap">
                        <div class="voodbuilder-media-browser__grid" data-mb-grid role="listbox" aria-label="Media"></div>
                        <div class="voodbuilder-media-browser__status" data-mb-status></div>
                        <div class="voodbuilder-media-browser__sentinel" data-mb-sentinel aria-hidden="true"></div>
                    </div>
                    <div class="voodbuilder-media-browser__url-row">
                        <input type="url" data-mb-url class="voodbuilder-editor-input" aria-label="External media URL" placeholder="Or paste an external URL…" />
                        <button type="button" class="voodbuilder-editor-btn" data-mb-url-add>Add URL</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const root = document.querySelector('.voodbuilder-editor-root') ?? document.body;
    root.appendChild(modal);

    const els = {
        sidebar: modal.querySelector('[data-mb-sidebar]'),
        gallerySelect: modal.querySelector('[data-mb-gallery-select]'),
        types: modal.querySelector('[data-mb-types]'),
        search: modal.querySelector('[data-mb-search]'),
        grid: modal.querySelector('[data-mb-grid]'),
        status: modal.querySelector('[data-mb-status]'),
        sentinel: modal.querySelector('[data-mb-sentinel]'),
        subtitle: modal.querySelector('[data-mb-subtitle]'),
        dropzone: modal.querySelector('[data-mb-dropzone]'),
        file: modal.querySelector('[data-mb-file]'),
        progress: modal.querySelector('[data-mb-progress]'),
        dropHint: modal.querySelector('[data-mb-drop-hint]'),
        url: modal.querySelector('[data-mb-url]'),
        urlAdd: modal.querySelector('[data-mb-url-add]'),
    };

    if (lockedType) {
        els.types?.querySelectorAll('[data-mb-type]').forEach((btn) => {
            const t = btn.getAttribute('data-mb-type');
            const on = t === lockedType || (lockedType === 'image' && t === 'image') || (lockedType === 'video' && t === 'video');
            btn.hidden = t !== '' && t !== lockedType;
            btn.setAttribute('aria-pressed', t === lockedType || (t === '' && false) ? 'true' : 'false');
            if (t === lockedType) {
                btn.setAttribute('aria-pressed', 'true');
            }
            if (t === '') {
                btn.hidden = true;
            }
        });
        state.type = lockedType;
    }

    let moreObserver = null;

    const close = (selected) => {
        if (! modal.isConnected) {
            return;
        }

        state.observer?.disconnect();
        moreObserver?.disconnect();
        window.clearTimeout(state.searchTimer);
        window.removeEventListener('keydown', onKeyDown, true);
        modal.remove();
        activeBrowser = null;

        if (! selected) {
            onClose?.();
        }
    };

    const selectSrc = (src, meta = null) => {
        const trimmed = String(src ?? '').trim();

        if (trimmed === '') {
            return;
        }

        if (wantsVideo && ! wantsImage && ! isVideoAssetSrc(trimmed)) {
            return;
        }

        if (wantsImage && ! wantsVideo && isVideoAssetSrc(trimmed)) {
            return;
        }

        onSelect(trimmed, meta ?? undefined);
        close(true);
    };

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close(false);
        }
    };

    activeBrowser = { close };

    const updateTypeButtons = () => {
        els.types?.querySelectorAll('[data-mb-type]').forEach((btn) => {
            const t = btn.getAttribute('data-mb-type') || '';
            const active = (state.type ?? '') === t;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('is-active', active);
        });
    };

    const selectedGallery = () => {
        if (state.galleryId == null) {
            return null;
        }

        return state.galleries.find((gallery) => Number(gallery.id) === Number(state.galleryId)) ?? null;
    };

    const includeDescendantsForSelection = () => selectedGallery()?.kind === 'group';

    const uploadContextGalleryId = () => state.uploadGalleryId ?? state.galleryId;

    const updateUploadHint = () => {
        const path = state.uploadGallery?.path || state.uploadGallery?.name;
        const browsingGroup = selectedGallery()?.kind === 'group';

        if (path) {
            els.subtitle.textContent = browsingGroup
                ? (labels.mediaBrowserFolderUploadSubtitle
                    ?? `Browsing a folder · uploads go to ${path}`)
                : (labels.mediaBrowserUploadSubtitle
                    ?? `Uploads go to ${path} · browse any gallery to choose`);
            els.dropHint.textContent = labels.mediaBrowserUploadDropHint
                ?? `Uploads → ${path}`;

            return;
        }

        els.subtitle.textContent = labels.mediaBrowserBrowseSubtitle ?? 'Browse and choose media';
        els.dropHint.textContent = labels.mediaBrowserUploadDropDefault ?? 'Uploads → default gallery';
    };

    const galleryLabel = (gallery) => {
        if (gallery.label) {
            return gallery.label;
        }

        if (gallery.breadcrumb && gallery.breadcrumb !== gallery.name) {
            return gallery.breadcrumb;
        }

        return gallery.name ?? '';
    };

    const renderGalleries = () => {
        const allLabel = 'All media';
        const items = [
            { id: null, name: allLabel, label: allLabel, depth: 0, media_count: state.total || null, is_default: false },
            ...state.galleries,
        ];

        els.sidebar.innerHTML = items.map((gallery) => {
            const id = gallery.id;
            const active = (id == null && state.galleryId == null)
                || (id != null && Number(id) === Number(state.galleryId));
            const count = gallery.media_count != null ? `<span>${escapeHtml(String(gallery.media_count))}</span>` : '';
            const badge = gallery.is_default ? '<em>default</em>' : '';
            const depth = Number(gallery.depth ?? 0);
            const kindClass = gallery.kind === 'group' ? ' is-group' : (gallery.kind === 'album' ? ' is-album' : '');
            const label = galleryLabel(gallery);
            const pathHint = gallery.path && gallery.path !== label && ! label.includes('›')
                ? `<small class="voodbuilder-media-browser__nav-path">${escapeHtml(gallery.path)}</small>`
                : '';

            return `<button type="button" class="voodbuilder-media-browser__nav${active ? ' is-active' : ''}${kindClass}" data-mb-depth="${depth}" data-mb-gallery="${id == null ? '' : escapeHtml(String(id))}">
                <span class="voodbuilder-media-browser__nav-label">${escapeHtml(label)}${badge}${pathHint}</span>${count}
            </button>`;
        }).join('');

        els.gallerySelect.innerHTML = items.map((gallery) => {
            const id = gallery.id == null ? '' : String(gallery.id);
            const selected = (gallery.id == null && state.galleryId == null)
                || (gallery.id != null && Number(gallery.id) === Number(state.galleryId));

            return `<option value="${escapeHtml(id)}" ${selected ? 'selected' : ''}>${escapeHtml(galleryLabel(gallery))}</option>`;
        }).join('');
    };

    const observeThumbs = () => {
        state.observer?.disconnect();

        if (typeof IntersectionObserver !== 'function') {
            els.grid.querySelectorAll('[data-mb-lazy]').forEach((img) => {
                const src = img.getAttribute('data-mb-lazy');

                if (src) {
                    img.src = src;
                    img.removeAttribute('data-mb-lazy');
                }
            });

            return;
        }

        state.observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (! entry.isIntersecting) {
                    continue;
                }

                const img = entry.target;
                const src = img.getAttribute('data-mb-lazy');

                if (src) {
                    img.src = src;
                    img.removeAttribute('data-mb-lazy');
                }

                state.observer.unobserve(img);
            }
        }, {
            root: els.grid.closest('.voodbuilder-media-browser__grid-wrap'),
            rootMargin: '120px',
            threshold: 0.01,
        });

        els.grid.querySelectorAll('[data-mb-lazy]').forEach((img) => state.observer.observe(img));
    };

    const renderGrid = ({ append = false } = {}) => {
        if (! append) {
            els.grid.innerHTML = '';
        }

        const html = state.items.map((item) => {
            const isVideo = item.type === 'video' || isVideoAssetSrc(item.src);
            const name = escapeHtml(item.name || 'Media');
            const thumb = item.thumb || (! isVideo ? item.src : null);
            const caption = escapeHtml(String(item.caption ?? ''));
            const alt = escapeHtml(String(item.alt ?? ''));
            const credits = escapeHtml(String(item.credits ?? ''));
            const fileName = escapeHtml(String(item.file_name ?? item.name ?? ''));
            const id = item.id != null ? escapeHtml(String(item.id)) : '';
            const uuid = escapeHtml(String(item.uuid ?? ''));

            const preview = isVideo
                ? `<div class="voodbuilder-media-browser__tile-video" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                   </div>`
                : `<img alt="${alt}" decoding="async" data-mb-lazy="${escapeHtml(thumb)}" />`;

            return `<button type="button" class="voodbuilder-media-browser__tile" role="option" data-mb-src="${escapeHtml(item.src)}" data-mb-caption="${caption}" data-mb-alt="${alt}" data-mb-credits="${credits}" data-mb-name="${name}" data-mb-file-name="${fileName}" data-mb-id="${id}" data-mb-uuid="${uuid}" title="${name}">
                <span class="voodbuilder-media-browser__tile-preview">${preview}</span>
                <span class="voodbuilder-media-browser__tile-meta">
                    <span class="voodbuilder-media-browser__tile-name">${name}</span>
                    <span class="voodbuilder-media-browser__tile-kind">${isVideo ? 'Video' : 'Photo'}</span>
                </span>
            </button>`;
        }).join('');

        if (append) {
            els.grid.insertAdjacentHTML('beforeend', html);
        } else {
            els.grid.innerHTML = html || '';
        }

        observeThumbs();

        const empty = state.items.length === 0 && ! state.loading;
        els.status.textContent = empty
            ? 'No media in this view.'
            : (state.loading
                ? 'Loading…'
                : `${state.total} item${state.total === 1 ? '' : 's'}${state.hasMore ? ' · scroll for more' : ''}`);
        updateUploadHint();
    };

    const fetchGalleries = async () => {
        if (! galleriesUrl) {
            state.galleries = [];

            return;
        }

        try {
            const url = buildUrl(galleriesUrl, {
                type: state.type,
                gallery_id: state.galleryId,
            });
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            state.galleries = Array.isArray(payload?.data) ? payload.data : [];
            state.uploadGalleryId = payload?.upload_gallery_id ?? null;
            state.uploadGallery = payload?.upload_gallery ?? null;
            updateUploadHint();
        } catch {
            state.galleries = [];
        }
    };

    const fetchPage = async ({ reset = false } = {}) => {
        if (state.loading) {
            return;
        }

        if (reset) {
            state.page = 1;
            state.items = [];
            state.hasMore = false;
        }

        state.loading = true;

        if (reset) {
            els.status.textContent = 'Loading…';
        }

        try {
            const url = buildUrl(libraryUrl, {
                page: state.page,
                per_page: 48,
                type: state.type,
                gallery_id: state.galleryId,
                include_descendants: includeDescendantsForSelection() ? 1 : null,
                q: state.search || null,
            });
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (! response.ok) {
                throw new Error('load failed');
            }

            const payload = await response.json();
            const chunk = Array.isArray(payload?.data) ? payload.data : [];
            const meta = payload?.meta ?? {};

            state.items = reset ? chunk : [...state.items, ...chunk];
            state.total = Number(meta.total ?? state.items.length);
            state.hasMore = Boolean(meta.has_more);
            state.uploadGalleryId = payload?.upload_gallery_id ?? state.uploadGalleryId;
            state.uploadGallery = payload?.upload_gallery ?? state.uploadGallery;
            updateUploadHint();
        } catch {
            if (reset) {
                state.items = [];
                state.total = 0;
                state.hasMore = false;
            }
        } finally {
            state.loading = false;
            renderGalleries();
            renderGrid({ append: false });
        }
    };

    const loadMore = async () => {
        if (! state.hasMore || state.loading) {
            return;
        }

        state.page += 1;
        await fetchPage({ reset: false });
    };

    if (typeof IntersectionObserver === 'function') {
        moreObserver = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                void loadMore();
            }
        }, {
            root: els.grid.closest('.voodbuilder-media-browser__grid-wrap'),
            rootMargin: '200px',
        });

        moreObserver.observe(els.sentinel);
    }

    const uploadFiles = async (fileList) => {
        const files = [...(fileList ?? [])];

        if (files.length === 0 || ! uploadUrl || state.uploading) {
            return;
        }

        state.uploading = true;
        els.progress.hidden = false;
        els.dropzone.classList.add('is-uploading');

        let done = 0;

        for (const file of files) {
            els.progress.textContent = `Uploading ${done + 1}/${files.length}…`;

            try {
                const body = new FormData();
                body.append('file', file);

                const contextId = uploadContextGalleryId();

                if (contextId != null) {
                    body.append('gallery_id', String(contextId));
                }

                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(editor),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                if (! response.ok) {
                    throw new Error('upload failed');
                }
            } catch {
                els.progress.textContent = `Failed: ${file.name}`;
            }

            done += 1;
        }

        state.uploading = false;
        els.dropzone.classList.remove('is-uploading');
        els.progress.hidden = true;

        // After upload, land on the album that received files (never leave the user on an empty folder view).
        if (state.uploadGalleryId != null) {
            state.galleryId = Number(state.uploadGalleryId);
        }

        await fetchGalleries();
        await fetchPage({ reset: true });
    };

    modal.addEventListener('click', (event) => {
        const target = event.target;

        if (! (target instanceof Element)) {
            return;
        }

        if (target.closest('[data-mb-close]')) {
            close(false);

            return;
        }

        const typeBtn = target.closest('[data-mb-type]');

        if (typeBtn && ! lockedType) {
            state.type = typeBtn.getAttribute('data-mb-type') || null;

            if (state.type === '') {
                state.type = null;
            }

            updateTypeButtons();
            void fetchGalleries().then(() => fetchPage({ reset: true }));

            return;
        }

        const galleryBtn = target.closest('[data-mb-gallery]');

        if (galleryBtn) {
            const raw = galleryBtn.getAttribute('data-mb-gallery');
            state.galleryId = raw ? Number(raw) : null;
            // Refresh upload_gallery_* so folders map to their Library album before drop.
            void fetchGalleries().then(() => fetchPage({ reset: true }));

            return;
        }

        const tile = target.closest('[data-mb-src]');

        if (tile) {
            const caption = tile.getAttribute('data-mb-caption');
            const alt = tile.getAttribute('data-mb-alt');
            const credits = tile.getAttribute('data-mb-credits');
            const name = tile.getAttribute('data-mb-name');
            const fileName = tile.getAttribute('data-mb-file-name');
            const idRaw = tile.getAttribute('data-mb-id');
            const uuid = tile.getAttribute('data-mb-uuid');
            selectSrc(tile.getAttribute('data-mb-src'), {
                caption: caption || null,
                alt: alt || null,
                credits: credits || null,
                name: name || null,
                file_name: fileName || null,
                id: idRaw ? Number(idRaw) : undefined,
                uuid: uuid || null,
            });
        }
    });

    els.gallerySelect?.addEventListener('change', () => {
        const raw = els.gallerySelect.value;
        state.galleryId = raw ? Number(raw) : null;
        void fetchGalleries().then(() => fetchPage({ reset: true }));
    });

    els.search?.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(() => {
            state.search = String(els.search.value ?? '').trim();
            void fetchPage({ reset: true });
        }, 280);
    });

    els.dropzone?.addEventListener('click', () => els.file?.click());
    els.file?.addEventListener('change', () => {
        void uploadFiles(els.file.files);
        els.file.value = '';
    });

    ['dragenter', 'dragover'].forEach((name) => {
        els.dropzone?.addEventListener(name, (event) => {
            event.preventDefault();
            els.dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((name) => {
        els.dropzone?.addEventListener(name, (event) => {
            event.preventDefault();
            els.dropzone.classList.remove('is-dragover');

            if (name === 'drop') {
                void uploadFiles(event.dataTransfer?.files);
            }
        });
    });

    els.urlAdd?.addEventListener('click', () => selectSrc(els.url?.value));
    els.url?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            selectSrc(els.url.value);
        }
    });

    window.addEventListener('keydown', onKeyDown, true);
    updateTypeButtons();

    await fetchGalleries();
    renderGalleries();
    await fetchPage({ reset: true });

    els.search?.focus();

    return true;
}
