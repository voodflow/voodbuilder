@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;

    $column = $column ?? 'contact';
    $fallbackTitle = (string) __('voodbuilder::pro.editor.footer_settings.' . $column . '_default_title');
    $textColumn = SiteFooterConfig::textColumn($config, $column);
    $title = $textColumn['title'] !== '' ? $textColumn['title'] : $fallbackTitle;
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
        @foreach ($textColumn['lines'] as $line)
            @php($href = SiteFooterConfig::lineHref((string) $line))
            <li>
                @if ($href)
                    <a
                        href="{{ $href }}"
                        class="transition-colors hover:text-vp-brand-1"
                        @if (str_starts_with($href, 'http')) target="_blank" rel="noopener noreferrer" @endif
                    >{{ $line }}</a>
                @else
                    {{ $line }}
                @endif
            </li>
        @endforeach
    </ul>
</div>
