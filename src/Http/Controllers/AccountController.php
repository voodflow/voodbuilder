<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class AccountController extends Controller
{
    public function __invoke(): View
    {
        return view('vpress::pages.account');
    }
}
