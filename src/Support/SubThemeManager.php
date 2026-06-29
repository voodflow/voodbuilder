<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;

final class SubThemeManager
{
    public static function updateLabel(string $id, string $label): SubThemeOperationResult
    {
        $location = SubThemeLocator::resolve($id);

        if ($location === null) {
            return new SubThemeOperationResult(false, $id, "Theme \"{$id}\" was not found.");
        }

        if (! $location->isApp()) {
            return new SubThemeOperationResult(
                false,
                $id,
                'Bundled package themes cannot be renamed from the admin. Clone the theme to customize its name.',
            );
        }

        $label = trim($label);

        if ($label === '') {
            return new SubThemeOperationResult(false, $id, 'Display name is required.');
        }

        $definition = SubThemeLocator::definitionFor($id);
        $definition['label'] = $label;

        if (! ConfigureSubThemesForVpress::upsertInConfig($id, $definition)) {
            return new SubThemeOperationResult(false, $id, 'Could not update config/vpress.php.');
        }

        app(SubThemeRegistry::class)->register($id, $definition);

        return new SubThemeOperationResult(true, $id);
    }

    public static function updateMetadata(string $id, string $label, ?string $description = null): SubThemeOperationResult
    {
        $location = SubThemeLocator::resolve($id);

        if ($location === null) {
            return new SubThemeOperationResult(false, $id, "Theme \"{$id}\" was not found.");
        }

        if (! $location->isApp()) {
            return new SubThemeOperationResult(
                false,
                $id,
                'Bundled package themes cannot be edited from the admin. Clone the theme to customize it.',
            );
        }

        $label = trim($label);

        if ($label === '') {
            return new SubThemeOperationResult(false, $id, 'Display name is required.');
        }

        $definition = SubThemeLocator::definitionFor($id);
        $definition['label'] = $label;
        $definition['description'] = trim((string) $description);

        if (! ConfigureSubThemesForVpress::upsertInConfig($id, $definition)) {
            return new SubThemeOperationResult(false, $id, 'Could not update config/vpress.php.');
        }

        app(SubThemeRegistry::class)->register($id, $definition);

        return new SubThemeOperationResult(true, $id);
    }

    public static function delete(string $id, string $fallbackId = 'site'): SubThemeOperationResult
    {
        $location = SubThemeLocator::resolve($id);

        if ($location === null) {
            return new SubThemeOperationResult(false, $id, "Theme \"{$id}\" was not found.");
        }

        if (! $location->isApp()) {
            return new SubThemeOperationResult(false, $id, 'Bundled package themes cannot be deleted.');
        }

        if ($id === $fallbackId) {
            return new SubThemeOperationResult(false, $id, 'Choose a different theme than the fallback.');
        }

        $registry = app(SubThemeRegistry::class);

        if (! $registry->exists($fallbackId)) {
            return new SubThemeOperationResult(false, $id, "Fallback theme \"{$fallbackId}\" is not registered.");
        }

        if (! ConfigureSubThemesForVpress::removeFromConfig($id)) {
            return new SubThemeOperationResult(false, $id, 'Theme is not registered in config/vpress.php.');
        }

        if (is_dir($location->themeRoot)) {
            File::deleteDirectory($location->themeRoot);
        }

        if (is_dir($location->viewsRoot)) {
            File::deleteDirectory($location->viewsRoot);
        }

        SyncThemeStylesheetImports::sync();
        self::purgeThemeReferences($id, $fallbackId);
        $registry->unregister($id);

        return new SubThemeOperationResult(true, $id);
    }

    public static function slugFromLabel(string $label): string
    {
        $slug = Str::slug(trim($label), '-');

        if ($slug === '' || in_array($slug, ['default', 'docs'], true)) {
            $slug = 'theme';
        }

        if (! preg_match('/^[a-z]/', $slug)) {
            $slug = 'theme-'.$slug;
        }

        return $slug;
    }

    public static function saveAppThemeMetadata(
        string $id,
        string $label,
        ?string $description,
        ?string $targetId = null,
    ): SubThemeOperationResult {
        $targetId = filled($targetId) ? Str::kebab($targetId) : null;

        if ($targetId !== null && $targetId !== $id) {
            $rename = self::renameId($id, $targetId);

            if (! $rename->success) {
                return $rename;
            }

            $id = $rename->id;
        }

        $metadata = self::updateMetadata($id, $label, $description);

        if (! $metadata->success) {
            return $metadata;
        }

        return new SubThemeOperationResult(true, $id);
    }

