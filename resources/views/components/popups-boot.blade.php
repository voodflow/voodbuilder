@if (
    config('voodbuilder.popups.enabled', true)
    && \Illuminate\Support\Facades\Schema::hasTable('voodbuilder_popups')
    && \Voodflow\Voodbuilder\Models\BuilderPopup::query()->where('enabled', true)->exists()
)
    <script type="application/json" data-voodbuilder-popups-config>
        {!! json_encode([
            'endpoint' => route('voodbuilder.popups.public', absolute: false),
            'eventsEndpoint' => route('voodbuilder.popups.events', absolute: false),
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
@endif
