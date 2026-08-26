# VoodBuilder documentation

English product and technical documentation for **`voodflow/voodbuilder`** (core page builder).

| Section | Audience | Description |
|---------|----------|-------------|
| [Sales overview](./sales/README.md) | Buyers, partners, PMs | Core vs companions, value props |
| [Developer hub](./developer/README.md) | Integrators & companion authors | SDK, modules, editor extension |
| [User + developer manuals](./manual/index.md) | Editors & developers | VitePress-ready manuals |
| [Editor engineering](./EDITOR.md) | Core maintainers | Canvas bootstrap, upgrades |
| [Release checklist](./RELEASE_CHECKLIST.md) | Maintainers | Ship readiness |

## Package position

VoodBuilder **core** owns pages, chrome, menus, themes, the visual editor shell, and the PHP/JS registration SDK. Commercial feature packs (Components, Dynamics, Templates, Elements, Popups, Forms, …) integrate as **companions** via `Voodbuilder::registerModule()` and public APIs — never by forking core or patching GrapesJS under `node_modules`.

## Quick links

- Extending: [manual/developer/extending-overview](./manual/developer/extending-overview.md)
- Companion depth: [developer/companion-integration](./developer/companion-integration.md)
- Surviving GrapesJS upgrades: [EDITOR.md](./EDITOR.md) (*Surviving GrapesJS upgrades*)
- Sample plugin: [manual/developer/sample-plugin](./manual/developer/sample-plugin.md)
