@props([
    'menu' => 'main',
])

@php($items = \Voodflow\Vpress\Support\Navigation::items($menu))

@if($items->isNotEmpty())
    <nav class="mb-6" aria-label="{{ __('Navigation') }}">
        <div class="flex flex-col gap-0.5">
            @foreach($items as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    @class([
                        'flex items-center gap-1 py-1.5 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1',
                        'font-semibold text-vp-brand-1' => $item->isActive(),
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
    </nav>
@endif
