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

export function shouldUseVideoFacade(model) {
    if (! model || model.get('autoplay')) {
        return false;
    }

    return EMBED_PROVIDERS.has(String(model.get('provider') ?? ''));
}

export function resolvePosterUrl(model) {
    const custom = String(model.get('poster') ?? '').trim();

    if (custom) {
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

export function buildVideoFacadeMarkup(model, embedSrc) {
    const poster = resolvePosterUrl(model);
    const classes = model.getClasses?.().join(' ') ?? '';
    const classAttr = classes ? ` ${escapeHtml(classes)}` : '';
    const embed = escapeHtml(embedSrc);
    const posterAttr = poster ? ` data-vb-poster="${escapeHtml(poster)}"` : '';

    const posterMarkup = poster
        ? `<img class="vb-video-facade__poster" src="${escapeHtml(poster)}" alt="" loading="lazy" decoding="async" />`
        : '<div class="vb-video-facade__poster vb-video-facade__poster--empty" aria-hidden="true"></div>';

    return `<div class="vb-video-facade${classAttr}" data-vb-video-facade data-vb-embed-src="${embed}"${posterAttr} role="button" tabindex="0" aria-label="Play video">${posterMarkup}<span class="vb-video-facade__play" aria-hidden="true"></span></div>`;
}

export function createVideoFacadeElement(model, embedSrc, doc = document) {
    const wrapper = doc.createElement('div');
    wrapper.innerHTML = buildVideoFacadeMarkup(model, embedSrc);

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
