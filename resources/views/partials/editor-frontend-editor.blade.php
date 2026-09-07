@php
    /** @var array<string, mixed> $editorConfig */
@endphp

<script type="application/json" data-voodbuilder-editor-config>
{!! json_encode($editorConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

<div class="voodbuilder-editor-frontend" data-voodbuilder-editor-root>
    <div class="voodbuilder-editor-frontend__canvas" data-voodbuilder-editor-canvas></div>
</div>

@if (! \Voodflow\Voodbuilder\Support\Editor\EditorAssets::isBuilt())
    <div class="voodbuilder-editor-frontend__notice" role="alert">
        <p>{{ __('voodbuilder::pro.frontend.assets_missing') }}</p>
        <p><code>{{ \Voodflow\Voodbuilder\Support\Editor\EditorAssets::buildInstructions() }}</code></p>
    </div>
@endif
