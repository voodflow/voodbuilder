@php
    use Voodflow\Voodbuilder\Support\LandingBlockSupport;

    $items = $config['items'] ?? [];
    $shell = LandingBlockSupport::sectionShellClass($config);
@endphp

<section class="vp-landing-steps {{ $shell }}">
    <div class="mx-auto max-w-5xl">
        @if (! empty($config['heading']))
            <h2 class="mb-10 text-center text-3xl font-bold text-vp-text-1">{{ $config['heading'] }}</h2>
        @endif

        <ol class="space-y-6">
            @foreach ($items as $index => $item)
                <li class="flex gap-5 rounded-2xl border border-vp-divider bg-vp-bg-elv p-6">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-vp-brand-1 text-sm font-bold text-white">
                        {{ $index + 1 }}
                    </span>
                    <div>
                        @if (! empty($item['title']))
                            <h3 class="text-lg font-semibold text-vp-text-1">{{ $item['title'] }}</h3>
                        @endif
                        @if (! empty($item['description']))
                            <p class="mt-2 text-sm leading-relaxed text-vp-text-2">{{ $item['description'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
