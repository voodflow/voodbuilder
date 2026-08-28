@php
    /** @var array<string, mixed> $config */
    /** @var list<array{url: string, alt: string, caption: string}> $slides */
    $layout = (string) ($config['layout'] ?? 'grid');
    $columns = max(1, min(6, (int) ($config['columns'] ?? 3)));
    $showCaptions = (bool) ($config['show_captions'] ?? true);
    $lightbox = (bool) ($config['lightbox'] ?? true);
    $heading = filled($config['heading'] ?? null) ? (string) $config['heading'] : null;
    $galleryDescription = filled($galleryDescription ?? $config['gallery_description'] ?? null)
        ? trim(strip_tags((string) ($galleryDescription ?? $config['gallery_description'])))
        : null;

    $galleryStyle = "--vb-media-cols: {$columns};";
@endphp

@once
    <style>
        .vb-media-gallery[data-vb-layout='grid'],
        .vb-media-gallery[data-vb-layout='masonry'] {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(var(--vb-media-cols, 3), minmax(0, 1fr));
        }

        .vb-media-gallery[data-vb-layout='grid'] .vb-media-gallery__thumb img,
        .vb-media-gallery[data-vb-layout='grid'] > figure img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
        }

        .vb-media-gallery[data-vb-layout='masonry'] .vb-media-gallery__thumb img,
        .vb-media-gallery[data-vb-layout='masonry'] > figure img {
            display: block;
            width: 100%;
            height: auto;
        }

        .vb-media-gallery__thumb {
            padding: 0;
            border: none;
            background: transparent;
        }
    </style>
@endonce

@if ($slides === [])
    <section class="voodbuilder-editor-section">
        <div
            class="voodbuilder-editor-container rounded-2xl bg-vp-bg-alt p-8 text-center text-sm text-vp-text-2 ring-1 ring-black/5"
            data-voodbuilder-role="content"
            data-voodbuilder-content-width="normal"
        >
            <p>{{ __('voodbuilder::blocks.media_collection.empty') }}</p>
        </div>
    </section>
