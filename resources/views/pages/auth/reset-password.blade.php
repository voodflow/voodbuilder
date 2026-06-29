@extends(config('voodbuilder.layouts.page', 'voodbuilder::layouts.page'))

@section('page')
    @php
        $isInvite = session('invite') || request()->boolean('invite');
    @endphp

    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-3xl font-bold text-vp-text-1">
            {{ $isInvite ? __('voodbuilder::auth.invite_title') : __('voodbuilder::auth.reset_password_title') }}
        </h1>
        <p class="mb-8 text-vp-text-2">
            {{ $isInvite ? __('voodbuilder::auth.invite_lead') : __('voodbuilder::auth.reset_password_lead') }}
        </p>

        <section class="rounded-xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label class="mb-1 block text-sm font-medium text-vp-text-1" for="reset-email">{{ __('voodbuilder::auth.email') }}</label>
                    <input
                        id="reset-email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                        autocomplete="email"
                        required
                        @if ($isInvite) readonly @endif
                    >
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-vp-text-1" for="reset-password">{{ __('voodbuilder::auth.password') }}</label>
                    <input
                        id="reset-password"
                        type="password"
                        name="password"
                        class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                        autocomplete="new-password"
                        required
                        autofocus
                    >
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-vp-text-1" for="reset-password-confirmation">{{ __('voodbuilder::auth.confirm_password') }}</label>
                    <input
                        id="reset-password-confirmation"
                        type="password"
                        name="password_confirmation"
                        class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2">
                    {{ $isInvite ? __('voodbuilder::auth.invite_submit') : __('voodbuilder::auth.reset_password_submit') }}
                </button>
            </form>
        </section>
    </div>
@endsection
