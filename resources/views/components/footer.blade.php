@php
    use Voodflow\Vpress\Models\VpressSettings;

    $brandName = VpressSettings::brandName();
@endphp

<footer class="border-t border-vp-divider px-6 py-8 text-center text-sm text-vp-text-2 md:px-8">
    @if(\Voodflow\Vpress\Support\Navigation::items('footer')->isNotEmpty())
        <nav class="mb-3 flex flex-wrap justify-center gap-4" aria-label="{{ __('Footer') }}">
            @foreach(\Voodflow\Vpress\Support\Navigation::items('footer') as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    class="transition-colors hover:text-vp-brand-1"
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ __($item->label) }}
                </a>
            @endforeach
        </nav>
    @endif
    <p>&copy; {{ date('Y') }} {{ $brandName }}</p>
</footer>
