@php
    use Voodflow\Vpress\Support\LandingBlockSupport;
    use Voodflow\Vpress\Support\ResolvableLinkSupport;

    $items = $config['items'] ?? [];
    $columns = (int) ($config['columns'] ?? 3);
    $gridClass = match ($columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
    $shell = LandingBlockSupport::sectionShellClass($config);
@endphp

<section class="vp-landing-features {{ $shell }}">
    <div class="mx-auto max-w-6xl">
        @if (! empty($config['heading']) || ! empty($config['subheading']))
            <div class="mb-10 text-center">
                @if (! empty($config['heading']))
                    <h2 class="text-3xl font-bold text-vp-text-1">{{ $config['heading'] }}</h2>
                @endif
                @if (! empty($config['subheading']))
                    <p class="mx-auto mt-3 max-w-3xl text-lg text-vp-text-2">{{ $config['subheading'] }}</p>
                @endif
            </div>
        @endif

        <div class="grid gap-6 {{ $gridClass }}">
            @foreach ($items as $item)
                <article class="rounded-2xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm">
                    @if (! empty($item['icon']))
                        <div class="mb-4 text-3xl" aria-hidden="true">{{ $item['icon'] }}</div>
                    @endif
                    @if (! empty($item['title']))
                        <h3 class="text-xl font-semibold text-vp-text-1">{{ $item['title'] }}</h3>
                    @endif
                    @if (! empty($item['description']))
                        <p class="mt-2 text-sm leading-relaxed text-vp-text-2">{{ $item['description'] }}</p>
                    @endif
                    @php($itemUrl = ResolvableLinkSupport::resolve($item, 'link', 'link_url'))
                    @if (filled($itemUrl))
                        <a href="{{ $itemUrl }}" class="mt-4 inline-flex text-sm font-semibold text-vp-brand-1 hover:underline" @if (ResolvableLinkSupport::opensInNewTab($item, 'link')) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $item['link_label'] ?? __('vpress::landing.learn_more') }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
