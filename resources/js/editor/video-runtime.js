/**
 * Click-to-play for video facades on published pages.
 */

function activateVideoFacade(facade) {
    if (! facade || facade.dataset.vbVideoActivated === '1') {
        return;
    }

    const embedSrc = facade.dataset.vbEmbedSrc ?? '';

    if (embedSrc === '') {
        return;
    }

    facade.dataset.vbVideoActivated = '1';
    facade.removeAttribute('role');
    facade.removeAttribute('tabindex');
    facade.removeAttribute('aria-label');

    const iframe = document.createElement('iframe');
    iframe.src = embedSrc;
    iframe.setAttribute('allowfullscreen', 'allowfullscreen');
    iframe.setAttribute(
        'allow',
        'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
    );
    iframe.setAttribute('frameborder', '0');
    iframe.className = 'vb-video-facade__iframe';
    iframe.title = 'Video player';

    facade.replaceChildren(iframe);
}

function bindVideoFacade(facade) {
    if (! facade || facade.dataset.vbVideoReady === '1') {
        return;
    }

    facade.dataset.vbVideoReady = '1';

    const onActivate = (event) => {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        // Facades often sit inside card <a href="#"> wrappers (Featured stories).
        // Without this, play both starts the video and jumps the page to the top.
        event.preventDefault();
        event.stopPropagation();

        activateVideoFacade(facade);
    };

    facade.addEventListener('click', onActivate);
    facade.addEventListener('keydown', onActivate);
}

/**
 * Native <video controls> / hosts nested in placeholder hash links also navigate
 * to "#" when the play control is clicked — cancel those navigations in capture.
 *
 * @param {ParentNode} root
 */
function bindMediaInsideHashLinks(root) {
    if (! root?.addEventListener || root.dataset?.vbMediaHashGuard === '1') {
        return;
    }

    if (root instanceof HTMLElement) {
        root.dataset.vbMediaHashGuard = '1';
    } else if (root === document) {
        document.documentElement.dataset.vbMediaHashGuard = '1';
    }

    root.addEventListener('click', (event) => {
        const target = event.target;

        if (! (target instanceof Element)) {
            return;
        }

        const media = target.closest(
            '[data-vb-video-facade], .vb-video-facade, .vb-video-host, video, audio, iframe[src*="youtube"], iframe[src*="youtube-nocookie"], iframe[src*="vimeo"]',
        );

        if (! media) {
            return;
        }

        const link = media.closest('a[href="#"], a[href=""], a[href="/#"]');

        if (! link) {
            return;
        }

        event.preventDefault();
    }, true);
}

export function initVideoFacades(root = document) {
    if (! root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('[data-vb-video-facade]').forEach((facade) => {
        bindVideoFacade(facade);
    });

    bindMediaInsideHashLinks(root);
}
