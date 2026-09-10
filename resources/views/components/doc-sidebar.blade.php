@props([
    'title',
    'items' => [],
    'currentUrl' => null,
])

<nav aria-label="{{ $title }}">
    <div class="mb-2 text-[13px] font-bold leading-snug text-vp-text-1">{{ $title }}</div>
    <div class="flex flex-col gap-0.5">
        @foreach($items as $item)
            @php
                $url = is_string($item) ? $item : ($item['url'] ?? '#');
                $label = is_string($item) ? $item : ($item['title'] ?? $item['label'] ?? '');
                $active = $currentUrl && $url === $currentUrl;
            @endphp
            <a
                href="{{ $url }}"
                @class([
                    'block -ml-3 border-l-2 py-1.5 pl-3 text-[14px] font-medium leading-snug text-vp-text-2 transition-colors hover:text-vp-text-1',
                    'border-vp-brand-1 text-vp-brand-1 font-semibold' => $active,
                    'border-transparent' => ! $active,
                ])
                @if($active) aria-current="page" @endif
            >
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>
