/**
 * Frontend runtime for VoodBuilder GrapesJS utility blocks (carousel, counters, CTA, logos, …).
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

export function initVbRuntime() {
    initReadingTime();
    initSocialShare();
    initCarousels();
    initSliders();
    initAnimatedCounters();
    initAnimatedCtas();
    initViewportAnimations();
    initLogoScroll();
}

function prefersReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true;
}

function parseNumber(value, fallback = 0) {
    const parsed = Number.parseFloat(String(value ?? '').replace(/[^\d.-]/g, ''));

    return Number.isFinite(parsed) ? parsed : fallback;
}

function formatCounterValue(value, decimals, prefix, suffix) {
    const formatted = value.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return `${prefix}${formatted}${suffix}`;
}

function counterFinalLabel(node) {
    const stored = node.getAttribute('data-vb-count-label');

    if (stored) {
        return stored;
    }

    const decimals = Math.max(0, Math.min(2, Number.parseInt(node.getAttribute('data-vb-count-decimals') ?? '0', 10) || 0));
    const prefix = node.getAttribute('data-vb-count-prefix') ?? '';
    const suffix = node.getAttribute('data-vb-count-suffix') ?? '';
    const to = parseNumber(node.getAttribute('data-vb-count-to'), 0);

    return formatCounterValue(to, decimals, prefix, suffix);
}

function animateCounter(node, { force = false } = {}) {
    if (! force && node.dataset.vbCountPlayed === '1') {
        return;
    }

    if (node.__vbCountRaf) {
        window.cancelAnimationFrame(node.__vbCountRaf);
        node.__vbCountRaf = 0;
    }

    node.dataset.vbCountPlayed = '1';
    node.dataset.vbCountAnimating = '1';

    const source = node.getAttribute('data-vb-count-source') || 'static';
    const from = parseNumber(node.getAttribute('data-vb-count-from'), 0);
    let to = parseNumber(node.getAttribute('data-vb-count-to'), from);
    const duration = Math.max(200, parseNumber(node.getAttribute('data-vb-count-duration'), 1600));
    const delay = Math.max(0, parseNumber(node.getAttribute('data-vb-count-delay'), 0));
    const decimals = Math.max(0, Math.min(2, Number.parseInt(node.getAttribute('data-vb-count-decimals') ?? '0', 10) || 0));
    const prefix = node.getAttribute('data-vb-count-prefix') ?? '';
    const suffix = node.getAttribute('data-vb-count-suffix') ?? '';
    const finalLabel = counterFinalLabel(node);

    if (source === 'dynamic') {
        to = parseNumber(node.getAttribute('data-vb-count-to') || finalLabel, to);
    }

    const finish = () => {
        node.textContent = finalLabel;
        node.dataset.vbCountAnimating = '0';
        node.setAttribute('data-vb-count-label', finalLabel);
    };

    if (prefersReducedMotion()) {
        finish();

        return;
    }

    const startAt = performance.now() + delay;

    const tick = (now) => {
        if (now < startAt) {
            node.__vbCountRaf = window.requestAnimationFrame(tick);

            return;
        }

        const progress = Math.min(1, (now - startAt) / duration);
        const eased = 1 - ((1 - progress) ** 3);
        const current = from + ((to - from) * eased);

        // Visual-only update. The GrapesJS model keeps a stable label via
        // data-vb-count-label / toHTML — do not write animation frames into components().
        node.textContent = formatCounterValue(current, decimals, prefix, suffix);

        if (progress < 1) {
            node.__vbCountRaf = window.requestAnimationFrame(tick);
        } else {
            finish();
        }
    };

    node.__vbCountRaf = window.requestAnimationFrame(tick);
}

/**
 * @param {{ root?: ParentNode, force?: boolean, preferImmediate?: boolean }} [options]
 */
export function initAnimatedCounters(options = {}) {
    const root = options.root ?? document;
    const force = options.force === true;
    const preferImmediate = options.preferImmediate === true;
    const nodes = [...root.querySelectorAll('[data-voodbuilder-animated-counter]')];

    if (nodes.length === 0) {
        return;
    }

    if (force) {
        nodes.forEach((node) => {
            node.dataset.vbCountPlayed = '0';
        });
    }

    const immediate = nodes.filter((node) => {
        const trigger = node.getAttribute('data-vb-count-trigger') || 'viewport';

        return preferImmediate || trigger === 'immediate';
    });
    const deferred = nodes.filter((node) => ! immediate.includes(node));

    immediate.forEach((node) => animateCounter(node, { force }));

    if (deferred.length === 0 || typeof IntersectionObserver === 'undefined') {
        deferred.forEach((node) => animateCounter(node, { force }));

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            animateCounter(entry.target, { force });
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.2, rootMargin: '0px 0px -5% 0px' });

    deferred.forEach((node) => observer.observe(node));
}

