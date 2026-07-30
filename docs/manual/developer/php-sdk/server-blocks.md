---
title: PHP SDK — Server blocks
description: Server-rendered and RichEditor-backed editor blocks.
---

# PHP SDK — Server blocks

## EditorServerBlock

Implement `Voodflow\Voodbuilder\Contracts\EditorServerBlock` and register:

```php
Voodbuilder::editorServerBlock('Acme', AcmeLatestPostsBlock::class);
```

The editor shows a placeholder; the public (and preview) pipeline asks your class for HTML.

`EditorConfigurableBlock` extends the contract when the block needs configuration UI.

## Rich content bridge

```php
Voodbuilder::editorRichContentBlock('Acme', AcmeRichBlock::class);
```

Maps a Filament `RichContentCustomBlock` into an editor-friendly placeholder.

## Admin TipTap only

```php
Voodbuilder::richContentBlock('landing', LandingHeroBlock::class);
```

Registers for the Filament rich editor without necessarily exposing a visual-builder library entry.

::: tip Screenshot needed
Editor canvas showing a server-block placeholder vs rendered public HTML.
:::