@else
    <section
        class="voodbuilder-editor-section vx-media-collection"
        @if ($lightbox) data-vx-gallery @endif
    >
        <div
            class="voodbuilder-editor-container w-full"
            data-voodbuilder-role="content"
            data-voodbuilder-content-width="normal"
        >
            @if ($heading)
                <h2 class="mb-4 text-2xl font-semibold text-vp-text-1" data-voodbuilder-name="Heading">{{ $heading }}</h2>
            @endif

            @if ($galleryDescription)
                <p class="{{ $heading ? 'mb-6' : 'mb-4' }} text-base leading-relaxed text-vp-text-2" data-voodbuilder-name="Gallery description">
                    {{ $galleryDescription }}
                </p>
            @endif

            <div
                class="vb-media-gallery"
                style="{{ $galleryStyle }}"
                data-vb-layout="{{ $layout }}"
                data-vb-columns="{{ $columns }}"
                data-voodbuilder-role="gallery"
                data-voodbuilder-name="Gallery"
            >
                @foreach ($slides as $index => $slide)
                    @if ($lightbox)
                        <div
                            role="button"
                            tabindex="0"
                            class="vb-media-gallery__thumb cursor-zoom-in overflow-hidden rounded-xl ring-1 ring-black/5"
                            data-vx-gallery-index="{{ $index }}"
                            data-voodbuilder-skip-cta="true"
                            data-voodbuilder-name="Gallery item"
                            aria-label="{{ $slide['alt'] }}"
                        >
                            <img
                                src="{{ $slide['url'] }}"
                                alt="{{ $slide['alt'] }}"
                                loading="lazy"
                                class="{{ $layout === 'masonry' ? 'block h-auto w-full' : 'block h-auto w-full object-cover' }}"
                            >
                        </div>
                    @else
                        <figure
                            class="overflow-hidden rounded-xl ring-1 ring-black/5"
                            data-voodbuilder-name="Gallery item"
                        >
                            <img
                                src="{{ $slide['url'] }}"
                                alt="{{ $slide['alt'] }}"
                                loading="lazy"
                                class="{{ $layout === 'masonry' ? 'block h-auto w-full' : 'block h-auto w-full object-cover' }}"
                            >
                            @if ($showCaptions && filled($slide['caption'] ?? null))
                                <figcaption class="px-3 py-2 text-sm text-vp-text-2">{{ $slide['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endif
                @endforeach
            </div>

            @if ($lightbox)
                <dialog
                    class="vx-gallery-dialog"
                    data-vx-gallery-dialog
                    data-voodbuilder-skip-cta="true"
                    aria-label="{{ $heading ?? __('voodbuilder::blocks.media_collection.gallery') }}"
                >
                    <div class="vx-gallery-dialog__viewport">
                        <button
                            type="button"
                            class="vx-gallery-dialog__close"
                            data-vx-gallery-close
                            data-voodbuilder-skip-cta="true"
                            aria-label="{{ __('voodbuilder::blocks.media_collection.close') }}"
                        >&times;</button>
                        <div class="vx-gallery-dialog__row">
                            <button
                                type="button"
                                class="vx-gallery-dialog__nav vx-gallery-dialog__nav--prev"
                                data-vx-gallery-prev
                                data-voodbuilder-skip-cta="true"
                                aria-label="{{ __('voodbuilder::blocks.media_collection.prev') }}"
                            >&lsaquo;</button>
                            <div class="vx-gallery-dialog__stage">
                                <img src="" alt="" class="vx-gallery-dialog__image" data-vx-gallery-image>
                                <p class="vx-gallery-dialog__caption" data-vx-gallery-caption hidden></p>
                            </div>
                            <button
                                type="button"
                                class="vx-gallery-dialog__nav vx-gallery-dialog__nav--next"
                                data-vx-gallery-next
                                data-voodbuilder-skip-cta="true"
                                aria-label="{{ __('voodbuilder::blocks.media_collection.next') }}"
                            >&rsaquo;</button>
                        </div>
                    </div>
                </dialog>

                <script type="application/json" data-vx-gallery-data>@json($slides)</script>
            @endif
        </div>
    </section>

    @if ($lightbox)
        @once
            @push('head')
                <style>
                    .vx-gallery-dialog {
                        padding: 0;
                        border: none;
                        background: transparent;
                        margin: 0;
                        max-width: none;
                        width: 100vw;
                        height: 100vh;
                        overflow: visible;
                    }

                    .vx-gallery-dialog:not([open]) {
                        display: none;
                    }

                    .vx-gallery-dialog[open] {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .vx-gallery-dialog::backdrop {
                        background: rgba(15, 23, 42, 0.88);
                    }

                    .vx-gallery-dialog__viewport {
                        position: relative;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        height: 100%;
                        padding: clamp(1rem, 3vw, 2rem);
                        box-sizing: border-box;
                    }

                    .vx-gallery-dialog__row {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: clamp(0.75rem, 2vw, 1.5rem);
                        width: min(96vw, 1200px);
                        max-height: 90vh;
                    }

                    .vx-gallery-dialog__stage {
                        flex: 1;
                        min-width: 0;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                        border-radius: 1rem;
                        background: rgba(15, 23, 42, 0.35);
                        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
                    }

                    .vx-gallery-dialog__image {
                        display: block;
                        width: 100%;
                        max-width: 100%;
                        max-height: min(75vh, calc(90vh - 4rem));
                        margin: 0 auto;
                        object-fit: contain;
                    }

                    .vx-gallery-dialog__caption {
                        margin: 0;
                        padding: 0.875rem 1.25rem;
                        text-align: center;
                        font-size: 0.9375rem;
                        line-height: 1.5;
                        font-weight: 500;
                        color: #fff;
                        background: rgba(0, 0, 0, 0.78);
                    }

                    .vx-gallery-dialog__caption[hidden] {
                        display: none;
                    }

                    .vx-gallery-dialog__close {
                        position: absolute;
                        top: clamp(0.75rem, 2vw, 1.25rem);
                        right: clamp(0.75rem, 2vw, 1.25rem);
                        z-index: 2;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 2.75rem;
                        height: 2.75rem;
                        border: none;
                        border-radius: 999px;
                        background: rgba(0, 0, 0, 0.55);
                        font-size: 1.75rem;
                        line-height: 1;
                        cursor: pointer;
                        color: #fff;
                    }

                    .vx-gallery-dialog__close:hover {
                        background: rgba(0, 0, 0, 0.72);
                    }

                    .vx-gallery-dialog__nav {
                        flex-shrink: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border: none;
                        background: rgba(255, 255, 255, 0.95);
                        width: 3rem;
                        height: 3rem;
                        border-radius: 999px;
                        font-size: 1.75rem;
                        line-height: 1;
                        cursor: pointer;
                        color: #0f172a;
                        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
                    }

                    .vx-gallery-dialog__nav:hover {
                        background: #fff;
                    }

                    @media (max-width: 640px) {
                        .vx-gallery-dialog__row {
                            gap: 0.5rem;
                        }

                        .vx-gallery-dialog__nav {
                            width: 2.5rem;
                            height: 2.5rem;
                            font-size: 1.5rem;
                        }
                    }
                </style>
            @endpush

            @push('scripts')
                <script>
                    document.querySelectorAll('[data-vx-gallery]').forEach((root) => {
                        const dataEl = root.querySelector('[data-vx-gallery-data]');
                        let dialog = root.querySelector('[data-vx-gallery-dialog]');

                        if (!dataEl || !dialog) {
                            return;
                        }

                        if (dialog.parentElement !== document.body) {
                            document.body.appendChild(dialog);
                        }

                        const image = dialog.querySelector('[data-vx-gallery-image]');
                        const caption = dialog.querySelector('[data-vx-gallery-caption]');
                        const closeButton = dialog.querySelector('[data-vx-gallery-close]');
                        const prevButton = dialog.querySelector('[data-vx-gallery-prev]');
                        const nextButton = dialog.querySelector('[data-vx-gallery-next]');

                        if (!image || !caption) {
                            return;
                        }

                        const slides = JSON.parse(dataEl.textContent || '[]');
                        let index = 0;

                        const render = () => {
                            const slide = slides[index];

                            if (!slide) {
                                return;
                            }

                            image.src = slide.url;
                            image.alt = slide.alt;
                            const captionText = slide.caption || slide.alt || '';
                            caption.textContent = captionText;
                            caption.hidden = captionText === '';
                        };

                        const open = (nextIndex) => {
                            index = nextIndex;
                            render();

                            if (typeof dialog.showModal === 'function') {
                                dialog.showModal();
                            }
                        };

                        root.querySelectorAll('[data-vx-gallery-index]').forEach((trigger) => {
                            trigger.addEventListener('click', () => open(Number(trigger.dataset.vxGalleryIndex || 0)));
                            trigger.addEventListener('keydown', (event) => {
                                if (event.key === 'Enter' || event.key === ' ') {
                                    event.preventDefault();
                                    open(Number(trigger.dataset.vxGalleryIndex || 0));
                                }
                            });
                        });

                        closeButton?.addEventListener('click', () => dialog.close());
                        prevButton?.addEventListener('click', () => {
                            index = (index - 1 + slides.length) % slides.length;
                            render();
                        });
                        nextButton?.addEventListener('click', () => {
                            index = (index + 1) % slides.length;
                            render();
                        });

                        dialog.addEventListener('click', (event) => {
                            if (event.target === dialog) {
                                dialog.close();
                            }
                        });

                        dialog.addEventListener('keydown', (event) => {
                            if (event.key === 'ArrowLeft') {
                                event.preventDefault();
                                index = (index - 1 + slides.length) % slides.length;
                                render();
                            }

                            if (event.key === 'ArrowRight') {
                                event.preventDefault();
                                index = (index + 1) % slides.length;
                                render();
                            }
                        });
                    });
                </script>
            @endpush
        @endonce
    @endif
@endif
