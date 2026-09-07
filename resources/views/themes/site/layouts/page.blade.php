@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

@section('body_class')
    voodbuilder-sub-theme-events @yield('body_class_extra')
@endsection

@section('content')
    <div class="voodbuilder-site-shell">
        <div class="voodbuilder-site-content">
            @yield('page')
        </div>
    </div>
@endsection
