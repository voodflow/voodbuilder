# Layout contract — Chrome templates & plugin content

Voodbuilder owns the **site chrome** (header, footer, global nav). Content packages (vdocs, vtuts, blog, …) own the **page body** inside `@section('content')` or channel-specific sections (`doc`, `page`, …).

## Chrome layout (option C)

A **Chrome layout** is a Editor template stored in Admin → **Layouts**. It wraps plugin pages:

```
┌─────────────────────────────────────┐
│  Header (Editor blocks)           │
├─────────────────────────────────────┤
│  @yield('content') — plugin body    │
├─────────────────────────────────────┤
│  Footer (Editor blocks)           │
└─────────────────────────────────────┘
```

The editor must keep a single element with `data-voodbuilder-content-slot` (starter template includes one). On the public site, that marker is removed and Blade renders plugin content in `<main>`.

Resolution order:

1. Enabled **non-default** layout whose `channel_ids` includes the current content channel
2. Else peer channel with an explicit assignment (`docs` ↔ `tutorials`)
3. Else enabled layout marked **default** (site-wide fallback; its `channel_ids` are ignored)
4. Else classic `voodbuilder::layouts.app` shell (no chrome)

Assign channels in Admin → Layouts. Channels are registered by packages or the host app (see below).
A layout marked default should not also list channels — it is the catch-all when nothing more specific matches.
Documentation and Tutorials share chrome when only one side is assigned, so a “Docs” layout covers both unless Tutorials has its own.

## Plugin layout (first-party: vdocs, vtuts)

Each package ships a **standalone parachute layout** (`vdocs::layouts.*`, `vtuts::layouts.*`) that works without Voodbuilder.

When integrated, host `config/*.php` points layouts at Voodbuilder views:

```php
// config/vdocs.php
'doc_layout' => config('voodbuilder.layouts.doc'),
```

Those Voodbuilder layouts `@extend(PluginLayout::appShell())`, which picks `chrome-app` when a chrome layout is active.

Helpers:

- `Voodflow\Vdocs\Support\VdocsLayout` — `doc()`, `home()`, `page()`, `integrated()`
- `Voodflow\Vtuts\Support\VtutLayout` — `page()`, `doc()`, `integrated()`

Use them in Blade instead of raw `config()` so standalone vs integrated behaviour stays consistent.

## Third-party plugins — persistent integration

If a package does **not** call `Voodbuilder::contentChannel()` in its service provider, register it in the **host app**:

**`config/voodbuilder-integrations.php`** (application code, not vendor):

```php
return [
    'content_channels' => [
        'blog' => [
            'label' => 'Blog',
            'routes' => ['blog.*'],
            'search' => \App\Models\BlogPost::class, // optional
        ],
    ],
];
```

`IntegrationRegistrar` loads this file on boot. Composer updates to the third-party package will **not** remove your registration.

Alternatives (same persistence guarantees):

| Approach | Survives package update? |
|----------|-------------------------|
| `config/voodbuilder-integrations.php` | Yes |
| `config/voodbuilder.php` → `content_channels` | Yes |
| `AppServiceProvider::boot()` → `Voodbuilder::contentChannel(...)` | Yes |
| Edit vendor `ServiceProvider` | **No** |

For layout views, third-party packages should read layout from config with a package default, e.g. `config('myblog.layout', 'myblog::layouts.app')`. The host overrides that key to `voodbuilder.layouts.page` (or `doc`, etc.).

## Content channel definition

```php
Voodbuilder::contentChannel('docs', new DocumentationContentChannel);

// or array form:
Voodbuilder::contentChannel('blog', [
    'label' => 'Blog',
    'routes' => ['blog.*'],
    'search' => BlogPost::class, // optional, static search method
]);
```

Route patterns drive menu highlighting, sub-theme resolution, and chrome layout assignment.

## Sections vs content

| Layer | Blade | Owner |
|-------|-------|-------|
| Chrome shell | `chrome-app` → before/after HTML | Voodbuilder |
| Channel layout | `doc`, `page`, `full-width` | Voodbuilder (configurable) |
| Plugin body | `@section('doc')`, `@section('content')`, … | vdocs / vtuts / blog |

Do **not** wrap every doc/tutorial page in Editor. Only the shared chrome is visual; plugin templates stay Blade/Livewire.

## Site Pages with chrome layout

When a chrome layout is assigned to the `pages` channel (includes `/` home and `/pages/{slug}`):

- Nav/footer come **only** from Admin → Layouts
- Editor page content must not include `site_nav_*` or `site_footer_*` blocks — they are stripped on save and render
- Edit nav/footer in the **Layout** visual editor, not in the page editor