    public static function renameId(string $fromId, string $toId): SubThemeOperationResult
    {
        $fromId = Str::kebab($fromId);
        $toId = Str::kebab($toId);

        if ($fromId === $toId) {
            return new SubThemeOperationResult(true, $fromId);
        }

        if ($toId === '' || in_array($toId, ['default', 'docs'], true) || ! preg_match('/^[a-z][a-z0-9-]*$/', $toId)) {
            return new SubThemeOperationResult(false, $fromId, 'Invalid theme id.');
        }

        $location = SubThemeLocator::resolve($fromId);

        if ($location === null || ! $location->isApp()) {
            return new SubThemeOperationResult(false, $fromId, 'Only app themes can be renamed.');
        }

        if (SubThemeImporter::themeExists($toId)) {
            return new SubThemeOperationResult(false, $fromId, "Theme \"{$toId}\" already exists.");
        }

        $definition = SubThemeLocator::definitionFor($fromId);
        $newThemeDir = dirname(ThemeConvention::appCssPath($toId));

        if (is_dir($location->themeRoot)) {
            if (is_dir($newThemeDir)) {
                File::deleteDirectory($newThemeDir);
            }

            File::moveDirectory($location->themeRoot, $newThemeDir);
        }

        $newViewsDir = ThemeConvention::appViewsPath($toId);

        if (is_dir($location->viewsRoot)) {
            if (is_dir($newViewsDir)) {
                File::deleteDirectory($newViewsDir);
            }

            File::moveDirectory($location->viewsRoot, $newViewsDir);
        }

        ConfigureSubThemesForVpress::removeFromConfig($fromId);
        ConfigureSubThemesForVpress::upsertInConfig($toId, $definition);
        SyncThemeStylesheetImports::sync();
        self::migrateThemeReferences($fromId, $toId);

        $registry = app(SubThemeRegistry::class);
        $registry->unregister($fromId);
        $registry->register($toId, $definition);

        return new SubThemeOperationResult(true, $toId);
    }

    /**
     * @return list<SubThemeLocation>
     */
    public static function manageableAppThemes(): array
    {
        return SubThemeLocator::exportable()
            ->filter(fn (SubThemeLocation $location): bool => $location->isApp())
            ->values()
            ->all();
    }

    protected static function purgeThemeReferences(string $id, string $fallbackId): void
    {
        $data = VpressSettings::data();

        $updates = [];

        if (($data['sub_theme'] ?? null) === $id) {
            $updates['sub_theme'] = $fallbackId;
        }

        $channelThemes = is_array($data['content_channel_sub_themes'] ?? null)
            ? $data['content_channel_sub_themes']
            : [];

        foreach ($channelThemes as $channelId => $themeId) {
            if ($themeId === $id) {
                $channelThemes[$channelId] = $fallbackId;
            }
        }

        if ($channelThemes !== ($data['content_channel_sub_themes'] ?? [])) {
            $updates['content_channel_sub_themes'] = $channelThemes;
        }

        $colors = is_array($data['sub_theme_colors'] ?? null) ? $data['sub_theme_colors'] : [];

        if (array_key_exists($id, $colors)) {
            unset($colors[$id]);
            $updates['sub_theme_colors'] = $colors;
        }

        if ($updates !== []) {
            VpressSettings::saveData($updates);
        }

        SitePage::query()
            ->where('sub_theme', $id)
            ->update(['sub_theme' => $fallbackId]);
    }

    protected static function migrateThemeReferences(string $fromId, string $toId): void
    {
        $data = VpressSettings::data();
        $updates = [];

        if (($data['sub_theme'] ?? null) === $fromId) {
            $updates['sub_theme'] = $toId;
        }

        $channelThemes = is_array($data['content_channel_sub_themes'] ?? null)
            ? $data['content_channel_sub_themes']
            : [];

        foreach ($channelThemes as $channelId => $themeId) {
            if ($themeId === $fromId) {
                $channelThemes[$channelId] = $toId;
            }
        }

        if ($channelThemes !== ($data['content_channel_sub_themes'] ?? [])) {
            $updates['content_channel_sub_themes'] = $channelThemes;
        }

        $colors = is_array($data['sub_theme_colors'] ?? null) ? $data['sub_theme_colors'] : [];

        if (array_key_exists($fromId, $colors)) {
            $colors[$toId] = $colors[$fromId];
            unset($colors[$fromId]);
            $updates['sub_theme_colors'] = $colors;
        }

        if ($updates !== []) {
            VpressSettings::saveData($updates);
        }

        SitePage::query()
            ->where('sub_theme', $fromId)
            ->update(['sub_theme' => $toId]);
    }
}
