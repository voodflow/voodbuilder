@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\NavigationMenuResolver;

    $items = Navigation::items($menuSlug);
    $display = Navigation::linkDisplay($menuSlug);
    $align = SiteFooterConfig::normalizeSocialAlign($socialAlign ?? ($config['social_align'] ?? null));
    $justify = SiteFooterConfig::socialJustifyClass($align);
@endphp

@if ($items->isNotEmpty())
    <div
        class="inline-flex flex-wrap items-center gap-3 {{ $justify }}"
        data-voodbuilder-social-links
        data-voodbuilder-social-align="{{ $align }}"
    >
        @foreach ($items as $item)
            <x-voodbuilder::social-menu-item :item="$item" :display="$display" />
        @endforeach
    </div>
@elseif ($preview ?? false)
    <div
        class="inline-flex flex-wrap items-center gap-3 text-xs text-vp-text-2 {{ $justify }}"
        data-voodbuilder-social-links
        data-voodbuilder-social-align="{{ $align }}"
    >
        {{ __('voodbuilder::pro.grapesjs.blocks.site_footer_empty', ['menu' => NavigationMenuResolver::placementLabel($menuSlug)]) }}
    </div>
@endif
