---
title: PHP SDK — Blocks
description: Register static HTML blocks in the editor library.
---

# PHP SDK — Blocks

Register static HTML blocks from any ServiceProvider:

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::editorBlock(
    id: 'acme-hero',
    label: 'Acme hero',
    category: 'Acme',
    content: <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg text-vp-text-2 py-24">
  <div class="voodbuilder-editor-container mx-auto px-6" data-voodbuilder-role="content">
    <h1 class="text-4xl font-bold text-vp-text-1">Title</h1>
    <p class="mt-4 max-w-2xl">Intro copy.</p>
  </div>
</section>
HTML,
);
```

## Guidelines

- Use **theme tokens** (`bg-vp-*`, `text-vp-*`) for light/dark  
- Follow the [block authoring](../block-authoring) contract for heroes and dropzones  
- Group by `category` (your package name)  
- Blocks are global to the editor library  

Optional `$attributes` array passes through to the block definition for advanced metadata.

Related: [Server blocks](./server-blocks), [Rich content](./server-blocks#rich-content-bridge).
