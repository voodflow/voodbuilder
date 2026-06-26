@php
    /** @var \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection $posts */
@endphp

<div class="vpress-blog-post-list">
    @foreach ($posts as $post)
        <article class="vpress-blog-post-card">
            <time class="vpress-blog-post-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j, Y') }}
            </time>
            @if ($post->category)
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-vp-text-3">{{ $post->category->name }}</p>
            @endif
            <h2 class="vpress-blog-post-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h2>
            @if ($post->excerpt)
                <p class="vpress-blog-post-excerpt">{{ $post->excerpt }}</p>
            @endif
            <a href="{{ $post->getUrl() }}" class="vpress-blog-post-read-more">
                {{ __('vpress::demo.blog.read_more') }}
            </a>
        </article>
    @endforeach
</div>
