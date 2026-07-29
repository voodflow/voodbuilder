<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Editor\EditorChromeLayoutEditorGate;

class ChromeLayoutEditorController extends Controller
{
    public function show(ChromeLayout $chromeLayout): View
    {
        abort_unless(EditorChromeLayoutEditorGate::isEditing($chromeLayout), 403);

        $config = EditorChromeLayoutEditorGate::config($chromeLayout);
        $config['exitUrl'] = ChromeLayoutResource::getUrl('edit', ['record' => $chromeLayout]);

        return view('voodbuilder::pages.chrome-layout-editor', [
            'layout' => $chromeLayout,
            'editorEditor' => true,
            'chromeLayoutEditor' => true,
            'editorConfig' => $config,
        ]);
    }
}
