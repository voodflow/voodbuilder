@props(['column', 'columnClass' => 'lg:w-1/4 md:w-1/2 w-full px-4', 'titleClass' => 'title-font font-medium text-gray-900 tracking-widest text-sm mb-3'])

<div class="{{ $columnClass }}" data-voodbuilder-column-title="{{ $column['index'] ?? '' }}">
    <h2 class="{{ $titleClass }}">{{ $column['title'] }}</h2>

    @if (($column['links'] ?? []) !== [])
        <nav class="list-none mb-10">
            @foreach ($column['links'] as $link)
                <li>
                    <a
                        href="{{ $link['url'] }}"
                        class="text-gray-600 hover:text-gray-800"
                        @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </nav>
    @endif
</div>
