@if (filled($config['packages'] ?? null))
    <section class="mx-auto mb-16 max-w-6xl">
        @if (filled($config['title'] ?? null))
            <h2 class="mb-6 text-center text-2xl font-semibold text-vp-text-1">{{ $config['title'] }}</h2>
        @endif
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($config['packages'] as $package)
                <article class="flex h-full flex-col rounded-xl border border-vp-divider bg-vp-bg-alt p-6 text-center">
                    @if (filled($package['title'] ?? null))
                        <h3 class="text-lg font-semibold text-vp-text-1">{{ $package['title'] }}</h3>
                    @endif
                    @if (filled($package['text'] ?? null))
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-vp-text-2">{{ $package['text'] }}</p>
                    @endif
                    @if (filled($package['button_label'] ?? null) && filled($package['button_url'] ?? null))
                        <a
                            href="{{ $package['button_url'] }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-6 inline-flex h-10 items-center justify-center self-center rounded-full border border-transparent bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
                        >
                            {{ $package['button_label'] }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
