@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <div class="mx-auto max-w-md">
        <h1 class="mb-2 text-3xl font-bold text-vp-text-1">{{ __('vpress::auth.register_title') }}</h1>
        <p class="mb-6 text-sm text-vp-text-2">{{ __('vpress::auth.register_lead') }}</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="register-name">{{ __('vpress::auth.name') }}</label>
                <input
                    id="register-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="name"
                    required
                    autofocus
                >
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="register-email">{{ __('vpress::auth.email') }}</label>
                <input
                    id="register-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="email"
                    required
                >
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="register-password">{{ __('vpress::auth.password') }}</label>
                <input
                    id="register-password"
                    type="password"
                    name="password"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="new-password"
                    required
                >
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="register-password-confirmation">{{ __('vpress::auth.confirm_password') }}</label>
                <input
                    id="register-password-confirmation"
                    type="password"
                    name="password_confirmation"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2">
                {{ __('vpress::auth.register') }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-vp-text-2">
            {{ __('vpress::auth.already_have_account') }}
            <a href="{{ route('login') }}" class="font-medium text-vp-brand-1 hover:text-vp-brand-2">{{ __('vpress::auth.login') }}</a>
        </p>
    </div>
@endsection
