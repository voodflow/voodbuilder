<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Voodbuilder\Support\PublicDiskUrl;

/**
 * HTTP controller: Editor Asset.
 */
class EditorAssetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $maxSize = (int) config('voodbuilder.editor.upload.max_size', 4096);

        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:'.$maxSize],
        ]);

        $disk = (string) config('voodbuilder.editor.upload.disk', 'public');
        $directory = trim((string) config('voodbuilder.editor.upload.directory', 'voodbuilder'), '/');
        $file = $validated['file'];

        $path = $file->storePubliclyAs($directory, $file->hashName(), $disk);
        $url = PublicDiskUrl::fromPath($path);

        return response()->json([
            'data' => [$url],
        ]);
    }
}
