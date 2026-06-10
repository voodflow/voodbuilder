@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-blog
@endsection

@section('content')
    <div class="vpress-blog-shell">
        <div class="vpress-blog-layout">
            @include('vpress::sub-themes.blog.partials.sidebar-left', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])

            <div class="vpress-blog-main">
                @yield('section_index')
            </div>

            @include('vpress::sub-themes.blog.partials.sidebar-right', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])
        </div>
    </div>
@endsection
