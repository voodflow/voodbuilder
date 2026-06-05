@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('content')
    <div class="VPHome">
        @yield('home')
    </div>
@endsection
