{{-- Core stub: safe when voodflow/vpopups is absent or disabled. --}}
@if (
    \Voodflow\Voodbuilder\Voodbuilder::modules()->isEnabled('popups')
    && view()->exists('vpopups::components.popups-boot')
)
    @include('vpopups::components.popups-boot')
@endif
