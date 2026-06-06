@if(filled($config['features'] ?? null))
    <section class="mx-auto mb-16 max-w-6xl">
        @if(filled($config['title'] ?? null))
            <h2 class="mb-4 text-2xl font-semibold text-vp-text-1">{{ $config['title'] }}</h2>
        @endif
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($config['features'] as $feature)
                <div class="h-full rounded-xl border border-vp-divider bg-vp-bg-alt p-6">
                    @if(filled($feature['icon'] ?? null))
                        <div class="mb-3 text-[28px] leading-none">{{ $feature['icon'] }}</div>
                    @endif
                    <h3 class="mb-2 text-base font-semibold text-vp-text-1">{{ $feature['title'] ?? '' }}</h3>
                    <p class="text-sm leading-relaxed text-vp-text-2">{{ $feature['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
