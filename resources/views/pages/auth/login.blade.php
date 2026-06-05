@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="VPAuth">
        <h1 class="VPAuthTitle">{{ __('vpress::auth.login_title') }}</h1>

        <form method="POST" action="{{ route('login') }}" class="VPAuthForm">
            @csrf

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="login-email">{{ __('vpress::auth.email') }}</label>
                <input
                    id="login-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="VPAuthInput"
                    autocomplete="email"
                    required
                    autofocus
                >
                @error('email')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="login-password">{{ __('vpress::auth.password') }}</label>
                <input
                    id="login-password"
                    type="password"
                    name="password"
                    class="VPAuthInput"
                    autocomplete="current-password"
                    required
                >
                @error('password')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <label class="VPAuthRemember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>{{ __('vpress::auth.remember') }}</span>
            </label>

            <button type="submit" class="VPButton brand VPAuthSubmit">{{ __('vpress::auth.login') }}</button>
        </form>

        @if (config('vpress.auth.registration_enabled', true) && Route::has('register'))
            <p class="VPAuthSwitch">
                {{ __('vpress::auth.no_account') }}
                <a href="{{ route('register') }}">{{ __('vpress::auth.create_account') }}</a>
            </p>
        @endif
    </div>
@endsection
