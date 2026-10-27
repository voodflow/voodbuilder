@php
    $preview = (bool) ($preview ?? false);
    $canvasPreview = (bool) ($canvasPreview ?? false);

    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $stickyNavMode = in_array($config['sticky_nav'] ?? 'inherit', ['inherit', 'sticky', 'static'], true)
        ? ($config['sticky_nav'] ?? 'inherit')
        : 'inherit';
    $stickyNavEnabled = match ($stickyNavMode) {
        'sticky' => true,
        'static' => false,
        default => (bool) VoodbuilderSettings::get('sticky_nav', false),
    };
@endphp

<div
    @class([
        'voodbuilder-site-header-spacer' => $stickyNavEnabled,
        'relative' => $preview,
    ])
    data-voodbuilder-editor-site-header
    data-voodbuilder-nav-variant="fullscreen"
    @if ($canvasPreview) data-gjs-type="default" @endif
>
    <x-voodbuilder::nav-fullscreen
        :canvas-preview="$canvasPreview"
        :sticky-nav-mode="$config['sticky_nav'] ?? 'inherit'"
        :show-search="(bool) ($config['show_search'] ?? true)"
        :show-profile-menu="(bool) ($config['show_profile_menu'] ?? true)"
        :uppercase-first-level="(bool) ($config['uppercase_first_level'] ?? false)"
        :brand-config="$config"
    />
</div>
