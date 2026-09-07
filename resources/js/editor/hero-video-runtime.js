/**
 * Background YouTube/Vimeo hero embeds: harden URL params, ensure cover host,
 * size the 16:9 iframe to cover the media box (viewport math fails on tall
 * mobile heroes), and reveal after provider chrome has settled.
 */

const HERO_MEDIA_SELECTOR = '.voodbuilder-hero-media';
const STRIP_CLASSES = [
    'w-full',
    'aspect-video',
    'rounded',
    'vb-video-host',
    'voodbuilder-hero-media__video',
];
const REVEAL_DELAY_MS = 700;
/* Crop provider chrome (YouTube title on hover lives in the top strip). */
const COVER_SCALE = 1.35;

/**
 * @param {string} src
 * @returns {string}
 */
function hardenHeroEmbedSrc(src) {
    const value = String(src ?? '').trim();

    if (value === '') {
        return value;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.toLowerCase();

        if (host.includes('youtube.com') || host.includes('youtube-nocookie.com')) {
            url.searchParams.set('controls', '0');
            url.searchParams.set('mute', '1');
            url.searchParams.set('autoplay', url.searchParams.get('autoplay') ?? '1');
            url.searchParams.set('playsinline', '1');
            url.searchParams.set('rel', '0');
            url.searchParams.set('modestbranding', '1');
            url.searchParams.set('iv_load_policy', '3');
            url.searchParams.set('fs', '0');
            url.searchParams.set('disablekb', '1');
            url.searchParams.set('enablejsapi', '1');
            url.searchParams.delete('showinfo');

            if (url.searchParams.get('loop') === '1') {
                const id = url.pathname.split('/').filter(Boolean).pop() ?? '';

                if (id !== '' && ! url.searchParams.get('playlist')) {
                    url.searchParams.set('playlist', id);
                }
            }
        } else if (host.includes('vimeo.com')) {
            url.searchParams.set('background', '1');
            url.searchParams.set('controls', '0');
            url.searchParams.set('muted', '1');
            url.searchParams.set('autoplay', url.searchParams.get('autoplay') ?? '1');
            url.searchParams.set('loop', url.searchParams.get('loop') ?? '1');
            url.searchParams.set('title', '0');
            url.searchParams.set('byline', '0');
            url.searchParams.set('portrait', '0');
            url.searchParams.set('badge', '0');
        } else {
            return value;
        }

        return url.toString();
    } catch {
        return value;
    }
}

/**
 * @param {HTMLElement} media
 * @param {HTMLIFrameElement} iframe
 * @returns {HTMLElement}
 */
function ensureEmbedHost(media, iframe) {
    const parent = iframe.parentElement;

    if (parent instanceof HTMLElement
        && (parent.classList.contains('voodbuilder-hero-media__embed') || parent.hasAttribute('data-vb-embed-bg'))) {
        return parent;
    }

    const embed = document.createElement('div');
    embed.className = 'voodbuilder-hero-media__embed';
    embed.setAttribute('data-vb-embed-bg', '');
    embed.setAttribute('aria-hidden', 'true');
    iframe.replaceWith(embed);
    embed.appendChild(iframe);

    return embed;
}

/**
 * Full-bleed shield above the iframe so empty layout gaps never hover YouTube.
 *
 * @param {HTMLElement} media
 */
function ensureHitShield(media) {
    if (media.querySelector(':scope > .voodbuilder-hero-media__hit')) {
        return;
    }

    const hit = document.createElement('div');
    hit.className = 'voodbuilder-hero-media__hit';
    hit.setAttribute('aria-hidden', 'true');
    media.appendChild(hit);
}

/**
 * Cover-size a 16:9 iframe against its embed host (not the viewport).
 *
 * @param {HTMLIFrameElement} iframe
 */
function sizeHeroIframeToCover(iframe) {
    const host = iframe.closest('.voodbuilder-hero-media__embed, [data-vb-embed-bg], .voodbuilder-hero-media');

    if (! (host instanceof HTMLElement)) {
        return;
    }

    const fit = host.getAttribute('data-vb-embed-fit') || 'cover';

    if (fit === 'contain' || fit === 'fill') {
        iframe.style.setProperty('width', '100%', 'important');
        iframe.style.setProperty('height', '100%', 'important');
        iframe.style.setProperty('transform', 'translate(-50%, -50%)');

        return;
    }

    const rect = host.getBoundingClientRect();
    const hostW = rect.width;
    const hostH = rect.height;

    if (hostW < 2 || hostH < 2) {
        return;
    }

    const hostRatio = hostW / hostH;
    const videoRatio = 16 / 9;
    let width;
    let height;

    if (hostRatio > videoRatio) {
        width = hostW;
        height = hostW / videoRatio;
    } else {
        height = hostH;
        width = hostH * videoRatio;
    }

    width *= COVER_SCALE;
    height *= COVER_SCALE;

    const position = host.getAttribute('data-vb-embed-position') || 'center';
    let translateY = '-50%';

    if (position === 'top') {
        translateY = '-40%';
    } else if (position === 'bottom') {
        translateY = '-60%';
    }

    // Must beat theme.css `width/height: … !important` CQ fallbacks.
    iframe.style.setProperty('width', `${Math.ceil(width)}px`, 'important');
    iframe.style.setProperty('height', `${Math.ceil(height)}px`, 'important');
    iframe.style.setProperty('transform', `translate(-50%, ${translateY})`);
}

/**
 * @param {HTMLIFrameElement} iframe
 */
function prepareHeroIframe(iframe) {
    if (! iframe || iframe.dataset.vbHeroVideoReady === '1') {
        return;
    }

    const media = iframe.closest(HERO_MEDIA_SELECTOR);

    if (! (media instanceof HTMLElement)) {
        return;
    }

    iframe.dataset.vbHeroVideoReady = '1';
    ensureEmbedHost(media, iframe);
    ensureHitShield(media);

    iframe.classList.add('voodbuilder-hero-media__iframe', 'is-pending');

    for (const className of STRIP_CLASSES) {
        iframe.classList.remove(className);
    }

    iframe.style.removeProperty('max-width');
    iframe.style.removeProperty('max-height');
    iframe.style.removeProperty('aspect-ratio');
    iframe.style.setProperty('pointer-events', 'none', 'important');
    iframe.setAttribute('loading', 'eager');
    iframe.setAttribute('tabindex', '-1');
    iframe.setAttribute('inert', '');
    iframe.removeAttribute('allowfullscreen');

    const hardened = hardenHeroEmbedSrc(iframe.getAttribute('src') ?? '');

    if (hardened !== '' && hardened !== iframe.getAttribute('src')) {
        iframe.setAttribute('src', hardened);
    }

    sizeHeroIframeToCover(iframe);

    const reveal = () => {
        iframe.classList.remove('is-pending');
        sizeHeroIframeToCover(iframe);
    };

    const scheduleReveal = () => {
        window.setTimeout(reveal, REVEAL_DELAY_MS);
    };

    iframe.addEventListener('load', scheduleReveal, { once: true });
    window.setTimeout(reveal, REVEAL_DELAY_MS + 2500);

    const host = iframe.closest('.voodbuilder-hero-media__embed, [data-vb-embed-bg], .voodbuilder-hero-media');

    if (host instanceof HTMLElement && typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(() => {
            sizeHeroIframeToCover(iframe);
        });
        observer.observe(host);
    }
}

export function initHeroBackgroundVideos(root = document) {
    if (! root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll(`${HERO_MEDIA_SELECTOR} iframe`).forEach((node) => {
        if (node instanceof HTMLIFrameElement) {
            prepareHeroIframe(node);
        }
    });
}
