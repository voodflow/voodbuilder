@php
    use Voodflow\Vpress\Models\VpressSettings;

    $brandName = VpressSettings::brandName();
@endphp

<footer class="VPFooter">
    @if(\Voodflow\Vpress\Support\Navigation::items('footer')->isNotEmpty())
        <nav class="VPFooterNav" aria-label="{{ __('Footer') }}">
            @foreach(\Voodflow\Vpress\Support\Navigation::items('footer') as $item)
                <a
                    href="{{ $item->resolveUrl() }}"
                    class="VPFooterNavLink"
                    @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ __($item->label) }}
                </a>
            @endforeach
        </nav>
    @endif
    <p>&copy; {{ date('Y') }} {{ $brandName }}</p>
</footer>
