<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPopupEditorGate;

class PopupEditorController extends Controller
{
    public function show(BuilderPopup $popup): View
    {
        abort_unless(GrapesJsPopupEditorGate::isEditing($popup), 403);

        $config = GrapesJsPopupEditorGate::config($popup);

        try {
            $config['exitUrl'] = PopupResource::getUrl('edit', ['record' => $popup]);
        } catch (\Throwable) {
            // Filament panel may be unavailable (e.g. package tests); keep gate exitUrl.
        }

        return view('voodbuilder::pages.popup-editor', [
            'popup' => $popup,
            'grapesJsEditor' => true,
            'grapesJsConfig' => $config,
            'voodbuilderSubTheme' => $config['subTheme'] ?? null,
        ]);
    }
}
