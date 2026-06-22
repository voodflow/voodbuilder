@php
    $columns = max(1, min(4, (int) ($config['columns'] ?? 4)));
    $defaultTitles = [
        1 => __('vpress::pro.grapesjs.blocks.footer_col_1_title'),
        2 => __('vpress::pro.grapesjs.blocks.footer_col_2_title'),
        3 => __('vpress::pro.grapesjs.blocks.footer_col_3_title'),
        4 => __('vpress::pro.grapesjs.blocks.footer_col_4_title'),
    ];
@endphp

@for ($index = 1; $index <= 4; $index++)
    @php
        $menuSlug = 'footer_col_'.$index;
        $hidden = $index > $columns;
    @endphp
    <div
        @class([
            'lg:w-1/4 md:w-1/2 w-full px-4 mb-10',
            'hidden' => $hidden,
        ])
        data-vpress-footer-col="{{ $index }}"
    >
        <h2 class="title-font font-medium text-vp-text-1 tracking-widest text-sm mb-3" data-vpress-footer-title>
            {{ $defaultTitles[$index] }}
        </h2>
        <nav class="list-none" data-vpress-menu="{{ $menuSlug }}" aria-label="{{ $defaultTitles[$index] }}"></nav>
    </div>
@endfor
