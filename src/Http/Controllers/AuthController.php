<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Voodflow\Voodbuilder\Support\RegisteredUserRole;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        return view('voodbuilder::pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('voodbuilder::auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectAfterAuth());
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! config('voodbuilder.auth.registration_enabled', true)) {
            abort(404);
        }

        return view('voodbuilder::pages.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        if (! config('voodbuilder.auth.registration_enabled', true)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $userClass = config('auth.providers.users.model');

        /** @var User $user */
        $user = $userClass::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        RegisteredUserRole::assign($user);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectAfterAuth());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(VoodbuilderUrls::home());
    }

    protected function redirectAfterAuth(): string
    {
        $route = (string) config('voodbuilder.auth.redirect_after_login', 'voodbuilder.account');

        return Route::has($route) ? route($route) : VoodbuilderUrls::home();
    }
}
