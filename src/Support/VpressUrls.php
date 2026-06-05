<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\Route;

final class VpressUrls
{
    public static function home(): string
    {
        return Route::has('home') ? route('home') : url('/');
    }

    public static function login(): string
    {
        return Route::has('login') ? route('login') : url('/login');
    }

    public static function register(): string
    {
        return Route::has('register') ? route('register') : url('/register');
    }

    public static function logout(): string
    {
        return Route::has('logout') ? route('logout') : url('/logout');
    }
}
