@extends(config('voodbuilder.layouts.page', 'voodbuilder::layouts.page'))

@php
    use Filament\Facades\Filament;
    use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $panel = Filament::getPanel('admin');
    $adminUrl = auth()->check()
        ? SitePageResource::getUrl('create')
        : $panel->getLoginUrl();
    $ctaLabel = auth()->check()
        ? __('voodbuilder::home.empty.cta')
        : __('voodbuilder::home.empty.cta_login');
@endphp

@section('content')
    <section class="mx-auto flex w-full max-w-2xl flex-1 flex-col items-center justify-center px-6 py-16 text-center">
        <div class="w-full rounded-2xl border border-vp-divider bg-vp-bg-alt/60 px-8 py-12 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-vp-brand-1">
                {{ VoodbuilderSettings::brandName() }}
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-vp-text-1">
                {{ __('voodbuilder::home.empty.title') }}
            </h1>
            <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-vp-text-2">
                {{ __('voodbuilder::home.empty.description') }}
            </p>
            <div class="mt-8">
                <a
                    href="{{ $adminUrl }}"
                    class="inline-flex items-center justify-center rounded-lg bg-vp-brand-1 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-vp-brand-1"
                >
                    {{ $ctaLabel }}
                </a>
            </div>
        </div>
    </section>
@endsection
