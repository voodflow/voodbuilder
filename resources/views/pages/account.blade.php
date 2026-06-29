@extends(config('voodbuilder.layouts.page', 'voodbuilder::layouts.page'))

@section('page')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-3xl font-bold text-vp-text-1">{{ __('voodbuilder::account.title') }}</h1>
        <p class="mb-8 text-vp-text-2">{{ __('voodbuilder::account.lead') }}</p>

        <livewire:voodbuilder.account-settings />
    </div>
@endsection
