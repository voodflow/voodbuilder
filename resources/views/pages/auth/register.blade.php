@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="VPAuth">
        <h1 class="VPAuthTitle">{{ __('vpress::auth.register_title') }}</h1>
        <p class="VPAuthLead">{{ __('vpress::auth.register_lead') }}</p>

        <form method="POST" action="{{ route('register') }}" class="VPAuthForm">
            @csrf

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="register-name">{{ __('vpress::auth.name') }}</label>
                <input
                    id="register-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="VPAuthInput"
                    autocomplete="name"
                    required
                    autofocus
                >
                @error('name')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="register-email">{{ __('vpress::auth.email') }}</label>
                <input
                    id="register-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="VPAuthInput"
                    autocomplete="email"
                    required
                >
                @error('email')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="register-password">{{ __('vpress::auth.password') }}</label>
                <input
                    id="register-password"
                    type="password"
                    name="password"
                    class="VPAuthInput"
                    autocomplete="new-password"
                    required
                >
                @error('password')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="register-password-confirmation">{{ __('vpress::auth.confirm_password') }}</label>
                <input
                    id="register-password-confirmation"
                    type="password"
                    name="password_confirmation"
                    class="VPAuthInput"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="VPButton brand VPAuthSubmit">{{ __('vpress::auth.register') }}</button>
        </form>

        <p class="VPAuthSwitch">
            {{ __('vpress::auth.already_have_account') }}
            <a href="{{ route('login') }}">{{ __('vpress::auth.login') }}</a>
        </p>
    </div>
@endsection
