<section class="VPSection">
    <div class="VPSectionHeader">
        <h2 class="VPSectionTitle">{{ __('Latest tutorials') }}</h2>
        <a href="{{ route('tutorials.index') }}" class="VPSectionLink">{{ __('View all') }} →</a>
    </div>
    <div @class(['VPGrid', 'VPGrid--cols-' . ($columns ?? 3)])>
        @forelse ($tutorials as $tutorial)
            <x-tutorials::tutorial-card :tutorial="$tutorial" variant="grid" />
        @empty
            <p class="VPEmpty">{{ __('No tutorials published yet.') }}</p>
        @endforelse
    </div>
</section>
