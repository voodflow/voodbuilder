@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

@php
    use Filament\Facades\Filament;
    use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
    use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\EmptySiteGuidance;

    $needsLayout = $welcomeNeedsLayout ?? EmptySiteGuidance::needsChromeLayout();
    $authenticated = auth()->check();

    $adminUrl = null;

    try {
        if ($authenticated) {
            $adminUrl = $needsLayout
                ? ChromeLayoutResource::getUrl('create')
                : SitePageResource::getUrl('create');
        } else {
            $adminUrl = Filament::getPanel('admin')->getLoginUrl();
        }
    } catch (Throwable) {
        $adminUrl = url('/admin');
    }

    $ctaLabel = EmptySiteGuidance::ctaLabel($authenticated);
@endphp

@section('content')
    <section class="mx-auto flex w-full max-w-2xl min-h-[min(100dvh,36rem)] flex-col items-center justify-center px-6 py-16 text-center">
        <div class="w-full rounded-2xl bg-vp-bg-alt/60 px-8 py-12 ring-1 ring-black/5 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-vp-brand-1">
                {{ VoodbuilderSettings::brandName() }}
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-vp-text-1">
                {{ EmptySiteGuidance::title() }}
            </h1>
            <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-vp-text-2">
                {{ EmptySiteGuidance::description() }}
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
