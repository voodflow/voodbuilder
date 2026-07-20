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

    @if ($preview)
        <div class="pointer-events-none absolute top-2 right-2 z-10">
            <span class="pointer-events-auto rounded-md border border-dashed border-vp-divider bg-vp-bg px-2 py-1 text-xs text-vp-text-2 shadow-sm">
                {{ __('voodbuilder::pro.grapesjs.blocks.site_nav_preview') }}
            </span>
        </div>
    @endif
</div>
