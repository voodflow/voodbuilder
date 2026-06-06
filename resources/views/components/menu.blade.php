@props([
    'menu' => 'main',
    'class' => 'flex items-center',
    'linkClass' => 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1',
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
                        'text-vp-brand-1' => $item->isActive(),
                    ])
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    <span>{{ __($item->label) }}</span>
                    @if($item->isExternal())
                        <x-vpress::external-link-icon />
                    @endif
                </a>
            @endforeach
        </nav>
    @else
        <div {{ $attributes->class(['flex items-center']) }}>
            @foreach($items as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    @class([
                        $linkClass,
                        'text-vp-brand-1' => $item->isActive(),
                    ])
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    <span>{{ __($item->label) }}</span>
                    @if($item->isExternal())
                        <x-vpress::external-link-icon />
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endif
