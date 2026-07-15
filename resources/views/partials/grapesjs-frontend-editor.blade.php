@php
    /** @var array<string, mixed> $grapesJsConfig */
@endphp

<script type="application/json" data-voodbuilder-grapesjs-config>
{!! json_encode($grapesJsConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

<div class="voodbuilder-grapesjs-frontend" data-voodbuilder-grapesjs-root>
    <div class="voodbuilder-grapesjs-frontend__canvas" data-voodbuilder-grapesjs-canvas></div>
</div>

@if (! \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets::isBuilt())
    <div class="voodbuilder-grapesjs-frontend__notice" role="alert">
        <p>{{ __('voodbuilder::pro.frontend.assets_missing') }}</p>
        <p><code>{{ \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets::buildInstructions() }}</code></p>
    </div>
@endif
