@php
    $preview = (bool) ($preview ?? false);
    $canvasPreview = (bool) ($canvasPreview ?? false);
    $variant = $config['variant'] ?? 'simple';

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
    data-voodbuilder-gjs-site-header
    data-voodbuilder-nav-variant="simple"
    @if ($canvasPreview) data-gjs-type="default" @endif
>
    <x-voodbuilder::nav
        :has-doc-sidebar="false"
        :show-reading-progress="false"
        :canvas-preview="$canvasPreview"
        variant="simple"
        :main-nav-align="$config['main_nav_align'] ?? 'start'"
        :sticky-nav-mode="$config['sticky_nav'] ?? 'inherit'"
        :show-search="(bool) ($config['show_search'] ?? true)"
        :show-notifications="(bool) ($config['show_notifications'] ?? true)"
        :show-profile-menu="(bool) ($config['show_profile_menu'] ?? true)"
        :brand-config="$config"
        :in-page-block="true"
    />
</div>
