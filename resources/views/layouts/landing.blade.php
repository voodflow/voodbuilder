@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('content')
    <div class="vpress-landing-shell">
        @yield('landing')
    </div>
@endsection
