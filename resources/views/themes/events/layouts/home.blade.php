@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-events
@endsection

@section('content')
    <div class="vpress-events-shell">
        <div class="vpress-events-content">
            @yield('home')
        </div>
    </div>
@endsection
