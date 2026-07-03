@props([
    'menu',
    'title' => null,
    'canvasPreview' => false,
])

@php
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;

    $items = Navigation::items($menu);
    $columnIndex = (int) preg_replace('/\D+/', '', (string) $menu);
    $columnTitle = $title ?? SiteFooterColumnPlacements::defaultColumnTitle(max(1, $columnIndex));
@endphp

@if ($items->isNotEmpty() || $canvasPreview)
    <div class="min-w-0">
        <h2 class="title-font mb-3 text-sm font-medium tracking-widest text-vp-text-1">
            {{ $columnTitle }}
        </h2>
        <nav aria-label="{{ $columnTitle }}">
            @if ($items->isNotEmpty())
                <ul class="list-none space-y-2">
                    @foreach ($items as $item)
                        <x-voodbuilder::footer-menu-item :item="$item" />
                    @endforeach
                </ul>
            @elseif ($canvasPreview)
                <p class="text-sm text-vp-text-3">
                    {{ __('voodbuilder::pro.grapesjs.blocks.site_footer_empty', ['menu' => $columnTitle]) }}
                </p>
            @endif
        </nav>
    </div>
@endif
