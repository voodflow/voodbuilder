@props([
    'menu' => 'main',
    'class' => 'VPNavBarMenu',
    'linkClass' => 'VPNavBarMenuLink',
    'extra' => false,
    'wrapped' => true,
])

@php($items = \Voodflow\Vpress\Support\Navigation::items($menu))

@if($items->isNotEmpty())
    @if($wrapped)
        <nav {{ $attributes->class([$class]) }} aria-label="{{ __('Navigation') }}">
            @foreach($items as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    @class([
                        $linkClass,
                        'active' => $item->isActive(),
                        'VPNavBarMenuLink--extra' => $extra,
                    ])
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ __($item->label) }}
                </a>
            @endforeach
        </nav>
    @else
        <div {{ $attributes->class(['VPNavBarMenuInline']) }}>
            @foreach($items as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    @class([
                        $linkClass,
                        'active' => $item->isActive(),
                        'VPNavBarMenuLink--extra' => $extra,
                    ])
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ __($item->label) }}
                </a>
            @endforeach
        </div>
    @endif
@endif
