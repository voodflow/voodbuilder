@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('content')
    <div class="VPPageHeader">
        <h1 class="VPPageTitle">{{ \Voodflow\Vpress\Models\VpressSettings::brandName() }}</h1>
        <p class="VPPageDescription">
            {{ __('Create your home page from the admin panel under Site → Pages.') }}
        </p>
    </div>
@endsection
