@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-news
@endsection

@section('content')
    <div class="vpress-news-shell">
        <div class="vpress-news-layout">
            @include('vpress::sub-themes.news.partials.sidebar-left', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])

            <div class="vpress-news-main">
                @yield('section_index')
            </div>

            @include('vpress::sub-themes.news.partials.sidebar-right', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])
        </div>
    </div>
@endsection
