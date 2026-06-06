@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-3xl font-bold text-vp-text-1">{{ __('vpress::auth.login_title') }}</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="login-email">{{ __('vpress::auth.email') }}</label>
                <input
                    id="login-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="email"
                    required
                    autofocus
                >
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="login-password">{{ __('vpress::auth.password') }}</label>
                <input
                    id="login-password"
                    type="password"
                    name="password"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="current-password"
                    required
                >
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-vp-text-2">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>{{ __('vpress::auth.remember') }}</span>
            </label>

            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2">
                {{ __('vpress::auth.login') }}
            </button>
        </form>

        @if (config('vpress.auth.registration_enabled', true) && Route::has('register'))
            <p class="mt-6 text-center text-sm text-vp-text-2">
                {{ __('vpress::auth.no_account') }}
                <a href="{{ route('register') }}" class="font-medium text-vp-brand-1 hover:text-vp-brand-2">{{ __('vpress::auth.create_account') }}</a>
            </p>
        @endif
    </div>
@endsection
