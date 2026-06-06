@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-3xl font-bold text-vp-text-1">{{ __('vpress::account.title') }}</h1>
        <p class="mb-8 text-vp-text-2">{{ __('vpress::account.lead') }}</p>

        <livewire:vpress.account-settings />
    </div>
@endsection
