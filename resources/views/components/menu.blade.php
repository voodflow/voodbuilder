@props([
    'menu' => 'main',
    'class' => 'flex items-center',
    'linkClass' => 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1',
    'extra' => false,
    'wrapped' => true,
])

@php($items = \Voodflow\Voodbuilder\Support\Navigation::items($menu))

@if($items->isNotEmpty())
    @if($wrapped)
        <nav {{ $attributes->class([$class, 'gap-1']) }} aria-label="{{ __('Navigation') }}">
            @foreach($items as $item)
                <x-voodbuilder::menu-nav-item :item="$item" :link-class="$linkClass" />
            @endforeach
        </nav>
    @else
        <div {{ $attributes->class(['flex items-center gap-1']) }}>
            @foreach($items as $item)
                <x-voodbuilder::menu-nav-item :item="$item" :link-class="$linkClass" />
            @endforeach
        </div>
    @endif
@endif