/**
 * Style Manager “on visible” animations — play when the element enters the viewport.
 * Marker class: .vb-animate-on-visible (paired with animate-* utilities).
 *
 * @param {{ root?: ParentNode, force?: boolean, preferImmediate?: boolean }} [options]
 */
export function initViewportAnimations(options = {}) {
    const root = options.root ?? document;
    const force = options.force === true;
    const preferImmediate = options.preferImmediate === true;
    const nodes = [...root.querySelectorAll('.vb-animate-on-visible')];

    if (nodes.length === 0) {
        return;
    }

    const reveal = (node) => {
        if (force) {
            node.classList.remove('is-visible');
            void node.offsetWidth;
        }

        window.requestAnimationFrame(() => {
            node.classList.add('is-visible');
        });
    };

    if (prefersReducedMotion()) {
        nodes.forEach((node) => node.classList.add('is-visible'));

        return;
    }

    if (preferImmediate || typeof IntersectionObserver === 'undefined') {
        nodes.forEach((node) => reveal(node));

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            reveal(entry.target);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -5% 0px' });

    nodes.forEach((node) => {
        if (node.classList.contains('is-visible') && ! force) {
            return;
        }

        observer.observe(node);
    });
}

function shouldSkipEditorAnimationReplay(element) {
    if (! (element instanceof Element)) {
        return true;
    }

    if (element.closest('.vb-logo-scroll')) {
        return true;
    }

    const className = element.getAttribute('class') ?? '';

    return /(?:^|\s)animate-logo-marquee(?:\s|$)/.test(className);
}

/**
 * Restart Tailwind `animate-*` keyframes on a subtree (editor canvas preview).
 *
 * @param {ParentNode} root
 */
export function restartCssKeyframeAnimations(root) {
    const scope = root ?? document;

    scope.querySelectorAll('[class*="animate-"]').forEach((element) => {
        if (shouldSkipEditorAnimationReplay(element)) {
            return;
        }

        const className = element.getAttribute('class') ?? '';

        if (! /(?:^|\s)(?:hover:|active:)?animate-[\w-]+/.test(className)) {
            return;
        }

        element.style.animation = 'none';
        void element.offsetWidth;
        element.style.removeProperty('animation');
    });
}

/**
 * Editor-only: reveal on-visible markers and replay Style Manager animations
 * so fade/slide effects are visible while authoring.
 *
 * @param {{ root?: ParentNode }} [options]
 */
export function replayEditorCanvasAnimations(options = {}) {
    const root = options.root ?? document;

    initViewportAnimations({ root, force: true, preferImmediate: true });
    restartCssKeyframeAnimations(root);
}

/**
 * Editor load / large template apply: show the final motion state without
 * restarting keyframes or spinning counters (avoids jank + touchstart spam).
 *
 * @param {{ root?: ParentNode }} [options]
 */
export function settleEditorCanvasPreview(options = {}) {
    const root = options.root ?? document;

    root.querySelectorAll('[data-voodbuilder-animated-cta]').forEach((node) => {
        node.classList.add('is-visible');
    });

    root.querySelectorAll('.vb-animate-on-visible').forEach((node) => {
        node.classList.add('is-visible');
    });

    root.querySelectorAll('[data-voodbuilder-animated-counter]').forEach((node) => {
        if (node.__vbCountRaf) {
            window.cancelAnimationFrame(node.__vbCountRaf);
            node.__vbCountRaf = 0;
        }

        node.dataset.vbCountPlayed = '1';
        node.dataset.vbCountAnimating = '0';
        node.textContent = counterFinalLabel(node);
    });
}

/**
 * @param {{ root?: ParentNode, force?: boolean }} [options]
 */
export function initAnimatedCtas(options = {}) {
    const root = options.root ?? document;
    const force = options.force === true;
    const nodes = [...root.querySelectorAll('[data-voodbuilder-animated-cta]')];

    if (nodes.length === 0) {
        return;
    }

    const reveal = (node) => {
        const duration = Math.max(200, parseNumber(node.getAttribute('data-vb-anim-duration'), 700));
        const delay = Math.max(0, parseNumber(node.getAttribute('data-vb-anim-delay'), 0));

        node.style.setProperty('--vb-anim-duration', `${duration}ms`);
        node.style.setProperty('--vb-anim-delay', `${delay}ms`);

        if (force) {
            node.classList.remove('is-visible');
            // Force reflow so CSS transition replays in the editor canvas.
            void node.offsetWidth;
        }

        window.requestAnimationFrame(() => {
            node.classList.add('is-visible');
        });
    };

    if (prefersReducedMotion()) {
        nodes.forEach((node) => node.classList.add('is-visible'));

        return;
    }

    if (typeof IntersectionObserver === 'undefined') {
        nodes.forEach((node) => reveal(node));

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            reveal(entry.target);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    nodes.forEach((node) => {
        if (force) {
            // Editor canvas iframes often miss the first intersection; replay immediately.
            reveal(node);

            return;
        }

        observer.observe(node);
    });
}

function ensureLogoScrollStructure(root) {
    let mover = root.querySelector('[data-vb-logo-mover], .vb-logo-scroll__mover');
    let track = root.querySelector('[data-vb-items-root], .vb-logo-scroll__track:not(.vb-logo-scroll__track--clone)');

    if (! track) {
        return null;
    }

    if (! mover) {
        mover = document.createElement('div');
        mover.className = 'vb-logo-scroll__mover flex w-max items-center animate-logo-marquee [animation-duration:var(--logo-marquee-duration,28s)] hover:[animation-play-state:paused]';
        mover.setAttribute('data-vb-logo-mover', '');
        track.parentNode?.insertBefore(mover, track);
        mover.appendChild(track);
    }

    track.querySelectorAll('.vb-logo-scroll__item').forEach((item) => {
        if (item.querySelector('a.vb-logo-scroll__link')) {
            return;
        }

        const image = item.querySelector('img');

        if (! image) {
            return;
        }

        const link = document.createElement('a');
        link.className = 'vb-logo-scroll__link inline-flex items-center justify-center';
        link.href = '#';
        link.title = image.getAttribute('alt') || 'Partner logo';
        image.replaceWith(link);
        link.appendChild(image);
    });

    return { mover, track };
}

function duplicateLogoTrack(root) {
    const structure = ensureLogoScrollStructure(root);

    if (! structure) {
        return;
    }

    const { mover, track } = structure;

    // Drop previous runtime clones (never part of the GrapesJS model).
    mover.querySelectorAll('.vb-logo-scroll__track--clone').forEach((node) => node.remove());

    const containerWidth = Math.max(root.clientWidth || 0, root.parentElement?.clientWidth || 0, 320);
    const sourceWidth = Math.max(track.scrollWidth || 0, 1);

    // Repeat the logo set until we cover at least 2× the viewport (seamless loop at -50%).
    let copies = 1;
    while ((sourceWidth * copies) < (containerWidth * 2) && copies < 12) {
        copies += 1;
    }

    // Need an even number of track copies so -50% lands on a clean boundary.
    if (copies % 2 === 1) {
        copies += 1;
    }

    for (let index = 1; index < copies; index += 1) {
        const clone = track.cloneNode(true);
        clone.removeAttribute('data-vb-items-root');
        clone.setAttribute('aria-hidden', 'true');
        clone.setAttribute('data-vb-logo-clone', '1');
        clone.classList.add('vb-logo-scroll__track--clone', 'pointer-events-none');
        mover.appendChild(clone);
    }

    // Restart Tailwind animation from the left.
    mover.style.animation = 'none';
    void mover.offsetWidth;
    mover.style.removeProperty('animation');
    root.dataset.vbLogoReady = '1';
}

/**
 * @param {{ root?: ParentNode }} [options]
 */
export function initLogoScroll(options = {}) {
    const root = options.root ?? document;

    root.querySelectorAll('[data-voodbuilder-logo-scroll]').forEach((node) => {
        const speed = node.getAttribute('data-vb-logo-speed') || 'normal';
        const direction = node.getAttribute('data-vb-logo-direction') || 'left';
        const pauseHover = node.getAttribute('data-vb-logo-pause-hover') !== '0';
        const duration = speed === 'slow' ? 45 : speed === 'fast' ? 14 : 28;

        node.style.setProperty('--logo-marquee-duration', `${duration}s`);
        node.classList.add('overflow-hidden', 'w-full');

        const mover = node.querySelector('[data-vb-logo-mover], .vb-logo-scroll__mover');

        if (mover) {
            mover.classList.add(
                'animate-logo-marquee',
                '[animation-duration:var(--logo-marquee-duration,28s)]',
            );
            mover.classList.toggle('[animation-direction:reverse]', direction === 'right');
            mover.classList.toggle('hover:[animation-play-state:paused]', pauseHover);

            if (direction === 'right') {
                mover.style.animationDirection = 'reverse';
            } else {
                mover.style.removeProperty('animation-direction');
            }
        }

        node.dataset.vbLogoReady = '0';

        const refresh = () => {
            duplicateLogoTrack(node);
        };

        // Wait a frame so layout widths are available (esp. after editor drop).
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(refresh);
        });

        if (typeof ResizeObserver !== 'undefined' && ! node.__vbLogoResizeObserver) {
            const observer = new ResizeObserver(() => refresh());
            observer.observe(node);
            node.__vbLogoResizeObserver = observer;
        }
    });
}
