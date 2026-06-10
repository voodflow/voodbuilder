@props(['items' => [], 'title' => null])

@if(count($items) > 0)
    <nav class="vp-outline" aria-labelledby="vpress-outline-title">
        <div id="vpress-outline-title" class="vp-outline__title">
            {{ $title ?? __('On this page') }}
        </div>
        <div class="vp-outline__rail">
            @foreach($items as $item)
                <a
                    href="#{{ $item['id'] }}"
                    @class([
                        'vp-outline__link',
                        'vp-outline__link--level-3' => ($item['level'] ?? 2) === 3,
                        'vp-outline__link--level-4' => ($item['level'] ?? 2) >= 4,
                    ])
                    data-toc-link
                >
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
