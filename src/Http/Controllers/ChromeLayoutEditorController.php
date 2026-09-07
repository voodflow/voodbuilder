<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Editor\EditorChromeLayoutEditorGate;
use Voodflow\Voodbuilder\Support\Editor\EditorHostChrome;

/**
 * HTTP controller: Chrome Layout Editor.
 */
class ChromeLayoutEditorController extends Controller
{
    public function show(ChromeLayout $chromeLayout): View
    {
        abort_unless(EditorChromeLayoutEditorGate::isEditing($chromeLayout), 403);

        // Same reason as the page editor: companion overlays must stand down before the
        // layout renders, or they end up as content inside the authoring canvas.
        EditorHostChrome::markActive();

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
