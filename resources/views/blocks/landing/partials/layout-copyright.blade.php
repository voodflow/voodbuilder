@php
    $year = filled($config['copyright_year'] ?? null) ? (string) $config['copyright_year'] : (string) now()->year;
    $brand = $config['copyright_brand'] ?? ($organizer['brand_name'] ?? null);
    $highlight = $config['copyright_highlight'] ?? null;
@endphp

<p class="text-gray-500 text-sm text-center sm:text-left">
    @if ($brand)
        © {{ $year }} {{ $brand }}
    @endif

    @if ($highlight)
        <span class="text-gray-600 ml-1">{{ $highlight }}</span>
    @endif
</p>
