<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

class EditorMediaPreviewController extends Controller
{
    public function __invoke(Request $request, int $media): BinaryFileResponse
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        $mediaModel = Media::query()->findOrFail($media);

        abort_unless(is_file($mediaModel->getPath()), 404);

        return response()->file($mediaModel->getPath(), [
            'Content-Type' => $mediaModel->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
