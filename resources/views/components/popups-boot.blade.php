{{-- Core stub: safe when voodbuilder-popups is absent or disabled. --}}
@if (
    \Voodflow\Voodbuilder\Voodbuilder::modules()->isEnabled('popups')
    && view()->exists('voodbuilder-popups::components.popups-boot')
)
    @include('voodbuilder-popups::components.popups-boot')
@endif
