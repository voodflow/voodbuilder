@php
    $primaryUrl = $config['primary_url'] ?? config('cosmolab.docs_url');
    $secondaryUrl = $config['secondary_url'] ?? config('cosmolab.shop_url');
@endphp

<div class="py-12 text-center md:py-16">
    <p class="bg-gradient-to-br from-vp-brand-1 to-sky-400 bg-clip-text text-[56px] leading-[1.1] font-bold text-transparent">{{ $config['name'] ?? config('app.name') }}</p>
    <p class="text-[56px] leading-[1.1] font-bold text-vp-text-1">{{ $config['headline'] ?? '' }}</p>
    @if(filled($config['tagline'] ?? null))
        <p class="mx-auto mt-4 max-w-xl text-xl text-vp-text-2">{{ $config['tagline'] }}</p>
    @endif
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        @if(filled($config['primary_label'] ?? null) && filled($primaryUrl))
            <a
                href="{{ $primaryUrl }}"
                @if(str_starts_with($primaryUrl, 'http')) target="_blank" rel="noopener" @endif
                class="inline-flex h-10 items-center rounded-full border border-transparent bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
            >
                {{ $config['primary_label'] }}
            </a>
        @endif
        @if(filled($config['secondary_label'] ?? null) && filled($secondaryUrl))
            <a
                href="{{ $secondaryUrl }}"
                target="_blank"
                rel="noopener"
                class="inline-flex h-10 items-center rounded-full border border-transparent bg-[#ebebef] px-5 text-sm font-medium text-vp-text-1 transition-colors hover:bg-[#e4e4e9] dark:bg-vp-bg-alt dark:hover:bg-vp-divider"
            >
                {{ $config['secondary_label'] }}
            </a>
        @endif
    </div>
</div>
