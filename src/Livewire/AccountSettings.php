<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Livewire;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AccountSettings extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public TemporaryUploadedFile|string|null $avatarUpload = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        try {
            app(UpdatesUserProfileInformation::class)->update($user, [
                'name' => $this->name,
                'email' => $this->email,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        if ($this->avatarUpload instanceof TemporaryUploadedFile) {
            $this->updateAvatar($user);
        }

        session()->flash('account_status', __('vpress::account.profile_saved'));

        $this->dispatch('account-saved');
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        try {
            app(UpdatesUserPasswords::class)->update($user, [
                'current_password' => $this->current_password,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('account_status', __('vpress::account.password_saved'));
    }

    public function removeAvatar(): void
    {
        if (! $this->avatarsEnabled()) {
            return;
        }

        $user = auth()->user();

        if ($user === null || blank($user->avatar ?? null)) {
            return;
        }

        $disk = (string) config('vpress.account.avatar.disk', 'public');
        Storage::disk($disk)->delete((string) $user->avatar);

        $user->forceFill(['avatar' => null])->save();

        session()->flash('account_status', __('vpress::account.avatar_removed'));
    }

    public function avatarsEnabled(): bool
    {
        return (bool) config('vpress.account.avatar.enabled', true)
            && Schema::hasColumn('users', 'avatar');
    }

    public function avatarUrl(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        if ($user instanceof HasAvatar) {
            return $user->getFilamentAvatarUrl();
        }

        if (blank($user->avatar ?? null)) {
            return null;
        }

        return asset('storage/'.$user->avatar);
    }

    public function render(): View
    {
        return view('vpress::livewire.account-settings');
    }

    protected function updateAvatar(mixed $user): void
    {
        if (! $this->avatarsEnabled()) {
            return;
        }

        $this->validate([
            'avatarUpload' => ['required', 'image', 'max:2048'],
        ]);

        $disk = (string) config('vpress.account.avatar.disk', 'public');
        $directory = (string) config('vpress.account.avatar.directory', 'avatars');

        if (filled($user->avatar ?? null)) {
            Storage::disk($disk)->delete((string) $user->avatar);
        }

        $path = $this->avatarUpload->store($directory, $disk);

        $user->forceFill(['avatar' => $path])->save();

        $this->avatarUpload = null;
    }
}
