@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

@section('body_class')
    voodbuilder-sub-theme-events @yield('body_class_extra')
@endsection

@section('content')
    <div class="voodbuilder-site-shell voodbuilder-full-width-shell">
        <div class="voodbuilder-landing-shell">
            @yield('full_width')
        </div>
    </div>
@endsection
