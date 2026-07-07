<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

final class GrapesJsComponentTailwindCompiler
{
    public static function compile(string $html, string $scope = 'component'): ?string
    {
        $html = trim($html);

        if ($html === '') {
            return null;
        }

        $scope = $scope === 'page' ? 'page' : 'component';

        $scriptPath = self::scriptPath();

        if (! is_file($scriptPath)) {
            return null;
        }

        $nodeBinary = self::resolveNodeBinary();

        if ($nodeBinary === null) {
            return null;
        }

        $result = Process::path(base_path())
            ->timeout(60)
            ->env([
                'VOODBUILDER_APP_ROOT' => base_path(),
                'VOODBUILDER_TAILWIND_SCOPE' => $scope,
            ])
            ->input($html)
            ->run([$nodeBinary, $scriptPath]);

        if (! $result->successful()) {
            Log::warning('voodbuilder.component_tailwind_compile_failed', [
                'exit_code' => $result->exitCode(),
                'stderr' => Str::limit($result->errorOutput(), 2000),
            ]);

            return null;
        }

        $payload = json_decode($result->output(), true);

        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            Log::warning('voodbuilder.component_tailwind_compile_invalid_response', [
                'output' => Str::limit($result->output(), 2000),
            ]);

            return null;
        }

        $css = trim((string) ($payload['css'] ?? ''));

        return $css !== '' ? $css : null;
    }

    public static function scriptPath(): string
    {
        return dirname(__DIR__, 3).'/scripts/compile-component-tailwind.mjs';
    }

    protected static function resolveNodeBinary(): ?string
    {
        foreach (['node', 'nodejs'] as $candidate) {
            $probe = Process::run([$candidate, '--version']);

            if ($probe->successful()) {
                return $candidate;
            }
        }

        return null;
    }
}
