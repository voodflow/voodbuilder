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

        if (event.type === 'keydown') {
            event.preventDefault();
        }

        activateVideoFacade(facade);
    };

    facade.addEventListener('click', onActivate);
    facade.addEventListener('keydown', onActivate);
}

export function initVideoFacades(root = document) {
    if (! root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('[data-vb-video-facade]').forEach((facade) => {
        bindVideoFacade(facade);
    });
}
