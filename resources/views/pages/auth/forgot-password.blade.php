@extends(config('voodbuilder.layouts.page', 'voodbuilder::layouts.page'))

@section('page')
    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-3xl font-bold text-vp-text-1">{{ __('voodbuilder::auth.forgot_password_title') }}</h1>
        <p class="mb-8 text-vp-text-2">{{ __('voodbuilder::auth.forgot_password_lead') }}</p>

        @if (session('status'))
            <p
                class="mb-6 rounded-lg border border-vp-brand-1/20 bg-vp-gray-soft px-4 py-3 text-sm font-medium text-vp-brand-1"
                role="status"
            >
                {{ session('status') }}
            </p>
        @endif

        <section class="rounded-xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm">
            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-vp-text-1" for="forgot-email">{{ __('voodbuilder::auth.email') }}</label>
                    <input
                        id="forgot-email"
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

                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2">
                    {{ __('voodbuilder::auth.forgot_password_submit') }}
                </button>
            </form>
        </section>

        <p class="mt-6 text-center text-sm text-vp-text-2">
            <a href="{{ route('login') }}" class="font-medium text-vp-brand-1 hover:text-vp-brand-2">{{ __('voodbuilder::auth.back_to_login') }}</a>
        </p>
    </div>
@endsection
