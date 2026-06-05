<section class="VPSection">
    <div class="VPSectionHeader">
        <h2 class="VPSectionTitle">{{ __('vpress::home.latest_tutorials') }}</h2>
        @php
            $tutorialsIndexUrl = class_exists(\Voodflow\Tutorials\Support\TutorialUrls::class)
                ? \Voodflow\Tutorials\Support\TutorialUrls::index()
                : route('tutorials.index');
        @endphp
        <a href="{{ $tutorialsIndexUrl }}" class="VPSectionLink">{{ __('vpress::home.view_all') }} →</a>
    </div>
    <div @class(['VPGrid', 'VPGrid--cols-' . ($columns ?? 3)])>
        @forelse ($tutorials as $tutorial)
            <x-tutorials::tutorial-card :tutorial="$tutorial" variant="grid" />
        @empty
            <p class="VPEmpty">{{ __('vpress::home.no_tutorials') }}</p>
        @endforelse
    </div>
</section>
