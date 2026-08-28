/**
 * @jodit/image-editor plugin — click the preview to set the Focus (tilt-shift) center.
 */

import { createDefaultFocus, selectors } from '@jodit/image-editor';

/**
 * @param {() => HTMLElement | null} getContainer
 * @returns {import('@jodit/image-editor').EditorPlugin}
 */
export function createFocusPointPlugin(getContainer) {
    return {
        name: 'voodbuilder-focus-point',
        setup(api) {
            /** @type {HTMLElement | null} */
            let overlay = null;
            /** @type {HTMLElement | null} */
            let marker = null;
            /** @type {number | null} */
            let raf = null;

            const wrap = () => getContainer()?.querySelector('[data-jie-canvas-wrap]') ?? null;

            const ensureOverlay = () => {
                const canvasWrap = wrap();

                if (! canvasWrap) {
                    return null;
                }

                if (overlay?.parentElement === canvasWrap) {
                    return canvasWrap;
                }

                overlay = document.createElement('div');
                overlay.className = 'vb-jie-focus-overlay';
                overlay.hidden = true;
                overlay.setAttribute('aria-hidden', 'true');

                marker = document.createElement('div');
                marker.className = 'vb-jie-focus-marker';
                overlay.appendChild(marker);

                canvasWrap.appendChild(overlay);

                return canvasWrap;
            };

            const syncMarker = () => {
                const state = api.getState();
                const canvasWrap = ensureOverlay();

                if (! canvasWrap || ! overlay || ! marker) {
                    return;
                }

                if (state.activeTab !== 'focus') {
                    overlay.hidden = true;
                    canvasWrap.style.cursor = '';

                    return;
                }

                overlay.hidden = false;
                canvasWrap.style.cursor = 'crosshair';

                const focus = selectors.selectDesign(state).focus ?? createDefaultFocus();
                const fit = selectors.selectViewportFit(state);

                if (! fit) {
                    return;
                }

                const cx = fit.offsetX + focus.x * fit.width;
                const cy = fit.offsetY + focus.y * fit.height;

                marker.style.left = `${cx}px`;
                marker.style.top = `${cy}px`;

                if (focus.shape === 'radial') {
                    const radiusPx = focus.radius * Math.min(fit.width, fit.height);
                    marker.style.width = `${Math.max(12, radiusPx * 2)}px`;
                    marker.style.height = `${Math.max(12, radiusPx * 2)}px`;
                    marker.style.borderRadius = '9999px';
                    marker.style.transform = 'translate(-50%, -50%)';
                } else {
                    marker.style.width = `${Math.max(40, fit.width * 0.6)}px`;
                    marker.style.height = `${Math.max(8, focus.radius * fit.height * 0.35)}px`;
                    marker.style.borderRadius = '4px';
                    marker.style.transform = `translate(-50%, -50%) rotate(${focus.angle}deg)`;
                }
            };

            const scheduleSync = () => {
                if (raf !== null) {
                    cancelAnimationFrame(raf);
                }

                raf = requestAnimationFrame(() => {
                    raf = null;
                    syncMarker();
                });
            };

            /**
             * @param {PointerEvent} event
             */
            const onPointerDown = (event) => {
                const state = api.getState();

                if (state.activeTab !== 'focus' || selectors.selectIsCropping(state)) {
                    return;
                }

                const canvasWrap = wrap();

                if (! canvasWrap) {
                    return;
                }

                const fit = selectors.selectViewportFit(state);

                if (! fit) {
                    return;
                }

                const rect = canvasWrap.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                if (
                    x < fit.offsetX
                    || y < fit.offsetY
                    || x > fit.offsetX + fit.width
                    || y > fit.offsetY + fit.height
                ) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const nx = (x - fit.offsetX) / fit.width;
                const ny = (y - fit.offsetY) / fit.height;
                const focus = selectors.selectDesign(state).focus ?? createDefaultFocus();

                api.update({
                    design: {
                        focus: {
                            ...focus,
                            x: Math.min(1, Math.max(0, nx)),
                            y: Math.min(1, Math.max(0, ny)),
                        },
                    },
                    commit: true,
                });

                scheduleSync();
            };

            const container = getContainer();

            if (container) {
                container.addEventListener('pointerdown', onPointerDown, true);
            }

            const observer = container
                ? new MutationObserver(scheduleSync)
                : null;

            if (container && observer) {
                observer.observe(container, {
                    subtree: true,
                    attributes: true,
                    childList: true,
                });
            }

            const tick = window.setInterval(scheduleSync, 120);

            scheduleSync();

            return () => {
                if (container) {
                    container.removeEventListener('pointerdown', onPointerDown, true);
                }

                observer?.disconnect();

                window.clearInterval(tick);

                if (raf !== null) {
                    cancelAnimationFrame(raf);
                }

                overlay?.remove();
                overlay = null;
                marker = null;

                const canvasWrap = wrap();

                if (canvasWrap) {
                    canvasWrap.style.cursor = '';
                }
            };
        },
    };
}
