@php
    $navbar = $navbar ?? [];
    $links = $navbar['links'] ?? [];
    $linkClass = $linkClass ?? 'mr-5 hover:text-gray-900';
@endphp

@if ($links !== [])
    <nav class="{{ $navClass ?? 'md:ml-auto flex flex-wrap items-center text-base justify-center' }}">
        @foreach ($links as $link)
            <a
                href="{{ $link['url'] }}"
                class="{{ $loop->last && ! str_contains($linkClass, 'mr-5') ? 'hover:text-gray-900' : $linkClass }}"
                @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif

@if (! empty($navbar['cta_label']) && ! empty($navbar['cta_url']))
    <a
        href="{{ $navbar['cta_url'] }}"
        class="{{ $ctaClass ?? 'inline-flex items-center bg-gray-100 border-0 py-1 px-3 focus:outline-none hover:bg-gray-200 rounded text-base mt-4 md:mt-0' }}"
    >
        {{ $navbar['cta_label'] }}
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-4 h-4 ml-1" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 12h14M12 5l7 7-7 7"></path>
        </svg>
    </a>
@endif
