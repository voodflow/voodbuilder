@php
    use Voodflow\Vpress\Support\LandingBlockContent;

    $section = LandingBlockContent::section($config, ['section_width' => 'contained'], 'light');
    $heading = $config['heading'] ?? __('vpress::landing.social.default_heading');
@endphp

<section
    class="vp-landing-social-share {{ $section['shell'] }} {{ $section['corners'] }} {{ $section['appearance']['class'] }}"
    @if ($section['appearance']['style'] !== '') style="{{ $section['appearance']['style'] }}" @endif
>
    <div class="mx-auto flex max-w-4xl flex-col gap-4 px-6 py-8 md:px-10 {{ $section['align'] }}">
        @if (filled($heading))
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] opacity-80">{{ $heading }}</h2>
        @endif

        @if ($links !== [])
            <div
                class="vp-social-links flex flex-wrap gap-2 {{ ($config['text_align'] ?? 'center') === 'left' ? 'justify-start' : 'justify-center' }}"
                data-share-url="{{ $shareUrl }}"
            >
                @foreach ($links as $link)
                    @if ($link['key'] === 'copy_link')
                        <button
                            type="button"
                            class="vp-share-copy inline-flex items-center rounded-md border border-current/20 px-4 py-2 text-sm font-semibold transition hover:border-current/40"
                            data-copy-url="{{ $shareUrl }}"
                            data-copied-label="{{ __('vpress::landing.social.copied') }}"
                        >
                            {{ $link['label'] }}
                        </button>
                    @elseif (filled($link['url']))
                        <a
                            href="{{ $link['url'] }}"
                            class="inline-flex items-center rounded-md border border-current/20 px-4 py-2 text-sm font-semibold transition hover:border-current/40"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>

<script>
    (function () {
        if (window.__vpShareCopyInit) {
            return;
        }

        window.__vpShareCopyInit = true;

        document.addEventListener('click', (event) => {
            const button = event.target.closest('.vp-share-copy');

            if (!button) {
                return;
            }

            const url = button.dataset.copyUrl;

            if (!url || !navigator.clipboard) {
                return;
            }

            event.preventDefault();

            navigator.clipboard.writeText(url).then(() => {
                const original = button.textContent;
                button.textContent = button.dataset.copiedLabel || original;
                window.setTimeout(() => {
                    button.textContent = original;
                }, 2000);
            });
        });
    })();
</script>
