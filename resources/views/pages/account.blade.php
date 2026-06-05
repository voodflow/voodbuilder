@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="VPAccountPage">
        <h1 class="VPAccountTitle">{{ __('vpress::account.title') }}</h1>
        <p class="VPAccountLead">{{ __('vpress::account.lead') }}</p>

        <livewire:vpress.account-settings />
    </div>
@endsection
