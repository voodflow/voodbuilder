#!/usr/bin/env python3
"""Rename vpress package identifiers to voodbuilder (vendor voodflow unchanged)."""

from __future__ import annotations

import os
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {'.git', 'vendor', 'node_modules', 'scripts'}
TEXT_EXTENSIONS = {
    '.php', '.js', '.css', '.json', '.md', '.blade.php', '.xml', '.yml', '.yaml', '.html', '.txt', '.stub'
}

# Order matters: longer / more specific tokens first.
REPLACEMENTS = [
    ('Voodflow\\Vpress\\', 'Voodflow\\Voodbuilder\\'),
    ('Voodflow\\\\Vpress\\\\', 'Voodflow\\\\Voodbuilder\\\\'),
    ('voodflow/vpress', 'voodflow/voodbuilder'),
    ('ConfigureFrontendForVpress', 'ConfigureFrontendForVoodbuilder'),
    ('ConfigureSubThemesForVpress', 'ConfigureSubThemesForVoodbuilder'),
    ('ConfigureRoutesForVpress', 'ConfigureRoutesForVoodbuilder'),
    ('ConfigureVtutsForVpress', 'ConfigureVtutsForVoodbuilder'),
    ('ConfigureViteForVpress', 'ConfigureViteForVoodbuilder'),
    ('ConfigureNpmForVpress', 'ConfigureNpmForVoodbuilder'),
    ('ApplyVpressSiteConfig', 'ApplyVoodbuilderSiteConfig'),
    ('VpressLandingGrapesJsBlocks', 'VoodbuilderLandingGrapesJsBlocks'),
    ('VpressSectionGrapesJsBlocks', 'VoodbuilderSectionGrapesJsBlocks'),
    ('VpressSettingsPage', 'VoodbuilderSettingsPage'),
    ('VpressLandingBlocks', 'VoodbuilderLandingBlocks'),
    ('VpressServiceProvider', 'VoodbuilderServiceProvider'),
    ('VpressSettings', 'VoodbuilderSettings'),
    ('VpressSeeder', 'VoodbuilderSeeder'),
    ('VpressPlugin', 'VoodbuilderPlugin'),
    ('VpressPaths', 'VoodbuilderPaths'),
    ('VpressTheme', 'VoodbuilderTheme'),
    ('VpressUrls', 'VoodbuilderUrls'),
    ('VpressSeo', 'VoodbuilderSeo'),
    ('Vpress.php', 'Voodbuilder.php'),
    ('class Vpress', 'class Voodbuilder'),
    ('vpress-dynamic-config', 'voodbuilder-dynamic-config'),
    ('vpress-grapesjs', 'voodbuilder-grapesjs'),
    ('vpress-home-landing-catalog', 'voodbuilder-home-landing-catalog'),
    ('vpress-gjs', 'voodbuilder-gjs'),
    ('data-vpress-', 'data-voodbuilder-'),
    ('vpress::', 'voodbuilder::'),
    ('vpress_model_integrations', 'voodbuilder_model_integrations'),
    ('vpress_menu_items', 'voodbuilder_menu_items'),
    ('vpress_settings', 'voodbuilder_settings'),
    ('vpress_menus', 'voodbuilder_menus'),
    ('resources/vpress/', 'resources/voodbuilder/'),
    ('resources/views/vpress/', 'resources/views/voodbuilder/'),
    ('public/css/vpress/', 'public/css/voodbuilder/'),
    ('public/js/vpress/', 'public/js/voodbuilder/'),
    ("config('vpress", "config('voodbuilder"),
    ('config("vpress', 'config("voodbuilder'),
    ("'vpress.", "'voodbuilder."),
    ('"vpress.', '"voodbuilder.'),
    ('/vpress/', '/voodbuilder/'),
    ('packages/voodflow/vpress', 'packages/voodflow/voodbuilder'),
]

# Whole-word vpress -> voodbuilder (avoid vitepress).
VPRESS_WORD = re.compile(r'(?<!vi)(?<![A-Za-z])vpress(?![A-Za-z])', re.IGNORECASE)


def should_process(path: Path) -> bool:
    if any(part in SKIP_DIRS for part in path.parts):
        return False
    name = path.name
    if name.endswith('.blade.php'):
        return True
    return path.suffix in TEXT_EXTENSIONS


def transform(content: str) -> str:
    for old, new in REPLACEMENTS:
        content = content.replace(old, new)

    def repl(match: re.Match[str]) -> str:
        token = match.group(0)
        if token.isupper():
            return 'VOODBUILDER'
        if token[0].isupper():
            return 'Voodbuilder'
        return 'voodbuilder'

    return VPRESS_WORD.sub(repl, content)


def process_files() -> int:
    changed = 0
    for path in ROOT.rglob('*'):
        if not path.is_file() or not should_process(path):
            continue
        original = path.read_text(encoding='utf-8')
        updated = transform(original)
        if updated != original:
            path.write_text(updated, encoding='utf-8')
            changed += 1
    return changed


if __name__ == '__main__':
    count = process_files()
    print(f'Updated {count} files')
