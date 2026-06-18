<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class GrapesJsAssetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $maxSize = (int) config('vpress.grapesjs.upload.max_size', 4096);

        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:'.$maxSize],
        ]);

        $disk = (string) config('vpress.grapesjs.upload.disk', 'public');
        $directory = (string) config('vpress.grapesjs.upload.directory', 'vpress/grapesjs');

        $path = $validated['file']->store($directory, $disk);
        $url = Storage::disk($disk)->url($path);

        return response()->json([
            'data' => [$url],
        ]);
    }
}
