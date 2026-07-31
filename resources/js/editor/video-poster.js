/**
 * Video poster / click-to-play facade for embed providers when autoplay is off.
 */

const EMBED_PROVIDERS = new Set(['yt', 'ytnc', 'vi']);

export function getPosterTrait() {
    return {
        type: 'text',
        label: 'Poster',
        name: 'poster',
        changeProp: true,
        placeholder: 'https://…/cover.jpg (optional)',
    };
}

/**
 * GrapesJS may store booleans as real booleans, 0/1, or "true"/"false" strings.
 *
 * @param {unknown} value
 * @returns {boolean}
 */
export function isTruthyProp(value) {
    if (value === true || value === 1 || value === '1') {
        return true;
    }

    if (value === false || value === 0 || value === '0' || value == null || value === '') {
        return false;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();

        if (normalized === 'false' || normalized === 'no' || normalized === 'off') {
            return false;
        }

        if (normalized === 'true' || normalized === 'yes' || normalized === 'on') {
            return true;
        }
    }

    return Boolean(value);
}

export function shouldUseVideoFacade(model) {
    if (! model || isTruthyProp(model.get('autoplay'))) {
        return false;
    }

    return EMBED_PROVIDERS.has(String(model.get('provider') ?? ''));
}

export function resolvePosterUrl(model) {
    const custom = String(model.get('poster') ?? '').trim();

    if (custom && ! /^data:image\/svg\+xml/i.test(custom)) {
        return custom;
    }

    const videoId = String(model.get('videoId') ?? '').trim();

    if (videoId === '') {
        return '';
    }

    const provider = String(model.get('provider') ?? '');

    if (provider === 'yt' || provider === 'ytnc') {
        return `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
    }

    if (provider === 'vi') {
        return `https://vumbnail.com/${videoId}.jpg`;
    }

    return '';
}

export function getActivationEmbedSrc(model, VideoModel) {
    if (! VideoModel || ! shouldUseVideoFacade(model)) {
        return String(model.get('src') ?? '');
    }

    const wasAutoplay = model.get('autoplay');
    const provider = model.get('provider');

    model.set('autoplay', true, { silent: true });

    let src = '';

    if (provider === 'yt') {
        src = VideoModel.prototype.getYoutubeSrc.call(model);
    } else if (provider === 'ytnc') {
        src = VideoModel.prototype.getYoutubeNoCookieSrc.call(model);
    } else if (provider === 'vi') {
        src = VideoModel.prototype.getVimeoSrc.call(model);
    }

    model.set('autoplay', wasAutoplay, { silent: true });

    return src;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Serialize a Grapes style object to a CSS declaration string.
 *
 * @param {Record<string, unknown>|null|undefined} style
 * @returns {string}
 */
export function styleObjectToAttribute(style) {
    if (! style || typeof style !== 'object') {
        return '';
    }

    return Object.entries(style)
        .filter(([property, value]) => {
            if (! property || value == null) {
                return false;
            }

            return String(value).trim() !== '';
        })
        .map(([property, value]) => `${property}: ${String(value).trim()}`)
        .join('; ');
}

/**
 * Stable id + inline style for export (CssComposer #id ↔ HTML), same contract as
 * animated-counter toHTML. Canvas nested facades must omit these (host keeps id).
 *
 * @param {object|null|undefined} model
 * @returns {{ id: string, style: string }}
 */
export function resolveVideoFacadeIdentity(model) {
    const attrs = model?.getAttributes?.() ?? {};
    const id = String(attrs.id ?? model?.getId?.() ?? '').trim();
    let style = String(attrs.style ?? '').trim();

    if (style === '' && typeof model?.getStyle === 'function') {
        style = styleObjectToAttribute(model.getStyle({ inline: true }) ?? {});
    }

    return { id, style };
}

/**
 * @param {object} model
 * @param {string} embedSrc
 * @param {{ persistIdentity?: boolean }} [options]
 * @returns {string}
 */
export function buildVideoFacadeMarkup(model, embedSrc, options = {}) {
    const persistIdentity = options.persistIdentity !== false;
    const poster = resolvePosterUrl(model);
    const classes = model.getClasses?.().join(' ') ?? '';
    const classAttr = classes ? ` ${escapeHtml(classes)}` : '';
    const embed = escapeHtml(embedSrc);
    const posterAttr = poster ? ` data-vb-poster="${escapeHtml(poster)}"` : '';
    const { id, style } = persistIdentity
        ? resolveVideoFacadeIdentity(model)
        : { id: '', style: '' };
    const idAttr = id !== '' ? ` id="${escapeHtml(id)}"` : '';
    const styleAttr = style !== '' ? ` style="${escapeHtml(style)}"` : '';

    const posterMarkup = poster
        ? `<img class="vb-video-facade__poster" src="${escapeHtml(poster)}" alt="" loading="lazy" decoding="async" />`
        : '<div class="vb-video-facade__poster vb-video-facade__poster--empty" aria-hidden="true"></div>';

    return `<div class="vb-video-facade${classAttr}"${idAttr}${styleAttr} data-vb-video-facade data-vb-embed-src="${embed}"${posterAttr} role="button" tabindex="0" aria-label="Play video">${posterMarkup}<span class="vb-video-facade__play" aria-hidden="true"></span></div>`;
}

export function createVideoFacadeElement(model, embedSrc, doc = document) {
    const wrapper = doc.createElement('div');
    // Nested under the video host in canvas — do not duplicate id/style on the child.
    wrapper.innerHTML = buildVideoFacadeMarkup(model, embedSrc, { persistIdentity: false });

    return wrapper.firstElementChild;
}

function insertPosterTrait(traits) {
    const next = [...traits];
    const videoIdIndex = next.findIndex((trait) => trait?.name === 'videoId');

    if (videoIdIndex === -1) {
        next.splice(1, 0, getPosterTrait());

        return next;
    }

    next.splice(videoIdIndex + 1, 0, getPosterTrait());

    return next;
}

export function extendVideoTraits(traits) {
    return insertPosterTrait(traits);
}

/**
 * Append chrome-hiding query params when controls are off (GrapesJS only sets controls=0).
 *
 * @param {string} src
 * @param {object} model
 * @returns {string}
 */
export function hardenEmbedSrcControls(src, model) {
    const value = String(src ?? '').trim();

    if (value === '' || isTruthyProp(model?.get?.('controls'))) {
        return value;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.toLowerCase();

        if (host.includes('youtube.com') || host.includes('youtube-nocookie.com')) {
            url.searchParams.set('controls', '0');
            url.searchParams.set('modestbranding', '1');
            url.searchParams.set('iv_load_policy', '3');
            url.searchParams.set('fs', '0');
            url.searchParams.set('disablekb', '1');
            url.searchParams.set('playsinline', '1');
            url.searchParams.set('rel', '0');
            url.searchParams.delete('showinfo');
        } else if (host.includes('vimeo.com')) {
            url.searchParams.set('title', '0');
            url.searchParams.set('byline', '0');
            url.searchParams.set('portrait', '0');
            url.searchParams.set('badge', '0');
            url.searchParams.set('controls', '0');
        } else {
            return value;
        }

        return url.toString();
    } catch {
        return value;
    }
}
