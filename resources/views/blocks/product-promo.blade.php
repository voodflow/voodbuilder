@php
    $buttonUrl = $config['button_url'] ?? null;
@endphp

<section class="mx-auto mt-8 max-w-3xl rounded-xl border border-vp-divider bg-vp-bg-alt px-6 py-8 text-center">
    @if (filled($config['title'] ?? null))
        <h2 class="text-2xl font-semibold text-vp-text-1">{{ $config['title'] }}</h2>
    @endif
    @if (filled($config['text'] ?? null))
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-vp-text-2 md:text-base">{{ $config['text'] }}</p>
    @endif
    @if (filled($config['button_label'] ?? null) && filled($buttonUrl))
        <a
            href="{{ $buttonUrl }}"
            target="_blank"
            rel="noopener"
            class="mt-6 inline-flex h-10 items-center rounded-full border border-transparent bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
        >
            {{ $config['button_label'] }}
        </a>
    @endif
</section>
