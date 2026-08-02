<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\File;
use Voodflow\Voodbuilder\Support\Editor\EditorMediaLibrary;

/**
 * HTTP controller: Editor Asset upload + reusable Spatie media library listing.
 */
class EditorAssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('voodbuilder_media_libraries')) {
            return response()->json([
                'message' => 'Media library is not installed. Run: php artisan migrate',
                'data' => [],
            ], 503);
        }

        $type = $request->query('type');
        $type = is_string($type) && in_array($type, ['image', 'video'], true) ? $type : null;

        return response()->json([
            'data' => array_map(
                static fn (array $asset): array => [
                    'src' => $asset['src'],
                    'type' => $asset['type'],
                    'name' => $asset['name'],
                    'uuid' => $asset['uuid'],
                    'id' => $asset['id'],
                ],
                EditorMediaLibrary::listAssets($type),
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('voodbuilder_media_libraries')) {
            return response()->json([
                'message' => 'Media library is not installed. Run: php artisan migrate',
            ], 503);
        }

        $imageMaxKb = (int) config('voodbuilder.editor.upload.max_size', 4096);
        $videoMaxKb = (int) config('voodbuilder.editor.upload.video_max_size', max($imageMaxKb, 51200));
        $uploaded = $request->file('file');
        $mime = (string) ($uploaded?->getMimeType() ?? '');
        $isVideo = str_starts_with($mime, 'video/');

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                File::types([
                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
                    'mp4', 'webm', 'ogg', 'mov', 'm4v',
                ])->max($isVideo ? $videoMaxKb : $imageMaxKb),
            ],
        ]);

        $media = EditorMediaLibrary::store($validated['file']);
        $payload = EditorMediaLibrary::toAssetPayload($media);

        // GrapesJS FileUploader expects data[] of URL strings (or asset objects).
        // Video vs image typing is handled client-side by AssetManager.addType('video').
        return response()->json([
            'data' => [$payload['src']],
            'media' => $payload,
        ]);
    }
}
