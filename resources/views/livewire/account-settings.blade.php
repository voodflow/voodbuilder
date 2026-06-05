<div class="VPAccount">
    @if (session('account_status'))
        <p class="VPAccountFlash" role="status">{{ session('account_status') }}</p>
    @endif

    <section class="VPAccountCard">
        <h2 class="VPAccountCardTitle">{{ __('vpress::account.profile_section') }}</h2>

        <form wire:submit="updateProfile" class="VPAccountForm">
            @if ($this->avatarsEnabled())
                <div class="VPAccountAvatarRow">
                    <div class="VPAccountAvatarPreview" aria-hidden="true">
                        @if ($this->avatarUrl())
                            <img src="{{ $this->avatarUrl() }}" alt="">
                        @else
                            <span>{{ str(auth()->user()?->name)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>
                    <div class="VPAccountAvatarActions">
                        <label class="VPButton soft VPAccountUploadLabel">
                            {{ __('vpress::account.upload_avatar') }}
                            <input type="file" wire:model="avatarUpload" accept="image/*" class="sr-only">
                        </label>
                        @if ($this->avatarUrl())
                            <button type="button" class="VPButton soft" wire:click="removeAvatar">
                                {{ __('vpress::account.remove_avatar') }}
                            </button>
                        @endif
                        @error('avatarUpload')<p class="VPAuthError">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="account-name">{{ __('vpress::account.name') }}</label>
                <input id="account-name" type="text" wire:model="name" class="VPAuthInput" required>
                @error('name')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="account-email">{{ __('vpress::account.email') }}</label>
                <input id="account-email" type="email" wire:model="email" class="VPAuthInput" required>
                @error('email')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="VPButton brand">{{ __('vpress::account.save_profile') }}</button>
        </form>
    </section>

    <section class="VPAccountCard">
        <h2 class="VPAccountCardTitle">{{ __('vpress::account.password_section') }}</h2>

        <form wire:submit="updatePassword" class="VPAccountForm">
            <div class="VPAuthField">
                <label class="VPAuthLabel" for="account-current-password">{{ __('vpress::account.current_password') }}</label>
                <input id="account-current-password" type="password" wire:model="current_password" class="VPAuthInput" autocomplete="current-password">
                @error('current_password')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="account-password">{{ __('vpress::account.new_password') }}</label>
                <input id="account-password" type="password" wire:model="password" class="VPAuthInput" autocomplete="new-password">
                @error('password')<p class="VPAuthError">{{ $message }}</p>@enderror
            </div>

            <div class="VPAuthField">
                <label class="VPAuthLabel" for="account-password-confirmation">{{ __('vpress::account.confirm_password') }}</label>
                <input id="account-password-confirmation" type="password" wire:model="password_confirmation" class="VPAuthInput" autocomplete="new-password">
            </div>

            <button type="submit" class="VPButton brand">{{ __('vpress::account.save_password') }}</button>
        </form>
    </section>
</div>
