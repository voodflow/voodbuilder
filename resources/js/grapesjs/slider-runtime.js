/**
 * Scroll-snap slider runtime for `.voodbuilder-slider` blocks (image/video carousels).
 *
 * Bundling: imported by `vb-runtime.js` → `initVbRuntime()` → called from `site-runtime.js`
 * on DOMContentLoaded / livewire:navigated. No separate Vite entry is required.
 */

/**
 * @param {HTMLElement} track
 * @param {number} direction
 */
function scrollSliderTrack(track, direction) {
    const slide = track.querySelector('.voodbuilder-slider__slide');

    if (! slide) {
        return;
    }

    const styles = window.getComputedStyle(track);
    const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 16;
    const amount = slide.getBoundingClientRect().width + gap;

    track.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

/**
 * @param {HTMLElement} root
 */
function wireScrollSlider(root) {
    if (root.dataset.vbScrollSliderReady === '1') {
        return;
    }

    const track = root.querySelector('.voodbuilder-slider__track');

    if (! track) {
        return;
    }

    root.dataset.vbScrollSliderReady = '1';

    root.querySelector('[data-vb-slider-prev]')?.addEventListener('click', () => scrollSliderTrack(track, -1));
    root.querySelector('[data-vb-slider-next]')?.addEventListener('click', () => scrollSliderTrack(track, 1));

    if (root.getAttribute('data-vb-slider-autoplay') !== '1') {
        return;
    }

    const intervalMs = Math.max(
        2000,
        Number.parseInt(root.getAttribute('data-vb-slider-interval') ?? '5000', 10) || 5000,
    );

    window.setInterval(() => {
        const slides = [...track.querySelectorAll('.voodbuilder-slider__slide')];

        if (slides.length < 2) {
            return;
        }

        const trackRect = track.getBoundingClientRect();
        const currentIndex = slides.findIndex((slide) => {
            const rect = slide.getBoundingClientRect();

            return rect.left >= trackRect.left - 4 && rect.left <= trackRect.left + 8;
        });
        const nextIndex = currentIndex >= 0 && currentIndex < slides.length - 1 ? currentIndex + 1 : 0;
        const target = slides[nextIndex];

        if (target) {
            track.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
        }
    }, intervalMs);
}

export function initScrollSliders() {
    document.querySelectorAll('.voodbuilder-slider[data-voodbuilder-slider]').forEach((root) => {
        wireScrollSlider(root);
    });
}
