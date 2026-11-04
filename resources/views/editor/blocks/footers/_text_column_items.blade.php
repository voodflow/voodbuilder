@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\MenuTablerIcons;

    /**
     * Rows of a footer text column (Contacts / Details).
     *
     * Kept as its own partial because saved layout markup is hydrated at render
     * time: EditorSlotHydrator re-renders these rows from data-voodbuilder-config
     * instead of trusting the <li> nodes GrapesJS serialized when the layout was
     * authored. Without that the published footer keeps stale contacts forever.
     */
    $column = $column ?? 'contact';
    $textColumn = SiteFooterConfig::textColumn($config, $column);
    $items = $column === 'contact' ? SiteFooterConfig::contactItems($config) : [];
@endphp
@if ($items !== [])
    @foreach ($items as $item)
        @php($itemHref = $item['href'])
        <li class="flex items-start gap-2.5" data-voodbuilder-footer-text-item>
            <span
                class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center text-vp-text-3"
                @if ($item['icon'] !== '') data-voodbuilder-footer-text-icon="{{ $item['icon'] }}" @endif
                aria-hidden="true"
            >{!! $item['icon'] !== '' ? MenuTablerIcons::svgHtml($item['icon'], 'h-4 w-4') : '' !!}</span>
            @if ($itemHref)
                <a
                    href="{{ $itemHref }}"
                    class="transition-colors hover:text-vp-brand-1"
                    @if (str_starts_with($itemHref, 'http')) target="_blank" rel="noopener noreferrer" @endif
                >{{ $item['text'] }}</a>
            @else
                <span>{{ $item['text'] }}</span>
            @endif
        </li>
    @endforeach
@else
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
@endif
