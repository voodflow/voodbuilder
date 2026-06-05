<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Voodflow\Vpress\Support\RegisteredUserRole;
use Voodflow\Vpress\Support\VpressUrls;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        return view('vpress::pages.auth.login');
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
                ->withErrors(['email' => __('vpress::auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectAfterAuth());
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! config('vpress.auth.registration_enabled', true)) {
            abort(404);
        }

        return view('vpress::pages.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        if (! config('vpress.auth.registration_enabled', true)) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $userClass = config('auth.providers.users.model');

        /** @var \Illuminate\Foundation\Auth\User $user */
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

        return redirect(VpressUrls::home());
    }

    protected function redirectAfterAuth(): string
    {
        $route = (string) config('vpress.auth.redirect_after_login', 'vpress.account');

        return Route::has($route) ? route($route) : VpressUrls::home();
    }
}
