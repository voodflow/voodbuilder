@php
    $columns = (int) ($columns ?? 3);
    $gridCols = match (true) {
        $columns >= 4 => 'sm:grid-cols-2 lg:grid-cols-4',
        $columns === 2 => 'sm:grid-cols-2',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
    $tutorialsIndexUrl = class_exists(\Voodflow\Vtuts\Support\VtutUrls::class)
        ? \Voodflow\Vtuts\Support\VtutUrls::index()
        : route('vtuts.index');
@endphp

<section class="mt-16">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h2 class="text-2xl font-semibold text-vp-text-1">{{ __('vpress::home.latest_vtuts') }}</h2>
        <a
            href="{{ $tutorialsIndexUrl }}"
            class="shrink-0 text-sm font-medium text-vp-brand-1 transition-colors hover:text-vp-brand-2"
        >
            {{ __('vpress::home.view_all') }} →
        </a>
    </div>

    <div @class(['grid grid-cols-1 gap-5', $gridCols])>
        @forelse ($tutorials as $tutorial)
            <x-vtuts::tutorial-card :tutorial="$tutorial" variant="grid" />
        @empty
            <p class="text-sm text-vp-text-2">{{ __('vpress::home.no_vtuts') }}</p>
        @endforelse
    </div>
</section>
