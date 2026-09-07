<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Support\SitePageAccess;
use Voodflow\Voodbuilder\Support\SitePageResolver;

/**
 * Unlock a password-protected site page for the current session.
 */
class SitePageUnlockController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $page = SitePageResolver::publishedFromSlug($slug);

        if (! $page->password_protected) {
            return redirect()->to($page->getUrl());
        }

        $page->loadMissing('credentials');

        $requiresEmail = SitePageAccess::requiresEmailField($page);

        $validated = $request->validate([
            'email' => [$requiresEmail ? 'required' : 'nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $ok = SitePageAccess::attemptUnlock(
            $page,
            $request,
            $validated['email'] ?? null,
            $validated['password'],
        );

        if (! $ok) {
            throw ValidationException::withMessages([
                'password' => [__('voodbuilder::gate.password.invalid')],
            ]);
        }

        return redirect()->to($page->getUrl());
    }
}
