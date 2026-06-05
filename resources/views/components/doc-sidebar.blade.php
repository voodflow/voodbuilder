@props([
    'title',
    'items' => [],
    'currentUrl' => null,
])

<nav class="VPSidebarNav" aria-label="{{ $title }}">
    <div class="VPSidebarGroup">
        <div class="VPSidebarGroupTitle">{{ $title }}</div>
        <div class="VPSidebarGroupItems">
            @foreach($items as $item)
                @php
                    $url = is_string($item) ? $item : ($item['url'] ?? '#');
                    $label = is_string($item) ? $item : ($item['title'] ?? $item['label'] ?? '');
                    $active = $currentUrl && $url === $currentUrl;
                @endphp
                <a
                    href="{{ $url }}"
                    class="VPSidebarLink @if($active) active @endif"
                    @if($active) aria-current="page" @endif
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
