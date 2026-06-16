@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-blog vpress-has-reading-progress
@endsection

@section('content')
    <div class="vpress-blog-shell" data-vpress-article>
        <div class="vpress-blog-content">
            @yield('page')
        </div>
    </div>
@endsection
