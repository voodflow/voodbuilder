@if ($item->hasChildren())
    <div class="flex flex-col gap-2">
        <span class="text-xs font-semibold uppercase tracking-wide text-vp-text-3">{{ __($item->label) }}</span>
        <div class="flex flex-wrap gap-3 pl-2">
            @foreach ($item->navigationChildren() as $child)
                @include('voodbuilder::admin.partials.menu-preview-item', ['item' => $child])
            @endforeach
        </div>
    </div>
@else
    <a
        href="{{ $item->resolveUrl() }}"
        class="text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    >
        {{ __($item->label) }}
    </a>
@endif
