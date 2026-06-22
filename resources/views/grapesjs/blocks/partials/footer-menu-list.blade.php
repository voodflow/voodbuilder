@foreach ($items as $item)
    <li>
        <a
            href="{{ $item->resolveUrl() }}"
            class="text-vp-text-2 transition-colors hover:text-vp-brand-1"
        >
            {{ $item->label }}
        </a>
    </li>
@endforeach
