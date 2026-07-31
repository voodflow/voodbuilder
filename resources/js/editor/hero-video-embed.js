/**
 * YouTube / Vimeo URLs for full-bleed hero backgrounds (muted loop embeds).
 */

export const HERO_VIDEO_PROVIDERS = {
    file: 'file',
    yt: 'yt',
    ytnc: 'ytnc',
    vi: 'vi',
};

/**
 * @param {string | null | undefined} value
 * @returns {'file'|'yt'|'ytnc'|'vi'}
 */
export function normalizeHeroVideoProvider(value) {
    const raw = String(value ?? '').trim().toLowerCase();

    if (raw === 'yt' || raw === 'youtube') {
        return 'yt';
    }

    if (raw === 'ytnc' || raw === 'youtube-nocookie' || raw === 'nocookie') {
        return 'ytnc';
    }

    if (raw === 'vi' || raw === 'vimeo') {
        return 'vi';
    }

    return 'file';
}

/**
 * @param {string} provider
 * @returns {boolean}
 */
export function isHeroEmbedProvider(provider) {
    return ['yt', 'ytnc', 'vi'].includes(normalizeHeroVideoProvider(provider));
}

/**
 * @param {string} value
 * @param {'yt'|'ytnc'|'vi'} provider
 * @returns {string}
 */
export function normalizeHeroVideoId(value, provider) {
    const raw = String(value ?? '').trim();

    if (raw === '') {
        return '';
    }

    if (! /^https?:\/\//i.test(raw)) {
        return raw;
    }

    try {
        const url = new URL(raw);

        if (provider === 'vi') {
            const parts = url.pathname.split('/').filter(Boolean);

            return parts[parts.length - 1] ?? raw;
        }

        if (url.hostname.includes('youtu.be')) {
            return url.pathname.replace(/^\//, '').split('/')[0] ?? raw;
        }

        const fromQuery = url.searchParams.get('v');

        if (fromQuery) {
            return fromQuery;
        }

        const embedMatch = url.pathname.match(/\/(?:embed|shorts)\/([^/]+)/);

        if (embedMatch?.[1]) {
            return embedMatch[1];
        }
    } catch {
        // keep raw
    }

    return raw;
}

/**
 * @param {'yt'|'ytnc'|'vi'} provider
 * @param {string} videoId
 * @param {{ autoplay?: boolean, loop?: boolean, muted?: boolean }} [options]
 * @returns {string}
 */
export function buildHeroEmbedSrc(provider, videoId, options = {}) {
    const id = String(videoId ?? '').trim();

    if (id === '') {
        return '';
    }

    const autoplay = options.autoplay !== false;
    const loop = options.loop !== false;
    const muted = options.muted !== false;

    if (provider === 'vi') {
        const params = new URLSearchParams({
            background: '1',
            autoplay: autoplay ? '1' : '0',
            loop: loop ? '1' : '0',
            muted: muted ? '1' : '0',
            byline: '0',
            title: '0',
            portrait: '0',
        });

        return `https://player.vimeo.com/video/${encodeURIComponent(id)}?${params.toString()}`;
    }

    const host = provider === 'ytnc'
        ? 'https://www.youtube-nocookie.com'
        : 'https://www.youtube.com';

    const params = new URLSearchParams({
        autoplay: autoplay ? '1' : '0',
        mute: muted ? '1' : '0',
        controls: '0',
        playsinline: '1',
        rel: '0',
        modestbranding: '1',
        iv_load_policy: '3',
        disablekb: '1',
        fs: '0',
        enablejsapi: '1',
    });

    if (loop) {
        params.set('loop', '1');
        // YouTube requires playlist=id for single-video loop.
        params.set('playlist', id);
    }

    return `${host}/embed/${encodeURIComponent(id)}?${params.toString()}`;
}
