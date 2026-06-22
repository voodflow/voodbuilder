<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Voodflow\Vpress\Support\PublicDiskUrl;

class GrapesJsAssetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $maxSize = (int) config('vpress.grapesjs.upload.max_size', 4096);

        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:'.$maxSize],
        ]);

        $disk = (string) config('vpress.grapesjs.upload.disk', 'public');
        $directory = trim((string) config('vpress.grapesjs.upload.directory', 'vpress/grapesjs'), '/');
        $file = $validated['file'];

        $path = $file->storePubliclyAs($directory, $file->hashName(), $disk);
        $url = PublicDiskUrl::fromPath($path);

        return response()->json([
            'data' => [$url],
        ]);
    }
}
