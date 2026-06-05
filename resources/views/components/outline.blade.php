@props(['items' => [], 'title' => null])

@if(count($items) > 0)
    <nav class="VPDocAsideOutline" aria-labelledby="vpress-outline-title">
        <div id="vpress-outline-title" class="VPDocAsideOutlineTitle">
            {{ $title ?? __('On this page') }}
        </div>
        <div class="VPDocAsideOutlineItems">
            @foreach($items as $item)
                <a
                    href="#{{ $item['id'] }}"
                    class="VPDocAsideOutlineLink level-{{ $item['level'] }}"
                    data-outline-link
                >
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
