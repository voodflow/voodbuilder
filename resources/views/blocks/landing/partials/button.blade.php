@if (filled($label ?? null) && filled($url ?? null))
    <a
        href="{{ $url }}"
        class="inline-flex items-center justify-center rounded-lg px-6 py-3 text-sm font-semibold transition {{ $class ?? '' }}"
        @if ($open_in_new_tab ?? false) target="_blank" rel="noopener noreferrer" @endif
    >
        {{ $label }}
    </a>
@endif
