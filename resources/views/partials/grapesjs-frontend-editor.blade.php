@php
    /** @var array<string, mixed> $grapesJsConfig */
@endphp

<script type="application/json" data-vpress-grapesjs-config>
{!! json_encode($grapesJsConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

<div class="vpress-grapesjs-frontend" data-vpress-grapesjs-root>
    <div class="vpress-grapesjs-frontend__canvas" data-vpress-grapesjs-canvas></div>
</div>

@push('scripts')
    @if (\Voodflow\Vpress\Support\GrapesJs\GrapesJsAssets::isBuilt())
        @vite(\Voodflow\Vpress\Support\GrapesJs\GrapesJsAssets::viteEntries())
    @else
        <div class="vpress-grapesjs-frontend__notice" role="alert">
            <p>{{ __('vpress::pro.frontend.assets_missing') }}</p>
            <p><code>{{ \Voodflow\Vpress\Support\GrapesJs\GrapesJsAssets::buildInstructions() }}</code></p>
        </div>
    @endif
@endpush
