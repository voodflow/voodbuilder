<div class="space-y-6">
    @if (session('account_status'))
        <p
            class="rounded-lg border border-vp-brand-1/20 bg-vp-gray-soft px-4 py-3 text-sm font-medium text-vp-brand-1"
            role="status"
        >
            {{ session('account_status') }}
        </p>
    @endif

    <section class="rounded-xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-vp-text-1">{{ __('vpress::account.profile_section') }}</h2>

        <form wire:submit="updateProfile" class="space-y-4">
            @if ($this->avatarsEnabled())
                <div class="flex flex-wrap items-center gap-4">
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-vp-gray-soft text-xl font-semibold text-vp-text-2"
                        aria-hidden="true"
                    >
                        @if ($this->avatarUrl())
                            <img src="{{ $this->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span>{{ str(auth()->user()?->name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-9 cursor-pointer items-center justify-center rounded-full border border-vp-divider bg-vp-gray-soft px-4 text-sm font-medium text-vp-text-1 transition-colors hover:border-vp-brand-1 hover:text-vp-brand-1">
                            {{ __('vpress::account.upload_avatar') }}
                            <input type="file" wire:model="avatarUpload" accept="image/*" class="sr-only">
                        </label>
                        @if ($this->avatarUrl())
                            <button
                                type="button"
                                class="inline-flex h-9 items-center justify-center rounded-full border border-vp-divider bg-vp-gray-soft px-4 text-sm font-medium text-vp-text-1 transition-colors hover:border-vp-brand-1 hover:text-vp-brand-1"
                                wire:click="removeAvatar"
                            >
                                {{ __('vpress::account.remove_avatar') }}
                            </button>
                        @endif
                        @error('avatarUpload')<p class="w-full text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="account-name">{{ __('vpress::account.name') }}</label>
                <input
                    id="account-name"
                    type="text"
                    wire:model="name"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    required
                >
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="account-email">{{ __('vpress::account.email') }}</label>
                <input
                    id="account-email"
                    type="email"
                    wire:model="email"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    required
                >
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
            >
                {{ __('vpress::account.save_profile') }}
            </button>
        </form>
    </section>

    <section class="rounded-xl border border-vp-divider bg-vp-bg-elv p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-vp-text-1">{{ __('vpress::account.password_section') }}</h2>

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="account-current-password">{{ __('vpress::account.current_password') }}</label>
                <input
                    id="account-current-password"
                    type="password"
                    wire:model="current_password"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="current-password"
                >
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="account-password">{{ __('vpress::account.new_password') }}</label>
                <input
                    id="account-password"
                    type="password"
                    wire:model="password"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="new-password"
                >
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-vp-text-1" for="account-password-confirmation">{{ __('vpress::account.confirm_password') }}</label>
                <input
                    id="account-password-confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    class="w-full rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-vp-text-1 outline-none focus:border-vp-brand-1"
                    autocomplete="new-password"
                >
            </div>

            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-full bg-vp-brand-3 px-5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-2"
            >
                {{ __('vpress::account.save_password') }}
            </button>
        </form>
    </section>
</div>
