@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;

    $column = $column ?? 'contact';
    $title = SiteFooterConfig::textColumnTitle($config, $column);
@endphp

<div
    class="min-w-0 px-4 mb-10 w-full lg:mb-0 lg:flex-1"
    data-voodbuilder-footer-text-col="{{ $column }}"
>
    <h2
        class="title-font text-xs font-semibold uppercase tracking-[0.12em] text-vp-text-1 mb-4"
        data-voodbuilder-footer-text-title="{{ $column }}"
    >
        {{ $title }}
    </h2>
    <ul class="list-none space-y-2.5 text-sm leading-6 text-vp-text-2" data-voodbuilder-footer-text-lines="{{ $column }}">
        @include('voodbuilder::editor.blocks.footers._text_column_items', [
            'config' => $config,
            'column' => $column,
        ])
    </ul>
</div>
