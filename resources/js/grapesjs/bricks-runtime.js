/**
 * Frontend runtime for Bricks-style GrapesJS blocks.
 */

function wordsInElement(element) {
    const text = element?.innerText ?? element?.textContent ?? '';

    return text.trim().split(/\s+/).filter(Boolean).length;
}

function formatReadingTime(minutes) {
    const value = Math.max(1, Math.round(minutes));

    return `${value} min read`;
}

export function initReadingTime() {
    document.querySelectorAll('[data-voodbuilder-reading-time]').forEach((node) => {
        const article = node.closest('[data-voodbuilder-article]')
            ?? document.querySelector('[data-voodbuilder-article], [data-doc-article], [data-vdocs-article], article');

        const wordsPerMinute = Number.parseInt(node.getAttribute('data-vb-words-per-minute') ?? '200', 10);
        const wpm = Number.isFinite(wordsPerMinute) && wordsPerMinute > 0 ? wordsPerMinute : 200;
        const words = wordsInElement(article ?? document.body);
        const minutes = words / wpm;

        node.textContent = formatReadingTime(minutes);
    });
}

function buildShareUrl(network, pageUrl, title) {
    const encodedUrl = encodeURIComponent(pageUrl);
    const encodedTitle = encodeURIComponent(title);

    switch (network) {
        case 'facebook':
            return `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
        case 'x':
            return `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
        case 'linkedin':
            return `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
        case 'whatsapp':
            return `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`;
        case 'email':
            return `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
        default:
            return '#';
    }
}

export function initSocialShare() {
    const pageUrl = window.location.href;
    const title = document.title;

    document.querySelectorAll('[data-voodbuilder-social-share]').forEach((root) => {
        const shareUrl = root.getAttribute('data-share-url')?.trim() || pageUrl;

        root.setAttribute('data-share-url', shareUrl);

        root.querySelectorAll('[data-network]').forEach((control) => {
            const network = control.getAttribute('data-network');

            if (! network) {
                return;
            }

            if (network === 'copy_link') {
                control.setAttribute('data-copy-url', shareUrl);

                return;
            }

            if (control.tagName === 'A') {
                control.setAttribute('href', buildShareUrl(network, shareUrl, title));
            }
        });
    });

    if (window.__vbSocialShareCopyInit) {
        return;
    }

    window.__vbSocialShareCopyInit = true;

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-network="copy_link"]');

        if (! button || ! navigator.clipboard) {
            return;
        }

        const url = button.getAttribute('data-copy-url') || pageUrl;

        event.preventDefault();
        navigator.clipboard.writeText(url).then(() => {
            const original = button.textContent;
            button.textContent = button.getAttribute('data-copied-label') || 'Copied';
            window.setTimeout(() => {
                button.textContent = original;
            }, 2000);
        });
    });
}

function scrollCarousel(track, direction) {
    const slide = track.querySelector('.vb-carousel__slide');

    if (! slide) {
        return;
    }

    const amount = slide.getBoundingClientRect().width + 16;

    track.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

export function initCarousels() {
    document.querySelectorAll('[data-voodbuilder-carousel]').forEach((root) => {
        const track = root.querySelector('.vb-carousel__track');

        if (! track || root.dataset.vbCarouselReady === '1') {
            return;
        }

        root.dataset.vbCarouselReady = '1';

        root.querySelector('[data-carousel-prev]')?.addEventListener('click', () => scrollCarousel(track, -1));
        root.querySelector('[data-carousel-next]')?.addEventListener('click', () => scrollCarousel(track, 1));
    });
}

export function initSliders() {
    document.querySelectorAll('[data-voodbuilder-slider]').forEach((root) => {
        if (root.dataset.vbSliderReady === '1') {
            return;
        }

        root.dataset.vbSliderReady = '1';

        const slides = [...root.querySelectorAll('.vb-slider__slide')];
        const dots = [...root.querySelectorAll('.vb-slider__dot')];

        const activate = (index) => {
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === index);
                slide.hidden = slideIndex !== index;
            });
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
                dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
            });
        };

        const initial = slides.findIndex((slide) => slide.classList.contains('is-active'));

        activate(initial >= 0 ? initial : 0);

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const index = Number.parseInt(dot.getAttribute('data-slider-dot') ?? '0', 10);

                activate(Number.isFinite(index) ? index : 0);
            });
        });
    });
}

export function initBricksRuntime() {
    initReadingTime();
    initSocialShare();
    initCarousels();
    initSliders();
}
