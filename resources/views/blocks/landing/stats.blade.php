@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $items = $config['items'] ?? [];
    $shell = LandingBlockSupport::sectionShellClass($config);
@endphp

<section class="vp-landing-stats {{ $shell }}">
    <div class="mx-auto max-w-6xl">
        @if (! empty($config['heading']))
            <h2 class="mb-10 text-center text-3xl font-bold text-vp-text-1">{{ $config['heading'] }}</h2>
        @endif

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-{{ min(4, max(2, count($items))) }}">
            @foreach ($items as $item)
                <div class="rounded-2xl border border-vp-divider bg-vp-bg-elv p-6 text-center">
                    @if (! empty($item['value']))
                        <p class="text-4xl font-extrabold tracking-tight text-vp-brand-1">{{ $item['value'] }}</p>
                    @endif
                    @if (! empty($item['label']))
                        <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-vp-text-2">{{ $item['label'] }}</p>
                    @endif
                    @if (! empty($item['description']))
                        <p class="mt-2 text-sm text-vp-text-3">{{ $item['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
