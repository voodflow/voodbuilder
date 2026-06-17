@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $items = $config['items'] ?? [];
    $shell = LandingBlockSupport::sectionShellClass($config, ['section_width' => 'narrow']);
@endphp

<section class="vp-landing-faq {{ $shell }}">
    <div class="mx-auto max-w-3xl">
        @if (! empty($config['heading']))
            <h2 class="mb-8 text-center text-3xl font-bold text-vp-text-1">{{ $config['heading'] }}</h2>
        @endif

        <div class="space-y-3">
            @foreach ($items as $item)
                <details class="group rounded-xl border border-vp-divider bg-vp-bg-elv px-5 py-4">
                    <summary class="cursor-pointer list-none font-semibold text-vp-text-1 marker:content-none">
                        <span class="flex items-center justify-between gap-4">
                            <span>{{ $item['question'] ?? '' }}</span>
                            <span class="text-vp-brand-1 transition group-open:rotate-45">+</span>
                        </span>
                    </summary>
                    @if (! empty($item['answer']))
                        <p class="mt-3 text-sm leading-relaxed text-vp-text-2">{{ $item['answer'] }}</p>
                    @endif
                </details>
            @endforeach
        </div>
    </div>
</section>
