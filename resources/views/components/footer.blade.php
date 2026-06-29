@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $brandName = VoodbuilderSettings::brandName();
@endphp

<footer class="border-t border-vp-divider px-6 py-8 text-center text-sm text-vp-text-2 md:px-8">
    @if(\Voodflow\Voodbuilder\Support\Navigation::items('footer')->isNotEmpty())
        <nav class="mb-3 flex flex-wrap justify-center gap-4" aria-label="{{ __('Footer') }}">
            @foreach(\Voodflow\Voodbuilder\Support\Navigation::items('footer') as $item)
                @if ($item->hasChildren())
                    @foreach ($item->children as $child)
                        <a
                            href="{{ $child->resolveUrl() }}"
                            class="transition-colors hover:text-vp-brand-1"
                            @if($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                        >
                            {{ __($child->label) }}
                        </a>
                    @endforeach
                @else
                    <a
                        href="{{ $item->resolveUrl() }}"
                        class="transition-colors hover:text-vp-brand-1"
                        @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ __($item->label) }}
                    </a>
                @endif
            @endforeach
        </nav>
    @endif
    <p>&copy; {{ date('Y') }} {{ $brandName }}</p>
</footer>
