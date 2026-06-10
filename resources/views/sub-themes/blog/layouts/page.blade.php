@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-blog
@endsection

@section('content')
    <div class="vpress-blog-shell">
        <div class="vpress-blog-content">
            @yield('page')
        </div>
    </div>
@endsection
