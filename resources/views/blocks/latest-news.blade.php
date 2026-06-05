<section class="VPSection">
    <div class="VPSectionHeader">
        <h2 class="VPSectionTitle">{{ __('Latest news') }}</h2>
        <a href="{{ route('blog.index') }}" class="VPSectionLink">{{ __('View all') }} →</a>
    </div>
    <div class="VPGrid">
        @forelse ($posts as $post)
            <x-ink::post-card :post="$post" />
        @empty
            <p class="VPEmpty">{{ __('No news published yet.') }}</p>
        @endforelse
    </div>
</section>
