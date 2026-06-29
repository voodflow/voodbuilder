@php
    $vpressSubTheme = $page->resolvedSubTheme();
    $posts = $sectionPosts ?? collect();
@endphp

@extends($page->layoutView())

@section('section_index')
    @if ($vpressSubTheme === 'news')
        <header class="voodbuilder-news-desk-header">
            <p class="voodbuilder-news-desk-kicker">{{ __('voodbuilder::demo.news.desk_kicker') }}</p>
            <h1 class="voodbuilder-news-desk-title">{{ $page->title }}</h1>
            <p class="voodbuilder-news-desk-intro">{{ $page->displayExcerpt() }}</p>
        </header>

        @include('voodbuilder::themes.news.partials.story-grid', ['posts' => $posts])
    @else
        <header class="voodbuilder-blog-index-header">
            <h1 class="voodbuilder-blog-index-title">{{ $page->title }}</h1>
            <p class="voodbuilder-blog-index-intro">{{ $page->displayExcerpt() }}</p>
        </header>

        @include('voodbuilder::themes.blog.partials.post-list', ['posts' => $posts])
    @endif
@endsection
