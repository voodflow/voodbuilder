/**
 * Content-panel settings for background video hero sections (vb-bg-video).
 * Mirrors image-content-settings.js UX: Choose/Clear + presentation + playback.
 */

import {
    createCheckboxField,
    createCheckboxGrid,
    createFormSection,
    createImageUrlField,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import {
    isHeroEmbedProvider,
    normalizeHeroVideoId,
    normalizeHeroVideoProvider,
} from './hero-video-embed.js';
import { safeFindComponents } from './tailwind-visual-style.js';

function runWithSettingsChangeGuard(editor, callback) {
    if (! editor || typeof callback !== 'function') {
        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentClasses(component) {
    const classes = component?.getClasses?.() ?? component?.get?.('classes') ?? [];

    if (Array.isArray(classes)) {
        return classes.map((item) => (typeof item === 'string' ? item : String(item?.id ?? item?.get?.('name') ?? '')));
    }

    return [];
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {import('grapesjs').Component | null}
 */
function findAncestorSection(component) {
    let current = component;

    while (current) {
        if (componentTag(current) === 'section') {
            return current;
        }

        current = current.parent?.() ?? null;
    }

    return null;
}

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function findHeroMediaHost(section) {
    return safeFindComponents(
        section,
        '[data-voodbuilder-role="media"], .voodbuilder-hero-media',
    )[0];
}

function findHeroMediaVideo(section) {
    return safeFindComponents(
        section,
        '[data-voodbuilder-role="media"] video, .voodbuilder-hero-media__video',
    )[0];
}

function findHeroMediaEmbed(section) {
    return safeFindComponents(
        section,
        '[data-vb-embed-bg], .voodbuilder-hero-media__embed',
    )[0];
}

/**
 * @param {import('grapesjs').Component | null | undefined} section
 * @returns {boolean}
 */
function isBackgroundVideoSection(section) {
    if (! section?.get) {
        return false;
    }

    const type = String(section.get('type') ?? '');
    const blockId = String(section.getAttributes?.()?.['data-voodbuilder-section-block'] ?? '');

    return type === 'vb-bg-video' || blockId === 'vb-bg-video' || blockId.includes('vb-bg-video');
}

/**
 * @param {import('grapesjs').Component} component
 * @param {import('grapesjs').Component} section
 * @returns {boolean}
 */
function shouldOfferHeroVideoSettings(component, section) {
    if (component === section) {
        return true;
    }

    if (String(component.get?.('type') ?? '') === 'vb-bg-video') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const role = String(attrs['data-voodbuilder-role'] ?? '');
    const classes = componentClasses(component);
    const tag = componentTag(component);

    if (tag === 'video' || classes.includes('voodbuilder-hero-media__video')) {
        return true;
    }

    if (tag === 'iframe' || classes.includes('voodbuilder-hero-media__iframe') || classes.includes('voodbuilder-hero-media__embed')) {
        return true;
    }

    if (attrs['data-vb-embed-bg'] != null) {
        return true;
    }

    if (role === 'media' || role === 'shade' || role === 'content') {
        return true;
    }

    if (classes.includes('voodbuilder-hero-media') || classes.includes('voodbuilder-hero-media__shade')) {
        return true;
    }

    return false;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {{ section: object, video: object | null, embed: object | null, mediaHost: object, source: object } | null}
 */
export function resolveVideoSettingsContext(component) {
    if (! component?.get) {
        return null;
    }

    const section = componentTag(component) === 'section'
        ? component
        : findAncestorSection(component);

    if (! section || ! isBackgroundVideoSection(section)) {
        return null;
    }

    const mediaHost = findHeroMediaHost(section);

    if (! mediaHost) {
        return null;
    }

    if (! shouldOfferHeroVideoSettings(component, section)) {
        return null;
    }

    return {
        section,
        video: findHeroMediaVideo(section) ?? null,
        embed: findHeroMediaEmbed(section) ?? null,
        mediaHost,
        source: component,
    };
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isVideoSettingsComponent(component) {
    return resolveVideoSettingsContext(component) != null;
}

/**
 * @param {import('grapesjs').Component} section
 * @param {import('grapesjs').Component | null} video
 * @param {string} url
 */
function applyVideoSrc(section, video, url) {
    const next = String(url ?? '').trim();

    section.addAttributes({
        'data-vb-video-src': next || null,
        'data-vb-video-provider': 'file',
    });

    if (! video) {
        return;
    }

    if (next !== '') {
        video.addAttributes({ src: next });
        video.set?.('src', next);
    } else {
        video.removeAttributes?.('src');
        video.set?.('src', '');
    }
}

/**
 * @param {import('grapesjs').Component} section
 * @param {'file'|'yt'|'ytnc'|'vi'} provider
 * @param {string} [videoId]
 */
function applyHeroVideoProvider(section, provider, videoId) {
    const nextProvider = normalizeHeroVideoProvider(provider);
    const attrs = {
        'data-vb-video-provider': nextProvider,
    };

    if (isHeroEmbedProvider(nextProvider)) {
        const id = normalizeHeroVideoId(videoId ?? section.getAttributes?.()?.['data-vb-video-id'] ?? '', nextProvider);
        attrs['data-vb-video-id'] = id || null;
    }

    section.addAttributes(attrs);
}

/**
 * @param {import('grapesjs').Component} section
 * @param {'yt'|'ytnc'|'vi'} provider
 * @param {string} videoId
 */
function applyHeroEmbedId(section, provider, videoId) {
    const id = normalizeHeroVideoId(videoId, provider);

    section.addAttributes({
        'data-vb-video-provider': provider,
        'data-vb-video-id': id || null,
    });
}

/**
 * @param {import('grapesjs').Component} section
 * @param {import('grapesjs').Component | null} video
 * @param {string} url
 */
function applyPosterSrc(section, video, url) {
    const next = String(url ?? '').trim();

    section.addAttributes({ 'data-vb-video-poster': next || null });

    if (! video) {
        return;
    }

    if (next !== '') {
        video.addAttributes({ poster: next });
    } else {
        video.removeAttributes?.('poster');
    }
}

/**
 * @param {import('grapesjs').Component} section
 * @param {import('grapesjs').Component | null} video
 * @param {import('grapesjs').Component | null} embed
 * @param {{ opacity?: string, fit?: string, position?: string }} values
 */
function applyHeroVideoPresentation(section, video, embed, values) {
    const opacity = values.opacity != null ? String(values.opacity) : null;
    const fit = values.fit != null ? String(values.fit) : null;
    const position = values.position != null ? String(values.position) : null;

    const styles = {
        position: 'absolute',
        inset: '0',
        display: 'block',
        width: '100%',
        height: '100%',
        'max-width': 'none',
    };

    if (opacity != null) {
        styles.opacity = opacity;
    }

    if (fit != null) {
        styles['object-fit'] = fit;
        styles['--vb-object-fit'] = fit;
    }

    if (position != null) {
        const objectPosition = position === 'top'
            ? 'center top'
            : (position === 'bottom' ? 'center bottom' : 'center');
        styles['object-position'] = objectPosition;
        styles['--vb-object-position'] = objectPosition;
    }

    if (video) {
        video.addStyle(styles);
    }

    if (embed) {
        const embedStyles = {
            position: 'absolute',
            inset: '0',
            display: 'block',
            overflow: 'hidden',
            'pointer-events': 'none',
        };

        if (opacity != null) {
            embedStyles.opacity = opacity;
        }

        if (fit != null) {
            embedStyles['--vb-object-fit'] = fit;
            embed.addAttributes({ 'data-vb-embed-fit': fit });
        }

        if (position != null) {
            const objectPosition = position === 'top'
                ? 'center top'
                : (position === 'bottom' ? 'center bottom' : 'center');
            embedStyles['--vb-object-position'] = objectPosition;
            embed.addAttributes({ 'data-vb-embed-position': position });
        }

        embed.addStyle(embedStyles);
    }

    const attrs = {};

    if (opacity != null) {
        attrs['data-vb-bg-opacity'] = opacity;
    }

    if (fit != null) {
        attrs['data-vb-bg-size'] = fit;
    }

    if (position != null) {
        attrs['data-vb-bg-position'] = position;
    }

    if (Object.keys(attrs).length > 0) {
        section.addAttributes(attrs);
    }
}

/**
 * @param {import('grapesjs').Component} section
 * @param {string} name
 * @param {boolean} enabled
 */
function applyPlaybackFlag(section, name, enabled) {
    section.addAttributes({ [name]: enabled ? '1' : '0' });
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderVideoContentSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    const context = resolveVideoSettingsContext(component);

    if (! mount || ! context) {
        return false;
    }

    const { section, video, embed } = context;
    const key = `video:${componentKey(section)}`;
    const existing = mount.querySelector('[data-voodbuilder-video-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const sectionAttrs = section.getAttributes?.() ?? {};
    const videoAttrs = video?.getAttributes?.() ?? {};
    const videoStyle = video?.getStyle?.() ?? {};
    const embedStyle = embed?.getStyle?.() ?? {};

    let provider = normalizeHeroVideoProvider(sectionAttrs['data-vb-video-provider']);
    let videoId = String(sectionAttrs['data-vb-video-id'] ?? '').trim();
    let videoSrc = String(sectionAttrs['data-vb-video-src'] ?? videoAttrs.src ?? '').trim();
    let posterSrc = String(sectionAttrs['data-vb-video-poster'] ?? videoAttrs.poster ?? '').trim();
    let opacity = String(sectionAttrs['data-vb-bg-opacity'] ?? videoStyle.opacity ?? embedStyle.opacity ?? '1');
    let fit = String(sectionAttrs['data-vb-bg-size'] ?? videoStyle['object-fit'] ?? 'cover');
    let position = String(sectionAttrs['data-vb-bg-position'] ?? 'center');
    let minHeight = String(sectionAttrs['data-vb-min-height'] ?? '70vh');
    let autoplay = sectionAttrs['data-vb-autoplay'] !== '0';
    let muted = sectionAttrs['data-vb-muted'] !== '0';
    let loop = sectionAttrs['data-vb-loop'] !== '0';
    let controls = sectionAttrs['data-vb-controls'] === '1';

    if (videoStyle['object-position']?.includes('top')) {
        position = 'top';
    } else if (videoStyle['object-position']?.includes('bottom')) {
        position = 'bottom';
    }

    if (/^data:image\/svg\+xml/i.test(posterSrc)) {
        posterSrc = '';
    }

    const { section: form, fields } = createFormSection(
        labels.videoSettingsHeroTitle ?? 'Background video',
    );
    form.setAttribute('data-voodbuilder-video-settings', '');
    form.setAttribute('data-component-key', key);

    const syncProviderUi = () => {
        const isEmbed = isHeroEmbedProvider(provider);

        form.querySelectorAll('[data-vb-video-source-panel]').forEach((panel) => {
            const forProvider = panel.getAttribute('data-vb-video-source-panel');

            if (forProvider === 'yt') {
                panel.hidden = ! (provider === 'yt' || provider === 'ytnc');
            } else if (forProvider === 'vi') {
                panel.hidden = provider !== 'vi';
            } else if (forProvider === 'file') {
                panel.hidden = provider !== 'file';
            }
        });

        form.querySelectorAll('[data-vb-hero-file-only]').forEach((el) => {
            el.hidden = isEmbed;
        });
    };

    const presentationTarget = () => ({
        video: findHeroMediaVideo(section) ?? video,
        embed: findHeroMediaEmbed(section) ?? embed,
    });

    fields.append(
        createSelectField({
            label: labels.videoSettingsSource ?? 'Source',
            name: 'bgVideoProvider',
            value: provider === 'ytnc' ? 'yt' : provider,
            options: [
                { value: 'yt', label: labels.videoSettingsSourceYoutube ?? 'YouTube' },
                { value: 'vi', label: labels.videoSettingsSourceVimeo ?? 'Vimeo' },
                { value: 'file', label: labels.videoSettingsSourceFile ?? 'File URL / media' },
            ],
            onChange: (value) => {
                if (value === 'yt') {
                    provider = provider === 'ytnc' ? 'ytnc' : 'yt';
                } else if (value === 'vi') {
                    provider = 'vi';
                } else {
                    provider = 'file';
                }

                runWithSettingsChangeGuard(editor, () => {
                    applyHeroVideoProvider(section, provider, videoId);
                });
                syncProviderUi();
            },
        }),
    );

    const ytPanel = document.createElement('div');
    ytPanel.setAttribute('data-vb-video-source-panel', 'yt');
    const { field: ytField, input: ytInput } = createTextField({
        label: labels.videoSettingsYoutubeId ?? 'YouTube ID / URL',
        name: 'bgVideoYoutubeId',
        value: videoId,
        placeholder: 'dQw4w9WgXcQ or https://youtu.be/…',
    });
    ytInput.addEventListener('change', () => {
        videoId = normalizeHeroVideoId(ytInput.value, 'yt');
        ytInput.value = videoId;
        runWithSettingsChangeGuard(editor, () => {
            applyHeroEmbedId(section, provider === 'ytnc' ? 'ytnc' : 'yt', videoId);
        });
    });
    ytPanel.append(ytField);
    ytPanel.append(
        createCheckboxField({
            label: labels.videoSettingsPrivacy ?? 'Privacy enhanced (nocookie)',
            name: 'bgVideoYoutubePrivacy',
            checked: provider === 'ytnc',
            onChange: (checked) => {
                provider = checked ? 'ytnc' : 'yt';
                runWithSettingsChangeGuard(editor, () => {
                    applyHeroEmbedId(section, provider, videoId);
                });
            },
        }),
    );
    fields.append(ytPanel);

    const viPanel = document.createElement('div');
    viPanel.setAttribute('data-vb-video-source-panel', 'vi');
    const { field: viField, input: viInput } = createTextField({
        label: labels.videoSettingsVimeoId ?? 'Vimeo ID / URL',
        name: 'bgVideoVimeoId',
        value: videoId,
        placeholder: '123456789 or https://vimeo.com/…',
    });
    viInput.addEventListener('change', () => {
        videoId = normalizeHeroVideoId(viInput.value, 'vi');
        viInput.value = videoId;
        runWithSettingsChangeGuard(editor, () => {
            applyHeroEmbedId(section, 'vi', videoId);
        });
    });
    viPanel.append(viField);
    fields.append(viPanel);

    const filePanel = document.createElement('div');
    filePanel.setAttribute('data-vb-video-source-panel', 'file');
    filePanel.append(
        createImageUrlField({
            label: labels.videoSettingsFileSrc ?? labels.videoSettingsSrc ?? 'Video file',
            name: 'bgVideoSrc',
            value: videoSrc,
            editor,
            hidePathInput: false,
            assetTypes: ['video'],
            // No sidebar video preview — stays black; canvas already shows the clip.
            previewMode: 'none',
            chooseLabel: labels.videoSettingsChoose ?? labels.imageSettingsChoose ?? 'Choose',
            clearLabel: labels.videoSettingsClear ?? labels.imageSettingsClear ?? 'Clear',
            placeholder: labels.videoSettingsSrcPlaceholder ?? 'https://…/video.mp4',
            onChange: (url) => {
                videoSrc = url;
                runWithSettingsChangeGuard(editor, () => {
                    applyVideoSrc(section, findHeroMediaVideo(section) ?? video, url);
                });
            },
        }),
    );
    fields.append(filePanel);

    fields.append(
        createImageUrlField({
            label: labels.videoSettingsPoster ?? 'Poster image',
            name: 'bgVideoPoster',
            value: posterSrc,
            editor,
            hidePathInput: true,
            assetTypes: ['image'],
            previewMode: 'image',
            chooseLabel: labels.videoSettingsChoose ?? labels.imageSettingsChoose ?? 'Choose',
            clearLabel: labels.videoSettingsClear ?? labels.imageSettingsClear ?? 'Clear',
            onChange: (url) => {
                posterSrc = url;
                runWithSettingsChangeGuard(editor, () => {
                    applyPosterSrc(section, findHeroMediaVideo(section) ?? video, url);
                });
            },
        }),
    );

    fields.append(
        createSelectField({
            label: labels.videoSettingsOpacity ?? labels.imageSettingsOpacity ?? 'Opacity',
            name: 'bgVideoOpacity',
            value: opacity,
            options: [
                { value: '0.35', label: '35%' },
                { value: '0.45', label: '45%' },
                { value: '0.55', label: '55%' },
                { value: '0.65', label: '65%' },
                { value: '0.75', label: '75%' },
                { value: '0.9', label: '90%' },
                { value: '1', label: '100%' },
            ],
            onChange: (value) => {
                opacity = value;
                const targets = presentationTarget();
                runWithSettingsChangeGuard(editor, () => {
                    applyHeroVideoPresentation(section, targets.video, targets.embed, { opacity: value });
                });
            },
        }),
    );

    const fitField = createSelectField({
        label: labels.videoSettingsFit ?? 'Video fit',
        name: 'bgVideoFit',
        value: fit,
        options: [
            { value: 'cover', label: labels.imageSettingsFitCover ?? 'Cover' },
            { value: 'contain', label: labels.imageSettingsFitContain ?? 'Contain' },
            { value: 'fill', label: labels.imageSettingsFitFill ?? 'Fill' },
        ],
        onChange: (value) => {
            fit = value;
            const targets = presentationTarget();
            runWithSettingsChangeGuard(editor, () => {
                applyHeroVideoPresentation(section, targets.video, targets.embed, { fit: value });
            });
        },
    });
    fitField.setAttribute('data-vb-hero-file-only', '');
    fields.append(fitField);

    const positionField = createSelectField({
        label: labels.videoSettingsPosition ?? 'Video position',
        name: 'bgVideoPosition',
        value: position,
        options: [
            { value: 'center', label: labels.imageSettingsPositionCenter ?? 'Center' },
            { value: 'top', label: labels.imageSettingsPositionTop ?? 'Top' },
            { value: 'bottom', label: labels.imageSettingsPositionBottom ?? 'Bottom' },
        ],
        onChange: (value) => {
            position = value;
            const targets = presentationTarget();
            runWithSettingsChangeGuard(editor, () => {
                applyHeroVideoPresentation(section, targets.video, targets.embed, { position: value });
            });
        },
    });
    positionField.setAttribute('data-vb-hero-file-only', '');
    fields.append(positionField);

    fields.append(
        createSelectField({
            label: labels.videoSettingsMinHeight ?? 'Minimum height',
            name: 'bgVideoMinHeight',
            value: minHeight,
            options: [
                { value: '50vh', label: '50vh' },
                { value: '70vh', label: '70vh' },
                { value: '100vh', label: '100vh' },
            ],
            onChange: (value) => {
                minHeight = value;
                runWithSettingsChangeGuard(editor, () => {
                    section.addAttributes({ 'data-vb-min-height': value });
                    section.addStyle({ 'min-height': value });
                });
            },
        }),
    );

    const playbackChecks = [
        createCheckboxField({
            label: labels.videoSettingsAutoplay ?? 'Autoplay',
            name: 'bgVideoAutoplay',
            checked: autoplay,
            onChange: (checked) => {
                autoplay = checked;
                runWithSettingsChangeGuard(editor, () => {
                    applyPlaybackFlag(section, 'data-vb-autoplay', checked);
                });
            },
        }),
        createCheckboxField({
            label: labels.videoSettingsMuted ?? 'Muted',
            name: 'bgVideoMuted',
            checked: muted,
            onChange: (checked) => {
                muted = checked;
                runWithSettingsChangeGuard(editor, () => {
                    applyPlaybackFlag(section, 'data-vb-muted', checked);
                });
            },
        }),
        createCheckboxField({
            label: labels.videoSettingsLoop ?? 'Loop',
            name: 'bgVideoLoop',
            checked: loop,
            onChange: (checked) => {
                loop = checked;
                runWithSettingsChangeGuard(editor, () => {
                    applyPlaybackFlag(section, 'data-vb-loop', checked);
                });
            },
        }),
    ];

    const controlsField = createCheckboxField({
        label: labels.videoSettingsControls ?? 'Show controls',
        name: 'bgVideoControls',
        checked: controls,
        onChange: (checked) => {
            controls = checked;
            runWithSettingsChangeGuard(editor, () => {
                applyPlaybackFlag(section, 'data-vb-controls', checked);
            });
        },
    });
    controlsField.setAttribute('data-vb-hero-file-only', '');
    playbackChecks.push(controlsField);

    fields.append(createCheckboxGrid(playbackChecks));

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-hint';
    hint.textContent = labels.videoSettingsHeroHint
        ?? 'YouTube / Vimeo use a muted looping embed. Self-hosted MP4 remains the most reliable full-bleed background.';
    fields.append(hint);

    mount.appendChild(form);
    syncProviderUi();

    return true;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isInlineVideoSettingsComponent(component) {
    return Boolean(component?.is?.('video') || component?.get?.('type') === 'video');
}

/**
 * Extract YouTube / Vimeo id from a pasted URL or bare id.
 *
 * @param {string} value
 * @param {'yt'|'ytnc'|'vi'} provider
 * @returns {string}
 */
export function normalizeVideoIdInput(value, provider) {
    return normalizeHeroVideoId(value, provider);
}

/**
 * Content panel for the GrapesJS `video` block (YouTube / Vimeo / file).
 *
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderInlineVideoContentSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! isInlineVideoSettingsComponent(component)) {
        return false;
    }

    const key = `inline-video:${componentKey(component)}`;
    const existing = mount.querySelector('[data-voodbuilder-inline-video-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    let provider = String(component.get?.('provider') ?? 'yt');
    let videoId = String(component.get?.('videoId') ?? '').trim();
    let src = String(component.get?.('src') ?? component.getAttributes?.()?.src ?? '').trim();
    let poster = String(component.get?.('poster') ?? '').trim();
    let autoplay = Boolean(component.get?.('autoplay'));
    let muted = Boolean(component.get?.('muted'));
    let loop = Boolean(component.get?.('loop'));
    let controls = component.get?.('controls') !== false;

    const { section: form, fields } = createFormSection(labels.videoSettingsTitle ?? 'Video');
    form.setAttribute('data-voodbuilder-inline-video-settings', '');
    form.setAttribute('data-component-key', key);

    const syncProviderUi = () => {
        form.querySelectorAll('[data-vb-video-source-panel]').forEach((panel) => {
            const forProvider = panel.getAttribute('data-vb-video-source-panel');

            if (forProvider === 'yt') {
                panel.hidden = ! (provider === 'yt' || provider === 'ytnc');
            } else if (forProvider === 'vi') {
                panel.hidden = provider !== 'vi';
            } else if (forProvider === 'so') {
                panel.hidden = provider !== 'so';
            }
        });
    };

    fields.append(
        createSelectField({
            label: labels.videoSettingsSource ?? 'Source',
            name: 'videoProvider',
            value: provider === 'ytnc' ? 'yt' : provider,
            options: [
                { value: 'yt', label: labels.videoSettingsSourceYoutube ?? 'YouTube' },
                { value: 'vi', label: labels.videoSettingsSourceVimeo ?? 'Vimeo' },
                { value: 'so', label: labels.videoSettingsSourceFile ?? 'File URL / media' },
            ],
            onChange: (value) => {
                if (value === 'yt') {
                    provider = 'yt';
                } else if (value === 'vi') {
                    provider = 'vi';
                } else {
                    provider = 'so';
                }

                runWithSettingsChangeGuard(editor, () => {
                    component.set('provider', provider);
                });
                syncProviderUi();
            },
        }),
    );

    const ytPanel = document.createElement('div');
    ytPanel.setAttribute('data-vb-video-source-panel', 'yt');
    const { field: ytField, input: ytInput } = createTextField({
        label: labels.videoSettingsYoutubeId ?? 'YouTube ID / URL',
        name: 'videoYoutubeId',
        value: videoId,
        placeholder: 'dQw4w9WgXcQ or https://youtu.be/…',
    });
    ytInput.addEventListener('change', () => {
        videoId = normalizeVideoIdInput(ytInput.value, 'yt');
        ytInput.value = videoId;
        runWithSettingsChangeGuard(editor, () => {
            component.set('videoId', videoId);
        });
    });
    ytPanel.append(ytField);
    ytPanel.append(
        createCheckboxField({
            label: labels.videoSettingsPrivacy ?? 'Privacy enhanced (nocookie)',
            name: 'videoYoutubePrivacy',
            checked: provider === 'ytnc',
            onChange: (checked) => {
                provider = checked ? 'ytnc' : 'yt';
                runWithSettingsChangeGuard(editor, () => {
                    component.set('provider', provider);
                });
            },
        }),
    );
    fields.append(ytPanel);

    const viPanel = document.createElement('div');
    viPanel.setAttribute('data-vb-video-source-panel', 'vi');
    const { field: viField, input: viInput } = createTextField({
        label: labels.videoSettingsVimeoId ?? 'Vimeo ID / URL',
        name: 'videoVimeoId',
        value: videoId,
        placeholder: '123456789 or https://vimeo.com/…',
    });
    viInput.addEventListener('change', () => {
        videoId = normalizeVideoIdInput(viInput.value, 'vi');
        viInput.value = videoId;
        runWithSettingsChangeGuard(editor, () => {
            component.set('videoId', videoId);
        });
    });
    viPanel.append(viField);
    fields.append(viPanel);

    const soPanel = document.createElement('div');
    soPanel.setAttribute('data-vb-video-source-panel', 'so');
    soPanel.append(
        createImageUrlField({
            label: labels.videoSettingsFileSrc ?? 'Video file',
            name: 'videoFileSrc',
            value: src,
            editor,
            hidePathInput: false,
            assetTypes: ['video'],
            // No sidebar video preview — stays black; canvas already shows the clip.
            previewMode: 'none',
            chooseLabel: labels.videoSettingsChoose ?? 'Choose',
            clearLabel: labels.videoSettingsClear ?? 'Clear',
            placeholder: labels.videoSettingsSrcPlaceholder ?? 'https://…/video.mp4',
            onChange: (url) => {
                src = url;
                runWithSettingsChangeGuard(editor, () => {
                    component.set('src', url);
                    component.addAttributes?.({ src: url || null });
                });
            },
        }),
    );
    fields.append(soPanel);

    fields.append(
        createImageUrlField({
            label: labels.videoSettingsPoster ?? 'Poster image',
            name: 'videoPoster',
            value: /^data:image\/svg\+xml/i.test(poster) ? '' : poster,
            editor,
            hidePathInput: true,
            assetTypes: ['image'],
            previewMode: 'image',
            chooseLabel: labels.videoSettingsChoose ?? 'Choose',
            clearLabel: labels.videoSettingsClear ?? 'Clear',
            onChange: (url) => {
                poster = url;
                runWithSettingsChangeGuard(editor, () => {
                    component.set('poster', url);

                    if (url) {
                        component.addAttributes?.({ poster: url });
                    } else {
                        component.removeAttributes?.('poster');
                    }
                });
            },
        }),
    );

    fields.append(
        createCheckboxGrid([
            createCheckboxField({
                label: labels.videoSettingsAutoplay ?? 'Autoplay',
                name: 'videoAutoplay',
                checked: autoplay,
                onChange: (checked) => {
                    autoplay = checked;

                    if (checked) {
                        muted = true;
                        const mutedInput = form.querySelector('input[name="videoMuted"]');

                        if (mutedInput instanceof HTMLInputElement) {
                            mutedInput.checked = true;
                        }
                    }

                    runWithSettingsChangeGuard(editor, () => {
                        component.set('autoplay', checked);

                        if (checked) {
                            component.set('muted', true);
                            component.addAttributes?.({
                                muted: true,
                                playsinline: true,
                                'webkit-playsinline': true,
                            });
                        }
                    });
                },
            }),
            createCheckboxField({
                label: labels.videoSettingsMuted ?? 'Muted',
                name: 'videoMuted',
                checked: muted || autoplay,
                onChange: (checked) => {
                    // Browsers block unmuted autoplay — keep muted while autoplay is on.
                    if (! checked && autoplay) {
                        muted = true;
                        const mutedInput = form.querySelector('input[name="videoMuted"]');

                        if (mutedInput instanceof HTMLInputElement) {
                            mutedInput.checked = true;
                        }

                        return;
                    }

                    muted = checked;
                    runWithSettingsChangeGuard(editor, () => {
                        component.set('muted', checked);
                    });
                },
            }),
            createCheckboxField({
                label: labels.videoSettingsLoop ?? 'Loop',
                name: 'videoLoop',
                checked: loop,
                onChange: (checked) => {
                    loop = checked;
                    runWithSettingsChangeGuard(editor, () => {
                        component.set('loop', checked);
                    });
                },
            }),
            createCheckboxField({
                label: labels.videoSettingsControls ?? 'Show controls',
                name: 'videoControls',
                checked: controls,
                onChange: (checked) => {
                    controls = checked;
                    runWithSettingsChangeGuard(editor, () => {
                        component.set('controls', checked);
                    });
                },
            }),
        ]),
    );

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-hint';
    hint.textContent = labels.videoSettingsHint
        ?? 'Browsers only allow autoplay when the video is muted. Enabling Autoplay turns Muted on automatically.';
    fields.append(hint);

    mount.appendChild(form);
    syncProviderUi();

    return true;
}
