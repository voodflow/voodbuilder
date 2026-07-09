<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\GrapesJsPopupHtmlNormalizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;

final class GrapesJsPopupEditorGate
{
    public static function canEdit(BuilderPopup $popup): bool
    {
        return config('voodbuilder.popups.enabled', true)
            && PageBuilderAccess::userCanUsePageBuilder();
    }

    public static function isEditing(BuilderPopup $popup): bool
    {
        return self::canEdit($popup) && request()->boolean('edit');
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(BuilderPopup $popup): array
    {
        $subTheme = (string) config('voodbuilder.popups.editor_sub_theme', 'site');

        return [
            'popupMode' => true,
            'popupId' => $popup->getKey(),
            'popupName' => $popup->name,
            'saveUrl' => self::editorRoute('voodbuilder.grapesjs.popups.content.update', $popup),
            'exitUrl' => route('voodbuilder.popups.editor', $popup),
            'viewPageUrl' => route('voodbuilder.popups.editor', ['popup' => $popup, 'edit' => 1]),
            'uploadUrl' => self::editorRoute('voodbuilder.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($popup),
            'blocksUrl' => self::editorRoute('voodbuilder.grapesjs.blocks'),
            'bindingsUrl' => self::editorRoute('voodbuilder.grapesjs.bindings'),
            'blocksRenderUrl' => self::editorRoute('voodbuilder.grapesjs.blocks.render'),
            'codeHighlightUrl' => self::editorRoute('voodbuilder.grapesjs.code.highlight'),
            'globalClassesUrl' => self::editorRoute('voodbuilder.grapesjs.global-classes.index'),
            'componentsUrl' => self::editorRoute('voodbuilder.grapesjs.components.index'),
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => GrapesJsComponentCategoryNormalizer::categories(),
            'plugins' => config('voodbuilder.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => true,
            'themePaletteCss' => ThemePalette::cssForCanvas($subTheme),
            'builderBrand' => config('voodbuilder.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => GrapesJsEditorGate::sharedEditorLabels(),
        ];
    }

    /**
     * @return array{html: string, css: string, js: string, project: null}
     */
    public static function initialPayload(BuilderPopup $popup): array
    {
        $payload = $popup->builderPayload();

        return [
            'html' => $payload['html'],
            'css' => $payload['css'],
            'js' => $payload['js'],
            'project' => null,
        ];
    }

    /**
     * @param  array{html?: string, css?: string, js?: string, project?: mixed}  $payload
     * @return array{html: string, css: string, js: string}
     */
    public static function normalizePayload(array $payload): array
    {
        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $payload['html'] ?? '',
            'css' => $payload['css'] ?? '',
            'js' => $payload['js'] ?? '',
            'project' => null,
        ], recompilePageCss: true);

        $normalized['html'] = app(GrapesJsBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);
        $normalized['html'] = GrapesJsPopupHtmlNormalizer::normalize($normalized['html']);

        return [
            'html' => $normalized['html'],
            'css' => $normalized['css'],
            'js' => $normalized['js'] ?? '',
        ];
    }

    private static function editorRoute(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters, absolute: false);
    }
}
