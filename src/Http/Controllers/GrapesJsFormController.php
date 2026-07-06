<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Events\GrapesJsFormSubmitted;
use Voodflow\Voodbuilder\Models\SitePage;

class GrapesJsFormController extends Controller
{
    public function __invoke(Request $request, SitePage $sitePage): JsonResponse
    {
        abort_unless($sitePage->published, 404);

        $formType = (string) $request->input('form_type', 'contact');

        if ($formType === 'newsletter') {
            $validated = $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'form_type' => ['nullable', 'string', 'in:newsletter'],
                'newsletter_list' => ['nullable', 'string', 'max:100'],
                '_honeypot' => ['nullable', 'max:0'],
            ]);

            event(new GrapesJsFormSubmitted($sitePage, array_merge($validated, [
                'form_type' => 'newsletter',
            ])));

            return response()->json([
                'ok' => true,
                'message' => config('voodbuilder.grapesjs.forms.newsletter_success_message'),
            ]);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            '_honeypot' => ['nullable', 'max:0'],
        ]);

        if (blank($validated['email'] ?? null) && blank($validated['message'] ?? null)) {
            return response()->json([
                'message' => __('At least one contact field is required.'),
            ], 422);
        }

        event(new GrapesJsFormSubmitted($sitePage, array_merge($validated, [
            'form_type' => 'contact',
        ])));

        return response()->json([
            'ok' => true,
            'message' => config('voodbuilder.grapesjs.forms.success_message'),
        ]);
    }
}
