# Route map

## Public web (`routes/web.php`)

| Name | Path (conceptual) | Module |
|---|---|---|
| `home` / `home.localized` | `/`, `/{locale}` | Pages |
| `voodbuilder.pages.show` | `/{prefix}/{slug}` | Pages |
| `voodbuilder.search` | search route | Core/Menus |
| `login` / `register` / `logout` | auth | Core site |
| `voodbuilder.account` | account | Core site |

Locale middleware wraps non-default locales.

## Editor API (from `VoodbuilderServiceProvider::registerEditorRoutes`)

Prefix group (package-configured), middleware `web` + auth + throttle unless noted.

| Route name | Module |
|---|---|
| `…forms.submit` | Editor / Forms (public throttle) |
| `…blocks`, `…blocks.render` | Editor |
| `…bindings`, `…bindings.preview` | Dynamic Data |
| `…link-targets` | Editor / Menus |
| `…media.preview` | Dynamic Data / Media |
| `…code.highlight` | Editor |
| `…upload` | Editor |
| `…pages.update` | Pages |
| `…pages.revisions.*` | History |
| `…global-classes.*` | Components |
| `…components.*` | Components |
| `…page-templates.*` | Templates |
| `…popups.*` (CRUD + content) | Popups |
| `…chrome-layouts.content.update` | Layouts |
| `…popups.public`, `…popups.events` | Popups (public throttle) |
| `…popups.editor`, `…chrome-layouts.editor` | Popups / Layouts |

## Admin helper

| Route | Module |
|---|---|
| navigation menu preview | Menus |

## Extraction rule

When a module is disabled, its routes must not register. Core must not 500 if a disabled module’s URL is hit — prefer 404 or graceful JSON error.
